<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php'; // Assure-toi que PHPMailer est installé via Composer

function envoyerEmail($destinataire, $sujet, $corps, $journaliser = false, $id_protocole = null, $conn = null) {
    $mail = new PHPMailer(true);

    try {
        // Configuration SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'jorisbere@gmail.com'; // à remplacer
        $mail->Password = 'hbycyntmjwtvicct'; // à remplacer
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Expéditeur et destinataire
        $mail->setFrom('jorisbere@gmail.com', 'Gestion Mémoire');
        $mail->addAddress($destinataire);

        // Contenu
        $mail->isHTML(true);
        $mail->Subject = $sujet;
        $mail->Body    = $corps;

        $mail->send();

        $statut = $mail->send() ? 'envoye' : 'echec';

        if ($journaliser && $conn && $id_protocole) {
            $stmt = $conn->prepare("INSERT INTO journal_emails (protocole_id, destinataire, sujet, statut, date_envoi) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("isss", $id_protocole, $destinataire, $sujet, $statut);
            $stmt->execute();
        }


        return true;
    } catch (Exception $e) {
        return false;
    }
}
