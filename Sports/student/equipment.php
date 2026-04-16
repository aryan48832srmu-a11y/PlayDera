<?php
require_once '../config/config.php';
redirectIfNotLoggedIn();
if ($_SESSION['user_type'] !== 'student') {
    header('Location: ../admin/dashboard.php');
    exit;
}

$equipment = readJSON(EQUIPMENT_FILE);

$search = $_GET['search'] ?? '';
if ($search) {
    $equipment = array_filter($equipment, fn($eq) => stripos($eq['name'], $search) !== false || stripos($eq['category'], $search) !== false);
}

$page = intval($_GET['page'] ?? 1);
$perPage = 10;
$totalPages = ceil(count($equipment) / $perPage);
$equipment = array_slice($equipment, ($page - 1) * $perPage, $perPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment - Sports Equipment</title>
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
                <p class="text-center text-gray-400 text-sm mt-2">Student Panel</p>
            </div>
            <nav class="space-y-2">
                <a href="dashboard.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl">
                    <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
                </a>
                <a href="equipment.php" class="sidebar-link active flex items-center gap-3 px-4 py-3 rounded-xl">
                    <i data-lucide="package" class="w-5 h-5"></i> Equipment
                </a>
                <a href="myloans.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl">
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
            <header class="flex justify-between items-center mb-8">
                <div>
                    <h2 class="text-2xl font-bold">Equipment</h2>
                    <p class="text-gray-400">Browse and request equipment</p>
                </div>
            </header>

            <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-500/20 border border-green-500/50 text-green-400 px-4 py-3 rounded-lg mb-4">
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-500/20 border border-red-500/50 text-red-400 px-4 py-3 rounded-lg mb-4">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); endif; ?>

            <div class="glass rounded-2xl p-6">
                <div class="mb-6">
                    <div class="relative max-w-md">
                        <input type="text" id="searchInput" placeholder="Search equipment..." value="<?= htmlspecialchars($search) ?>"
                            class="pl-12 pr-4 py-3 rounded-xl w-full"
                            onkeyup="debounceSearch(this.value)">
                        <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-gray-400 text-sm border-b border-gray-700">
                                <th class="text-left py-3 px-4">Equipment</th>
                                <th class="text-left py-3 px-4">Category</th>
                                <th class="text-left py-3 px-4">Available</th>
                                <th class="text-left py-3 px-4">Status</th>
                                <th class="text-left py-3 px-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($equipment as $eq): ?>
                            <tr class="border-b border-gray-700/50 hover:bg-gray-800/30">
                                <td class="py-3 px-4">
                                    <p class="font-medium"><?= htmlspecialchars($eq['name']) ?></p>
                                    <p class="text-gray-400 text-sm"><?= htmlspecialchars($eq['description']) ?></p>
                                </td>
                                <td class="py-3 px-4"><?= htmlspecialchars($eq['category']) ?></td>
                                <td class="py-3 px-4"><?= $eq['available_quantity'] ?></td>
                                <td class="py-3 px-4">
                                    <?php if ($eq['available_quantity'] > 0): ?>
                                    <span class="badge badge-success">Available</span>
                                    <?php else: ?>
                                    <span class="badge badge-danger">Out of Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4">
                                    <?php if ($eq['available_quantity'] > 0): ?>
                                    <form method="POST" action="../api/issue_item.php" class="inline">
                                        <input type="hidden" name="equipment_id" value="<?= $eq['id'] ?>">
                                        <input type="hidden" name="student_id" value="<?= $_SESSION['user_id'] ?>">
                                        <button type="submit" class="px-4 py-2 rounded-lg bg-blue-500/20 text-blue-400 hover:bg-blue-500/30 text-sm">
                                            Issue
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <span class="text-gray-500 text-sm">Unavailable</span>
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