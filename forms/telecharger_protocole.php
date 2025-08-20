<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$filename = $_GET['file'] ?? '';
$filepath = realpath('../uploads/protocoles/' . $filename);

if ($filepath && file_exists($filepath)) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
    readfile($filepath);
    exit();
} else {
    echo "Fichier introuvable.";
}
?>
