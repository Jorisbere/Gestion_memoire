<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

function envoyerEmail($destinataire, $sujet, $corps, $estHtml = false, $id_protocole = null, $conn = null) {
    $mail = new PHPMailer(true);

    try {
        $mail->SMTPDebug = 0;
        $mail->Debugoutput = 'error_log';

        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['GMAIL_USERNAME'] ?? 'jorisbere@gmail.com';
        $mail->Password   = $_ENV['GMAIL_APP_PASSWORD'] ?? 'hbycyntmjwtvicct';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom($mail->Username, 'Gestion Mémoire');
        $mail->addAddress($destinataire);
        $mail->addReplyTo('noreply@gestionmemoire.local', 'Ne pas répondre');

        $mail->CharSet = 'UTF-8';
        $mail->isHTML($estHtml);
        $mail->Subject = $sujet;
        $mail->Body    = $corps;

        $mail->addCustomHeader('X-GestionMemoire-ID', uniqid("GM_", true));

        $result = $mail->send();

        // Journalisation si connexion fournie
        if ($conn && $result) {
            $log = $conn->prepare("INSERT INTO journal_emails (destinataire, sujet, statut, date_envoi, id_protocole) VALUES (?, ?, 'envoyé', NOW(), ?)");
            $log->bind_param("ssi", $destinataire, $sujet, $id_protocole);
            $log->execute();
        }

        return $result;
    } catch (Exception $e) {
        error_log("Erreur d'envoi à $destinataire : {$mail->ErrorInfo}");

        if ($conn) {
            $log = $conn->prepare("INSERT INTO journal_emails (destinataire, sujet, statut, date_envoi, id_protocole) VALUES (?, ?, 'échec', NOW(), ?)");
            $log->bind_param("ssi", $destinataire, $sujet, $id_protocole);
            $log->execute();
        }

        return false;
    }
}
