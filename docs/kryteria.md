# Jak będzie oceniany projekt?

Poniżej znajdują się kryteria ewaluacji projektu zgodne z założeniami kursu. Każde kryterium sprawdza konkretny, możliwy do zaprezentowania element aplikacji. Nie wystarczy powiedzieć, że dana funkcja istnieje, trzeba ją uruchomić, wskazać w kodzie lub udokumentować w repozytorium.

Projekt należy zaprezentować podczas laboratorium, konsultacji albo w innym terminie uzgodnionym z prowadzącym. Podczas demo student powinien umieć wyjaśnić przepływ wybranego żądania: od interfejsu i routingu, przez kontroler oraz logikę aplikacji, aż do bazy danych i odpowiedzi.

**Warunek konieczny:** część backendowa musi być napisana obiektowo. Projekt z backendem napisanym strukturalnie otrzymuje ocenę 2.0, a pozostałe kryteria nie są wtedy punktowane. Samo umieszczenie pojedynczych funkcji w przypadkowych klasach nie oznacza programowania obiektowego.

## Punktacja

Projekt obejmuje 36 kryteriów. Za każde można otrzymać:

- **1 pkt** – kryterium zostało zrealizowane poprawnie, działa i można je zweryfikować;
- **0,5 pkt** – element istnieje, ale jest niepełny, niestabilny albo słabo udokumentowany;
- **0 pkt** – element nie istnieje, nie działa lub nie można go zaprezentować.

Wynik procentowy obliczamy według wzoru: zdobyte punkty / 36 × 100%.

| Punkty | Wynik | Ocena |
|---|---|---|
| 34–36 | 94–100% | 5.0 |
| 29,5–33,5 | 81–93% | 4.5 |
| 26–29 | 71–80% | 4.0 |
| 22–25,5 | 61–70% | 3.5 |
| 18,5–21,5 | 51–60% | 3.0 |
| 0–18 | poniżej 51% | 2.0 |

## Co przygotować przed oddaniem?

Przed prezentacją sprawdź projekt na czystym środowisku. Osoba oceniająca powinna móc sklonować repozytorium, utworzyć plik `.env` na podstawie `.env.example` i uruchomić aplikację zgodnie z instrukcją w `README.md`.

- ostateczna wersja znajduje się na gałęzi `main`;
- repozytorium nie zawiera haseł, tokenów ani produkcyjnych sekretów;
- aplikacja uruchamia się przez Docker Compose;
- baza posiada schemat i przykładowe dane umożliwiające demo;
- link do wdrożonej aplikacji oraz dane kont demonstracyjnych są opisane w `README.md`;
- testy, raport WCAG i wszystkie wymagane materiały są dostępne w repozytorium.

## Kryteria

| Nr | Kryterium | Warunek uzyskania 1 punktu |
|---|---|---|
| 1 | Dokumentacja w README.md | README zawiera opis problemu i funkcji aplikacji, instrukcję uruchomienia, wymagania, dane demonstracyjne, zrzuty najważniejszych ekranów, opis przepływu aplikacji, architekturę, ERD, sposób uruchomienia testów oraz link do wdrożenia. Zrzuty pokazują realny projekt, a nie sam prototyp. |
| 2 | Docker | Projekt uruchamia się powtarzalnie przez `docker compose up --build`. Kontenery aplikacji i PostgreSQL komunikują się po nazwach usług, dane bazy są utrwalone w wolumenie, a porty i zmienne są opisane. Pełny punkt wymaga uruchomienia projektu na czystym środowisku. |
| 3 | Architektura aplikacji | Aplikacja ma czytelny podział odpowiedzialności, np. MVC albo osobny frontend i backend. W README znajduje się diagram lub opis warstw i kierunku zależności. Kontroler nie powinien jednocześnie zawierać całego SQL, HTML i logiki biznesowej. |
| 4 | Routing | Router rozpoznaje metody HTTP i wzorce adresów. Projekt zawiera trasę statyczną, regex pattern, path parameter z przekazaniem i walidacją id oraz użyteczny query parameter. Błędne lub nieistniejące identyfikatory kończą się kontrolowaną odpowiedzią 400 albo 404. |
| 5 | Obiektowy backend | Backend wykorzystuje klasy, obiekty, enkapsulację, typy i podział odpowiedzialności. Kontrolery, repozytoria lub serwisy współpracują przez jasno określone interfejsy. Kod proceduralny jest ograniczony do punktu wejścia i konfiguracji. |
| 6 | Diagram ERD w Mermaid | Aktualny diagram ERD jest umieszczony bezpośrednio w README.md jako blok Mermaid `erDiagram`. Pokazuje tabele, klucze główne, klucze obce, istotne atrybuty i krotności relacji oraz jest zgodny z rzeczywistym schematem. |
| 7 | Git | Historia pokazuje systematyczną pracę: małe logiczne commity, czytelne komunikaty i brak jednego zbiorczego commita z całym projektem. Końcowa, działająca wersja wszystkich członków zespołu jest zmergowana do main. |
| 8 | Realizacja tematu | Projekt jest zgodny ze zgłoszonym tematem, realizuje jego najważniejszy proces od początku do końca i pozwala przeprowadzić spójne demo. Funkcjonalności nie mogą być wyłącznie statycznymi makietami. |
| 9 | HTML | Widoki wykorzystują semantyczny i poprawny HTML. Powtarzające się elementy, np. nagłówek, nawigacja i stopka, są wydzielone do współdzielonych szablonów lub komponentów. Formularze mają etykiety, odpowiednie typy pól i czytelne komunikaty błędów. |
| 10 | PostgreSQL | Aplikacja rzeczywiście korzysta z PostgreSQL. Połączenie jest konfigurowane poza kodem, zapytania są parametryzowane, a dane są mapowane w kontrolowany sposób. Student potrafi wskazać zapytanie wykonywane podczas demo. |
| 11 | Złożoność bazy danych | Schemat zawiera kilka powiązanych encji, poprawne klucze, ograniczenia i uzasadnione relacje. Dane są znormalizowane, nie występuje oczywista redundancja ani anomalie modyfikacji. Typy kolumn odpowiadają przechowywanym wartościom. |
| 12 | Eksport bazy do pliku .sql | Repozytorium zawiera aktualny plik .sql pozwalający odtworzyć schemat i bezpieczne dane demonstracyjne. Import działa na pustej bazie i nie zawiera produkcyjnych danych osobowych ani sekretów. |
| 13 | PHP | Kod korzysta ze współczesnej, wspieranej wersji PHP, deklaracji typów, poprawnej obsługi błędów i funkcji standardowych. Nie zawiera ostrzeżeń ani błędów podczas typowego scenariusza. Student rozumie prezentowany kod PHP. |
| 14 | JavaScript | JavaScript realizuje realne zachowanie interfejsu: walidację po stronie klienta, obsługę zdarzeń i świadomą manipulację DOM. Kod jest podzielony na funkcje lub moduły i nie składa się wyłącznie z jednego skopiowanego skryptu. |
| 15 | Fetch API (AJAX) | Aplikacja wykonuje co najmniej jedno uzasadnione żądanie GET i POST przez Fetch API bez pełnego przeładowania strony. Obsługuje JSON, statusy HTTP, stan ładowania, powodzenie i błąd oraz aktualizuje interfejs otrzymanymi danymi. |
| 16 | Design | Interfejs jest spójny, czytelny i zgodny z tematyką. Wykorzystuje konsekwentną typografię, paletę, odstępy i komponenty. Style znajdują się w arkuszach CSS, a nie są powielane jako przypadkowe style inline. |
| 17 | Responsywność | Co najmniej pięć istotnych widoków działa poprawnie na telefonie, tablecie i komputerze. Nie występuje poziome przewijanie ani nachodzenie elementów. Projekt wykorzystuje elastyczne układy i uzasadnione media queries. |
| 18 | Tryb dark/light mode | Motyw jest ustawiany atrybutem `data-theme` na elemencie `<html>`, wybór użytkownika zapisuje się w localStorage, a pierwszy wybór uwzględnia `prefers-color-scheme`. Krótki skrypt w `<head>` ustawia motyw przed renderowaniem i zapobiega efektowi FOUC (ang. Flash of Unstyled Content). |
| 19 | Zgodność z WCAG | Interfejs można obsłużyć klawiaturą, fokus jest widoczny, obrazy mają alternatywy, formularze etykiety, a kontrast jest wystarczający. W README znajduje się raport z narzędzia, np. Lighthouse, axe lub WAVE, z datą, wynikiem, zrzutem i opisem naprawionych problemów. |
| 20 | Logowanie | Użytkownik loguje się danymi z bazy. Hasło jest weryfikowane bez porównywania tekstu jawnego, błędne dane zwracają bezpieczny komunikat, a po zalogowaniu następuje poprawne przekierowanie i odtworzenie tożsamości użytkownika. |
| 21 | Sesja użytkownika | Stan zalogowania jest utrzymywany po przejściu między podstronami. Identyfikator sesji jest regenerowany po logowaniu, cookie ma właściwe ustawienia, a dane klienta nie są traktowane jako zaufane źródło uprawnień. |
| 22 | Uprawnienia użytkowników | Backend sprawdza uprawnienia przed każdą chronioną operacją. Ukrycie przycisku w HTML nie jest zabezpieczeniem. Próba ręcznego wywołania niedozwolonej trasy kończy się odpowiedzią 401 lub 403. |
| 23 | Role użytkowników | Aplikacja posiada co najmniej dwie sensowne role, np. USER i ADMIN. Role mają różne możliwości, są zapisane w bazie lub mapowane przez backend i można je zaprezentować na oddzielnych kontach. |
| 24 | Wylogowywanie | Wylogowanie unieważnia dane sesji i usuwa lub wygasza odpowiednie cookie. Użycie przycisku Wstecz albo ponowienie żądania do chronionej trasy nie przywraca dostępu. |
| 25 | Widoki, wyzwalacze, funkcje lub transakcje | W bazie lub repozytorium zastosowano co najmniej jeden z mechanizmów: widok, wyzwalacz, funkcję PostgreSQL albo transakcję. Użycie musi wynikać z rzeczywistego scenariusza, znajdować się w eksporcie lub migracji i zostać pokazane podczas demo. |
| 26 | Akcje na referencjach | Aplikacja wykonuje zapytania łączące powiązane tabele przez JOIN, a klucze obce mają przemyślane zachowanie ON DELETE i ewentualnie ON UPDATE. Student potrafi uzasadnić wybór CASCADE, RESTRICT, SET NULL lub zachowania domyślnego. |
| 27 | Bezpieczeństwo — Security Bingo | Zrealizowano i udokumentowano Security Bingo. Projekt stosuje m.in. hashowanie haseł, parametryzowane zapytania, walidację danych, kodowanie wyjścia, autoryzację po stronie serwera, ochronę formularzy oraz bezpieczny upload. W README znajduje się tabela z dowodem dla każdego zaznaczonego pola. |
| 28 | Brak replikacji kodu | Powtarzająca się logika została wydzielona do współdzielonych metod, klas, serwisów, komponentów lub szablonów. W repozytorium nie ma kilku kopii tego samego zapytania, walidacji ani rozbudowanego fragmentu HTML. |
| 29 | Czystość i przejrzystość kodu | Nazwy opisują odpowiedzialność, metody są krótkie, formatowanie spójne, a martwy kod i debugowanie zostały usunięte. Struktura katalogów jest przewidywalna. Komentarze wyjaśniają decyzje, zamiast powtarzać treść kodu. |
| 30 | Tłumaczenia | Interfejs obsługuje co najmniej język polski i angielski. Teksty nie są rozsiane po kontrolerach i widokach, lecz pobierane z katalogu lub mechanizmu tłumaczeń. Zmiana języka obejmuje także walidację i komunikaty błędów. |
| 31 | Testy jednostkowe | Repozytorium zawiera uruchamialne testy jednostkowe dla istotnej logiki backendu, w tym przypadek poprawny i błędny. Testy są izolowane, powtarzalne, kończą się powodzeniem i mają udokumentowane polecenie uruchomienia. |
| 32 | CRON | Projekt ma uzasadnione zadanie cykliczne, np. czyszczenie wygasłych danych lub wysyłkę przypomnień. Harmonogram, polecenie i sposób testowania są opisane. Zadanie zapisuje wynik lub log i jest odporne na bezpieczne ponowienie. |
| 33 | PHP Mailer | Aplikacja wysyła użyteczną wiadomość e-mail przez PHPMailer lub równoważny mechanizm SMTP. Dane serwera pocztowego pochodzą ze środowiska, błędy są obsługiwane, a lokalne demo może korzystać z Mailpit. |
| 34 | Upload zdjęcia | Backend sprawdza rozmiar, rzeczywisty typ MIME i dozwolone rozszerzenie, nadaje bezpieczną unikalną nazwę i zapisuje plik poza kodem aplikacji lub w przeznaczonym katalogu uploadów. W bazie przechowywany jest wyłącznie URL albo względna ścieżka, nie zawartość pliku. |
| 35 | Zmienne w .env lub Key Vault | Hasła, tokeny, klucze i konfiguracja zależna od środowiska nie są wpisane w kod ani commitowane. Repozytorium zawiera bezpieczny `.env.example` z nazwami wymaganych zmiennych. Produkcja korzysta ze zmiennych środowiskowych lub menedżera sekretów. |
| 36 | Deploy | Aplikacja jest dostępna pod publicznym adresem i działa poza komputerem autora. Wdrożona wersja odpowiada gałęzi main, używa produkcyjnej konfiguracji, ma działającą bazę, uploady i wymagane zmienne. Link oraz krótki opis wdrożenia znajdują się w README. |

## Minimalna struktura README.md

Możesz rozbudować poniższy plan, ale nie pomijaj informacji potrzebnych do uruchomienia i oceny projektu:

```markdown
# Nazwa projektu

## Autorzy i zakres odpowiedzialności
## Opis problemu i główne funkcje
## Zrzuty ekranu (co najmniej 5 mobilny, 5 webowych)
## Architektura
## Diagram ERD bazy danych (napisany w Mermaid)
## Routing / endpointy
## Uruchomienie przez Docker
## Zmienne środowiskowe
## Import bazy i dane demonstracyjne
## Testy
## Raport WCAG
## Security Bingo
## Zadanie CRON i wysyłka e-mail
## Deploy
```

## Przykład ERD napisany w Mermaid

Mermaid to prosty język tekstowy do tworzenia diagramów bez rysowania ich ręcznie. W Twoim przypadku możesz nim zrobić diagram pokazujący tabele w bazie danych, ich kolumny oraz relacje między tabelami.

```mermaid
erDiagram
    USER ||--o{ PROJECT : creates
    USER {
        int id PK
        string email UK
        string password_hash
    }
    PROJECT {
        int id PK
        int user_id FK
        string title
    }
```

## Ostatnia próba przed prezentacją

Sklonuj własne repozytorium do nowego katalogu i wykonaj wyłącznie kroki opisane w README. Jeżeli potrafisz uruchomić aplikację, odtworzyć bazę z danych testowych, zalogować się na dwie role, przejść główny flow, uruchomić testy i wskazać dowody dla kryteriów – jesteś gotowy do demo. Gratuluję! Zrobiłeś kawał dobrej pracy 💪
