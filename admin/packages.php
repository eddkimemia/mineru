<?php
// admin/packages.php
require_once 'header.php';

// Handle package addition/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $daily_profit = filter_input(INPUT_POST, 'daily_profit', FILTER_VALIDATE_FLOAT);
    $duration_days = filter_input(INPUT_POST, 'duration_days', FILTER_VALIDATE_INT);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (isset($_POST['add_package'])) {
        $daily_return = ($daily_profit / $price) * 100;
        $stmt = $pdo->prepare("INSERT INTO mining_packages (name, price, daily_profit, daily_return_percentage, duration_days, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $price, $daily_profit, $daily_return, $duration_days, $is_active]);
        echo "<div class='bg-green-50 text-green-600 p-3 rounded mb-4'>New package added.</div>";
    } elseif (isset($_POST['update_package'])) {
        $package_id = filter_input(INPUT_POST, 'package_id', FILTER_VALIDATE_INT);
        $daily_return = ($daily_profit / $price) * 100;
        $stmt = $pdo->prepare("UPDATE mining_packages SET name = ?, price = ?, daily_profit = ?, daily_return_percentage = ?, duration_days = ?, is_active = ? WHERE id = ?");
        $stmt->execute([$name, $price, $daily_profit, $daily_return, $duration_days, $is_active, $package_id]);
        echo "<div class='bg-green-50 text-green-600 p-3 rounded mb-4'>Package updated.</div>";
    }
}

try {
    $packages = $pdo->query("SELECT * FROM mining_packages ORDER BY price ASC")->fetchAll();
} catch (PDOException $e) {
    echo "<div class='bg-red-50 text-red-600 p-4 rounded'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

<div class="mb-8 flex justify-between items-center">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Mining Packages</h1>
        <p class="text-gray-600">Manage available mining plans.</p>
    </div>
    <button onclick="document.getElementById('addPackageModal').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded font-semibold hover:bg-blue-700 transition">
        <i class="ri-add-line mr-1"></i> Add Package
    </button>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($packages as $p): ?>
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <div class="flex justify-between items-start mb-4">
            <h2 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($p['name']); ?></h2>
            <span class="px-2 py-0.5 rounded-full text-xs <?php echo $p['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'; ?>">
                <?php echo $p['is_active'] ? 'Active' : 'Inactive'; ?>
            </span>
        </div>

        <form method="POST" action="" class="space-y-3">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="package_id" value="<?php echo $p['id']; ?>">

            <div>
                <label class="block text-xs font-medium text-gray-500 uppercase">Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($p['name']); ?>" required class="w-full text-sm border-b border-gray-200 py-1 outline-none focus:border-blue-500">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Price ($)</label>
                    <input type="number" name="price" step="0.01" value="<?php echo $p['price']; ?>" required class="w-full text-sm border-b border-gray-200 py-1 outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Daily Profit ($)</label>
                    <input type="number" name="daily_profit" step="0.01" value="<?php echo $p['daily_profit']; ?>" required class="w-full text-sm border-b border-gray-200 py-1 outline-none focus:border-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Duration (Days)</label>
                    <input type="number" name="duration_days" value="<?php echo $p['duration_days']; ?>" required class="w-full text-sm border-b border-gray-200 py-1 outline-none focus:border-blue-500">
                </div>
                <div class="flex items-center pt-4">
                    <input type="checkbox" name="is_active" id="active_<?php echo $p['id']; ?>" <?php echo $p['is_active'] ? 'checked' : ''; ?> class="mr-2">
                    <label for="active_<?php echo $p['id']; ?>" class="text-sm text-gray-700">Is Active</label>
                </div>
            </div>

            <button type="submit" name="update_package" class="w-full bg-gray-800 text-white py-1.5 rounded text-sm font-semibold hover:bg-black transition mt-4">Update Package</button>
        </form>
    </div>
    <?php endforeach; ?>
</div>

<!-- Add Modal -->
<div id="addPackageModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-gray-800">Add New Mining Package</h2>
            <button onclick="document.getElementById('addPackageModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600"><i class="ri-close-line text-2xl"></i></button>
        </div>

        <form method="POST" action="" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Package Name</label>
                <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 outline-none">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Price ($)</label>
                    <input type="number" name="price" step="0.01" required class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Daily Profit ($)</label>
                    <input type="number" name="daily_profit" step="0.01" required class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Duration (Days)</label>
                <input type="number" name="duration_days" required class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 outline-none">
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="is_active" id="new_active" checked class="mr-2">
                <label for="new_active" class="text-sm text-gray-700">Available for purchase immediately</label>
            </div>

            <button type="submit" name="add_package" class="w-full bg-blue-600 text-white py-2 rounded font-semibold hover:bg-blue-700 transition mt-4">Create Package</button>
        </form>
    </div>
</div>

<?php require_once 'footer.php'; ?>