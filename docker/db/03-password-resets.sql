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
