<?php
function calculateTax($profile, $income, $expenses = 0, $dependents = 0, $employees = 0) {
    // Nettoyage des données
    $income = max(0, floatval($income));
    $expenses = max(0, floatval($expenses));
    $dependents = max(0, intval($dependents));
    $employees = max(0, intval($employees));

    // Calcul selon le profil
    switch ($profile) {
        case 'salaried':
            $base = $income - $expenses;
            $deduction = $dependents * 100;
            $tax = $base * 0.15 - $deduction;
            break;

        case 'independent':
            $base = $income - $expenses;
            $deduction = $dependents * 80;
            $tax = $base * 0.20 - $deduction;
            break;

        case 'company':
            $base = $income - $expenses;
            $deduction = $employees * 50;
            $tax = $base * 0.25 - $deduction;
            break;

        default:
            $tax = 0;
    }

    // Ne jamais retourner un impôt négatif
    return max(0, round($tax, 2));
}
?>
