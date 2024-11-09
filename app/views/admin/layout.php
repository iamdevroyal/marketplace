<!-- views/admin/layout.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?= htmlspecialchars($settings['site_name'] ?? 'Admin Panel') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-800 text-white">
            <div class="p-4">
                <h1 class="text-xl font-bold"><?= htmlspecialchars($settings['site_name'] ?? 'Admin Panel') ?></h1>
            </div>
            <nav class="mt-4">
                <a href="/admin/dashboard" class="block px-4 py-2 hover:bg-gray-700 <?= $currentPage === 'dashboard' ? 'bg-gray-700' : '' ?>">
                    Dashboard
                </a>
                <a href="/admin/users" class="block px-4 py-2 hover:bg-gray-700 <?= $currentPage === 'users' ? 'bg-gray-700' : '' ?>">
                    Users
                </a>
                <a href="/admin/products" class="block px-4 py-2 hover:bg-gray-700 <?= $currentPage === 'products' ? 'bg-gray-700' : '' ?>">
                    Products
                </a>
                <a href="/admin/orders" class="block px-4 py-2 hover:bg-gray-700 <?= $currentPage === 'orders' ? 'bg-gray-700' : '' ?>">
                    Orders
                </a>
                <a href="/admin/settings" class="block px-4 py-2 hover:bg-gray-700 <?= $currentPage === 'settings' ? 'bg-gray-700' : '' ?>">
                    Settings
                </a>
            </nav>
        </aside>

        <!-- Main content -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto">
            <div class="container mx-auto px-6 py-8">
                <?php include BASE_PATH . '/views/partials/flash.php'; ?>

                <?= $content ?>
            </div>
        </main>
    </div>
</body>
</html>