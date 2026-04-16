<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/config.php';

$id = $_GET['id'] ?? '';

if (empty($id)) {
    echo json_encode(['error' => 'Equipment ID required']);
    exit;
}

$equipment = readJSON(EQUIPMENT_FILE);
$found = null;

foreach ($equipment as $eq) {
    if ($eq['id'] === $id) {
        $found = $eq;
        break;
    }
}

if ($found) {
    echo json_encode($found);
} else {
    echo json_encode(['error' => 'Equipment not found']);
}