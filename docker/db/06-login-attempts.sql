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
