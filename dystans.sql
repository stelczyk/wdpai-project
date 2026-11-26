-- Dystans – eksport bazy: schemat, widoki i dane demonstracyjne (fikcyjne osoby, hasło demo: Demo123!).
-- Wygenerowane przez bin/export-sql.sh z docker/db/*.sql – nie edytuj ręcznie.
-- Import do PUSTEJ bazy PostgreSQL (całość w jednej transakcji – błąd cofa wszystko):
--   psql -v ON_ERROR_STOP=1 -U <user> -d <pusta_baza> -f dystans.sql

BEGIN;

-- ===== 01-schema.sql =====
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

-- ===== 02-seed.sql =====
-- Dystans – bezpieczne dane demonstracyjne (fikcyjne osoby, brak prawdziwych danych osobowych).
-- Wszystkie konta mają hasło: Demo123!  (hash bcrypt z password_hash()).
-- Daty liczone od CURRENT_DATE, żeby wyprawa "w trakcie" była w trakcie niezależnie od dnia demo.

INSERT INTO users (email, password_hash, name, role) VALUES
    ('admin@dystans.app',   '$2y$10$1IM.hk7T7mOwZjNTvlt85OTpbCJw/dwHvAW7RL.vfse.J9fUUUzsa', 'Admin Dystansu',    'ADMIN'),
    ('kapitan@dystans.app', '$2y$10$1IM.hk7T7mOwZjNTvlt85OTpbCJw/dwHvAW7RL.vfse.J9fUUUzsa', 'Marta Kapitan',     'CAPTAIN'),
    ('jan@dystans.app',     '$2y$10$1IM.hk7T7mOwZjNTvlt85OTpbCJw/dwHvAW7RL.vfse.J9fUUUzsa', 'Jan Kowalski',      'USER'),
    ('anna@dystans.app',    '$2y$10$1IM.hk7T7mOwZjNTvlt85OTpbCJw/dwHvAW7RL.vfse.J9fUUUzsa', 'Anna Nowak',        'USER'),
    ('piotr@dystans.app',   '$2y$10$1IM.hk7T7mOwZjNTvlt85OTpbCJw/dwHvAW7RL.vfse.J9fUUUzsa', 'Piotr Wiśniewski',  'USER'),
    ('kasia@dystans.app',   '$2y$10$1IM.hk7T7mOwZjNTvlt85OTpbCJw/dwHvAW7RL.vfse.J9fUUUzsa', 'Kasia Zielińska',   'USER');

INSERT INTO disciplines (name, unit) VALUES
    ('Bieg', 'km'),
    ('Rower', 'km'),
    ('Pływanie', 'km'),
    ('Siłownia', 'min');

-- Trzy wyprawy: w trakcie, planowana i ukończona (zakładki ekranu "Moje wyprawy").
INSERT INTO expeditions (name, captain_id, start_date, end_date, max_members, join_code)
SELECT v.name, u.id, CURRENT_DATE + v.start_offset, CURRENT_DATE + v.end_offset, v.max_members, v.join_code
FROM (VALUES
    ('Kraków → Monza',     -21,  39, 10, '482913'),
    ('Gdańsk → Kopenhaga',  14,  60,  8, '730215'),
    ('Wrocław → Praga',    -90, -30,  6, '519046')
) AS v(name, start_offset, end_offset, max_members, join_code)
JOIN users u ON u.email = 'kapitan@dystans.app';

INSERT INTO checkpoints (expedition_id, name, km_from_start)
SELECT e.id, v.checkpoint, v.km
FROM (VALUES
    ('Kraków → Monza', 'Kraków', 0),
    ('Kraków → Monza', 'Ostrawa', 170),
    ('Kraków → Monza', 'Wiedeń', 460),
    ('Kraków → Monza', 'Graz', 660),
    ('Kraków → Monza', 'Klagenfurt', 800),
    ('Kraków → Monza', 'Udine', 930),
    ('Kraków → Monza', 'Werona', 1170),
    ('Kraków → Monza', 'Monza', 1330),
    ('Gdańsk → Kopenhaga', 'Gdańsk', 0),
    ('Gdańsk → Kopenhaga', 'Kołobrzeg', 230),
    ('Gdańsk → Kopenhaga', 'Szczecin', 360),
    ('Gdańsk → Kopenhaga', 'Rostock', 540),
    ('Gdańsk → Kopenhaga', 'Kopenhaga', 700),
    ('Wrocław → Praga', 'Wrocław', 0),
    ('Wrocław → Praga', 'Kłodzko', 95),
    ('Wrocław → Praga', 'Hradec Králové', 190),
    ('Wrocław → Praga', 'Praga', 300)
) AS v(expedition, checkpoint, km)
JOIN expeditions e ON e.name = v.expedition;

INSERT INTO expedition_members (expedition_id, user_id, joined_at)
SELECT e.id, u.id, e.start_date - 3
FROM (VALUES
    ('Kraków → Monza', 'kapitan@dystans.app'),
    ('Kraków → Monza', 'jan@dystans.app'),
    ('Kraków → Monza', 'anna@dystans.app'),
    ('Kraków → Monza', 'piotr@dystans.app'),
    ('Kraków → Monza', 'kasia@dystans.app'),
    ('Gdańsk → Kopenhaga', 'kapitan@dystans.app'),
    ('Gdańsk → Kopenhaga', 'jan@dystans.app'),
    ('Gdańsk → Kopenhaga', 'anna@dystans.app'),
    ('Wrocław → Praga', 'kapitan@dystans.app'),
    ('Wrocław → Praga', 'jan@dystans.app'),
    ('Wrocław → Praga', 'piotr@dystans.app')
) AS v(expedition, email)
JOIN expeditions e ON e.name = v.expedition
JOIN users u ON u.email = v.email;

-- Każda wyprawa ma wszystkie cztery dyscypliny z tymi samymi przelicznikami (kapitan może je zmienić).
INSERT INTO expedition_disciplines (expedition_id, discipline_id, km_factor)
SELECT e.id, d.id, v.km_factor
FROM expeditions e
CROSS JOIN (VALUES
    ('Bieg', 1.00),
    ('Rower', 0.35),
    ('Pływanie', 4.00),
    ('Siłownia', 0.10)
) AS v(discipline, km_factor)
JOIN disciplines d ON d.name = v.discipline;

-- route_km = (dystans albo minuty, zależnie od jednostki dyscypliny) × przelicznik wyprawy.
INSERT INTO activities (expedition_id, user_id, discipline_id, distance_km, duration_min,
                        activity_date, avg_heart_rate, note, route_km, status, created_at)
SELECT e.id, u.id, d.id, v.distance_km, v.duration_min,
       CURRENT_DATE - v.days_ago, v.avg_heart_rate, v.note,
       round(CASE d.unit WHEN 'km' THEN v.distance_km ELSE v.duration_min END * ed.km_factor, 2),
       v.status,
       (CURRENT_DATE - v.days_ago) + TIME '19:00'
FROM (VALUES
    ('Kraków → Monza', 'jan@dystans.app',     'Bieg',     10.20::numeric, 55,  19, 152::smallint, 'Poranny bieg nad Wisłą', 'APPROVED'),
    ('Kraków → Monza', 'jan@dystans.app',     'Rower',    62.00,          140, 15, 138,           'Wycieczka do Ojcowa',    'APPROVED'),
    ('Kraków → Monza', 'jan@dystans.app',     'Bieg',     8.40,           46,  2,  149,           NULL,                     'PENDING'),
    ('Kraków → Monza', 'anna@dystans.app',    'Pływanie', 2.50,           60,  18, 131,           'Basen AGH',              'APPROVED'),
    ('Kraków → Monza', 'anna@dystans.app',    'Bieg',     21.10,          118, 11, 161,           'Półmaraton treningowy',  'APPROVED'),
    ('Kraków → Monza', 'anna@dystans.app',    'Siłownia', NULL,           75,  1,  NULL,          'Trening siłowy',         'PENDING'),
    ('Kraków → Monza', 'piotr@dystans.app',   'Rower',    105.00,         230, 16, 142,           'Kraków – Tarnów',        'APPROVED'),
    ('Kraków → Monza', 'piotr@dystans.app',   'Rower',    300.00,         240, 9,  120,           'Podejrzanie szybko',     'REJECTED'),
    ('Kraków → Monza', 'piotr@dystans.app',   'Bieg',     6.00,           35,  3,  155,           NULL,                     'PENDING'),
    ('Kraków → Monza', 'kasia@dystans.app',   'Bieg',     5.00,           30,  17, 158,           'Pierwsza piątka',        'APPROVED'),
    ('Kraków → Monza', 'kasia@dystans.app',   'Pływanie', 1.80,           45,  7,  128,           NULL,                     'APPROVED'),
    ('Kraków → Monza', 'kasia@dystans.app',   'Siłownia', NULL,           60,  0,  NULL,          NULL,                     'PENDING'),
    ('Kraków → Monza', 'kapitan@dystans.app', 'Bieg',     12.00,          62,  13, 150,           'Długie wybieganie',      'APPROVED'),
    ('Kraków → Monza', 'kapitan@dystans.app', 'Rower',    80.00,          180, 5,  135,           NULL,                     'APPROVED'),
    ('Wrocław → Praga', 'jan@dystans.app',     'Rower',   120.00,         300, 80, 140,           NULL,                     'APPROVED'),
    ('Wrocław → Praga', 'piotr@dystans.app',   'Bieg',    42.20,          225, 60, 163,           'Maraton!',               'APPROVED'),
    ('Wrocław → Praga', 'kapitan@dystans.app', 'Pływanie', 3.00,          70,  45, 130,           'Seria na basenie',       'APPROVED')
) AS v(expedition, email, discipline, distance_km, duration_min, days_ago, avg_heart_rate, note, status)
JOIN expeditions e ON e.name = v.expedition
JOIN users u ON u.email = v.email
JOIN disciplines d ON d.name = v.discipline
JOIN expedition_disciplines ed ON ed.expedition_id = e.id AND ed.discipline_id = d.id;

-- Decyzje kapitana dla już rozpatrzonych aktywności (następnego dnia rano).
INSERT INTO audit_log (expedition_id, activity_id, actor_id, action, created_at)
SELECT a.expedition_id, a.id, e.captain_id, a.status, a.created_at + INTERVAL '14 hours'
FROM activities a
JOIN expeditions e ON e.id = a.expedition_id
WHERE a.status <> 'PENDING';

-- ===== 03-password-resets.sql =====
-- Dystans – tokeny resetu hasła (zadanie 207).
-- Na istniejącej bazie uruchom ręcznie:
--   docker compose exec db sh -c 'psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -f /docker-entrypoint-initdb.d/03-password-resets.sql'

CREATE TABLE IF NOT EXISTS password_resets (
    id         INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    -- CASCADE: po usunięciu konta jego tokeny nie mają sensu.
    user_id    INT         NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    -- Tylko skrót SHA-256 tokenu – wyciek bazy nie pozwala nikomu zresetować hasła.
    token_hash CHAR(64)    NOT NULL UNIQUE,
    expires_at TIMESTAMPTZ NOT NULL,
    -- Token jednorazowy: po użyciu dostaje datę i przestaje działać.
    used_at    TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- ===== 04-expedition-progress.sql =====
-- Dystans – widok postępu wypraw (zadania 301 / 401, kryterium 25).
-- Na istniejącej bazie uruchom ręcznie:
--   docker compose exec db sh -c 'psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -f /docker-entrypoint-initdb.d/04-expedition-progress.sql'
--
-- Jedno źródło prawdy dla „ile drużyna przeszła” – lista wypraw, dashboard i ranking
-- czytają postęp stąd, zamiast powtarzać to samo zapytanie w kilku miejscach.

CREATE OR REPLACE VIEW expedition_progress AS
SELECT e.id AS expedition_id,
       -- długość trasy = odległość ostatniego checkpointu od startu
       COALESCE((SELECT max(c.km_from_start) FROM checkpoints c WHERE c.expedition_id = e.id), 0) AS total_km,
       -- do postępu liczą się tylko aktywności zatwierdzone przez kapitana
       COALESCE((SELECT sum(a.route_km) FROM activities a
                 WHERE a.expedition_id = e.id AND a.status = 'APPROVED'), 0) AS approved_km
FROM expeditions e;

-- ===== 05-reminders.sql =====
-- Dystans – log przypomnień dla nieaktywnych uczestników (zadanie 705, cron bin/send-reminders.php).
-- Na istniejącej bazie uruchom ręcznie:
--   docker compose exec db sh -c 'psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -f /docker-entrypoint-initdb.d/05-reminders.sql'

CREATE TABLE IF NOT EXISTS reminder_log (
    id            INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    -- CASCADE: log przypomnień nie ma znaczenia bez konta ani bez wyprawy.
    user_id       INT          NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    expedition_id INT          NOT NULL REFERENCES expeditions (id) ON DELETE CASCADE,
    -- SENT: mail wysłany – ponowne uruchomienie go pominie. FAILED: błąd SMTP – kolejne uruchomienie spróbuje znowu.
    status        VARCHAR(6)   NOT NULL CHECK (status IN ('SENT', 'FAILED')),
    error         VARCHAR(500),
    created_at    TIMESTAMPTZ  NOT NULL DEFAULT now()
);

-- Skrypt pyta „czy ta osoba dostała już przypomnienie o tej wyprawie w ostatnich 48 h”.
CREATE INDEX IF NOT EXISTS reminder_log_member_idx ON reminder_log (user_id, expedition_id, created_at DESC);

-- ===== 06-login-attempts.sql =====
-- Dystans – nieudane próby logowania (ochrona przed zgadywaniem haseł, Security Bingo A4/E5).
-- Na istniejącej bazie uruchom ręcznie:
--   docker compose exec db sh -c 'psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -f /docker-entrypoint-initdb.d/06-login-attempts.sql'

CREATE TABLE IF NOT EXISTS login_attempts (
    id           INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    -- Bez klucza obcego do users: liczymy też próby na nieistniejące konta (zgadywanie adresów).
    email        VARCHAR(255) NOT NULL,
    ip           INET         NOT NULL,
    attempted_at TIMESTAMPTZ  NOT NULL DEFAULT now()
);

-- Blokada sprawdza ostatnie próby dla adresu e-mail albo dla adresu IP.
CREATE INDEX IF NOT EXISTS login_attempts_email_idx ON login_attempts (email, attempted_at DESC);
CREATE INDEX IF NOT EXISTS login_attempts_ip_idx ON login_attempts (ip, attempted_at DESC);

-- ===== 07-user-blocking.sql =====
-- Dystans – blokowanie kont przez administratora (panel /admin/users, API /api/users).
-- Na istniejącej bazie uruchom ręcznie:
--   docker compose exec db sh -c 'psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -f /docker-entrypoint-initdb.d/07-user-blocking.sql'

-- Zablokowany użytkownik nie zaloguje się, a jego otwarta sesja wygasa przy następnym żądaniu.
-- Blokada zamiast usunięcia zachowuje jego aktywności i historię wypraw.
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_blocked BOOLEAN NOT NULL DEFAULT FALSE;

COMMIT;
