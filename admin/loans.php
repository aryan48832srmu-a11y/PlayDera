<?php
require_once '../config/config.php';
redirectIfNotLoggedIn();
if ($_SESSION['user_type'] !== 'admin') {
    header('Location: ../student/dashboard.php');
    exit;
}

$loans = readJSON(LOANS_FILE);
$students = readJSON(STUDENT_FILE);
$equipment = readJSON(EQUIPMENT_FILE);

$filter = $_GET['filter'] ?? 'all';

if ($filter === 'active') {
    $loans = array_filter($loans, fn($l) => $l['status'] === 'active');
} elseif ($filter === 'returned') {
    $loans = array_filter($loans, fn($l) => $l['status'] === 'returned');
} elseif ($filter === 'overdue') {
    $now = time();
    $loans = array_filter($loans, fn($l) => $l['status'] === 'active' && strtotime($l['return_deadline']) < $now);
}

$search = $_GET['search'] ?? '';
if ($search) {
    $loans = array_filter($loans, fn($l) => stripos($l['student_name'], $search) !== false || stripos($l['equipment_name'], $search) !== false);
}

usort($loans, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

$page = intval($_GET['page'] ?? 1);
$perPage = 15;
$totalPages = ceil(count($loans) / $perPage);
$loans = array_slice($loans, ($page - 1) * $perPage, $perPage);

$now = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Management - Sports Equipment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Poppins', 'sans-serif'] },
                    colors: { primary: '#3b82f6', secondary: '#64748b', dark: '#1e293b', darker: '#0f172a' }
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
        input, select { background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255, 255, 255, 0.1); }
        input:focus, select:focus { outline: none; border-color: #3b82f6; }
        .modal-backdrop { background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(4px); }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-thumb { background: rgba(100, 116, 139, 0.5); border-radius: 4px; }
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
                <a href="loans.php" class="sidebar-link active flex items-center gap-3 px-4 py-3 rounded-xl">
                    <i data-lucide="clock" class="w-5 h-5"></i> Loans
                </a>
                <a href="students.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl">
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
                    <h2 class="text-2xl font-bold">Loan Management</h2>
                    <p class="text-gray-400">Track all equipment loans and returns</p>
                </div>
            </header>

            <div class="glass rounded-2xl p-6">
                <div class="flex flex-wrap gap-4 mb-6">
                    <div class="flex gap-2">
                        <a href="?filter=all" class="px-4 py-2 rounded-lg <?= $filter === 'all' ? 'bg-blue-500' : 'bg-gray-700/50 hover:bg-gray-600/50' ?>">
                            All Loans
                        </a>
                        <a href="?filter=active" class="px-4 py-2 rounded-lg <?= $filter === 'active' ? 'bg-blue-500' : 'bg-gray-700/50 hover:bg-gray-600/50' ?>">
                            Active
                        </a>
                        <a href="?filter=returned" class="px-4 py-2 rounded-lg <?= $filter === 'returned' ? 'bg-blue-500' : 'bg-gray-700/50 hover:bg-gray-600/50' ?>">
                            Returned
                        </a>
                        <a href="?filter=overdue" class="px-4 py-2 rounded-lg <?= $filter === 'overdue' ? 'bg-blue-500' : 'bg-gray-700/50 hover:bg-gray-600/50' ?>">
                            Overdue
                        </a>
                    </div>
                    <div class="relative flex-1 max-w-xs">
                        <input type="text" id="searchInput" placeholder="Search loans..." value="<?= htmlspecialchars($search) ?>"
                            class="pl-12 pr-4 py-2 rounded-xl w-full"
                            onkeyup="debounceSearch(this.value)">
                        <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-gray-400 text-sm border-b border-gray-700">
                                <th class="text-left py-3 px-4">ID</th>
                                <th class="text-left py-3 px-4">Student</th>
                                <th class="text-left py-3 px-4">Room</th>
                                <th class="text-left py-3 px-4">Equipment</th>
                                <th class="text-left py-3 px-4">Issue Time</th>
                                <th class="text-left py-3 px-4">Deadline</th>
                                <th class="text-left py-3 px-4">Status</th>
                                <th class="text-left py-3 px-4">Time Left</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($loans as $loan): 
                                $isOverdue = $loan['status'] === 'active' && strtotime($loan['return_deadline']) < $now;
                            ?>
                            <tr class="border-b border-gray-700/50 hover:bg-gray-800/30 <?= $isOverdue ? 'bg-red-500/10' : '' ?>">
                                <td class="py-3 px-4 font-mono text-sm"><?= htmlspecialchars($loan['id']) ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($loan['student_name']) ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($loan['room_number']) ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($loan['equipment_name']) ?></td>
                                <td class="py-3 px-4 text-sm"><?= htmlspecialchars($loan['issue_time']) ?></td>
                                <td class="py-3 px-4 text-sm <?= $isOverdue ? 'text-red-400' : '' ?>"><?= htmlspecialchars($loan['return_deadline']) ?></td>
                                <td class="py-3 px-4">
                                    <?php if ($loan['status'] === 'returned'): ?>
                                    <span class="badge badge-success">Returned</span>
                                    <?php elseif ($isOverdue): ?>
                                    <span class="badge badge-danger">Overdue</span>
                                    <?php else: ?>
                                    <span class="badge badge-info">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4">
                                    <?php if ($loan['status'] === 'active'): ?>
                                    <span class="countdown text-sm" data-deadline="<?= $loan['return_deadline'] ?>">--:--</span>
                                    <?php else: ?>
                                    <span class="text-gray-500">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                <div class="flex justify-center gap-2 mt-6">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>&filter=<?= $filter ?>&search=<?= htmlspecialchars($search) ?>" 
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
                window.location.href = '?search=' + encodeURIComponent(value) + '&filter=<?= $filter ?>';
            }, 500);
        }

        function updateCountdowns() {
            const now = new Date().getTime() / 1000;
            document.querySelectorAll('.countdown').forEach(el => {
                const deadline = new Date(el.dataset.deadline).getTime() / 1000;
                const diff = deadline - now;
                if (diff > 0) {
                    const hours = Math.floor(diff / 3600);
                    const minutes = Math.floor((diff % 3600) / 60);
                    el.textContent = hours + 'h ' + minutes + 'm';
                } else {
                    el.textContent = 'Overdue!';
                    el.classList.add('text-red-400');
                }
            });
        }
        
        setInterval(updateCountdowns, 1000);
        updateCountdowns();
    </script>
</body>
</html>