<?php
require_once '../config/config.php';

if (!isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}

$loan_id = sanitize($_POST['loan_id'] ?? '');

if (empty($loan_id)) {
    $_SESSION['error'] = 'Loan ID required';
    header('Location: ../' . $_SESSION['user_type'] . '/dashboard.php');
    exit;
}

$loans = readJSON(LOANS_FILE);
$equipment = readJSON(EQUIPMENT_FILE);

$loanFound = null;
$loanIndex = -1;

foreach ($loans as $index => &$loan) {
    if ($loan['id'] === $loan_id) {
        $loanFound = $loan;
        $loanIndex = $index;
        break;
    }
}

if (!$loanFound) {
    $_SESSION['error'] = 'Loan not found';
    header('Location: ../' . $_SESSION['user_type'] . '/dashboard.php');
    exit;
}

if ($loanFound['status'] === 'returned') {
    $_SESSION['error'] = 'Already returned';
    header('Location: ../' . $_SESSION['user_type'] . '/dashboard.php');
    exit;
}

$loans[$loanIndex]['status'] = 'returned';
$loans[$loanIndex]['return_time'] = date('Y-m-d H:i:s');

foreach ($equipment as &$eq) {
    if ($eq['id'] === $loanFound['equipment_id']) {
        $eq['available_quantity']++;
        $eq['issued_quantity']--;
        break;
    }
}

writeJSON(LOANS_FILE, $loans);
writeJSON(EQUIPMENT_FILE, $equipment);

$_SESSION['success'] = 'Equipment returned successfully!';
header('Location: ../' . $_SESSION['user_type'] . '/dashboard.php');
exit;