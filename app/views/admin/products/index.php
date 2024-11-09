<!-- views/admin/products/index.php -->
<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b flex justify-between items-center">
        <h2 class="text-lg font-medium">Products</h2>
        <div class="flex space-x-4">
            <form class="flex space-x-4">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Search products..." 
                       class="border rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                
                <select name="category" class="border rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Filter
                </button>
            </form>
            
            <a href="/admin/products/create" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                Add Product
            </a>
        </div>
    </div>
    
    <div class="p-6">
        <table class="w-full">
            <thead>
                <tr>
                    <th class="text-left py-2">Image</th>
                    <th class="text-left">Name</th>
                    <th class="text-left">Category</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Stock</th>
                    <th class="text-center">Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products['data'] as $product): ?>
                <tr class="border-t">
                    <td class="py-3">
                        <?php if ($product['image']): ?>
                            <img src="/uploads/products/<?= htmlspecialchars($product['image']) ?>" 
                                 alt="<?= htmlspecialchars($product['name']) ?>"
                                 class="w-16 h-16 object-cover rounded">
                        <?php else: ?>
                            <div class="w-16 h-16 bg-gray-200 rounded flex items-center justify-center">
                                <span class="text-gray-500">No image</span>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($product['name']) ?></td>
                    <td><?= htmlspecialchars($product['category_name']) ?></td>
                    <td class="text-right">$<?= number_format($product['price'], 2) ?></td>
                    <td class="text-right"><?= number_format($product['stock']) ?></td>
                    <td class="text-center">
                        <span class="px-2 py-1 text-xs rounded-full 
                            <?= $product['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                            <?= $product['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td class="text-right space-x-2">
                        <a href="/admin/products/edit/<?= $product['id'] ?>" 
                           class="text-blue-500 hover:text-blue-700">Edit</a>
                        <a href="#" onclick="deleteProduct(<?= $product['id'] ?>)"
                           class="text-red-500 hover:text-red-700">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($products['total_pages'] > 1): ?>
        <div class="mt-4 flex justify-center">
            <div class="flex space-x-2">
                <?php for ($i = 1; $i <= $products['total_pages']; $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>" 
                       class="px-3 py-1 rounded <?= $products['current_page'] === $i ? 'bg-blue-500 text-white' : 'bg-gray-200' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function deleteProduct(productId) {
    if (confirm('Are you sure you want to delete this product?')) {
        fetch(`/admin/products/delete/${productId}`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.error || 'Failed to delete product');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the product');
        });
    }
}
</script>