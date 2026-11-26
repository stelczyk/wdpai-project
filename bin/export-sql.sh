#!/bin/sh
# Składa dystans.sql (katalog główny repo) – jeden plik ze schematem, widokami i bezpiecznymi danymi demo (kryterium 12).
# Źródłem są skrypty docker/db/*.sql w tej samej kolejności, w jakiej ładuje je kontener bazy,
# więc eksport nie rozjedzie się z migracjami. Uruchom z katalogu projektu po każdej zmianie w docker/db:
#   sh bin/export-sql.sh
#
# Celowo nie pg_dump: zrzut zawierałby dane z lokalnych testów, a seed liczy daty od CURRENT_DATE
# (wyprawa „w trakcie” jest w trakcie w dniu importu) – zrzut zamroziłby te daty.
set -eu

out=dystans.sql

{
    echo '-- Dystans – eksport bazy: schemat, widoki i dane demonstracyjne (fikcyjne osoby, hasło demo: Demo123!).'
    echo '-- Wygenerowane przez bin/export-sql.sh z docker/db/*.sql – nie edytuj ręcznie.'
    echo '-- Import do PUSTEJ bazy PostgreSQL (całość w jednej transakcji – błąd cofa wszystko):'
    echo '--   psql -v ON_ERROR_STOP=1 -U <user> -d <pusta_baza> -f dystans.sql'
    echo
    echo 'BEGIN;'
    for file in docker/db/[0-9][0-9]-*.sql; do
        echo
        echo "-- ===== $(basename "$file") ====="
        tr -d '\r' < "$file"
    done
    echo
    echo 'COMMIT;'
} > "$out"

echo "Zapisano $out ($(wc -l < "$out") linii)"
