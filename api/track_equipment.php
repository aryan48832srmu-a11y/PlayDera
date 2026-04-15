<?php
header('Content-Type: application/json');
require_once '../config/config.php';

$equipment_id = sanitize($_GET['id'] ?? '');

if (empty($equipment_id)) {
    echo json_encode(['success' => false, 'message' => 'Equipment ID required']);
    exit;
}

$equipment = readJSON(EQUIPMENT_FILE);
$loans = readJSON(LOANS_FILE);
$students = readJSON(STUDENT_FILE);

$eqFound = null;
foreach ($equipment as $eq) {
    if ($eq['id'] === $equipment_id) {
        $eqFound = $eq;
        break;
    }
}

if (!$eqFound) {
    echo json_encode(['success' => false, 'message' => 'Equipment not found']);
    exit;
}

$currentHolder = null;
$loanHistory = [];
foreach ($loans as $loan) {
    if ($loan['equipment_id'] === $equipment_id) {
        if ($loan['status'] === 'active') {
            $currentHolder = [
                'student_name' => $loan['student_name'],
                'room_number' => $loan['room_number'],
                'issue_time' => $loan['issue_time'],
                'return_deadline' => $loan['return_deadline']
            ];
        }
        $loanHistory[] = [
            'id' => $loan['id'],
            'student_name' => $loan['student_name'],
            'room_number' => $loan['room_number'],
            'issue_time' => $loan['issue_time'],
            'return_time' => $loan['return_time'],
            'status' => $loan['status']
        ];
    }
}

echo json_encode([
    'success' => true,
    'equipment' => [
        'id' => $eqFound['id'],
        'name' => $eqFound['name'],
        'category' => $eqFound['category'],
        'total_quantity' => $eqFound['total_quantity'],
        'available_quantity' => $eqFound['available_quantity'],
        'issued_quantity' => $eqFound['issued_quantity']
    ],
    'current_holder' => $currentHolder,
    'loan_history' => array_slice($loanHistory, 0, 10)
]);