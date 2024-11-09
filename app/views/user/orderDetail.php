<?php
// views/user/order-detail.php
?>
<!DOCTYPE html>
<html>
<?php include BASE_PATH . '/views/partials/head.php'; ?>
<body>
    <?php include BASE_PATH . '/views/partials/nav.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center mb-6">
                <a href="/account" class="text-blue-600 hover:underline mr-4">← Back to Account</a>
                <h1 class="text-2xl font-bold">Order #<?= $order['order_number'] ?></h1>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="grid md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <h3 class="font-semibold mb-2">Order Details</h3>
                        <p class="text-gray-600">
                            Date: <?= date('F j, Y', strtotime($order['created_at'])) ?><br>
                            Status: <span class="font-medium"><?= ucfirst($order['status']) ?></span><br>
                            Store: <?= htmlspecialchars($order['vendor_name']) ?>
                        </p>
                    </div>
                    
                    <div>
                        <h3 class="font-semibold mb-2">Shipping Address</h3>
                        <p class="text-gray-600">
                            <?= htmlspecialchars($order['shipping_name']) ?><br>
                            <?= htmlspecialchars($order['shipping_address']) ?><br>
                            <?= htmlspecialchars($order['shipping_city']) ?>, 
                            <?= htmlspecialchars($order['shipping_state']) ?> 
                            <?= htmlspecialchars($order['shipping_zip']) ?>
                        </p>
                    </div>
                    
                    <div>
                        <h3 class="font-semibold mb-2">Payment Method</h3>
                        <p class="text-gray-600">
                            <?= ucfirst($order['payment_method']) ?><br>
                            <?php if ($order['payment_method'] === 'card'): ?>
                                ending in <?= substr($order['card_last4'], -4) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                
                <div class="border-t pt-6">
                    <h3 class="font-semibold mb-4">Order Items</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="px-4 py-2 text-left">Product</th>
                                    <th class="px-4 py-2 text-center">Quantity</th>
                                    <th class="px-4 py-2 text-right">Price</th>
                                    <th class="px-4 py-2 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order['items'] as $item): ?>
                                    <tr class="border-b">
                                        <td class="px-4 py-4">
                                            <div class="flex items-center">
                                                <img src="<?= htmlspecialchars($item['product_image']) ?>" 
                                                     alt="<?= htmlspecialchars($item['product_name']) ?>"
                                                     class="w-16 h-16 object-cover rounded mr-4">
                                                <div>
                                                    <h4 class="font-medium">
                                                        <?= htmlspecialchars($item['product_name']) ?>
                                                    </h4>
                                                    <?php if (!empty($item['variant'])): ?>
                                                        <p class="text-sm text-gray-600">
                                                            <?= htmlspecialchars($item['variant']) ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-center"><?= $item['quantity'] ?></td>
                                        <td class="px-4 py-4 text-right">
                                            $<?= number_format($item['price'], 2) ?>
                                        </td>
                                        <td class="px-4 py-4 text-right">
                                            $<?= number_format($item['price'] * $item['quantity'], 2) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="border-t">
                                    <td colspan="3" class="px-4 py-3 text-right">Subtotal:</td>
                                    <td class="px-4 py-3 text-right">
                                        $<?= number_format($order['subtotal'], 2) ?>
                                    </td>
                                </tr>
                                <?php if ($order['shipping_cost'] > 0): ?>
                                    <tr>
                                        <td colspan="3" class="px-4 py-3 text-right">Shipping:</td>
                                        <td class="px-4 py-3 text-right">
                                            $<?= number_format($order['shipping_cost'], 2) ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                <?php if ($order['tax'] > 0): ?>
                                    <tr>
                                        <td colspan="3" class="px-4 py-3 text-right">Tax:</td>
                                        <td class="px-4 py-3 text-right">
                                            $<?= number_format($order['tax'], 2) ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                <tr class="font-bold">
                                    <td colspan="3" class="px-4 py-3 text-right">Total:</td>
                                    <td class="px-4 py-3 text-right">
                                        $<?= number_format($order['total'], 2) ?>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            
            <?php if ($order['status'] === 'shipped'): ?>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="font-semibold mb-4">Tracking Information</h3>
                    <p>
                        Carrier: <?= htmlspecialchars($order['shipping_carrier']) ?><br>
                        Tracking Number: 
                        <a href="<?= $order['tracking_url'] ?>" 
                           class="text-blue-600 hover:underline" 
                           target="_blank">
                            <?= htmlspecialchars($order['tracking_number']) ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>