<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    // 🔍 Debug SMTP
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = 'html';

    // 📡 Configuration SMTP Gmail
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'jorisbere@gmail.com'; // Remplace par ton adresse Gmail
    $mail->Password   = 'hbycyntmjwtvicct';     // Mot de passe d'application Gmail
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // ✉️ Expéditeur et destinataire
    $mail->setFrom('jorisbere@gmail.com', 'Test SMTP');
    $mail->addAddress('jorisbere5@gmail.com', 'Destinataire');

    // 📄 Contenu
    $mail->isHTML(true);
    $mail->Subject = 'Test SMTP PHPMailer';
    $mail->Body    = '<b>Ce message confirme que la configuration SMTP fonctionne !</b>';

    $mail->send();
    echo '✅ Email envoyé avec succès.';
} catch (Exception $e) {
    echo "❌ Échec de l'envoi : {$mail->ErrorInfo}";
}
