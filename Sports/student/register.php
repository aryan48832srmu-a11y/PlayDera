<?php
require_once '../config/config.php';

if (isLoggedIn()) {
    if ($_SESSION['user_type'] === 'student') {
        header('Location: dashboard.php');
        exit;
    }
    header('Location: ../admin/dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $room = sanitize($_POST['room'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    
    if (empty($email) || empty($password) || empty($name)) {
        $error = 'All fields are required';
    } else {
        $students = readJSON(STUDENT_FILE);
        
        foreach ($students as $s) {
            if ($s['email'] === $email) {
                $error = 'Email already registered';
                break;
            }
        }
        
        if (empty($error)) {
            $newUser = [
                'id' => 'STU' . str_pad(count($students) + 1, 3, '0', STR_PAD_LEFT),
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'room_number' => $room,
                'phone' => $phone,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $students[] = $newUser;
            writeJSON(STUDENT_FILE, $students);
            $success = 'Registration successful! Please login.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - Sports Equipment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Poppins', 'sans-serif'] },
                    colors: { primary: '#3b82f6', dark: '#1e293b', darker: '#0f172a' }
                }
            }
        }
    </script>
    <style>
        * { font-family: 'Poppins', sans-serif; }
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
        }
        .glass {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        input {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="glass rounded-2xl shadow-2xl w-full max-w-md p-8">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-white">Student Registration</h1>
            <p class="text-gray-400 text-sm">Create New Account</p>
        </div>

        <?php if ($error): ?>
        <div class="bg-red-500/20 border border-red-500/50 text-red-400 px-4 py-3 rounded-lg mb-4 text-sm">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="bg-green-500/20 border border-green-500/50 text-green-400 px-4 py-3 rounded-lg mb-4 text-sm">
            <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm text-gray-400 mb-1">Full Name *</label>
                <input type="text" name="name" required class="w-full px-4 py-3 rounded-lg text-white" placeholder="Your full name">
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1">Email *</label>
                <input type="email" name="email" required class="w-full px-4 py-3 rounded-lg text-white" placeholder="your@email.com">
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1">Password *</label>
                <input type="password" name="password" required class="w-full px-4 py-3 rounded-lg text-white" placeholder="Create password">
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1">Room Number</label>
                <input type="text" name="room" class="w-full px-4 py-3 rounded-lg text-white" placeholder="A-101">
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1">Phone Number</label>
                <input type="tel" name="phone" class="w-full px-4 py-3 rounded-lg text-white" placeholder="9876543210">
            </div>
            <button type="submit" class="w-full py-3 rounded-lg bg-gradient-to-r from-green-500 to-green-600 text-white font-semibold hover:from-green-600 hover:to-green-700 transition">
                Register
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="login.php" class="text-green-500 hover:text-green-400 text-sm">
                Already have account? Login →
            </a>
        </div>
    </div>
</body>
</html>