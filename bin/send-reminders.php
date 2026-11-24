<?php

declare(strict_types=1);

// Cron: przypomnienia dla uczestników bez treningu od 48 h (zadanie 705).
// Uruchamia go serwis „cron” z docker/cron/crontab (codziennie 18:00); ręcznie:
//   docker compose exec php php bin/send-reminders.php            – wysyłka
//   docker compose exec php php bin/send-reminders.php --dry-run  – tylko lista odbiorców, bez maili
// Plik leży poza public/, więc nie da się go wywołać z przeglądarki.

use App\Service\ReminderService;
use App\Translator;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Konta nie mają zapisanego języka – maile idą w domyślnym języku aplikacji.
Translator::setLocale('pl');
$dryRun = in_array('--dry-run', $argv, true);
$started = date('Y-m-d H:i:s');

try {
    $result = (new ReminderService())->run($dryRun);
} catch (Throwable $exception) {
    // Np. baza niedostępna – wpis w logu crona i kod błędu, żeby było widać, że przebieg się nie udał.
    fwrite(STDERR, "[$started] reminders: ERROR " . $exception->getMessage() . PHP_EOL);
    exit(1);
}

if ($result['locked']) {
    echo "[$started] reminders: another run is in progress – skipped" . PHP_EOL;
    exit(0);
}

foreach ($result['recipients'] as $recipient) {
    echo '  ' . ($dryRun ? 'would remind: ' : 'reminded: ') . $recipient . PHP_EOL;
}
printf(
    "[%s] reminders%s: checked %d, sent %d, failed %d%s",
    $started,
    $dryRun ? ' (dry run)' : '',
    $result['checked'],
    $result['sent'],
    $result['failed'],
    PHP_EOL,
);

// Kod ≠ 0, gdy choć jedna wysyłka się nie udała – cron/monitoring może to wychwycić; kolejny przebieg ją ponowi.
exit($result['failed'] > 0 ? 2 : 0);
