<?php
require_once '../config/config.php';

if (isLoggedIn() && $_SESSION['user_type'] === 'student') {
    header('Location: dashboard.php');
    exit;
}
if (isLoggedIn() && $_SESSION['user_type'] === 'admin') {
    header('Location: ../admin/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $students = readJSON(STUDENT_FILE);
    $user = null;
    
    foreach ($students as $u) {
        if ($u['email'] === $email) {
            $user = $u;
            break;
        }
    }
    
    if ($user && ($user['password'] === $password || password_verify($password, $user['password']))) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_type'] = 'student';
        $_SESSION['room_number'] = $user['room_number'] ?? '';
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid email or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - Sports Equipment</title>
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
            <div class="w-20 h-20 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white">Student Login</h1>
            <p class="text-gray-400 text-sm">Sports Equipment Booking</p>
        </div>

        <?php if ($error): ?>
        <div class="bg-red-500/20 border border-red-500/50 text-red-400 px-4 py-3 rounded-lg mb-4 text-sm">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm text-gray-400 mb-1">Email</label>
                <input type="email" name="email" required class="w-full px-4 py-3 rounded-lg text-white" placeholder="your@email.com">
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1">Password</label>
                <input type="password" name="password" required class="w-full px-4 py-3 rounded-lg text-white" placeholder="Enter password">
            </div>
            <button type="submit" class="w-full py-3 rounded-lg bg-gradient-to-r from-green-500 to-green-600 text-white font-semibold hover:from-green-600 hover:to-green-700 transition">
                Login as Student
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="register.php" class="text-green-500 hover:text-green-400 text-sm">
                Register New Account →
            </a>
        </div>

        <div class="mt-4 text-center">
            <a href="../admin/index.php" class="text-blue-500 hover:text-blue-400 text-sm">
                Admin Login →
            </a>
        </div>

        <div class="mt-4 text-center">
            <a href="../index.php" class="text-gray-500 hover:text-gray-400 text-xs">
                Back to Home
            </a>
        </div>
    </div>
</body>
</html>