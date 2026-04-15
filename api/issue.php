<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action !== 'issue') {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

$equipmentId = sanitize($_POST['equipment_id'] ?? '');
$studentId = sanitize($_POST['student_id'] ?? '');

if (empty($equipmentId) || empty($studentId)) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$equipment = readJSON(EQUIPMENT_FILE);
$students = readJSON(STUDENT_FILE);
$loans = readJSON(LOANS_FILE);

$eqFound = null;
foreach ($equipment as &$eq) {
    if ($eq['id'] === $equipmentId) {
        $eqFound = $eq;
        if ($eq['available_quantity'] <= 0) {
            echo json_encode(['success' => false, 'message' => 'Equipment not available']);
            exit;
        }
        $eq['available_quantity']--;
        $eq['issued_quantity']++;
        break;
    }
}

if (!$eqFound) {
    echo json_encode(['success' => false, 'message' => 'Equipment not found']);
    exit;
}

$student = null;
foreach ($students as $s) {
    if ($s['id'] === $studentId) {
        $student = $s;
        break;
    }
}

if (!$student) {
    foreach ($students as $s) {
        if (isset($s['room_number']) && $s['room_number'] === $studentId) {
            $student = $s;
            $studentId = $s['id'];
            break;
        }
    }
}

if (!$student) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit;
}

writeJSON(EQUIPMENT_FILE, $equipment);

$loanId = 'LN' . str_pad(count($loans) + 1, 3, '0', STR_PAD_LEFT);
$issueTime = date('Y-m-d H:i:s');
$deadline = strtotime('+' . DEFAULT_RETURN_HOURS . ' hours');
$returnDeadline = date('Y-m-d H:i:s', $deadline);

$loans[] = [
    'id' => $loanId,
    'equipment_id' => $equipmentId,
    'equipment_name' => $eqFound['name'],
    'student_id' => $studentId,
    'student_name' => $student['name'],
    'room_number' => $student['room_number'] ?? '',
    'issue_time' => $issueTime,
    'return_deadline' => $returnDeadline,
    'return_time' => null,
    'status' => 'active',
    'reminded' => false,
    'created_at' => $issueTime
];

writeJSON(LOANS_FILE, $loans);

echo json_encode(['success' => true, 'message' => 'Equipment issued successfully', 'loan_id' => $loanId]);