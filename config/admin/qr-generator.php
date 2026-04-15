<?php
require_once '../config/config.php';
redirectIfNotLoggedIn();
if ($_SESSION['user_type'] !== 'admin') {
    header('Location: ../student/dashboard.php');
    exit;
}

$equipment = readJSON(EQUIPMENT_FILE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Generator - Sports Equipment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
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
        .qr-item { transition: all 0.3s ease; }
        .qr-item:hover { transform: scale(1.05); }
        @media print {
            body { background: white; }
            .glass { background: white; border: 1px solid #ddd; }
            .no-print { display: none; }
            .qr-grid { break-inside: avoid; }
        }
    </style>
</head>
<body class="text-white">
    <div class="flex min-h-screen">
        <aside class="w-64 glass fixed h-full p-4 no-print">
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
                <a href="students.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl">
                    <i data-lucide="users" class="w-5 h-5"></i> Students
                </a>
                <a href="qr-scanner.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl">
                    <i data-lucide="scan" class="w-5 h-5"></i> QR Scanner
                </a>
                <a href="qr-generator.php" class="sidebar-link active flex items-center gap-3 px-4 py-3 rounded-xl">
                    <i data-lucide="qr-code" class="w-5 h-5"></i> QR Generator
                </a>
                <a href="logout.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-red-400">
                    <i data-lucide="log-out" class="w-5 h-5"></i> Logout
                </a>
            </nav>
        </aside>

        <main class="flex-1 ml-64 p-8">
            <header class="flex justify-between items-center mb-8 no-print">
                <div>
                    <h2 class="text-2xl font-bold">QR Code Generator</h2>
                    <p class="text-gray-400">Generate & print QR codes for all equipment</p>
                </div>
                <button onclick="window.print()" class="px-6 py-3 rounded-xl bg-gradient-to-r from-blue-500 to-blue-600 text-white font-semibold flex items-center gap-2">
                    <i data-lucide="printer" class="w-5 h-5"></i> Print All
                </button>
            </header>

            <div class="glass rounded-2xl p-6">
                <div class="flex justify-between items-center mb-6 no-print">
                    <div class="relative max-w-md">
                        <input type="text" id="searchInput" placeholder="Search equipment..." 
                            class="pl-12 pr-4 py-3 rounded-xl w-full" onkeyup="filterEquipment()">
                        <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                    </div>
                </div>

                <div id="qrGrid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
                    <?php foreach ($equipment as $eq): ?>
                    <div class="qr-item glass-card rounded-xl p-4 text-center" data-name="<?= strtolower(htmlspecialchars($eq['name'])) ?>">
                        <div id="qr-<?= $eq['id'] ?>" class="flex justify-center mb-3"></div>
                        <p class="font-bold text-sm"><?= htmlspecialchars($eq['name']) ?></p>
                        <p class="text-gray-400 text-xs"><?= htmlspecialchars($eq['id']) ?></p>
                        <p class="text-green-400 text-xs mt-1"><?= $eq['available_quantity'] ?> available</p>
                        <button onclick="downloadQR('<?= $eq['id'] ?>', '<?= htmlspecialchars($eq['name']) ?>')" 
                            class="mt-2 px-3 py-1 rounded-lg bg-gray-700 hover:bg-gray-600 text-xs no-print">
                            Download
                        </button>
                    </div>
                    <script>
                        new QRCode(document.getElementById('qr-<?= $eq['id'] ?>'), {
                            text: '<?= $eq['id'] ?>',
                            width: 120,
                            height: 120,
                            colorDark: '#000000',
                            colorLight: '#ffffff',
                            correctLevel: QRCode.CorrectLevel.H
                        });
                    </script>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
        
        function filterEquipment() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            document.querySelectorAll('.qr-item').forEach(item => {
                if (item.dataset.name.includes(search)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        function downloadQR(id, name) {
            const canvas = document.getElementById('qr-' + id).querySelector('canvas');
            if (canvas) {
                const link = document.createElement('a');
                link.download = id + '_' + name.replace(/\s+/g, '_') + '.png';
                link.href = canvas.toDataURL();
                link.click();
            }
        }
    </script>
</body>
</html>