<?php
require_once '../config/config.php';

if (!isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}

$equipment_id = sanitize($_POST['equipment_id'] ?? '');
$student_id = sanitize($_POST['student_id'] ?? '');

if (empty($equipment_id) || empty($student_id)) {
    $_SESSION['error'] = 'Missing required fields';
    header('Location: ../' . $_SESSION['user_type'] . '/dashboard.php');
    exit;
}

$equipment = readJSON(EQUIPMENT_FILE);
$students = readJSON(STUDENT_FILE);
$loans = readJSON(LOANS_FILE);

$eqFound = null;
foreach ($equipment as &$eq) {
    if ($eq['id'] === $equipment_id) {
        $eqFound = $eq;
        if ($eq['available_quantity'] <= 0) {
            $_SESSION['error'] = 'Equipment not available';
            header('Location: ../' . $_SESSION['user_type'] . '/dashboard.php');
            exit;
        }
        $eq['available_quantity']--;
        $eq['issued_quantity']++;
        break;
    }
}

if (!$eqFound) {
    $_SESSION['error'] = 'Equipment not found';
    header('Location: ../' . $_SESSION['user_type'] . '/dashboard.php');
    exit;
}

$student = null;
foreach ($students as $s) {
    if ($s['id'] === $student_id) {
        $student = $s;
        break;
    }
}

if (!$student) {
    foreach ($students as $s) {
        if (isset($s['room_number']) && strtoupper($s['room_number']) === strtoupper($student_id)) {
            $student = $s;
            $student_id = $s['id'];
            break;
        }
    }
}

if (!$student) {
    foreach ($students as $s) {
        if (isset($s['name']) && strtoupper($s['name']) === strtoupper($student_id)) {
            $student = $s;
            $student_id = $s['id'];
            break;
        }
    }
}

if (!$student) {
    $student = [
        'id' => 'GUEST' . str_pad(count($students) + 1, 3, '0', STR_PAD_LEFT),
        'name' => $student_id,
        'room_number' => $student_id,
        'email' => 'guest@sports.edu'
    ];
    $student_id = $student['id'];
}

if (!$student) {
    $_SESSION['error'] = 'Student not found';
    header('Location: ../' . $_SESSION['user_type'] . '/dashboard.php');
    exit;
}

writeJSON(EQUIPMENT_FILE, $equipment);

$loanId = 'LN' . str_pad(count($loans) + 1, 3, '0', STR_PAD_LEFT);
$issueTime = date('Y-m-d H:i:s');
$deadline = strtotime('+' . DEFAULT_RETURN_HOURS . ' hours');
$returnDeadline = date('Y-m-d H:i:s', $deadline);

$loans[] = [
    'id' => $loanId,
    'equipment_id' => $equipment_id,
    'equipment_name' => $eqFound['name'],
    'student_id' => $student_id,
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

$_SESSION['success'] = 'Equipment issued successfully!';
header('Location: ../' . $_SESSION['user_type'] . '/dashboard.php');
exit;