<?php
header('Content-Type: application/json');
require_once '../config/config.php';

$message = strtolower(sanitize($_GET['message'] ?? ''));
$userId = $_SESSION['user_id'] ?? '';
$userType = $_SESSION['user_type'] ?? '';

if (empty($message)) {
    echo json_encode(['reply' => 'Kya help chahiye?', 'suggestions' => getSuggestions()]);
    exit;
}

$equipment = readJSON(EQUIPMENT_FILE);
$loans = readJSON(LOANS_FILE);
$students = readJSON(STUDENT_FILE);
$stats = getEquipmentStats();

$reply = '';
$suggestions = [];

if (preg_match('/^(hello|hi|hey|namaste|hii)/', $message)) {
    $greetings = ["Namaste! 🙏", "Hello! 👋", "Hi there! 😊", "Hey! Good to see you!"];
    $reply = $greetings[array_rand($greetings)] . " Mai Sports Bot hoon. Aapki kaise service kar sakta hoon?";
    $suggestions = ["Show equipment list", "My loans", "Overdue items"];
} elseif (preg_match('/^(bye|goodbye|see ya|tata)/', $message)) {
    $reply = "Alvida! 🙏 Phir milte hain!";
    $suggestions = [];
} elseif (preg_match('/(thanks|thank you|dhanyavad|shukriya)/', $message)) {
    $reply = "Welcome! 🙏 Koi kaam nahi. Aapki madad karta hoon!";
    $suggestions = ["Show all equipment", "How are you?"];
} elseif (preg_match('/(show|list|display).*(equipment|items|stock)/', $message) || $message === 'equipment' || $message === 'items') {
    $reply = "📦 Current Equipment Status:\n\n";
    foreach ($equipment as $e) {
        $reply .= "• {$e['name']}: {$e['total_quantity']} total, {$e['available_quantity']} available\n";
    }
    $reply .= "\nKis equipment ki detailed info chahiye?";
    $suggestions = ["Football available?", "Cricket status", "Badminton rackets"];
} elseif (preg_match('/(available|free|ha).*(hai|ha|ka)?/i', $message)) {
    $itemName = extractEquipmentName($message);
    if ($itemName) {
        $eq = findEquipment($itemName, $equipment);
        if ($eq) {
            $reply = "✅ {$eq['name']}: ";
            if ($eq['available_quantity'] > 0) {
                $reply .= "{$eq['available_quantity']} available hai!";
                $suggestions = ["Issue {$eq['name']}", "Tell about {$eq['name']}"];
            } else {
                $reply .= "Sorry, abhi available nahi hai. ☹️";
                $suggestions = ["Kon ke paas hai?", "Kya available hai?"];
            }
        }
    } else {
        $totalAvailable = $stats['available'];
        $reply = "📊 Total: {$stats['total']} items, {$totalAvailable} available, {$stats['issued']} issued.";
        $suggestions = ["Show all equipment", "Kis sport ki items?"];
    }
} elseif (preg_match('/(my|mera).*(loan|item|equipment|issued)/i', $message)) {
    $myLoans = array_filter($loans, fn($l) => $l['student_id'] === $userId && $l['status'] === 'active');
    if (!empty($myLoans)) {
        $reply = "📋 Aapke current loans:\n\n";
        foreach ($myLoans as $loan) {
            $reply .= "• {$loan['equipment_name']}\n   Deadline: {$loan['return_deadline']}\n";
        }
        $suggestions = ["Return all", "Extend deadline", "Time left?"];
    } else {
        $reply = "🎉 Aapke paas koi active loan nahi hai!";
        $suggestions = ["Show equipment", "Issue new equipment"];
    }
} elseif (preg_match('/(overdue|late|beech|ubin)/i', $message)) {
    $now = time();
    $overdueLoans = array_filter($loans, fn($l) => $l['status'] === 'active' && strtotime($l['return_deadline']) < $now);
    if (count($overdueLoans) > 0) {
        $reply = "⚠️ Currently " . count($overdueLoans) . " items overdue hain:\n\n";
        foreach (array_slice($overdueLoans, 0, 5) as $loan) {
            $reply .= "• {$loan['equipment_name']} - {$loan['student_name']}\n";
        }
        $suggestions = ["See all overdue", "Return now"];
    } else {
        $reply = "🎉 Great news! Koi overdue items nahi hai!";
        $suggestions = ["Show equipment stats", "My status"];
    }
} elseif (preg_match('/(kon|kaun|who).*(ke paas|has|with)/i', $message)) {
    $itemName = extractEquipmentName($message);
    if ($itemName) {
        $activeLoans = array_filter($loans, fn($l) => $l['equipment_name'] === $itemName && $l['status'] === 'active');
        if (!empty($activeLoans)) {
            $borrowers = array_column($activeLoans, 'student_name');
            $reply = "{$itemName} ye students ke paas hai:\n";
            foreach ($borrowers as $name) {
                $reply .= "• {$name}\n";
            }
        } else {
            $reply = "{$itemName} kisi ke paas nahi hai. Available hai! ✅";
            $suggestions = ["Issue {$itemName}"];
        }
    }
} elseif (preg_match('/(total|kitna|summar)/i', $message)) {
    $reply = "📊 Equipment Summary:\n\n";
    $reply .= "• Total Items: {$stats['total']}\n";
    $reply .= "• Available: {$stats['available']}\n";
    $reply .= "• Issued: {$stats['issued']}\n";
    $reply .= "• Overdue: {$stats['overdue']}";
    $suggestions = ["Show detailed list", "See overdue items"];
} elseif (preg_match('/(how are you|kaisa|kaise|status)/i', $message)) {
    $replies = ["Main perfect condition mein hoon! 😄 Aapka order karein.", "Badhiya! Service ke liye taiyar. ⚡", "EkDm! Aapki kya help kar sakta hoon?"];
    $reply = $replies[array_rand($replies)];
    $suggestions = ["Show equipment", "My loans"];
} elseif (preg_match('/(help|guide|kya kar sakte ho)/i', $message)) {
    $reply = "🤖 Commands jo aap use kar sakte hain:\n\n";
    $reply .= "📋 Info:\n";
    $reply .= "• 'Show equipment'\n• 'Total status'\n• 'Overdue items'\n• 'Available football?'\n\n";
    $reply .= "🎮 Your Info:\n";
    $reply .= "• 'My loans'\n• 'Deadline'\n\n";
    $reply .= "💬 General:\n";
    $reply .= "• 'Hello', 'Thanks', 'Help', 'Sports'";
} elseif (preg_match('/(game|sports|sport|khel)/i', $message)) {
    $reply = "🎮 Available Sports:\n\n";
    $reply .= "• ⚽ Ball: Football, Basketball, Volleyball\n";
    $reply .= "• 🎾 Racket: Badminton, Tennis\n";
    $reply .= "• 🏏 Cricket\n";
    $reply .= "• ♟️ Indoor: Chess, Table Tennis\n\n";
    $reply .= "Kis sport ki items chahiye?";
    $suggestions = ["Show football", "Show badminton", "Show chess"];
} elseif (preg_match('/(issue|lease|lena|book|request)/i', $message)) {
    $itemName = extractEquipmentName($message);
    if ($itemName) {
        $eq = findEquipment($itemName, $equipment);
        if ($eq && $eq['available_quantity'] > 0) {
            $reply = "✅ {$eq['name']} available hai! Issue karne ke liye QR scan karein ya admin se request karein.";
        } else {
            $reply = "❌ {$itemName} abhi available nahi hai.";
        }
    } else {
        $reply = "📝 Issue ke liye equipment ka naam batayein. Eg: 'Issue football'";
    }
    $suggestions = ["Show equipment list"];
} elseif (preg_match('/(return|wapas|dedena)/i', $message)) {
    $itemName = extractEquipmentName($message);
    if ($itemName) {
        $reply = "🔄 Return karne ke liye QR code scan karein ya admin ko inform karein.";
    } else {
        $myLoans = array_filter($loans, fn($l) => $l['student_id'] === $userId && $l['status'] === 'active');
        if (!empty($myLoans)) {
            $reply = "📋 Aapko ye items return karn hain:\n";
            foreach ($myLoans as $loan) {
                $reply .= "• {$loan['equipment_name']} (Due: {$loan['return_deadline']})\n";
            }
        } else {
            $reply = "Aapke paas koi loan nahi hai!";
        }
    }
} elseif (preg_match('/(deadline|due|kitna time|time left)/i', $message)) {
    $myLoans = array_filter($loans, fn($l) => $l['student_id'] === $userId && $l['status'] === 'active');
    if (!empty($myLoans)) {
        $reply = "⏰ Aapke deadlines:\n\n";
        foreach ($myLoans as $loan) {
            $deadline = strtotime($loan['return_deadline']);
            $now = time();
            $diff = $deadline - $now;
            $hours = floor($diff / 3600);
            $minutes = floor(($diff % 3600) / 60);
            $reply .= "• {$loan['equipment_name']}: ";
            if ($diff > 0) {
                $reply .= "{$hours}h {$minutes}m left\n";
            } else {
                $reply .= "OVERDUE! ⚠️\n";
            }
        }
    } else {
        $reply = "Aapke paas koi loan nahi hai.";
    }
} elseif (preg_match('/(who are you|kon hai tu|about)/i', $message)) {
    $reply = "🤖 Mai Sports Bot hoon!\n\nFeatures:\n";
    $reply .= "• Equipment check\n";
    $reply .= "• Loan tracking\n";
    $reply .= "• Deadline reminders\n";
    $reply .= "• Issue & return help\n\nAsk me anything!";
} else {
    $reply = "🤔 Samjha nahi. Phir se try karein!\nType 'Help' for commands.";
    $suggestions = getSuggestions();
}

if (empty($reply)) {
    $reply = "🤔 Samjha nahi. 'Help' likhein.";
    $suggestions = getSuggestions();
}

echo json_encode(['reply' => $reply, 'suggestions' => $suggestions]);

function getSuggestions() {
    return ["Show equipment", "My loans", "Total status", "Overdue items", "Help"];
}

function extractEquipmentName($message) {
    $names = ['football', 'cricket bat', 'badminton racket', 'tennis racket', 'basketball', 'volleyball', 'chess set', 'table tennis'];
    foreach ($names as $name) {
        if (strpos($message, $name) !== false) {
            if ($name === 'cricket bat') return 'Cricket Bat';
            if ($name === 'badminton racket') return 'Badminton Racket';
            if ($name === 'tennis racket') return 'Tennis Racket';
            if ($name === 'chess set') return 'Chess Set';
            if ($name === 'table tennis') return 'Table Tennis Kit';
            return ucfirst($name);
        }
    }
    $aliases = ['football' => 'Football', 'cricket' => 'Cricket Bat', 'badminton' => 'Badminton Racket',
        'tennis' => 'Tennis Racket', 'basketball' => 'Basketball', 'volleyball' => 'Volleyball',
        'chess' => 'Chess Set', 'tt' => 'Table Tennis Kit'];
    foreach ($aliases as $key => $full) {
        if (strpos($message, $key) !== false) return $full;
    }
    return '';
}

function findEquipment($name, $equipment) {
    foreach ($equipment as $e) {
        if (stripos($e['name'], $name) !== false) return $e;
    }
    return null;
}