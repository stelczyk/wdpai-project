<?php

declare(strict_types=1);

namespace App\Service;

use App\Env;
use App\Mailer;
use App\Repository\ReminderRepository;
use App\Translator;
use PHPMailer\PHPMailer\Exception as MailerException;

/**
 * Przypomnienia dla uczestników bez treningu od 48 h (cron: bin/send-reminders.php).
 * Każda wysyłka trafia do reminder_log: SENT pomijamy przy kolejnym uruchomieniu, FAILED ponawiamy.
 */
final class ReminderService
{
    public const INACTIVE_HOURS = 48;
    private const ERROR_MAX_LENGTH = 500;   // = VARCHAR(500) w reminder_log

    public function __construct(
        private readonly ReminderRepository $reminders = new ReminderRepository(),
        private readonly Mailer $mailer = new Mailer(),
    ) {
    }

    /**
     * @return array{locked: bool, checked: int, sent: int, failed: int, recipients: list<string>}
     *         locked = inny proces właśnie wysyła przypomnienia – nic nie zrobiliśmy
     */
    public function run(bool $dryRun = false): array
    {
        $summary = ['locked' => false, 'checked' => 0, 'sent' => 0, 'failed' => 0, 'recipients' => []];
        if (!$this->reminders->tryLock()) {
            return ['locked' => true] + $summary;
        }

        try {
            foreach ($this->reminders->findInactiveMembers(self::INACTIVE_HOURS) as $member) {
                $summary['checked']++;
                $summary['recipients'][] = $member['email'] . ' (' . $member['expedition_name'] . ')';
                if ($dryRun) {
                    continue;
                }

                // Błąd SMTP dla jednej osoby nie zatrzymuje reszty – zapisujemy go i lecimy dalej.
                try {
                    $this->mailer->send($member['email'], $member['name'], ...$this->message($member));
                    $this->reminders->log((int) $member['user_id'], (int) $member['expedition_id'], 'SENT', null);
                    $summary['sent']++;
                } catch (MailerException $exception) {
                    $error = mb_substr($exception->getMessage(), 0, self::ERROR_MAX_LENGTH);
                    $this->reminders->log((int) $member['user_id'], (int) $member['expedition_id'], 'FAILED', $error);
                    $summary['failed']++;
                }
            }
        } finally {
            $this->reminders->unlock();
        }

        return $summary;
    }

    /** @return array{0: string, 1: string} temat i treść (zwykły tekst) */
    private function message(array $member): array
    {
        $approved = (float) $member['approved_km'];
        $next = $member['next_name'] === null ? '' : Translator::get('reminder.next', [
            'name' => $member['next_name'],
            'km' => Translator::number((float) $member['next_km'] - $approved),
        ]);

        $body = implode("\n\n", [
            // Zwracamy się imieniem – pierwszy człon nazwy konta.
            Translator::get('reminder.greeting', ['name' => explode(' ', trim($member['name']))[0]]),
            Translator::get('reminder.body', [
                'hours' => self::INACTIVE_HOURS,
                'expedition' => $member['expedition_name'],
                'approved' => Translator::number($approved),
                'total' => Translator::number((float) $member['total_km'], 0),
            ]) . $next,
            Translator::get('reminder.cta', [
                'url' => rtrim(Env::get('APP_URL'), '/') . '/activities/new?expedition=' . $member['expedition_id'],
            ]),
            Translator::get('reminder.footer'),
        ]);

        return [Translator::get('reminder.subject', ['expedition' => $member['expedition_name']]), $body];
    }
}
