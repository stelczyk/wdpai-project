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
