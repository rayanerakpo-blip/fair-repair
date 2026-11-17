<?php
require_once __DIR__ . '/vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/vendor/phpmailer/src/SMTP.php';
require_once __DIR__ . '/vendor/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function envoyerMail(string $to, string $sujet, string $html, string $nomDestinataire = ''): bool
{
    require_once __DIR__ . '/mail_config.php';

    $mail = new PHPMailer (true);

    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->Port       = MAIL_PORT;

        if (!empty(MAIL_USER)) {
            $mail->SMTPAuth = true;
            $mail->Username = MAIL_USER;
            $mail->Password = MAIL_PASS;
        }

        if (!empty(MAIL_ENCRYPTION)) {
            $mail->SMTPSecure = MAIL_ENCRYPTION;
        }

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to, $nomDestinataire);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';

        $mail->Subject = $sujet;
        $mail->Body    = $html;
        $mail->AltBody = strip_tags($html);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erreur envoi mail à $to : " . $mail->ErrorInfo);
        return false;
    }
}

function mailTemplate(string $titre, string $contenu, string $lienTexte = '', string $lienUrl = ''): string
{
    $bouton = '';
    if ($lienTexte && $lienUrl) {
        $bouton = '<a href="' . htmlspecialchars($lienUrl) . '" style="display:inline-block;background:#0077b6;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:bold;margin-top:16px;">' . htmlspecialchars($lienTexte) . '</a>';
    }

    return '
    <!DOCTYPE html>
    <html lang="fr">
    <head><meta charset="UTF-8"></head>
    <body style="margin:0;padding:0;background:#f4f6f8;font-family:Arial,Helvetica,sans-serif;">
        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;padding:40px 0;">
            <tr><td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">
                    <tr style="background:#0077b6;">
                        <td style="padding:24px 32px;color:#ffffff;font-size:20px;font-weight:bold;">
                            🚴 Fair\'repair
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <h2 style="margin:0 0 16px 0;color:#023047;font-size:22px;">' . htmlspecialchars($titre) . '</h2>
                            <div style="color:#333;font-size:15px;line-height:1.6;">' . $contenu . '</div>
                            ' . $bouton . '
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 32px;background:#f8f9fa;color:#888;font-size:12px;text-align:center;">
                            Fair\'repair — Réparation de vélos solidaire
                        </td>
                    </tr>
                </table>
            </td></tr>
        </table>
    </body>
    </html>';
}