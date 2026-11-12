<?php

declare(strict_types=1);

namespace App;

use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\PHPMailer;

/** Wysyłka maili przez SMTP (PHPMailer). Dane serwera wyłącznie z .env. */
final class Mailer
{
    /** @throws MailerException gdy serwer SMTP odrzuci wiadomość lub jest niedostępny */
    public function send(string $toEmail, string $toName, string $subject, string $body): void
    {
        $mail = new PHPMailer(true);   // true = błędy jako wyjątki zamiast cichego false
        $mail->isSMTP();
        $mail->Host = Env::get('SMTP_HOST');
        $mail->Port = (int) Env::get('SMTP_PORT');
        $mail->CharSet = PHPMailer::CHARSET_UTF8;

        // Lokalny Mailpit nie wymaga logowania; produkcyjny serwer – tak.
        $user = Env::optional('SMTP_USER');
        if ($user !== '') {
            $mail->SMTPAuth = true;
            $mail->Username = $user;
            $mail->Password = Env::optional('SMTP_PASSWORD');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->setFrom(Env::get('MAIL_FROM'), Env::optional('MAIL_FROM_NAME'));
        $mail->addAddress($toEmail, $toName);
        $mail->Subject = $subject;
        $mail->Body = $body;   // zwykły tekst – link do kliknięcia, bez HTML do zabezpieczania

        $mail->send();
    }
}
