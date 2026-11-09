# Nazwa projektu

Jedno zdanie: jaki problem rozwiązujemy, dla kogo i w jaki sposób.

## Funkcje

| Obszar | Co robi |
|---|---|
| Strona startowa | Krótki opis funkcji |

## Tech stack

| Warstwa | Technologie |
|---|---|
| Backend | PHP 8.3 |
| Baza danych | PostgreSQL 17 |
| Serwer HTTP | Nginx |
| Konteneryzacja | Docker Compose |

## Szybki start

```bash
git clone https://github.com/LOGIN/REPOZYTORIUM.git
cd REPOZYTORIUM
docker compose up --build -d
```

Aplikacja: http://localhost:8080

## Struktura projektu

Krótki opis najważniejszych katalogów.

## Baza danych

Schemat: [`docker/db/01-schema.sql`](docker/db/01-schema.sql), dane demo: [`docker/db/02-seed.sql`](docker/db/02-seed.sql).
Oba pliki ładują się automatycznie przy pierwszym starcie kontenera `db`; żeby wgrać je od nowa:
`docker compose down -v && docker compose up -d` (usuwa dane w bazie).

```mermaid
erDiagram
    users ||--o{ expeditions : "prowadzi (captain_id)"
    users ||--o{ expedition_members : "należy"
    expeditions ||--o{ expedition_members : "ma uczestników"
    expeditions ||--o{ checkpoints : "ma trasę"
    expeditions ||--o{ expedition_disciplines : "ma przeliczniki"
    disciplines ||--o{ expedition_disciplines : "używana w"
    expedition_members ||--o{ activities : "dodaje"
    expedition_disciplines ||--o{ activities : "dyscyplina i przelicznik"
    expeditions ||--o{ audit_log : "historia decyzji"
    activities |o--o{ audit_log : "dotyczy"
    users |o--o{ audit_log : "decyduje"

    users {
        int id PK
        varchar email UK "unikalny bez względu na wielkość liter"
        varchar password_hash
        varchar name
        varchar role "USER | CAPTAIN | ADMIN"
        timestamptz created_at
    }

    expeditions {
        int id PK
        varchar name
        int captain_id FK "ON DELETE RESTRICT"
        date start_date
        date end_date "CHECK end_date >= start_date"
        smallint max_members
        char join_code UK "6-cyfrowy PIN"
        timestamptz created_at
    }

    checkpoints {
        int id PK
        int expedition_id FK "ON DELETE CASCADE"
        varchar name
        numeric km_from_start "unikalny w wyprawie"
    }

    expedition_members {
        int expedition_id PK, FK "ON DELETE CASCADE"
        int user_id PK, FK "ON DELETE CASCADE"
        timestamptz joined_at
    }

    disciplines {
        int id PK
        varchar name UK
        varchar unit "km | min"
    }

    expedition_disciplines {
        int expedition_id PK, FK "ON DELETE CASCADE"
        int discipline_id PK, FK "ON DELETE RESTRICT"
        numeric km_factor "przelicznik na km trasy"
    }

    activities {
        int id PK
        int expedition_id FK "z user_id i z discipline_id"
        int user_id FK "-> expedition_members, CASCADE"
        int discipline_id FK "-> expedition_disciplines, RESTRICT"
        numeric distance_km "NULL dla siłowni"
        smallint duration_min
        date activity_date
        smallint avg_heart_rate
        varchar note
        varchar photo_path "tylko ścieżka do pliku"
        numeric route_km "migawka przeliczenia"
        varchar status "PENDING | APPROVED | REJECTED"
        timestamptz created_at
    }

    audit_log {
        int id PK
        int expedition_id FK "ON DELETE CASCADE"
        int activity_id FK "ON DELETE SET NULL"
        int actor_id FK "ON DELETE SET NULL"
        varchar action "APPROVED | REJECTED"
        timestamptz created_at
    }
```

**Najważniejsze decyzje:**
- `activities` ma złożone klucze obce do `expedition_members` i `expedition_disciplines` – baza sama pilnuje,
  że aktywność dodaje członek wyprawy i tylko w dyscyplinie włączonej w tej wyprawie.
- `route_km` to migawka przeliczenia z dnia dodania – zmiana przelicznika nie przepisuje historii.
- Status wyprawy i długość trasy nie są kolumnami – wynikają z dat i ostatniego checkpointu.
- `audit_log` używa `SET NULL`, żeby historia decyzji kapitana przetrwała usunięcie aktywności lub konta.

## Zasady developmentu

- Branch dla każdej funkcji.
- Małe, logiczne commity.
- Sekrety wyłącznie w .env.

## Autorzy

- Imię i nazwisko — zakres odpowiedzialności