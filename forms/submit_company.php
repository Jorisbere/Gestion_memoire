<?php
session_start();
require_once '../config/db.php';
require_once '../includes/calculate_tax.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id      = $_SESSION['user_id'];
$declarant_id = $_POST['declarant_id'] ?? null;
$fullname     = trim($_POST['fullname']);
$email        = trim($_POST['email']);
$fiscal_id    = trim($_POST['fiscal_id']);
$year         = (int) $_POST['year'];
$income       = (float) $_POST['income'];
$expenses     = (float) ($_POST['expenses'] ?? 0.00);
$employees    = (int) ($_POST['employees'] ?? 0);
$profile      = 'company';

$tax_due = calculateTax($profile, $income, $expenses, 0, $employees);

try {
    // Enregistrement dans la base
    $stmt = $pdo->prepare("
        INSERT INTO declarations (
            user_id, declarant_id, year, income, dependents, expenses, tax_due,
            profile, fullname, email, fiscal_id
        ) VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user_id, $declarant_id, $year, $income, $expenses, $tax_due,
        $profile, $fullname, $email, $fiscal_id
    ]);

    $declaration_id = $pdo->lastInsertId();

    // Génération du PDF
    require_once __DIR__ . '/../lib/fpdf.php';
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Declaration fiscale - Entreprise', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 12);
    $pdf->Ln(5);
    $pdf->Cell(0, 10, "Nom : $fullname", 0, 1);
    $pdf->Cell(0, 10, "Email : $email", 0, 1);
    $pdf->Cell(0, 10, "Numero fiscal : $fiscal_id", 0, 1);
    $pdf->Ln(5);
    $pdf->Cell(0, 10, "Annee : $year", 0, 1);
    $pdf->Cell(0, 10, "Revenu : " . number_format($income, 0, ',', ' ') . " FCFA", 0, 1);
    $pdf->Cell(0, 10, "Dépenses : " . number_format($expenses, 0, ',', ' ') . " FCFA", 0, 1);
    $pdf->Cell(0, 10, "Employes : $employees", 0, 1);
    $pdf->Cell(0, 10, "Impot estime : " . number_format($tax_due, 0, ',', ' ') . " FCFA", 0, 1);

    // Sauvegarde du PDF
    $fileName = "declaration_company_" . time() . ".pdf";
    $filePath = "../documents/" . $fileName;
    $pdf->Output('F', $filePath);

    // Enregistrement dans pdf_documents
    $pdfStmt = $pdo->prepare("
        INSERT INTO pdf_documents (user_id, declaration_id, file_path)
        VALUES (?, ?, ?)
    ");
    $pdfStmt->execute([$user_id, $declaration_id, $filePath]);

    // Téléchargement direct
    header("Content-Type: application/pdf");
    header("Content-Disposition: attachment; filename=$fileName");
    readfile($filePath);
    exit;

} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
