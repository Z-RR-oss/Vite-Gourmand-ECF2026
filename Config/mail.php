<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';


// Charger les identifiants SMTP locaux
$fichierLocal =
    __DIR__ . '/mail.local.php';

if (file_exists($fichierLocal)) {
    require_once $fichierLocal;
}


/**
 * Envoyer un email avec PHPMailer.
 */
function envoyerEmail(
    string $emailDestinataire,
    string $nomDestinataire,
    string $sujet,
    string $contenuHtml
): bool {

    $constantesRequises = [
        'SMTP_HOST',
        'SMTP_PORT',
        'SMTP_USERNAME',
        'SMTP_PASSWORD',
        'SMTP_FROM_EMAIL',
        'SMTP_FROM_NAME'
    ];


    foreach ($constantesRequises as $constante) {

        if (!defined($constante)) {

            throw new RuntimeException(
                "Configuration email manquante : "
                . $constante
            );
        }
    }


    $mail = new PHPMailer(true);


    try {

        $mail->isSMTP();

        $mail->Host =
            SMTP_HOST;

        $mail->SMTPAuth =
            true;

        $mail->Username =
            SMTP_USERNAME;

        $mail->Password =
            SMTP_PASSWORD;

        $mail->Port =
            SMTP_PORT;


        /*
         * Port 465 :
         * chiffrement implicite SMTPS.
         *
         * Autres ports courants comme 587 :
         * STARTTLS.
         */
        if ((int) SMTP_PORT === 465) {

            $mail->SMTPSecure =
                PHPMailer::ENCRYPTION_SMTPS;

        } else {

            $mail->SMTPSecure =
                PHPMailer::ENCRYPTION_STARTTLS;
        }


        $mail->CharSet = 'UTF-8';


        $mail->setFrom(
            SMTP_FROM_EMAIL,
            SMTP_FROM_NAME
        );


        $mail->addAddress(
            $emailDestinataire,
            $nomDestinataire
        );


        $mail->isHTML(true);

        $mail->Subject =
            $sujet;

        $mail->Body =
            $contenuHtml;


        $mail->AltBody =
            strip_tags(
                str_replace(
                    ['<br>', '<br/>', '<br />'],
                    PHP_EOL,
                    $contenuHtml
                )
            );


        $mail->send();

        return true;


    } catch (Exception $e) {

        error_log(
            "Erreur envoi email : "
            . $mail->ErrorInfo
        );

        return false;
    }
}