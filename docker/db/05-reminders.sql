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
