<!-- views/admin/dashboard.php -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-gray-500 text-sm font-medium">Total Users</h3>
        <p class="text-3xl font-bold"><?= number_format($total_users) ?></p>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-gray-500 text-sm font-medium">Total Orders</h3>
        <p class="text-3xl font-bold"><?= number_format($total_orders) ?></p>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-gray-500 text-sm font-medium">Total Revenue</h3>
        <p class="text-3xl font-bold">$<?= number_format($total_revenue, 2) ?></p>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-gray-500 text-sm font-medium">Total Products</h3>
        <p class="text-3xl font-bold"><?= number_format($total_products) ?></p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Recent Orders -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h2 class="text-lg font-medium">Recent Orders</h2>
        </div>
        <div class="p-6">
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="text-left">Order ID</th>
                        <th class="text-left">Customer</th>
                        <th class="text-right">Amount</th>
                        <th class="text-right">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_orders as $order): ?>
                    <tr>
                        <td class="py-2">#<?= $order['id'] ?></td>
                        <td><?= htmlspecialchars($order['customer_name']) ?></td>
                        <td class="text-right">$<?= number_format($order['total'], 2) ?></td>
                        <td class="text-right">
                            <span class="px-2 py-1 text-xs rounded-full 
                                <?= $order['status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                    ($order['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') ?>">
                                <?= ucfirst($order['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Popular Products -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h2 class="text-lg font-medium">Popular Products</h2>
        </div>
        <div class="p-6">
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="text-left">Product</th>
                        <th class="text-right">Sales</th>
                        <th class="text-right">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($popular_products as $product): ?>
                    <tr>
                        <td class="py-2"><?= htmlspecialchars($product['name']) ?></td>
                        <td class="text-right"><?= number_format($product['sales_count']) ?></td>
                        <td class="text-right">$<?= number_format($product['revenue'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>