<?php
require_once '../config/config.php';
redirectIfNotLoggedIn();
if ($_SESSION['user_type'] !== 'student') {
    header('Location: ../admin/dashboard.php');
    exit;
}

$loans = readJSON(LOANS_FILE);
$userId = $_SESSION['user_id'];

$filter = $_GET['filter'] ?? 'all';

if ($filter === 'active') {
    $loans = array_filter($loans, fn($l) => $l['student_id'] === $userId && $l['status'] === 'active');
} elseif ($filter === 'returned') {
    $loans = array_filter($loans, fn($l) => $l['student_id'] === $userId && $l['status'] === 'returned');
} elseif ($filter === 'overdue') {
    $now = time();
    $loans = array_filter($loans, fn($l) => $l['student_id'] === $userId && $l['status'] === 'active' && strtotime($l['return_deadline']) < $now);
} else {
    $loans = array_filter($loans, fn($l) => $l['student_id'] === $userId);
}

usort($loans, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

$now = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Loans - Sports Equipment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: { extend: { fontFamily: { sans: ['Poppins', 'sans-serif'] } } }
        }
    </script>
    <style>
        * { font-family: 'Poppins', sans-serif; }
        body { background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%); min-height: 100vh; }
        .glass { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .sidebar-link { transition: all 0.3s ease; }
        .sidebar-link:hover, .sidebar-link.active { background: rgba(59, 130, 246, 0.2); border-left: 3px solid #3b82f6; }
    </style>
</head>
<body class="text-white">
    <div class="flex min-h-screen">
        <aside class="w-64 glass fixed h-full p-4">
            <div class="mb-8">
                <h1 class="text-xl font-bold text-center">
                    <i data-lucide="trophy" class="inline w-6 h-6 text-blue-500"></i> Sports Hub
                </h1>
                <p class="text-center text-gray-400 text-sm mt-2">Student Panel</p>
            </div>
            <nav class="space-y-2">
                <a href="dashboard.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl">
                    <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
                </a>
                <a href="equipment.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl">
                    <i data-lucide="package" class="w-5 h-5"></i> Equipment
                </a>
                <a href="myloans.php" class="sidebar-link active flex items-center gap-3 px-4 py-3 rounded-xl">
                    <i data-lucide="clock" class="w-5 h-5"></i> My Loans
                </a>
                <a href="qr-scanner.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl">
                    <i data-lucide="scan" class="w-5 h-5"></i> QR Scanner
                </a>
                <a href="logout.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-red-400">
                    <i data-lucide="log-out" class="w-5 h-5"></i> Logout
                </a>
            </nav>
        </aside>

        <main class="flex-1 ml-64 p-8">
            <header class="mb-8">
                <h2 class="text-2xl font-bold">My Loans</h2>
                <p class="text-gray-400">View your loan history</p>
            </header>

            <div class="glass rounded-2xl p-6">
                <div class="flex gap-2 mb-6">
                    <a href="?filter=all" class="px-4 py-2 rounded-lg <?= $filter === 'all' ? 'bg-blue-500' : 'bg-gray-700/50 hover:bg-gray-600/50' ?>">All</a>
                    <a href="?filter=active" class="px-4 py-2 rounded-lg <?= $filter === 'active' ? 'bg-blue-500' : 'bg-gray-700/50 hover:bg-gray-600/50' ?>">Active</a>
                    <a href="?filter=returned" class="px-4 py-2 rounded-lg <?= $filter === 'returned' ? 'bg-blue-500' : 'bg-gray-700/50 hover:bg-gray-600/50' ?>">Returned</a>
                    <a href="?filter=overdue" class="px-4 py-2 rounded-lg <?= $filter === 'overdue' ? 'bg-blue-500' : 'bg-gray-700/50 hover:bg-gray-600/50' ?>">Overdue</a>
                </div>

                <?php if (empty($loans)): ?>
                <p class="text-gray-400">No loans found</p>
                <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($loans as $loan): 
                        $isOverdue = $loan['status'] === 'active' && strtotime($loan['return_deadline']) < $now;
                    ?>
                    <div class="flex items-center justify-between p-4 bg-gray-800/30 rounded-xl <?= $isOverdue ? 'border border-red-500/30' : '' ?>">
                        <div>
                            <p class="font-medium"><?= htmlspecialchars($loan['equipment_name']) ?></p>
                            <p class="text-gray-400 text-sm">Issued: <?= htmlspecialchars($loan['issue_time']) ?></p>
                        </div>
                        <div class="text-right">
                            <?php if ($loan['status'] === 'returned'): ?>
                            <span class="badge badge-success">Returned</span>
                            <p class="text-gray-400 text-xs"><?= htmlspecialchars($loan['return_time']) ?></p>
                            <?php elseif ($isOverdue): ?>
                            <span class="badge badge-danger">Overdue</span>
                            <?php else: ?>
                            <span class="countdown text-yellow-400" data-deadline="<?= $loan['return_deadline'] ?>">--:--</span>
                            <?php endif; ?>
                            <?php if ($loan['status'] === 'active'): ?>
                            <form method="POST" action="../api/return_item.php" class="mt-2">
                                <input type="hidden" name="loan_id" value="<?= $loan['id'] ?>">
                                <button type="submit" class="px-4 py-2 rounded-lg bg-green-500/20 text-green-400 hover:bg-green-500/30 text-sm">Return</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
        
        function updateCountdowns() {
            const now = new Date().getTime() / 1000;
            document.querySelectorAll('.countdown').forEach(el => {
                const deadline = new Date(el.dataset.deadline).getTime() / 1000;
                const diff = deadline - now;
                if (diff > 0) {
                    const hours = Math.floor(diff / 3600);
                    const minutes = Math.floor((diff % 3600) / 60);
                    el.textContent = hours + 'h ' + minutes + 'm left';
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