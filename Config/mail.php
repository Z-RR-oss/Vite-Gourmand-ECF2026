<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';
if (is_file(__DIR__ . '/mail.local.php')) {
    require_once __DIR__ . '/mail.local.php';
}

function mailSetting(string $key, mixed $default = ''): mixed
{
    $environment = getenv($key);
    return $environment !== false ? $environment : (defined($key) ? constant($key) : $default);
}

// URL canonique, jamais dérivée de l'en-tête Host fourni par le navigateur.
function applicationUrl(): string
{
    return rtrim((string) mailSetting('APP_URL', 'http://vite-gourmand.local'), '/');
}

function envoyerEmail(
    string $emailDestinataire,
    string $nomDestinataire,
    string $sujet,
    string $contenuHtml,
    ?string $replyTo = null
): bool {
    try {
        $host = (string) mailSetting('SMTP_HOST');
        $from = (string) mailSetting('SMTP_FROM_EMAIL');
        if ($host === '' || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Configuration SMTP absente.');
        }
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = (int) mailSetting('SMTP_PORT', 587);
        $mail->SMTPAuth = filter_var(mailSetting('SMTP_AUTH', true), FILTER_VALIDATE_BOOLEAN);
        $mail->Username = (string) mailSetting('SMTP_USERNAME');
        $mail->Password = (string) mailSetting('SMTP_PASSWORD');
        $encryption = (string) mailSetting('SMTP_ENCRYPTION', $mail->Port === 465 ? 'ssl' : 'tls');
        // Le transport sans TLS est réservé aux boîtes de capture sur loopback.
        if ($encryption === 'none' && in_array($host, ['127.0.0.1', 'localhost', '::1'], true)) {
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false;
        } else {
            $mail->SMTPSecure = $encryption === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        }
        $mail->Timeout = 10;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($from, (string) mailSetting('SMTP_FROM_NAME', 'Vite & Gourmand'));
        $mail->addAddress($emailDestinataire, $nomDestinataire);
        if ($replyTo !== null) {
            $mail->addReplyTo($replyTo);
        }
        $mail->isHTML(true);
        $mail->Subject = $sujet;
        $mail->Body = $contenuHtml;
        $mail->AltBody = html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $contenuHtml)), ENT_QUOTES, 'UTF-8');
        return $mail->send();
    } catch (Throwable $exception) {
        // Ne pas journaliser adresses, corps, jetons ou identifiants de connexion.
        error_log('Envoi email échoué (' . get_class($exception) . '). Vérifier le transport SMTP.');
        return false;
    }
}
