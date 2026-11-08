-- Dystans – schemat bazy danych (PostgreSQL 17).
-- Ładowany automatycznie przy pierwszym starcie kontenera db (docker-entrypoint-initdb.d).

CREATE TABLE users (
    id            INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    email         VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name          VARCHAR(50)  NOT NULL,
    -- CAPTAIN może zakładać wyprawy; kapitanem konkretnej wyprawy jest expeditions.captain_id.
    role          VARCHAR(10)  NOT NULL DEFAULT 'USER' CHECK (role IN ('USER', 'CAPTAIN', 'ADMIN')),
    created_at    TIMESTAMPTZ  NOT NULL DEFAULT now()
);

-- Unikalny e-mail bez względu na wielkość liter (Jan@x.pl = jan@x.pl).
CREATE UNIQUE INDEX users_email_unique ON users (lower(email));

CREATE TABLE expeditions (
    id          INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    -- RESTRICT: nie można usunąć konta, które prowadzi wyprawę – najpierw trzeba przekazać kapitana.
    captain_id  INT          NOT NULL REFERENCES users (id) ON DELETE RESTRICT,
    start_date  DATE         NOT NULL,
    end_date    DATE         NOT NULL,
    max_members SMALLINT     NOT NULL CHECK (max_members BETWEEN 2 AND 100),
    join_code   CHAR(6)      NOT NULL UNIQUE CHECK (join_code ~ '^[0-9]{6}$'),
    created_at  TIMESTAMPTZ  NOT NULL DEFAULT now(),
    CHECK (end_date >= start_date)
);
-- Status (planowana / w trakcie / ukończona) i długość trasy nie są kolumnami:
-- wynikają z dat i z ostatniego checkpointu, więc nie mogą się rozjechać z danymi.

CREATE TABLE checkpoints (
    id            INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    -- CASCADE: punkty trasy nie istnieją bez swojej wyprawy.
    expedition_id INT          NOT NULL REFERENCES expeditions (id) ON DELETE CASCADE,
    name          VARCHAR(100) NOT NULL,
    km_from_start NUMERIC(7, 2) NOT NULL CHECK (km_from_start >= 0),
    UNIQUE (expedition_id, km_from_start)
);

CREATE TABLE expedition_members (
    expedition_id INT         NOT NULL REFERENCES expeditions (id) ON DELETE CASCADE,
    user_id       INT         NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    joined_at     TIMESTAMPTZ NOT NULL DEFAULT now(),
    PRIMARY KEY (expedition_id, user_id)
);

CREATE TABLE disciplines (
    id   INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    -- Jednostka, którą mnoży przelicznik: km (bieg, rower, pływanie) albo minuty (siłownia).
    unit VARCHAR(3)  NOT NULL CHECK (unit IN ('km', 'min'))
);

-- Przeliczniki ustawia kapitan dla swojej wyprawy, więc są tutaj, a nie w disciplines.
CREATE TABLE expedition_disciplines (
    expedition_id INT          NOT NULL REFERENCES expeditions (id) ON DELETE CASCADE,
    -- RESTRICT: dyscypliny używanej w wyprawie nie da się usunąć ze słownika.
    discipline_id INT          NOT NULL REFERENCES disciplines (id) ON DELETE RESTRICT,
    km_factor     NUMERIC(5, 2) NOT NULL CHECK (km_factor > 0),
    PRIMARY KEY (expedition_id, discipline_id)
);

CREATE TABLE activities (
    id             INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    expedition_id  INT           NOT NULL,
    user_id        INT           NOT NULL,
    discipline_id  INT           NOT NULL,
    distance_km    NUMERIC(6, 2) CHECK (distance_km > 0),
    duration_min   SMALLINT      NOT NULL CHECK (duration_min BETWEEN 1 AND 1440),
    activity_date  DATE          NOT NULL,
    avg_heart_rate SMALLINT      CHECK (avg_heart_rate BETWEEN 30 AND 250),
    note           VARCHAR(500),
    -- Tylko względna ścieżka do pliku w katalogu uploadów, nigdy zawartość pliku.
    photo_path     VARCHAR(255),
    -- Migawka przeliczenia z dnia dodania: późniejsza zmiana przelicznika nie przepisuje historii.
    route_km       NUMERIC(7, 2) NOT NULL CHECK (route_km >= 0),
    status         VARCHAR(8)    NOT NULL DEFAULT 'PENDING' CHECK (status IN ('PENDING', 'APPROVED', 'REJECTED')),
    created_at     TIMESTAMPTZ   NOT NULL DEFAULT now(),
    -- Aktywność może dodać tylko członek wyprawy; gdy opuszcza wyprawę, jego wkład znika razem z nim.
    FOREIGN KEY (expedition_id, user_id)
        REFERENCES expedition_members (expedition_id, user_id) ON DELETE CASCADE,
    -- Tylko w dyscyplinie włączonej w tej wyprawie; nie można wyłączyć dyscypliny, która ma aktywności.
    FOREIGN KEY (expedition_id, discipline_id)
        REFERENCES expedition_disciplines (expedition_id, discipline_id) ON DELETE RESTRICT
);

-- Feed, ranking i panel kapitana filtrują aktywności po wyprawie i statusie.
CREATE INDEX activities_expedition_status_idx ON activities (expedition_id, status);

CREATE TABLE audit_log (
    id            INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    expedition_id INT         NOT NULL REFERENCES expeditions (id) ON DELETE CASCADE,
    -- SET NULL: wpis o decyzji zostaje w historii, nawet gdy aktywność lub konto zostanie usunięte.
    activity_id   INT         REFERENCES activities (id) ON DELETE SET NULL,
    actor_id      INT         REFERENCES users (id) ON DELETE SET NULL,
    action        VARCHAR(8)  NOT NULL CHECK (action IN ('APPROVED', 'REJECTED')),
    created_at    TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX audit_log_expedition_idx ON audit_log (expedition_id, created_at DESC);
