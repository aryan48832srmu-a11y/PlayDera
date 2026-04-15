<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sports Equipment Management System</title>
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
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="glass rounded-2xl shadow-2xl w-full max-w-lg p-8 text-center">
        <div class="mb-8">
            <div class="w-24 h-24 bg-gradient-to-br from-blue-500 to-purple-500 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-white">Sports Equipment</h1>
            <p class="text-gray-400">Management System</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <a href="admin/index.php" class="block p-6 rounded-2xl bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 transition">
                <svg class="w-12 h-12 mx-auto mb-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <h2 class="text-xl font-bold text-white">Admin Panel</h2>
                <p class="text-blue-200 text-sm mt-1">Manage equipment & loans</p>
            </a>

            <a href="student/login.php" class="block p-6 rounded-2xl bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 transition">
                <svg class="w-12 h-12 mx-auto mb-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                <h2 class="text-xl font-bold text-white">Student Login</h2>
                <p class="text-green-200 text-sm mt-1">Issue & return equipment</p>
            </a>
        </div>

        <div class="mt-8 pt-6 border-t border-gray-700">
            <p class="text-gray-500 text-sm">Welcome to Sports Equipment Management System</p>
            <p class="text-gray-600 text-xs mt-2">Select your login type above</p>
        </div>
    </div>
</body>
</html>