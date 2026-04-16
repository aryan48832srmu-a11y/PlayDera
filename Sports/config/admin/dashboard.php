<?php
require_once '../config/config.php';
redirectIfNotLoggedIn();
if ($_SESSION['user_type'] !== 'admin') {
    header('Location: ../student/dashboard.php');
    exit;
}

$stats = getEquipmentStats();
$equipment = readJSON(EQUIPMENT_FILE);
$loans = readJSON(LOANS_FILE);
$students = readJSON(STUDENT_FILE);

$activeLoans = array_filter($loans, fn($l) => $l['status'] === 'active');
$returnedLoans = array_filter($loans, fn($l) => $l['status'] === 'returned');

$now = time();
$thirtyMin = $now + 1800; // 30 minutes
$overdueLoans = [];
$dueSoonLoans = [];
foreach ($loans as $loan) {
    if ($loan['status'] === 'active') {
        $deadline = strtotime($loan['return_deadline']);
        if ($deadline < $now) {
            $overdueLoans[] = $loan;
        } elseif ($deadline < $thirtyMin) {
            $dueSoonLoans[] = $loan;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sports Equipment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
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
        .glass-card { background: rgba(30, 41, 59, 0.5); backdrop-filter: blur(8px); border: 1px solid rgba(255, 255, 255, 0.08); }
        .sidebar-link { transition: all 0.3s ease; }
        .sidebar-link:hover, .sidebar-link.active { background: rgba(59, 130, 246, 0.2); border-left: 3px solid #3b82f6; }
        input, select { background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255, 255, 255, 0.1); }
        input:focus, select:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); }
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3); }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: rgba(15, 23, 42, 0.5); }
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
                <a href="dashboard.php" class="sidebar-link active flex items-center gap-3 px-4 py-3 rounded-xl">
                    <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
                </a>
                <a href="equipment.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl">
                    <i data-lucide="package" class="w-5 h-5"></i> Equipment
                </a>
                <a href="loans.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl">
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
                    <h2 class="text-2xl font-bold">Admin Dashboard</h2>
                    <p class="text-gray-400">Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?></p>
                </div>
                <div class="flex items-center gap-4">
                    <?php $totalAlerts = count($overdueLoans) + count($dueSoonLoans); ?>
                    <?php if ($totalAlerts > 0): ?>
                    <button onclick="showNotifications()" class="p-2 rounded-lg bg-orange-500/20 relative animate-pulse">
                        <i data-lucide="bell" class="w-5 h-5 text-orange-400"></i>
                        <span class="absolute -top-1 -right-1 w-5 h-5 bg-orange-500 rounded-full text-xs flex items-center justify-center"><?= $totalAlerts ?></span>
                    </button>
                    <?php endif; ?>
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

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="glass-card rounded-2xl p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Total Equipment</p>
                            <p class="text-3xl font-bold"><?= $stats['total'] ?></p>
                        </div>
                        <div class="w-14 h-14 bg-blue-500/20 rounded-xl flex items-center justify-center">
                            <i data-lucide="package" class="w-7 h-7 text-blue-500"></i>
                        </div>
                    </div>
                </div>
                <div class="glass-card rounded-2xl p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Available Items</p>
                            <p class="text-3xl font-bold text-green-400"><?= $stats['available'] ?></p>
                        </div>
                        <div class="w-14 h-14 bg-green-500/20 rounded-xl flex items-center justify-center">
                            <i data-lucide="check-circle" class="w-7 h-7 text-green-500"></i>
                        </div>
                    </div>
                </div>
                <div class="glass-card rounded-2xl p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Issued Items</p>
                            <p class="text-3xl font-bold text-yellow-400"><?= $stats['issued'] ?></p>
                        </div>
                        <div class="w-14 h-14 bg-yellow-500/20 rounded-xl flex items-center justify-center">
                            <i data-lucide="send" class="w-7 h-7 text-yellow-500"></i>
                        </div>
                    </div>
                </div>
                <div class="glass-card rounded-2xl p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Overdue Items</p>
                            <p class="text-3xl font-bold text-red-400"><?= $stats['overdue'] ?></p>
                        </div>
                        <div class="w-14 h-14 bg-red-500/20 rounded-xl flex items-center justify-center">
                            <i data-lucide="alert-triangle" class="w-7 h-7 text-red-500"></i>
                        </div>
                    </div>
                </div>
                <div class="glass-card rounded-2xl p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Due Within 30 Min</p>
                            <p class="text-3xl font-bold text-orange-400"><?= count($dueSoonLoans) ?></p>
                        </div>
                        <div class="w-14 h-14 bg-orange-500/20 rounded-xl flex items-center justify-center">
                            <i data-lucide="clock" class="w-7 h-7 text-orange-500"></i>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($overdueLoans)): ?>
            <div class="glass rounded-2xl p-6 mb-8 border border-red-500/30">
                <div class="flex items-center gap-3 mb-4">
                    <i data-lucide="alert-triangle" class="w-6 h-6 text-red-500"></i>
                    <h3 class="text-lg font-semibold">Overdue Items</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-gray-400 text-sm border-b border-gray-700">
                                <th class="text-left py-3 px-4">Student</th>
                                <th class="text-left py-3 px-4">Equipment</th>
                                <th class="text-left py-3 px-4">Room</th>
                                <th class="text-left py-3 px-4">Deadline</th>
                                <th class="text-left py-3 px-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($overdueLoans, 0, 5) as $loan): ?>
                            <tr class="border-b border-gray-700/50">
                                <td class="py-3 px-4"><?= htmlspecialchars($loan['student_name']) ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($loan['equipment_name']) ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($loan['room_number']) ?></td>
                                <td class="py-3 px-4 text-red-400"><?= htmlspecialchars($loan['return_deadline']) ?></td>
                                <td class="py-3 px-4"><span class="badge badge-danger">Overdue</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="glass rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Recent Equipment</h3>
                        <a href="equipment.php" class="text-blue-500 hover:text-blue-400 text-sm">View All</a>
                    </div>
                    <div class="space-y-3">
                        <?php foreach (array_slice($equipment, 0, 5) as $eq): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-800/30 rounded-xl">
                            <div>
                                <p class="font-medium"><?= htmlspecialchars($eq['name']) ?></p>
                                <p class="text-gray-400 text-sm"><?= htmlspecialchars($eq['category']) ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-green-400"><?= $eq['available_quantity'] ?> available</p>
                                <p class="text-gray-400 text-sm"><?= $eq['issued_quantity'] ?> issued</p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="glass rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Active Loans</h3>
                        <a href="loans.php" class="text-blue-500 hover:text-blue-400 text-sm">View All</a>
                    </div>
                    <div class="space-y-3">
                        <?php foreach (array_slice($activeLoans, 0, 5) as $loan): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-800/30 rounded-xl">
                            <div>
                                <p class="font-medium"><?= htmlspecialchars($loan['student_name']) ?></p>
                                <p class="text-gray-400 text-sm"><?= htmlspecialchars($loan['equipment_name']) ?></p>
                            </div>
                            <div class="text-right">
                                <span class="badge badge-info">Active</span>
                                <p class="text-gray-400 text-xs mt-1"><?= htmlspecialchars($loan['room_number']) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div id="toast-container" class="fixed top-4 right-4 z-50"></div>
    
    <script>
        lucide.createIcons();
        
        function showToast(message, type = 'info') {
            const colors = { success: 'bg-green-500', error: 'bg-red-500', info: 'bg-blue-500' };
            const toast = document.createElement('div');
            toast.className = `toast ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg mb-2`;
            toast.textContent = message;
            document.getElementById('toast-container').appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }

        setInterval(() => {
            fetch('../api/check_reminders.php')
                .then(r => r.json())
                .then(data => {
                    if (data.overdue && data.overdue.length > 0) {
                        data.overdue.forEach(item => showToast(`URGENT: ${item.student_name} - ${item.equipment_name} is OVERDUE!`, 'error'));
                    }
                    if (data.due_soon && data.due_soon.length > 0) {
                        data.due_soon.forEach(item => showToast(`${item.equipment_name} due in ${item.minutes_left} min for ${item.student_name}`, 'warning'));
                    }
                });
        }, 60000);
    </script>

    <div id="chatbot" class="fixed bottom-6 right-6 z-50">
        <button onclick="toggleChatbot()" class="w-14 h-14 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full shadow-lg flex items-center justify-center hover:scale-110 transition">
            <i data-lucide="message-circle" class="w-7 h-7"></i>
        </button>
        <div id="chatWindow" class="hidden absolute bottom-16 right-0 w-80 glass rounded-2xl p-4" style="max-height:450px;overflow-y:auto;">
            <div class="flex justify-between items-center mb-3">
                <h3 class="font-bold">Sports Bot 🤖</h3>
                <button onclick="toggleChatbot()" class="p-1 hover:bg-gray-700 rounded"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
            <div id="chatMessages" class="space-y-2 mb-3" style="min-height:180px;max-height:220px;overflow-y:auto;">
                <p class="text-green-400 text-sm">Namaste! 🙏 Mai Sports Bot hoon.</p>
                <p class="text-gray-400 text-sm">Kaise help kar sakta hoon?</p>
            </div>
            <div id="chatSuggestions" class="flex flex-wrap gap-1 mb-3">
                <button onclick="sendMessage('Show equipment')" class="px-2 py-1 text-xs bg-blue-500/30 hover:bg-blue-500/50 rounded">📦 Equipment</button>
                <button onclick="sendMessage('My loans')" class="px-2 py-1 text-xs bg-blue-500/30 hover:bg-blue-500/50 rounded">📋 My Loans</button>
                <button onclick="sendMessage('Total status')" class="px-2 py-1 text-xs bg-blue-500/30 hover:bg-blue-500/50 rounded">📊 Status</button>
                <button onclick="sendMessage('Help')" class="px-2 py-1 text-xs bg-blue-500/30 hover:bg-blue-500/50 rounded">❓ Help</button>
            </div>
            <div class="flex gap-2">
                <input type="text" id="chatInput" placeholder="Type or click suggestions..." class="flex-1 px-3 py-2 rounded-lg text-sm" onkeyup="if(event.key==='Enter')sendMessage()">
                <button onclick="sendMessage()" class="px-3 py-2 bg-blue-500 rounded-lg"><i data-lucide="send" class="w-4 h-4"></i></button>
            </div>
        </div>
    </div>

    <script>
        let chatOpen = false;
        function toggleChatbot() {
            chatOpen = !chatOpen;
            document.getElementById('chatWindow').classList.toggle('hidden', !chatOpen);
        }
        function sendMessage(msg) {
            const input = document.getElementById('chatInput');
            const message = msg || input.value.trim();
            if (!message) return;
            const messages = document.getElementById('chatMessages');
            messages.innerHTML += `<p class="text-sm"><strong>You:</strong> ${message}</p>`;
            fetch('../api/chatbot.php?message=' + encodeURIComponent(message))
                .then(r => r.json())
                .then(data => {
                    messages.innerHTML += `<p class="text-sm text-green-400">Bot: ${data.reply}</p>`;
                    messages.scrollTop = messages.scrollHeight;
                    if (data.suggestions && data.suggestions.length > 0) {
                        showSuggestions(data.suggestions);
                    }
                });
            input.value = '';
        }
        function showSuggestions(suggestions) {
            const container = document.getElementById('chatSuggestions');
            container.innerHTML = suggestions.map(s => 
                `<button onclick="sendMessage('${s.replace(/'/g, "\\'")}')" class="px-2 py-1 text-xs bg-purple-500/30 hover:bg-purple-500/50 rounded">${s}</button>`
            ).join('');
        }

        function showNotifications() {
            document.getElementById('notificationModal').classList.remove('hidden');
        }
        function closeNotifications() {
            document.getElementById('notificationModal').classList.add('hidden');
        }
    </script>

    <!-- Notification Modal -->
    <div id="notificationModal" class="fixed inset-0 bg-black/70 hidden z-50 flex items-center justify-center p-4">
        <div class="glass rounded-2xl p-6 w-full max-w-lg">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold flex items-center gap-2">
                    <i data-lucide="bell" class="w-6 h-6 text-orange-500"></i> Due Notifications
                </h3>
                <button onclick="closeNotifications()" class="p-2 hover:bg-gray-700 rounded"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            
            <?php if (!empty($overdueLoans)): ?>
            <div class="mb-4">
                <h4 class="text-red-400 font-bold mb-2 flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4"></i> Overdue (<?= count($overdueLoans) ?>)
                </h4>
                <div class="space-y-2 max-h-40 overflow-y-auto">
                    <?php foreach ($overdueLoans as $loan): ?>
                    <div class="p-3 bg-red-500/10 rounded-lg border border-red-500/30">
                        <p class="font-medium"><?= htmlspecialchars($loan['equipment_name']) ?></p>
                        <p class="text-sm text-gray-400"><?= htmlspecialchars($loan['student_name']) ?> - <?= htmlspecialchars($loan['room_number']) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($dueSoonLoans)): ?>
            <div>
                <h4 class="text-orange-400 font-bold mb-2 flex items-center gap-2">
                    <i data-lucide="clock" class="w-4 h-4"></i> Due Within 30 Minutes (<?= count($dueSoonLoans) ?>)
                </h4>
                <div class="space-y-2 max-h-40 overflow-y-auto">
                    <?php foreach ($dueSoonLoans as $loan): ?>
                    <div class="p-3 bg-orange-500/10 rounded-lg border border-orange-500/30">
                        <p class="font-medium"><?= htmlspecialchars($loan['equipment_name']) ?></p>
                        <p class="text-sm text-gray-400"><?= htmlspecialchars($loan['student_name']) ?> - Due: <?= htmlspecialchars($loan['return_deadline']) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($overdueLoans) && empty($dueSoonLoans)): ?>
            <p class="text-gray-400 text-center py-8">No due items!</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>