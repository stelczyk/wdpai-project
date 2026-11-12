<?php

declare(strict_types=1);

return [
    // Wspólne: header, footer, motyw, język
    'footer.brand' => 'Dystans Virtual Expeditions',
    'footer.copyright' => 'Wszystkie prawa zastrzeżone © {year}',
    'footer.terms' => 'Regulamin wypraw',
    'footer.privacy' => 'Polityka prywatności',
    'footer.support' => 'Wsparcie techniczne',
    'nav.label' => 'Menu główne',
    'nav.expeditions' => 'Wyprawy',
    'nav.add_activity' => 'Dodaj aktywność',
    'nav.login' => 'Zaloguj',
    'nav.logout' => 'Wyloguj',
    'theme.toggle' => 'Przełącz motyw',
    'theme.to_light' => 'Włącz jasny motyw',
    'theme.to_dark' => 'Włącz ciemny motyw',
    'lang.label' => 'Język',
    'lang.pl' => 'Polski',
    'lang.en' => 'English',

    // Strona główna
    'home.title' => 'Start',
    'home.tagline' => 'Wirtualne wyprawy ze znajomymi: każdy trening to kilometry na wspólnej trasie.',
    'home.cta' => 'Zobacz wyprawy',

    // Wyprawy
    'expeditions.title' => 'Wyprawy',
    'expedition.title' => 'Dashboard wyprawy',
    'expedition.heading' => 'Wyprawa: {name}',
    'expedition.progress' => 'Postęp',
    'expedition.nav_label' => 'Wyprawa nr {id}',
    'expedition.ranking' => 'Ranking',
    'expedition.captain_panel' => 'Panel kapitana',

    // Ranking
    'ranking.title' => 'Ranking wyprawy',
    'ranking.heading' => 'Ranking wyprawy nr {id}',
    'ranking.position' => 'Miejsce',
    'ranking.participant' => 'Uczestnik',
    'ranking.km' => 'Km',
    'ranking.back' => 'Wróć do wyprawy',

    // Panel kapitana
    'captain.title' => 'Panel kapitana',
    'captain.heading' => 'Panel kapitana – wyprawa nr {id}',
    'captain.pending' => 'Aktywności do zatwierdzenia',

    // Dodaj aktywność
    'activity.title' => 'Dodaj aktywność',
    'activity.type' => 'Rodzaj',
    'activity.distance' => 'Dystans (km)',
    'activity.date' => 'Data',
    'activity.photo' => 'Zdjęcie',
    'activity.submit' => 'Zapisz',

    // Logowanie i rejestracja – wspólne
    'auth.email' => 'Adres e-mail',
    'auth.email_placeholder' => 'jan.kowalski@dystans.app',
    'auth.password' => 'Hasło',
    'auth.show_password' => 'Pokaż hasło',
    'auth.hide_password' => 'Ukryj hasło',
    'auth.close_alert' => 'Zamknij powiadomienie',

    // Logowanie
    'login.title' => 'Logowanie',
    'login.heading' => 'Zaloguj się do wyprawy',
    'login.lead' => 'Wprowadź swoje dane telemetryczne, aby kontynuować wspólną trasę.',
    'login.error_title' => 'Błąd autoryzacji wyprawy',
    'login.email_hint' => 'Połączone z kontem ekspedycyjnym',
    'login.forgot' => 'Nie pamiętasz hasła?',
    'login.remember' => 'Zapamiętaj mnie na tym urządzeniu',
    'login.submit' => 'Zaloguj się',
    'login.or' => 'Lub',
    'login.google' => 'Kontynuuj z Google',
    'login.strava' => 'Kontynuuj ze Strava',
    'login.no_account' => 'Nie masz jeszcze profilu wyprawy?',
    'login.register_link' => 'Utwórz nowe konto drużynowe',
    'login.invalid_credentials' => 'Nieprawidłowy e-mail lub hasło.',

    // Walidacja formularzy
    'validation.required' => 'To pole jest wymagane.',
    'validation.email' => 'Podaj poprawny adres e-mail.',
    'validation.max_length' => 'Maksymalnie {max} znaków.',
    'validation.min_length' => 'Minimum {min} znaków.',
    'validation.email_taken' => 'Konto z tym adresem e-mail już istnieje.',
    'validation.password_mismatch' => 'Hasła nie są identyczne.',
    'validation.terms' => 'Musisz zaakceptować regulamin.',

    // Rejestracja
    'register.title' => 'Rejestracja',
    'register.heading' => 'Dołącz do wyprawy',
    'register.lead' => 'Załóż profil uczestnika i zacznij zamieniać treningi na kilometry trasy.',
    'register.error_title' => 'Nie udało się utworzyć konta',
    'register.name' => 'Imię lub pseudonim',
    'register.name_placeholder' => 'Jan Kowalski',
    'register.name_hint' => 'Tak zobaczą Cię inni w rankingu',
    'register.password_hint' => 'Minimum 8 znaków',
    'register.password_confirm' => 'Powtórz hasło',
    'register.terms' => 'Akceptuję regulamin wypraw',
    'register.submit' => 'Utwórz konto',
    'register.has_account' => 'Masz już profil wyprawy?',
    'register.login_link' => 'Zaloguj się',

    // Strony błędów
    'error.back' => 'Wróć na stronę główną',
    'error.400.title' => 'Nieprawidłowe żądanie',
    'error.400.message' => 'Adres zawiera nieprawidłowe dane, np. błędny identyfikator.',
    'error.401.title' => 'Wymagane logowanie',
    'error.401.message' => 'Zaloguj się, aby zobaczyć tę stronę.',
    'error.401.action' => 'Zaloguj się',
    'error.403.title' => 'Brak dostępu',
    'error.403.message' => 'Nie masz uprawnień do tej strony.',
    'error.404.title' => 'Nie znaleziono strony',
    'error.404.message' => 'Taka strona nie istnieje albo została usunięta.',
    'error.500.title' => 'Błąd serwera',
    'error.500.message' => 'Coś poszło nie tak po naszej stronie. Spróbuj ponownie za chwilę.',
];
