<?php
/**
 * Moduł wysyłania emailów - PHPMailer + Mailtrap
 * 
 * Funkcje:
 * - sendVerificationEmail($to, $username, $token) - Weryfikacja e-mail po rejestracji
 * - sendPasswordReset($to, $username, $token) - Reset hasła
 * - sendNotification($to, $subject, $message) - Powiadomienia ogólne
 */

require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Tworzy skonfigurowaną instancję PHPMailer
 */
function getMailer() {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'] ?? 'sandbox.smtp.mailtrap.io';
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USER'] ?? '';
        $mail->Password = $_ENV['SMTP_PASS'] ?? '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $_ENV['SMTP_PORT'] ?? 2525;
        $mail->CharSet = 'UTF-8';
        
        $mail->setFrom(
            $_ENV['SMTP_FROM_EMAIL'] ?? 'noreply@pbf.local',
            $_ENV['SMTP_FROM_NAME'] ?? 'PBF System'
        );
        
        return $mail;
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return null;
    }
}

/**
 * Wysyła email weryfikacyjny po rejestracji
 */
function sendVerificationEmail($to, $username, $token) {
    $mail = getMailer();
    if (!$mail) return false;
    
    try {
        $mail->addAddress($to, $username);
        $mail->isHTML(true);
        $mail->Subject = 'Weryfikacja konta - PBF System';
        
        $appUrl = $_ENV['APP_URL'] ?? 'http://localhost:8000';
        $verifyUrl = "$appUrl?action=verify&token=$token";
        
        $mail->Body = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2>Witaj, $username!</h2>
            <p>Dziękujemy za rejestrację w systemie PBF.</p>
            <p>Kliknij poniższy link, aby zweryfikować swój adres e-mail:</p>
            <p><a href='$verifyUrl' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Zweryfikuj e-mail</a></p>
            <p>Lub skopiuj link: <br><code>$verifyUrl</code></p>
            <hr>
            <p><small>Jeśli to nie Ty, zignoruj tę wiadomość.</small></p>
        </body>
        </html>
        ";
        
        $mail->AltBody = "Witaj $username! Zweryfikuj e-mail: $verifyUrl";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Wysyła email z linkiem do resetowania hasła
 */
function sendPasswordReset($to, $username, $token) {
    $mail = getMailer();
    if (!$mail) return false;
    
    try {
        $mail->addAddress($to, $username);
        $mail->isHTML(true);
        $mail->Subject = 'Reset hasła - PBF System';
        
        $appUrl = $_ENV['APP_URL'] ?? 'http://localhost:8000';
        $resetUrl = "$appUrl?action=reset_password&token=$token";
        
        $mail->Body = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2>Reset hasła</h2>
            <p>Witaj, $username!</p>
            <p>Otrzymaliśmy prośbę o reset hasła do twojego konta.</p>
            <p>Kliknij poniższy link, aby ustawić nowe hasło:</p>
            <p><a href='$resetUrl' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Zresetuj hasło</a></p>
            <p>Link ważny przez 1 godzinę.</p>
            <hr>
            <p><small>Jeśli to nie Ty, zignoruj tę wiadomość.</small></p>
        </body>
        </html>
        ";
        
        $mail->AltBody = "Reset hasła: $resetUrl (ważny 1h)";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Wysyła powiadomienie ogólne
 */
function sendNotification($to, $subject, $message, $username = '') {
    $mail = getMailer();
    if (!$mail) return false;
    
    try {
        $mail->addAddress($to, $username);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        
        $mail->Body = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2>" . htmlspecialchars($subject) . "</h2>
            <div>$message</div>
            <hr>
            <p><small>Powiadomienie z systemu PBF</small></p>
        </body>
        </html>
        ";
        
        $mail->AltBody = strip_tags($message);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail Error: {$mail->ErrorInfo}");
        return false;
    }
}
