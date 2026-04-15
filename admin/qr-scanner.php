<?php
require_once '../config/config.php';
redirectIfNotLoggedIn();
if ($_SESSION['user_type'] !== 'admin') {
    header('Location: ../student/dashboard.php');
    exit;
}

$equipment = readJSON(EQUIPMENT_FILE);
$loans = readJSON(LOANS_FILE);
$activeLoans = array_filter($loans, fn($l) => $l['status'] === 'active');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Scanner - Sports Equipment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Poppins', 'sans-serif'] } } } }
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
                <h1 class="text-xl font-bold text-center"><i data-lucide="trophy" class="inline w-6 h-6 text-blue-500"></i> Sports Hub</h1>
            </div>
            <nav class="space-y-2">
                <a href="dashboard.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl"><i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard</a>
                <a href="equipment.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl"><i data-lucide="package" class="w-5 h-5"></i> Equipment</a>
                <a href="loans.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl"><i data-lucide="clock" class="w-5 h-5"></i> Loans</a>
                <a href="students.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl"><i data-lucide="users" class="w-5 h-5"></i> Students</a>
                <a href="qr-scanner.php" class="sidebar-link active flex items-center gap-3 px-4 py-3 rounded-xl"><i data-lucide="scan" class="w-5 h-5"></i> QR Scanner</a>
                <a href="qr-generator.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl"><i data-lucide="qr-code" class="w-5 h-5"></i> QR Generator</a>
                <a href="logout.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-red-400"><i data-lucide="log-out" class="w-5 h-5"></i> Logout</a>
            </nav>
        </aside>

        <main class="flex-1 ml-64 p-8">
            <header class="mb-8">
                <h2 class="text-2xl font-bold">QR Scanner with Camera</h2>
                <p class="text-gray-400">Scan QR code using camera</p>
            </header>

            <?php if (isset($_SESSION['success'])): ?><div class="bg-green-500/20 text-green-400 px-4 py-3 rounded-lg mb-4"><?= $_SESSION['success'] ?></div><?php unset($_SESSION['success']); endif; ?>
            <?php if (isset($_SESSION['error'])): ?><div class="bg-red-500/20 text-red-400 px-4 py-3 rounded-lg mb-4"><?= $_SESSION['error'] ?></div><?php unset($_SESSION['error']); endif; ?>

            <div class="glass rounded-2xl p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Live Camera Scanner</h3>
                    <button onclick="toggleCamera()" id="camBtn" class="px-4 py-2 rounded-lg bg-blue-500 text-white flex items-center gap-2">
                        <i data-lucide="camera"></i> Start Camera
                    </button>
                </div>
                
                <video id="cameraVideo" playsinline style="width: 100%; max-width: 500px; height: 350px; background: #000; border-radius: 12px; display: none; object-fit: cover;"></video>
                <canvas id="cameraCanvas" style="display: none;"></canvas>
                
                <p id="cameraStatus" class="text-center text-sm mt-3 text-gray-400">Click "Start Camera" to begin scanning</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="glass rounded-2xl p-6">
                    <h3 class="text-lg font-semibold mb-4">Or Upload QR Image</h3>
                    <input type="file" id="qrImage" accept="image/*" onchange="scanImage(this)" class="w-full px-4 py-3 rounded-xl">
                </div>

                <div class="glass rounded-2xl p-6">
                    <h3 class="text-lg font-semibold mb-4">Or Enter Equipment ID</h3>
                    <div class="flex gap-2">
                        <input type="text" id="eqId" placeholder="EQP001" class="flex-1 px-4 py-3 rounded-xl bg-gray-800 border border-gray-600 text-red-400">
                        <button onclick="searchEq()" class="px-4 py-3 rounded-xl bg-green-500 text-white">Search</button>
                    </div>
                </div>
            </div>

            <div id="eqResult" class="hidden glass rounded-2xl p-6 mt-6">
                <h4 id="eqName" class="text-2xl font-bold"></h4>
                <p id="eqCat" class="text-gray-400"></p>
                <p id="eqHolder" class="text-yellow-400 mt-2"></p>
                <p id="eqAvail" class="text-3xl font-bold text-green-400 mt-2"></p>
                <form method="POST" action="../api/issue_item.php" class="mt-4 flex gap-4">
                    <input type="hidden" name="equipment_id" id="eqIdVal">
                    <input type="text" name="student_id" required placeholder="Student Name/Room/ID" class="flex-1 px-4 py-3 rounded-xl bg-gray-800 border border-gray-600 text-red-400">
                    <button type="submit" class="px-6 py-3 rounded-xl bg-blue-500 text-white font-bold">Issue</button>
                </form>
            </div>

            <div class="glass rounded-2xl p-6 mt-6">
                <h3 class="text-lg font-semibold mb-4">All Equipment</h3>
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    <?php foreach ($equipment as $eq): $h=''; foreach($activeLoans as $l) if($l['equipment_id']==$eq['id']){$h=$l['student_name'].' ('.$l['room_number'].')';break;} ?>
                    <div class="flex items-center justify-between p-3 bg-gray-800/30 rounded-xl">
                        <div><p class="font-medium"><?= htmlspecialchars($eq['name']) ?></p><p class="text-gray-400 text-sm"><?= htmlspecialchars($eq['id']) ?></p>
                        <?php if($h): ?><p class="text-yellow-400 text-sm">With: <?= $h ?></p><?php endif; ?></div>
                        <span class="text-green-400"><?= $eq['available_quantity'] ?> avl</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="glass rounded-2xl p-6 mt-6">
                <h3 class="text-lg font-semibold mb-4">Active Loans</h3>
                <div class="space-y-2">
                    <?php if(empty($activeLoans)): ?><p class="text-gray-400">No active loans</p>
                    <?php else: foreach($activeLoans as $loan): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-800/30 rounded-xl">
                        <div><p class="font-medium"><?= htmlspecialchars($loan['equipment_name']) ?></p><p class="text-gray-400 text-sm"><?= htmlspecialchars($loan['student_name']) ?> • <?= htmlspecialchars($loan['room_number']) ?></p></div>
                        <form method="POST" action="../api/return_item.php">
                            <input type="hidden" name="loan_id" value="<?= $loan['id'] ?>">
                            <button type="submit" class="px-4 py-2 rounded bg-green-500/20 text-green-400">Return</button>
                        </form>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    <script>
        lucide.createIcons();
        let video, canvas, stream, scanning = false;

        function toggleCamera() {
            if (scanning) {
                stopCamera();
            } else {
                startCamera();
            }
        }

        function startCamera() {
            video = document.getElementById('cameraVideo');
            canvas = document.getElementById('cameraCanvas');
            const btn = document.getElementById('camBtn');
            const status = document.getElementById('cameraStatus');
            
            btn.innerHTML = '<i data-lucide="camera"></i> Stop Camera';
            status.innerHTML = '<span class="text-yellow-400">Requesting camera access...</span>';
            lucide.createIcons();

            // Try multiple camera options
            const constraints = [
                { video: { facingMode: { exact: "environment" } } },
                { video: { facingMode: "environment" } },
                { video: { facingMode: "user" } },
                { video: true }
            ];

            tryCamera(constraints, 0, status, btn);
        }

        function tryCamera(options, index, status, btn) {
            if (index >= options.length) {
                status.innerHTML = '<span class="text-red-400">Camera blocked! Allow camera in browser address bar (🔒) or use below options.</span>';
                btn.innerHTML = '<i data-lucide="camera"></i> Start Camera';
                btn.className = 'px-4 py-2 rounded-lg bg-blue-500 text-white flex items-center gap-2';
                lucide.createIcons();
                return;
            }

            navigator.mediaDevices.getUserMedia(options[index])
                .then(s => {
                    stream = s;
                    video.srcObject = s;
                    video.style.display = 'block';
                    video.play();
                    scanning = true;
                    status.innerHTML = '<span class="text-green-400">✓ Camera active! Point at QR code</span>';
                    btn.innerHTML = '<i data-lucide="camera"></i> Stop Camera';
                    btn.className = 'px-4 py-2 rounded-lg bg-red-500 text-white flex items-center gap-2';
                    lucide.createIcons();
                    scanLoop();
                })
                .catch(e => {
                    tryCamera(options, index + 1, status, btn);
                });
        }

        function stopCamera() {
            scanning = false;
            if (stream) stream.getTracks().forEach(t => t.stop());
            video = document.getElementById('cameraVideo');
            video.style.display = 'none';
            const btn = document.getElementById('camBtn');
            const status = document.getElementById('cameraStatus');
            btn.innerHTML = '<i data-lucide="camera"></i> Start Camera';
            btn.className = 'px-4 py-2 rounded-lg bg-blue-500 text-white flex items-center gap-2';
            status.textContent = 'Camera stopped';
            lucide.createIcons();
        }

        function scanLoop() {
            if (!scanning) return;
            try {
                const ctx = canvas.getContext('2d');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                ctx.drawImage(video, 0, 0);
                const code = jsQR(ctx.getImageData(0, 0, canvas.width, canvas.height).data, canvas.width, canvas.height);
                if (code) {
                    document.getElementById('eqId').value = code.data;
                    searchEq();
                    stopCamera();
                    return;
                }
            } catch(e) {}
            requestAnimationFrame(scanLoop);
        }

        function scanImage(input) {
            if(input.files && input.files[0]) {
                const img = new Image();
                img.onload = function() {
                    canvas.width = img.width; canvas.height = img.height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0);
                    try {
                        const code = jsQR(ctx.getImageData(0,0,canvas.width,canvas.height).data, canvas.width, canvas.height);
                        if(code) { document.getElementById('eqId').value = code.data; searchEq(); }
                        else alert('No QR code found');
                    } catch(e) { alert('Error: '+e.message); }
                };
                img.src = URL.createObjectURL(input.files[0]);
            }
        }

        function searchEq() {
            const id = document.getElementById('eqId').value.trim().toUpperCase();
            if(!id) return;
            fetch('../api/get_equipment.php?id='+id).then(r=>r.json()).then(d=>{
                if(d && !d.error) {
                    document.getElementById('eqName').textContent = d.name;
                    document.getElementById('eqCat').textContent = d.category;
                    document.getElementById('eqAvail').textContent = d.available_quantity + ' available';
                    document.getElementById('eqIdVal').value = d.id;
                    let h = 'Available';
                    <?php foreach($activeLoans as $l): ?>
                    if(d.id === '<?= $l['equipment_id'] ?>') h = 'With: <?= addslashes($l['student_name']) ?> (<?= $l['room_number'] ?>)';
                    <?php endforeach; ?>
                    document.getElementById('eqHolder').textContent = h;
                    document.getElementById('eqResult').classList.remove('hidden');
                } else { alert('Not found'); }
            });
        }

        document.getElementById('eqId').addEventListener('keyup', function(e) {
            if(e.key === 'Enter') searchEq();
        });
    </script>
</body>
</html>