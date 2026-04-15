<?php
header('Content-Type: application/json');
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action !== 'return') {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

$loanId = sanitize($_POST['loan_id'] ?? '');

if (empty($loanId)) {
    echo json_encode(['success' => false, 'message' => 'Loan ID required']);
    exit;
}

$loans = readJSON(LOANS_FILE);
$equipment = readJSON(EQUIPMENT_FILE);

$loanFound = null;
foreach ($loans as &$loan) {
    if ($loan['id'] === $loanId) {
        if ($loan['status'] === 'returned') {
            echo json_encode(['success' => false, 'message' => 'Already returned']);
            exit;
        }
        $loanFound = $loan;
        $loan['status'] = 'returned';
        $loan['return_time'] = date('Y-m-d H:i:s');
        break;
    }
}

if (!$loanFound) {
    echo json_encode(['success' => false, 'message' => 'Loan not found']);
    exit;
}

foreach ($equipment as &$eq) {
    if ($eq['id'] === $loanFound['equipment_id']) {
        $eq['available_quantity']++;
        $eq['issued_quantity']--;
        break;
    }
}

writeJSON(LOANS_FILE, $loans);
writeJSON(EQUIPMENT_FILE, $equipment);

echo json_encode(['success' => true, 'message' => 'Equipment returned successfully']);