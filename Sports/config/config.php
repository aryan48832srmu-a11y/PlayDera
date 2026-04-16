<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DATA_DIR', __DIR__ . '/../data/');
define('ADMIN_FILE', DATA_DIR . 'admins.json');
define('STUDENT_FILE', DATA_DIR . 'students.json');
define('EQUIPMENT_FILE', DATA_DIR . 'equipment.json');
define('LOANS_FILE', DATA_DIR . 'loans.json');

define('DEFAULT_RETURN_HOURS', 6);
define('REMINDER_INTERVAL', 60000);

function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function readJSON($file) {
    if (!file_exists($file)) {
        return [];
    }
    $content = file_get_contents($file);
    return json_decode($content, true) ?: [];
}

function writeJSON($file, $data) {
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

function getEquipmentStats() {
    $equipment = readJSON(EQUIPMENT_FILE);
    $loans = readJSON(LOANS_FILE);
    
    $total = 0;
    $available = 0;
    $issued = 0;
    $overdue = 0;
    
    foreach ($equipment as $eq) {
        $total += $eq['total_quantity'];
    }
    
    $now = time();
    $activeLoans = array_filter($loans, fn($l) => $l['status'] === 'active');
    $issued = count($activeLoans);
    $available = $total - $issued;
    
    foreach ($activeLoans as $loan) {
        $deadline = strtotime($loan['return_deadline']);
        if ($deadline < $now) {
            $overdue++;
        }
    }
    
    return ['total' => $total, 'available' => $available, 'issued' => $issued, 'overdue' => $overdue];
}