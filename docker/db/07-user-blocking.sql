-- Dystans – blokowanie kont przez administratora (panel /admin/users, API /api/users).
-- Na istniejącej bazie uruchom ręcznie:
--   docker compose exec db sh -c 'psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -f /docker-entrypoint-initdb.d/07-user-blocking.sql'

-- Zablokowany użytkownik nie zaloguje się, a jego otwarta sesja wygasa przy następnym żądaniu.
-- Blokada zamiast usunięcia zachowuje jego aktywności i historię wypraw.
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_blocked BOOLEAN NOT NULL DEFAULT FALSE;
