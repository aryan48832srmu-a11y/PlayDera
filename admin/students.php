<?php
require_once '../config/config.php';
redirectIfNotLoggedIn();
if ($_SESSION['user_type'] !== 'admin') {
    header('Location: ../student/dashboard.php');
    exit;
}

$students = readJSON(STUDENT_FILE);
$loans = readJSON(LOANS_FILE);

$search = $_GET['search'] ?? '';
if ($search) {
    $students = array_filter($students, fn($s) => stripos($s['name'], $search) !== false || stripos($s['email'], $search) !== false || stripos($s['room_number'], $search) !== false);
}

$page = intval($_GET['page'] ?? 1);
$perPage = 15;
$totalPages = ceil(count($students) / $perPage);
$students = array_slice($students, ($page - 1) * $perPage, $perPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - Sports Equipment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Poppins', 'sans-serif'] },
                    colors: { primary: '#3b82f6' }
                }
            }
        }
    </script>
    <style>
        * { font-family: 'Poppins', sans-serif; }
        body { background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%); min-height: 100vh; }
        .glass { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .sidebar-link { transition: all 0.3s ease; }
        .sidebar-link:hover, .sidebar-link.active { background: rgba(59, 130, 246, 0.2); border-left: 3px solid #3b82f6; }
        input { background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="text-white">
    <div class="flex min-h-screen">
        <aside class="w-64 glass fixed h-full p-4">
            <div class="mb-8">
                <h1 class="text-xl font-bold text-center">
                    <i data-lucide="trophy" class="inline w-6 h-6 text-blue-500"></i> Sports Hub
                </h1>
            </div>
            <nav class="space-y-2">
                <a href="dashboard.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl">
                    <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
                </a>
                <a href="equipment.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl">
                    <i data-lucide="package" class="w-5 h-5"></i> Equipment
                </a>
                <a href="loans.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl">
                    <i data-lucide="clock" class="w-5 h-5"></i> Loans
                </a>
                <a href="students.php" class="sidebar-link active flex items-center gap-3 px-4 py-3 rounded-xl">
                    <i data-lucide="users" class="w-5 h-5"></i> Students
                </a>
                <a href="qr-scanner.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl">
                    <i data-lucide="scan" class="w-5 h-5"></i> QR Scanner
                </a>
                <a href="qr-generator.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl">
                    <i data-lucide="qr-code" class="w-5 h-5"></i> QR Generator
                </a>
                <a href="logout.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-red-400">
                    <i data-lucide="log-out" class="w-5 h-5"></i> Logout
                </a>
            </nav>
        </aside>

        <main class="flex-1 ml-64 p-8">
            <header class="flex justify-between items-center mb-8">
                <div>
                    <h2 class="text-2xl font-bold">Students</h2>
                    <p class="text-gray-400">View all registered students</p>
                </div>
            </header>

            <div class="glass rounded-2xl p-6">
                <div class="mb-6">
                    <div class="relative max-w-xs">
                        <input type="text" id="searchInput" placeholder="Search students..." value="<?= htmlspecialchars($search) ?>"
                            class="pl-12 pr-4 py-3 rounded-xl w-full"
                            onkeyup="debounceSearch(this.value)">
                        <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-gray-400 text-sm border-b border-gray-700">
                                <th class="text-left py-3 px-4">ID</th>
                                <th class="text-left py-3 px-4">Name</th>
                                <th class="text-left py-3 px-4">Email</th>
                                <th class="text-left py-3 px-4">Room</th>
                                <th class="text-left py-3 px-4">Phone</th>
                                <th class="text-left py-3 px-4">Active Loans</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): 
                                $activeLoans = array_filter($loans, fn($l) => $l['student_id'] === $student['id'] && $l['status'] === 'active');
                            ?>
                            <tr class="border-b border-gray-700/50 hover:bg-gray-800/30">
                                <td class="py-3 px-4 font-mono text-sm"><?= htmlspecialchars($student['id']) ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($student['name']) ?></td>
                                <td class="py-3 px-4 text-sm"><?= htmlspecialchars($student['email']) ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($student['room_number'] ?? '-') ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($student['phone'] ?? '-') ?></td>
                                <td class="py-3 px-4">
                                    <span class="px-3 py-1 rounded-full text-sm <?= count($activeLoans) > 0 ? 'bg-yellow-500/20 text-yellow-400' : 'bg-gray-700/50' ?>">
                                        <?= count($activeLoans) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                <div class="flex justify-center gap-2 mt-6">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= htmlspecialchars($search) ?>" 
                        class="px-4 py-2 rounded-lg <?= $i === $page ? 'bg-blue-500 text-white' : 'bg-gray-700/50 hover:bg-gray-600/50' ?>">
                        <?= $i ?>
                    </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
        
        let searchTimeout;
        function debounceSearch(value) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                window.location.href = '?search=' + encodeURIComponent(value);
            }, 500);
        }
    </script>
</body>
</html>