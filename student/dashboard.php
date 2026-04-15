<?php
require_once '../config/config.php';
redirectIfNotLoggedIn();
if ($_SESSION['user_type'] !== 'student') {
    header('Location: ../admin/dashboard.php');
    exit;
}

$equipment = readJSON(EQUIPMENT_FILE);
$loans = readJSON(LOANS_FILE);
$userId = $_SESSION['user_id'];

$myLoans = array_filter($loans, fn($l) => $l['student_id'] === $userId && $l['status'] === 'active');
$myHistory = array_filter($loans, fn($l) => $l['student_id'] === $userId);

$now = time();
$overdueCount = 0;
$nearDeadlineCount = 0;

foreach ($myLoans as $loan) {
    $deadline = strtotime($loan['return_deadline']);
    $timeLeft = $deadline - $now;
    
    if ($timeLeft < 0) {
        $overdueCount++;
    } elseif ($timeLeft > 0 && $timeLeft <= 1800) {
        $nearDeadlineCount++;
    }
}

$totalAvailable = array_sum(array_column($equipment, 'available_quantity'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Sports Equipment</title>
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
        .glass-card { background: rgba(30, 41, 59, 0.5); backdrop-filter: blur(8px); border: 1px solid rgba(255, 255, 255, 0.08); }
        .sidebar-link { transition: all 0.3s ease; }
        .sidebar-link:hover, .sidebar-link.active { background: rgba(59, 130, 246, 0.2); border-left: 3px solid #3b82f6; }
        input, select { background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255, 255, 255, 0.1); }
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-5px); }
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
                <p class="text-center text-gray-400 text-sm mt-2">Student Panel</p>
            </div>
            <nav class="space-y-2">
                <a href="dashboard.php" class="sidebar-link active flex items-center gap-3 px-4 py-3 rounded-xl">
                    <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
                </a>
                <a href="equipment.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl">
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
                    <h2 class="text-2xl font-bold">Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?></h2>
                    <p class="text-gray-400">Room: <?= htmlspecialchars($_SESSION['room_number']) ?></p>
                </div>
                <div class="flex items-center gap-4">
                    <?php if ($overdueCount > 0 || $nearDeadlineCount > 0): ?>
                    <button onclick="showAlert()" class="p-2 rounded-lg bg-red-500/20 relative">
                        <i data-lucide="alert-triangle" class="w-5 h-5 text-red-500"></i>
                        <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full text-xs flex items-center justify-center">
                            <?= $overdueCount + $nearDeadlineCount ?>
                        </span>
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

            <?php if ($overdueCount > 0): ?>
            <div class="glass rounded-xl p-4 mb-6 border border-red-500/30">
                <div class="flex items-center gap-3 text-red-400">
                    <i data-lucide="alert-triangle" class="w-6 h-6"></i>
                    <span>You have <?= $overdueCount ?> overdue item(s)! Please return immediately.</span>
                </div>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="glass-card rounded-2xl p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Total Available</p>
                            <p class="text-3xl font-bold"><?= $totalAvailable ?></p>
                        </div>
                        <div class="w-14 h-14 bg-blue-500/20 rounded-xl flex items-center justify-center">
                            <i data-lucide="package" class="w-7 h-7 text-blue-500"></i>
                        </div>
                    </div>
                </div>
                <div class="glass-card rounded-2xl p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">My Issued Items</p>
                            <p class="text-3xl font-bold text-yellow-400"><?= count($myLoans) ?></p>
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
                            <p class="text-3xl font-bold text-red-400"><?= $overdueCount ?></p>
                        </div>
                        <div class="w-14 h-14 bg-red-500/20 rounded-xl flex items-center justify-center">
                            <i data-lucide="alert-triangle" class="w-7 h-7 text-red-500"></i>
                        </div>
                    </div>
                </div>
                <div class="glass-card rounded-2xl p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">My History</p>
                            <p class="text-3xl font-bold text-green-400"><?= count($myHistory) ?></p>
                        </div>
                        <div class="w-14 h-14 bg-green-500/20 rounded-xl flex items-center justify-center">
                            <i data-lucide="history" class="w-7 h-7 text-green-500"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="glass rounded-2xl p-6">
                <h3 class="text-lg font-semibold mb-4">My Active Loans</h3>
                <?php if (empty($myLoans)): ?>
                <p class="text-gray-400">You have no active loans</p>
                <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($myLoans as $loan): 
                        $isOverdue = strtotime($loan['return_deadline']) < $now;
                    ?>
                    <div class="flex items-center justify-between p-4 bg-gray-800/30 rounded-xl <?= $isOverdue ? 'border border-red-500/30' : '' ?>">
                        <div>
                            <p class="font-medium"><?= htmlspecialchars($loan['equipment_name']) ?></p>
                            <p class="text-gray-400 text-sm">Issued: <?= htmlspecialchars($loan['issue_time']) ?></p>
                        </div>
                        <div class="text-right">
                            <?php if ($isOverdue): ?>
                            <span class="badge badge-danger">Overdue!</span>
                            <?php else: ?>
                            <span class="countdown text-yellow-400" data-deadline="<?= $loan['return_deadline'] ?>">--:-- left</span>
                            <?php endif; ?>
                            <form method="POST" action="../api/return_item.php" class="mt-2">
                                <input type="hidden" name="loan_id" value="<?= $loan['id'] ?>">
                                <button type="submit" class="px-4 py-2 rounded-lg bg-green-500/20 text-green-400 hover:bg-green-500/30 text-sm">
                                    Return
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <div id="toast-container" class="fixed top-4 right-4 z-50"></div>
    <div id="alertModal" class="fixed inset-0 modal-backdrop hidden z-50 flex items-center justify-center p-4">
        <div class="glass rounded-2xl p-6 w-full max-w-sm">
            <div class="text-center">
                <i data-lucide="alert-triangle" class="w-16 h-16 text-red-500 mx-auto mb-4"></i>
                <h3 class="text-xl font-bold mb-2">Attention!</h3>
                <p class="text-gray-400 mb-4">
                    <?php if ($overdueCount > 0): ?>
                    You have <?= $overdueCount ?> overdue item(s). Please return them immediately to avoid penalties.
                    <?php elseif ($nearDeadlineCount > 0): ?>
                    You have <?= $nearDeadlineCount ?> item(s) due soon. Return before deadline.
                    <?php endif; ?>
                </p>
                <button onclick="document.getElementById('alertModal').classList.add('hidden')" class="w-full py-3 rounded-xl bg-red-500 text-white font-semibold">
                    I Understand
                </button>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
        
        function showAlert() {
            document.getElementById('alertModal').classList.remove('hidden');
        }

        function showToast(message, type = 'info') {
            const colors = { success: 'bg-green-500', error: 'bg-red-500', info: 'bg-blue-500' };
            const toast = document.createElement('div');
            toast.className = `toast ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg mb-2`;
            toast.textContent = message;
            document.getElementById('toast-container').appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }

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

        setInterval(() => {
            fetch('../api/check_reminders.php')
                .then(r => r.json())
                .then(data => {
                    if (data.overdue && data.overdue.length > 0) {
                        data.overdue.forEach(item => {
                            if (item.student_id === '<?= $userId ?>') {
                                showToast(`URGENT: ${item.equipment_name} is OVERDUE! Return now!`, 'error');
                            }
                        });
                    }
                    if (data.due_soon && data.due_soon.length > 0) {
                        data.due_soon.forEach(item => {
                            if (item.student_id === '<?= $userId ?>') {
                                showToast(`${item.equipment_name} due in ${item.minutes_left} min!`, 'warning');
                            }
                        });
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
                <button onclick="sendMessage('Deadline')" class="px-2 py-1 text-xs bg-blue-500/30 hover:bg-blue-500/50 rounded">⏰ Deadline</button>
                <button onclick="sendMessage('Help')" class="px-2 py-1 text-xs bg-blue-500/30 hover:bg-blue-500/50 rounded">❓ Help</button>
            </div>
            <div class="flex gap-2">
                <input type="text" id="chatInput" placeholder="Type or click..." class="flex-1 px-3 py-2 rounded-lg text-sm" onkeyup="if(event.key==='Enter')sendMessage()">
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
    </script>
</body>
</html>