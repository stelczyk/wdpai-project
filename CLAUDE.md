# Dystans – projekt zaliczeniowy (PHP MVC, PostgreSQL)

Aplikacja: wirtualne wyprawy – znajomi logują treningi (bieg, rower, pływanie, siłownia),
które są przeliczane na km postępu na trasie z checkpointami (np. Kraków → Monza).
Role: USER (uczestnik), CAPTAIN (tworzy wyprawę, zatwierdza aktywności), ADMIN.
Ekrany: logowanie, dashboard wyprawy, dodaj aktywność (z uploadem zdjęcia), ranking,
panel kapitana, tworzenie wyprawy, profil, 404.

## Środowisko (Docker, docker-compose.yaml)
- Start: `docker compose up -d --build`. Kod montowany w kontenerach jako /app.
- Aplikacja (nginx → php-fpm): http://localhost:8080
- PostgreSQL: host `localhost:5433` (z kontenera php: `db:5432`); dane z `.env` (POSTGRES_*).
- Mailpit (podgląd maili): http://localhost:8025, pgAdmin: http://localhost:5050
- Skrypty .sql w docker/db/ są ładowane przy pierwszym starcie bazy (docker-entrypoint-initdb.d).
- Stan repo: na razie tylko public/index.php i konfiguracja Dockera; src/, composer.json, docs/ do utworzenia.

## Zasady projektu (zgodne z laboratoriami – NIE używać frameworków)
- PHP 8.3, `declare(strict_types=1);` w każdym pliku, typy parametrów i zwracane.
- Własny MVC: public/index.php (jedyny punkt wejścia) → App\Routing → kontroler → widok.
- Kontrolery w src/controllers (namespace App\Controller), dziedziczą po AppController i używają render().
- Logika biznesowa w serwisach (src/services), SQL tylko w repozytoriach (src/repositories), PDO z prepared statements.
- Autoload Composera (PSR-4), jedyne require_once to vendor/autoload.php.
- Widoki: public/views/*.html.php, wspólne części w public/views/partials. Semantyczny HTML, wyjście przez htmlspecialchars().
- CSS w plikach, bez stylów inline. Motyw: data-theme na <html>, localStorage, prefers-color-scheme.
- Teksty UI z katalogu tłumaczeń (PL/EN), nie wpisane na sztywno.
- Sekrety tylko w .env, aktualizuj .env.example.
- Kryteria oceny: docs/kryteria.md. Treści labów: docs/labs/.
- Przykłady z labów (my-app, ProjectController itd.) zawsze przenoś na encje i ekrany Dystansu.

## Styl pracy
- Najpierw krótki plan, potem implementacja.
- Małe logiczne commity z czytelnymi komunikatami.
- Po zmianach krótko wyjaśnij, jak działa kod – muszę go obronić na demo.

## Git
- Commity w stylu `prefiks: opis` (angielski, tryb rozkazujący): feat, fix, docs, refactor, test, chore.
  Przykład: `feat: add registration form`. Jeden commit = jedna logiczna zmiana.
- NIE dopisuj `Co-Authored-By` ani żadnej wzmianki o Claude w commitach i opisach PR.
- Przed commitem: `git status`, konkretne pliki w `git add` (nie `git add .`), `git diff --cached`.
- Praca na branchach (`feature/...`, `fix/...`, `docs/...`), do `main` przez Pull Request z opcją
  „Create a merge commit”. NIE squashuj commitów, nie używaj `rebase -i` ze squash/fixup,
  nigdy `push --force` na main. Rebase tylko na własnym, lokalnym branchu.
- Nie commituj `.env` ani sekretów.
