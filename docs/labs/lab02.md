# Laboratorium 2 – szkielet MVC, routing, namespace, Composer

## Część 1

MVC: routing, kontroler i widoki HTML
29-09-2026 11:00
Od działającego Dockera do pierwszego widoku
Po Laboratorium 1 przeglądarka potrafi już dotrzeć przez Nginx do kontenera PHP. Teraz zbudujemy wewnątrz aplikacji prosty szkielet MVC. PHP będzie dziś przede wszystkim zapisem znanych Ci pojęć obiektowych: klasy, dziedziczenia, metod i odpowiedzialności obiektów.

Nie tworzymy jeszcze kompletnego frameworka ani nie uczymy się całego PHP. Budujemy mały, czytelny wzorzec, który będzie można rozwijać podczas kolejnych spotkań.

Cel laboratorium: po wpisaniu adresu przeglądarka ma trafić do odpowiedniej metody kontrolera, a kontroler ma wyrenderować semantyczny widok HTML. Na Laboratorium 3 ten sam HTML ostylujesz za pomocą CSS.

 
1. Przepływ MVC

Każdy element ma jedną odpowiedzialność. Router rozpoznaje żądanie, kontroler decyduje co wykonać, a widok opisuje dokument HTML. To właśnie jest najważniejsza część dzisiejszego ćwiczenia.

2. Przygotuj strukturę katalogów
Rozbuduj projekt z poprzedniego laboratorium do poniższej postaci:

my-app/
├── docker-compose.yaml
├── Routing.php
├── docker/
├── public/
│   ├── index.php
│   └── views/
│       ├── home.html.php
│       ├── project.html.php
│       ├── projects.html.php
│       └── not-found.html.php
└── src/
    └── controllers/
        ├── AppController.php
        ├── DefaultController.php
        └── ProjectController.php
Plik public/index.php pełni rolę Front Controllera jest głównym i jedynym punktem wejścia do aplikacji webowej. Każde żądanie HTTP powinno być kierowane najpierw do tego pliku, który następnie uruchamia mechanizm routingu i przekazuje obsługę żądania do odpowiedniego kontrolera.

Pliki znajdujące się w src/, w szczególności klasy kontrolerów, nie powinny być dostępne bezpośrednio przez adres URL. Katalog public/ powinien być katalogiem udostępnianym przez serwer WWW.

 
3. PHP zapisuje znany Ci model obiektowy
Jeżeli znasz obiektową Javę, większość konstrukcji ma znajomy sens. Różni się głównie składnia.

PHP	Znaczenie	Skojarzenie z Javą
extends AppController	dziedziczenie	extends AppController
$this->render(...)	wywołanie metody obiektu	this.render(...)
ProjectController::class	pełna nazwa klasy bez tworzenia obiektu	ProjectController.class
public function show(int $id): void	publiczna metoda z typami	public void show(int id)
 
4. Umieść wspólne renderowanie w AppController
Każdy kontroler będzie potrzebował tej samej operacji: 

1️⃣ odnaleźć szablon, 

2️⃣ przekazać mu dane i zwrócić wynik do przeglądarki. 

Nie kopiuj tej metody do kolejnych klas. Wystarczy, że umieścimy ją w klasie bazowej, dzięki temu wszystkie klasy, które będa dziedziczyły klasę bazową będą miały dostęp do tej metody.

Umieść ją raz w src/controllers/AppController.php, a pozostałe kontrolery niech po niej dziedziczą.

<?php

declare(strict_types=1);

abstract class AppController
{
    protected function render(string $template, array $variables = []): void
    {
        $templatePath = dirname(__DIR__, 2) . '/public/views/' . $template . '.html.php';

        if (!is_file($templatePath)) {
            throw new \RuntimeException('View not found: ' . $template);
        }

        extract($variables, EXTR_SKIP);
        require $templatePath;
    }
}
 

extract() zamienia klucze tablicy na zmienne dostępne w widoku. Dane ['pageTitle' => 'Start'] będą więc w szablonie dostępne jako $pageTitle. Flaga EXTR_SKIP nie pozwala nadpisać już istniejących zmiennych.

Nazwę szablonu wybiera kontroler, a nie użytkownik w adresie. Dzięki temu nie pozwalamy przeglądarce wskazać dowolnego pliku na serwerze.

Jeśli wywołamy metodę render(...) w poniższy sposób 

        $this->render('project', [
            'pageTitle' => 'Szczegóły projektu',
            'projectId' => $id,
        ]);
elementy tablicy asocjacyjnej przekazanej jako drugi argument zostaną w `AppController` udostępnione widokowi `project.html.php` jako zmienne. Klucze tablicy staną się nazwami zmiennych, a przypisane do nich wartości, wartościami tych zmiennych:

$pageTitle = 'Szczegóły projektu';
$projectId = $id;
Dzięki temu w pliku `project.html.php` możemy bezpośrednio korzystać ze zmiennych `$pageTitle` oraz `$projectId`.

Zmienne na widok przekazujemy zatem w wygodny sposób za pomocą listy/ tablic asocjacyjnej w metodzie render.


5. Utwórz kontrolery
DefaultController obsłuży stronę główną i widok błędu. Zwróć uwagę, że metoda render() nie znajduje się w tej klasie, tak jak zaplanowaliśmy w punkcie 4. jest odziedziczona z AppController.

<?php

declare(strict_types=1);

require_once __DIR__ . '/AppController.php';


final class DefaultController extends AppController
{
    public function index(): void
    {
        $this->render('home', [
            'pageTitle' => 'Moja aplikacja',
        ]);
    }

    public function notFound(): void
    {
        $this->render('not-found', [
            'pageTitle' => 'Nie znaleziono strony',
        ]);
    }
}
 

Teraz zróbmy przykład, w którym użytkownik chce wyświetlić konkretny produkt, czy details jakiegoś projektu. W takiej sytuacji będzie chciał w przeglądarce przekazać id danego elementu. Drugi kontroler ProjectController przyjmie więc identyfikator projektu wyjęty z adresu URL przekazanego w przeglądarce:

<?php

declare(strict_types=1);

require_once __DIR__ . '/AppController.php';

final class ProjectController extends AppController
{
    public function index(): void
    {
        $this->render('projects', [
            'pageTitle' => 'Projekty',
        ]);
    }

    public function show(int $id): void
    {
        $this->render('project', [
            'pageTitle' => 'Szczegóły projektu',
            'projectId' => $id,
        ]);
    }
}
Na tym etapie nie pobieramy jeszcze projektu z bazy, to zrobimy na przyszłych laboratoriach. Chcemy jedynie udowodnić, że liczba z adresu przeszła przez router i kontroler aż do widoku.

 

6. Zbuduj Routing.php
Router przechowuje listę dozwolonych tras. Dla każdej z nich zapamiętuje metodę HTTP, wzorzec adresu, klasę kontrolera i nazwę akcji.

<?php

declare(strict_types=1);

final class Routing
{
    private array $routes = [];

    public function get(string $path, string $controller, string $action): void
    {
        $this->add('GET', $path, $controller, $action);
    }

    public function post(string $path, string $controller, string $action): void
    {
        $this->add('POST', $path, $controller, $action);
    }

    private function add(
        string $method,
        string $path,
        string $controller,
        string $action,
    ): void {
        $this->routes[] = [
            'method' => $method,
            'pattern' => $this->createPattern($path),
            'controller' => $controller,
            'action' => $action,
        ];
    }

	/* 
	Funkcja dzieli ścieżkę na fragmenty. Zwykłe fragmenty pozostawia bez zmian, 
	a fragment zapisany w klamrach, na przykład {id}, zamienia na nazwany 
	parametr przyjmujący cyfry. Następnie ponownie składa ścieżkę w wyrażenie regularne.
	*/
    private function createPattern(string $path): string
    {
        $parts = explode('/', $path);

        foreach ($parts as &$part) {
            if (str_starts_with($part, '{') && str_ends_with($part, '}')) {
                $parameterName = trim($part, '{}');
                $part = '(?P<' . $parameterName . '>\d+)';
                continue;
            }

            $part = preg_quote($part, '~');
        }
        unset($part);

        return '~^' . implode('/', $parts) . '$~';
    }

    public function dispatch(string $method, string $uri): bool
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (!preg_match($route['pattern'], $path, $matches)) {
                continue;
            }

            $parameters = array_filter(
                $matches,
                static fn (int|string $key): bool => is_string($key),
                ARRAY_FILTER_USE_KEY,
            );

            $parameters = array_map(
                static fn (string $value): int|string =>
                    ctype_digit($value) ? (int) $value : $value,
                $parameters,
            );

            $controllerClass = $route['controller'];
            $controller = new $controllerClass();

            if (!$controller instanceof AppController) {
                throw new \RuntimeException('Invalid controller');
            }

            $controller->{$route['action']}(...$parameters);

            return true;
        }

        return false;
    }
}
dispatch() działa jak selektor: przechodzi po znanych trasach i kończy pracę po pierwszym dopasowaniu. Tablica tras jest naszą białą listą. Użytkownik nie podaje nazwy klasy ani metody kontrolera. Na razie będziemy korzystać z metody get(), czyli definiujemy trasy, które będą jedynie wyświetlały informacje. Przetwarzanie danych z formularzy metodą post() jest już przygotowane, ale wykorzystamy to na późniejszym laboratorium.

 

Jak router rozpoznaje {id}?
Dla ścieżki podanej w Routing.php

$routing->get('/projects/{id}', ProjectController::class, 'show');
router automatycznie tworzy wyrażenie regularne:

~^/projects/(?P<id>\d+)$~
^ i $ oznaczają początek i koniec całego adresu;
/projects/ jest stałą częścią ścieżki;
\d+ oznacza co najmniej jedną cyfrę;
(?P<id>...) nadaje dopasowanej wartości nazwę id;
~ jest separatorem wyrażenia regularnego w PHP.
Adres /projects/12 pasuje i przekazuje id = 12 do metody:

ProjectController::show(12);
Adres /projects/abc nie pasuje, więc zakończy się odpowiedzią 404.

 

7. Połącz elementy w public/index.php
Kontrolerów nie będziemy wczytywać w każdym pliku osobno. Punkt wejścia ładuje potrzebne klasy, rejestruje trasy i przekazuje routerowi bieżące żądanie.

<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/controllers/DefaultController.php';
require_once dirname(__DIR__) . '/src/controllers/ProjectController.php';
require_once dirname(__DIR__) . '/Routing.php';

$routing = new Routing();

// TU BĘDZIEMY DEFINIOWAĆ WSZYSTKIE TRASY WYSTĘPUJĄCE W NASZYM PROJEKCIE
$routing->get('/projects', ProjectController::class, 'index');
$routing->get('/projects/{id}', ProjectController::class, 'show');
$routing->get('/', DefaultController::class, 'index');

$routeFound = $routing->dispatch(
    $_SERVER['REQUEST_METHOD'],
    $_SERVER['REQUEST_URI'],
);

if (!$routeFound) {
    http_response_code(404);
    (new DefaultController())->notFound();
}
 

8. Utwórz semantyczny widok HTML
Plik widoku ma rozszerzenie .php, ponieważ w kilku miejscach wstawia dane otrzymane od kontrolera. Nadal jest to przede wszystkim dokument HTML.

W public/views/home.html.php przygotuj strukturę, którą ostylujesz na następnym laboratorium:

<!doctype html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
</head>
<body>
    <main>
        <h1>Strona główna</h1>
        <a href="/projects">Zobacz projekty</a>
    </main>
</body>
</html>
Na razie nie dodawaj <div> tylko po to, żeby „było łatwiej stylować”. Najpierw wybierz element zgodny ze znaczeniem treści: header, nav, main, section, article lub footer.

 

Widok strony dla wszystkich projektów
public/views/projects.html.php

<!doctype html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
</head>
<body>
    <main>
        <h1>Strona do prezentacji listy wszystkich projektów</h1>
        <!-- TODO: Lista projektów wyświetlona w pętli -->
    </main>
</body>
</html>
 

Widok z parametrem
W pliku public/views/project.html.php sprawdź, czy identyfikator dotarł do widoku:

<!doctype html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
</head>
<body>
    <main>
        <h1>Id przekazane przez URL, do kontrolera i na widok: <?= $projectId ?></h1>
    </main>
</body>
</html>
 

Widok not found 404
Utwórz również prosty public/views/not-found.html.php:

<!doctype html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
</head>
<body>
    <main>
        <h1>404</h1>
        <p>Taka strona nie istnieje.</p>
        <a href="/">Wróć na stronę główną</a>
    </main>
</body>
</html>
Zasada na przyszłość: dane wyświetlane w HTML zabezpieczaj przez htmlspecialchars(). Rzutowanie (int) wystarcza tutaj tylko dlatego, że projectId ma być liczbą.
 
9. Sprawdź konfigurację Nginx
Routing zadziała dla głębszych adresów tylko wtedy, gdy Nginx przekaże nieznaną ścieżkę do public/index.php, czyli naszego FRONT CONTROLLERA W konfiguracji z Laboratorium 1 powinien znajdować się ten fragment:

location / {
    try_files $uri $uri/ /index.php?$query_string;
}
Jeśli nie masz tego wpisu w /docker/nginx/nginx.conf dodaj go, a po zmianie konfiguracji przebuduj środowisko:

docker compose up --build -d
 
10. Wykonaj trzy krótkie testy
Adres	Oczekiwany wynik
http://localhost:8080/	widok home.html.php
http://localhost:8080/projects/12	nagłówek „Projekt nr 12”
http://localhost:8080/projects/abc	widok 404 i status HTTP 404
http://localhost:8080/projects	widok listy projektów
 

MILESTONES: Jeżeli dotarłeś do tego miejsca, masz już pierwszą małą aplikację opartą o przepływ MVC. Potrafisz:
✅ wskazać rolę punktu wejścia, routera, kontrolera i widoku,
✅ utworzyć kontroler dziedziczący po AppController,
✅ dopasować adres przez wyrażenie regularne i pobrać z niego id,
✅ przekazać dane z kontrolera do widoku przez render(),
✅ przygotować semantyczny HTML gotowy do ostylowania.

Na kolejnym laboratorium skupimy się na wyglądzie: zmiennych CSS, selektorach, box modelu, Flexboxie, Gridzie i responsywności.

---

## Część 2

MVC: namespace i autoloader Composera
29-09-2026 11:10
Od ręcznego ładowania plików do autoloadera
W pierwszej części zbudowaliśmy działający szkielet MVC. Każdy kontroler ładował klasę bazową przez require_once, a index.php ładował wszystkie kontrolery. Dla trzech klas było to czytelne. W większym projekcie szybko powstałaby jednak długa lista plików, o której trzeba pamiętać przy każdej nowej klasie.


 

Nie zmienimy dziś zachowania aplikacji ani jej adresów. Wykonamy refactoring: uporządkujemy klasy za pomocą przestrzeni nazw, a odnajdywanie plików przekażemy Composerowi.

Composer to menedżer zależności dla PHP, jednak na tym przedmiocie nie będziemy korzystać z zewnętrznych bibliotek. Wykorzystamy go przede wszystkim do automatycznego ładowania klas (autoloading), dzięki czemu zamiast ręcznego używania require_once będziemy mogli pracować z przestrzeniami nazw i use. Composer przyda nam się również później do uruchamiania testów jednostkowych.

Cel tej części: w kodzie aplikacji ma pozostać tylko jedno require_once — załadowanie pliku vendor/autoload.php w punkcie wejścia.

 
1. Zobacz problem, który rozwiązujemy
Wersja 1
public/index.php
    └── require_once Routing.php
    ├── require_once DefaultController.php
    ├── require_once ProjectController.php
    └── każdy kontroler wymaga AppController.php

Wersja 2
public/index.php
    └── require_once vendor/autoload.php
            └── Composer odnajduje potrzebne klasy
Autoloader nie tworzy obiektów i nie uruchamia metod. Jego zadanie jest mniejsze: gdy PHP po raz pierwszy spotka nazwę klasy, autoloader odnajduje odpowiadający jej plik i go wczytuje.

 

2. Dodaj Composera do kontenera PHP
Obraz PHP-FPM z Laboratorium 1 nie zawiera jeszcze Composera. W pliku docker/php/Dockerfile skopiuj jego gotowy plik wykonywalny z oficjalnego obrazu:

FROM php:8.3-fpm-alpine

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
Przebuduj kontener i sprawdź instalację:

docker compose up --build -d
docker compose exec php composer --version

 
3. Opisz klasy w composer.json
W głównym katalogu projektu utwórz plik composer.json. Sekcja autoload opisuje relację między początkiem namespace a katalogiem na dysku.

{
  "autoload": {
    "psr-4": {
      "App\\Controller\\": "src/controllers/",
      "App\\": "src/"
    }
  }
}
Regułę czytamy następująco:

klasy zaczynające się od App\Controller\ znajdują się w src/controllers/;
pozostałe klasy zaczynające się od App\ znajdują się w src/;
bardziej szczegółowa reguła dla kontrolerów ma pierwszeństwo.
Dlaczego w JSON są dwa ukośniki? Wartość App\\Controller\\ w pliku JSON oznacza tekst App\Controller\. Pierwszy ukośnik zabezpiecza drugi.
 
4. Przenieś Routing.php do src
Reguła "App\\": "src/" oznacza, że klasa App\Routing powinna znajdować się w pliku src/Routing.php. Przenieś istniejący plik:

mv Routing.php src/Routing.php
Po refactoringu interesująca nas część projektu wygląda tak:

my-app/
├── composer.json
├── public/
│   ├── index.php
│   └── views/
└── src/
    ├── Routing.php
    └── controllers/
        ├── AppController.php
        ├── DefaultController.php
        └── ProjectController.php
 
5. Nadaj klasom jednoznaczne adresy
namespace jest adresem klasy w kodzie. Pełna nazwa klasy bazowej będzie teraz brzmiała App\Controller\AppController. To podobna rola do package w Javie.

Na początku src/controllers/AppController.php, bezpośrednio po declare, dodaj:

<?php

declare(strict_types=1);

namespace App\Controller;

abstract class AppController
{
    // Metoda render() pozostaje bez zmian.
}
Taką samą deklarację dodaj do DefaultController.php i ProjectController.php. Usuń z nich instrukcję require_once:

<?php

declare(strict_types=1);

namespace App\Controller;

final class ProjectController extends AppController
{
    public function show(int $id): void
    {
        $this->render('project', [
            'pageTitle' => 'Szczegóły projektu',
            'projectId' => $id,
        ]);
    }
}
Obie klasy znajdują się w tym samym namespace, dlatego ProjectController może dziedziczyć po AppController bez podawania jego pełnej nazwy.

 
6. Uporządkuj klasę Routing
W src/Routing.php zadeklaruj namespace App. Router korzysta z klasy bazowej kontrolerów, która znajduje się pod innym adresem, dlatego wprowadzamy ją przez use.

<?php

declare(strict_types=1);

namespace App;

use App\Controller\AppController;

final class Routing
{
    // Właściwość $routes oraz metody get(), post(), add()
    // i dispatch() pozostają takie jak w części 1.
}
 

7. Zrozum różnicę między namespace, use i autoloadingiem
Mechanizm	Odpowiedzialność	Skojarzenie z Javą
namespace	nadaje klasie pełny, jednoznaczny adres	package
use	pozwala używać krótszej nazwy w bieżącym pliku	import
autoloading	odnajduje plik zawierający klasę	automatyczne ładowanie klas z classpath
Zapamiętaj: use nie wczytuje pliku. To tylko alias nazwy dostępny w bieżącym pliku. Za odnalezienie i wczytanie pliku odpowiada autoloader Composera.
 
8. Wygeneruj autoloader
Po utworzeniu lub zmianie sekcji autoload poproś Composera o przebudowanie mapy klas:

docker compose exec php composer dump-autoload

 

Composer utworzy katalog vendor/ i plik vendor/autoload.php. Katalogu vendor/ nie edytujemy ręcznie i zazwyczaj nie zapisujemy w repozytorium. Dodaj go do .gitignore:

/vendor/
 
9. Uprość public/index.php
Punkt wejścia wczytuje teraz tylko autoloader. Instrukcje use pozwalają korzystać z krótkich nazw klas.

<?php

declare(strict_types=1);

use App\Controller\DefaultController;
use App\Controller\ProjectController;
use App\Routing;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$routing = new Routing();

$routing->get('/projects', ProjectController::class, 'index');
$routing->get('/projects/{id}', ProjectController::class, 'show');
$routing->get('/', DefaultController::class, 'index');

$routeFound = $routing->dispatch(
    $_SERVER['REQUEST_METHOD'],
    $_SERVER['REQUEST_URI'],
);

if (!$routeFound) {
    http_response_code(404);
    (new DefaultController())->notFound();
}
Adresy i widoki pozostały bez zmian. Zmieniliśmy organizację kodu, nie funkcję aplikacji. To dobry przykład refactoringu.

 
10. Sprawdź rezultat
Odśwież trzy adresy znane z pierwszej części:

http://localhost:8080/ — strona główna;
http://localhost:8080/projects/12 — widok projektu;
http://localhost:8080/projects/abc — odpowiedź 404.
Jeżeli widoki działają tak samo jak wcześniej, refactoring zakończył się powodzeniem.

 
Najczęstsze problemy
Komunikat lub objaw	Co sprawdzić?
Class "App\..." not found	namespace klasy, regułę PSR-4 i nazwę pliku
brak vendor/autoload.php	uruchom composer dump-autoload
działa lokalnie, nie działa w kontenerze	wielkość liter w nazwie katalogu i pliku
nowa klasa nie jest odnajdywana	czy jej namespace odpowiada katalogowi wskazanemu w composer.json
 
MILESTONES Jeżeli aplikacja po refactoringu nadal obsługuje wszystkie adresy, potrafisz:
✅ wyjaśnić, dlaczego ręczna lista require_once nie skaluje się wraz z projektem,
✅ porządkować klasy za pomocą namespace,
✅ skracać pełne nazwy klas za pomocą use,
✅ powiązać namespace z katalogiem przez reguły PSR-4,
✅ wygenerować i podłączyć autoloader Composera,
✅ dodać nową klasę bez ręcznego wczytywania jej pliku.

Szkielet aplikacji jest gotowy. Masz routing, kontrolery, renderowanie widoków i automatyczne ładowanie klas. Teraz możesz wrócić do warstwy widoku i na Laboratorium 3 nadać jej wygląd za pomocą CSS.

---

## ZADANIA

ZADANIE 1. Utwórz prototyp projektu aplikacji, który będziesz realizował w ramach przedmiotu. Wymagane jest minimum 5 widoków mobilny, oraz 5 widoków webowych. Projekt widoków/ screeny zapisz w PDF i zgłoś do kolejnych zajęć.

ZADANIE 2.
1. Uruchom stronę główną przez DefaultController.
2. Dodaj co najmniej jeden własny kontroler dziedziczący po AppController.
3. Dodaj trasę ze zwykłym adresem i trasę z parametrem.
4. Przekaż przynajmniej dwie wartości z kontrolera do widoku i wyświetl w HTMLu.
5. Zbuduj semantyczny HTML dla najważniejszego ekranu z Twojego prototypu.
6. Sprawdź poprawną odpowiedź 404 dla nieobsługiwanego adresu.

ZADANIE 3. Utwórz szablony HTML, które będą odpowiadały widokom Twojego projektu z zadania 1. Zmiany wyślij na github.

ZADANIE 4*. Dodaj Composer do obrazu PHP i wygeneruj autoloader. Usuń wszystkie ręczne require_once poza załadowaniem vendor/autoload.php. Nadaj namespace klasie routera i wszystkim kontrolerom.
