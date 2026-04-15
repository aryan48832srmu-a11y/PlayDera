<?php
header('Content-Type: application/json');
require_once '../config/config.php';

$loans = readJSON(LOANS_FILE);
$now = time();
$overdueItems = [];
$dueSoonItems = [];

foreach ($loans as $loan) {
    if ($loan['status'] === 'active') {
        $deadline = strtotime($loan['return_deadline']);
        $minutesUntilDue = ($deadline - $now) / 60;
        
        if ($deadline < $now) {
            if (empty($loan['reminded'])) {
                $overdueItems[] = [
                    'id' => $loan['id'],
                    'student_id' => $loan['student_id'],
                    'student_name' => $loan['student_name'],
                    'equipment_name' => $loan['equipment_name'],
                    'return_deadline' => $loan['return_deadline']
                ];
            }
        } elseif ($minutesUntilDue <= 30 && $minutesUntilDue > 0) {
            if (empty($loan['reminded_30min'])) {
                $dueSoonItems[] = [
                    'id' => $loan['id'],
                    'student_id' => $loan['student_id'],
                    'student_name' => $loan['student_name'],
                    'equipment_name' => $loan['equipment_name'],
                    'return_deadline' => $loan['return_deadline'],
                    'minutes_left' => round($minutesUntilDue)
                ];
            }
        }
    }
}

echo json_encode([
    'overdue' => $overdueItems,
    'due_soon' => $dueSoonItems
]);