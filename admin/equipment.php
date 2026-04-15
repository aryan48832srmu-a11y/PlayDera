<?php
require_once '../config/config.php';
redirectIfNotLoggedIn();
if ($_SESSION['user_type'] !== 'admin') {
    header('Location: ../student/dashboard.php');
    exit;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = sanitize($_POST['name'] ?? '');
        $category = sanitize($_POST['category'] ?? '');
        $quantity = intval($_POST['quantity'] ?? 0);
        $description = sanitize($_POST['description'] ?? '');
        
        if (empty($name) || $quantity <= 0) {
            $message = 'Please fill all required fields';
            $messageType = 'error';
        } else {
            $equipment = readJSON(EQUIPMENT_FILE);
            $newId = 'EQP' . str_pad(count($equipment) + 1, 3, '0', STR_PAD_LEFT);
            
            $equipment[] = [
                'id' => $newId,
                'name' => $name,
                'category' => $category,
                'total_quantity' => $quantity,
                'available_quantity' => $quantity,
                'issued_quantity' => 0,
                'description' => $description,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            writeJSON(EQUIPMENT_FILE, $equipment);
            $message = 'Equipment added successfully!';
            $messageType = 'success';
        }
    } elseif ($action === 'edit') {
        $id = $_POST['id'] ?? '';
        $name = sanitize($_POST['name'] ?? '');
        $category = sanitize($_POST['category'] ?? '');
        $quantity = intval($_POST['quantity'] ?? 0);
        $description = sanitize($_POST['description'] ?? '');
        
        $equipment = readJSON(EQUIPMENT_FILE);
        foreach ($equipment as &$eq) {
            if ($eq['id'] === $id) {
                $oldTotal = $eq['total_quantity'];
                $eq['name'] = $name;
                $eq['category'] = $category;
                $eq['description'] = $description;
                
                if ($quantity > $oldTotal) {
                    $diff = $quantity - $oldTotal;
                    $eq['total_quantity'] = $quantity;
                    $eq['available_quantity'] += $diff;
                } elseif ($quantity < $oldTotal) {
                    $diff = $oldTotal - $quantity;
                    $eq['total_quantity'] = $quantity;
                    $eq['available_quantity'] = max(0, $eq['available_quantity'] - $diff);
                }
                break;
            }
        }
        
        writeJSON(EQUIPMENT_FILE, $equipment);
        $message = 'Equipment updated successfully!';
        $messageType = 'success';
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        $equipment = readJSON(EQUIPMENT_FILE);
        $equipment = array_filter($equipment, fn($eq) => $eq['id'] !== $id);
        writeJSON(EQUIPMENT_FILE, array_values($equipment));
        $message = 'Equipment deleted successfully!';
        $messageType = 'success';
    }
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
    <title>Equipment Management - Sports Equipment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
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
        input:focus, select:focus { outline: none; border-color: #3b82f6; }
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
                <a href="equipment.php" class="sidebar-link active flex items-center gap-3 px-4 py-3 rounded-xl">
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
                    <h2 class="text-2xl font-bold">Equipment Management</h2>
                    <p class="text-gray-400">Manage all sports equipment inventory</p>
                </div>
                <button onclick="openModal('addModal')" class="btn-primary px-6 py-3 rounded-xl text-white font-semibold flex items-center gap-2">
                    <i data-lucide="plus" class="w-5 h-5"></i> Add Equipment
                </button>
            </header>

            <?php if ($message): ?>
            <div class="glass rounded-xl p-4 mb-6 <?= $messageType === 'error' ? 'border border-red-500/50 text-red-400' : 'border border-green-500/50 text-green-400' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>

            <div class="glass rounded-2xl p-6">
                <div class="flex justify-between items-center mb-6">
                    <div class="relative">
                        <input type="text" id="searchInput" placeholder="Search equipment..." value="<?= htmlspecialchars($search) ?>"
                            class="pl-12 pr-4 py-3 rounded-xl w-80"
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
                                <th class="text-left py-3 px-4">Category</th>
                                <th class="text-left py-3 px-4">Total</th>
                                <th class="text-left py-3 px-4">Available</th>
                                <th class="text-left py-3 px-4">Issued</th>
                                <th class="text-left py-3 px-4">Status</th>
                                <th class="text-left py-3 px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($equipment as $eq): ?>
                            <tr class="border-b border-gray-700/50 hover:bg-gray-800/30">
                                <td class="py-3 px-4 font-mono text-sm"><?= htmlspecialchars($eq['id']) ?></td>
                                <td class="py-3 px-4 font-medium"><?= htmlspecialchars($eq['name']) ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($eq['category']) ?></td>
                                <td class="py-3 px-4"><?= $eq['total_quantity'] ?></td>
                                <td class="py-3 px-4 text-green-400"><?= $eq['available_quantity'] ?></td>
                                <td class="py-3 px-4 text-yellow-400"><?= $eq['issued_quantity'] ?></td>
                                <td class="py-3 px-4">
                                    <?php if ($eq['available_quantity'] > 0): ?>
                                    <span class="badge badge-success">Available</span>
                                    <?php else: ?>
                                    <span class="badge badge-danger">Out of Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex gap-2">
                                        <button onclick="viewQR('<?= $eq['id'] ?>', '<?= htmlspecialchars($eq['name']) ?>')" class="p-2 rounded-lg bg-gray-700/50 hover:bg-gray-600/50" title="View QR">
                                            <i data-lucide="qr-code" class="w-4 h-4"></i>
                                        </button>
                                        <button onclick="editEquipment('<?= $eq['id'] ?>', '<?= htmlspecialchars($eq['name']) ?>', '<?= htmlspecialchars($eq['category']) ?>', <?= $eq['total_quantity'] ?>, '<?= htmlspecialchars($eq['description']) ?>')" class="p-2 rounded-lg bg-blue-500/20 hover:bg-blue-500/30" title="Edit">
                                            <i data-lucide="edit" class="w-4 h-4 text-blue-500"></i>
                                        </button>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $eq['id'] ?>">
                                            <button type="submit" onclick="return confirm('Delete this equipment?')" class="p-2 rounded-lg bg-red-500/20 hover:bg-red-500/30" title="Delete">
                                                <i data-lucide="trash" class="w-4 h-4 text-red-500"></i>
                                            </button>
                                        </form>
                                    </div>
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

    <div id="addModal" class="fixed inset-0 modal-backdrop hidden z-50 flex items-center justify-center p-4">
        <div class="glass rounded-2xl p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold">Add Equipment</h3>
                <button onclick="closeModal('addModal')" class="p-2 rounded-lg hover:bg-gray-700/50">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add">
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Equipment Name *</label>
                    <input type="text" name="name" required class="w-full px-4 py-3 rounded-xl" placeholder="e.g., Football">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Category</label>
                    <select name="category" class="w-full px-4 py-3 rounded-xl">
                        <option value="Ball Sports">Ball Sports</option>
                        <option value="Cricket">Cricket</option>
                        <option value="Racket Sports">Racket Sports</option>
                        <option value="Indoor Games">Indoor Games</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Quantity *</label>
                    <input type="number" name="quantity" required min="1" class="w-full px-4 py-3 rounded-xl" placeholder="10">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Description</label>
                    <textarea name="description" class="w-full px-4 py-3 rounded-xl" rows="2"></textarea>
                </div>
                <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-blue-500 to-blue-600 text-white font-semibold">Add Equipment</button>
            </form>
        </div>
    </div>

    <div id="editModal" class="fixed inset-0 modal-backdrop hidden z-50 flex items-center justify-center p-4">
        <div class="glass rounded-2xl p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold">Edit Equipment</h3>
                <button onclick="closeModal('editModal')" class="p-2 rounded-lg hover:bg-gray-700/50">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editId">
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Equipment Name</label>
                    <input type="text" name="name" id="editName" required class="w-full px-4 py-3 rounded-xl">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Category</label>
                    <select name="category" id="editCategory" class="w-full px-4 py-3 rounded-xl">
                        <option value="Ball Sports">Ball Sports</option>
                        <option value="Cricket">Cricket</option>
                        <option value="Racket Sports">Racket Sports</option>
                        <option value="Indoor Games">Indoor Games</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Total Quantity</label>
                    <input type="number" name="quantity" id="editQuantity" min="1" required class="w-full px-4 py-3 rounded-xl">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Description</label>
                    <textarea name="description" id="editDescription" class="w-full px-4 py-3 rounded-xl" rows="2"></textarea>
                </div>
                <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-blue-500 to-blue-600 text-white font-semibold">Update Equipment</button>
            </form>
        </div>
    </div>

    <div id="qrModal" class="fixed inset-0 modal-backdrop hidden z-50 flex items-center justify-center p-4">
        <div class="glass rounded-2xl p-6 w-full max-w-sm text-center">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold" id="qrTitle">QR Code</h3>
                <button onclick="closeModal('qrModal')" class="p-2 rounded-lg hover:bg-gray-700/50">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div id="qrcode" class="flex justify-center mb-4"></div>
            <p class="text-gray-400 text-sm mb-4" id="qrText"></p>
            <button onclick="downloadQR()" class="w-full py-3 rounded-xl bg-gradient-to-r from-green-500 to-green-600 text-white font-semibold">
                <i data-lucide="download" class="inline w-5 h-5 mr-2"></i> Download QR
            </button>
        </div>
    </div>

    <script>
        lucide.createIcons();
        
        function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
        function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
        
        let currentQR = '';
        
        function viewQR(id, name) {
            currentQR = id;
            document.getElementById('qrTitle').textContent = name;
            document.getElementById('qrText').textContent = 'ID: ' + id;
            document.getElementById('qrcode').innerHTML = '';
            new QRCode(document.getElementById('qrcode'), { text: id, width: 180, height: 180 });
            openModal('qrModal');
        }
        
        function downloadQR() {
            const canvas = document.getElementById('qrcode').querySelector('canvas');
            if (canvas) {
                const link = document.createElement('a');
                link.download = currentQR + '.png';
                link.href = canvas.toDataURL();
                link.click();
            }
        }
        
        function editEquipment(id, name, category, quantity, description) {
            document.getElementById('editId').value = id;
            document.getElementById('editName').value = name;
            document.getElementById('editCategory').value = category;
            document.getElementById('editQuantity').value = quantity;
            document.getElementById('editDescription').value = description;
            openModal('editModal');
        }
        
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