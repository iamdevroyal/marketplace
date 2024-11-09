<?php
// views/user/account.php
?>
<!DOCTYPE html>
<html>
<?php include BASE_PATH . '/views/partials/head.php'; ?>
<body>
    <?php include BASE_PATH . '/views/partials/nav.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <?php include BASE_PATH . '/views/partials/flash.php'; ?>
            
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold">My Account</h1>
                    <a href="/account/edit" 
                       class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Edit Profile
                    </a>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <h2 class="text-lg font-semibold mb-2">Account Details</h2>
                        <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($user['name']) ?></p>
                        <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                        <p><strong>Member Since:</strong> <?= date('F j, Y', strtotime($user['created_at'])) ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Order History -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold mb-4">Order History</h2>
                
                <?php if (empty($orders)): ?>
                    <p class="text-gray-600">No orders yet.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Order #
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Total
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            #<?= $order['order_number'] ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?= date('M j, Y', strtotime($order['created_at'])) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?= $order['status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                                   ($order['status'] === 'processing' ? 'bg-blue-100 text-blue-800' : 
                                                    'bg-gray-100 text-gray-800') ?>">
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            $<?= number_format($order['total'], 2) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="/order/<?= $order['id'] ?>" 
                                               class="text-blue-600 hover:text-blue-900">
                                                View Details
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include BASE_PATH . '/views/partials/footer.php'; ?>
</body>
</html>