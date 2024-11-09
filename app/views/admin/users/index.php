<!-- views/admin/users/index.php -->
<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b flex justify-between items-center">
        <h2 class="text-lg font-medium">Users</h2>
        <div class="flex space-x-4">
            <form class="flex">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Search users..." 
                       class="border rounded-l px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-r hover:bg-blue-600">
                    Search
                </button>
            </form>
        </div>
    </div>
    <div class="p-6">
        <table class="w-full">
            <thead>
                <tr>
                    <th class="text-left py-2">
                        <a href="?sort=name&order=<?= $sort === 'name' && $order === 'asc' ? 'desc' : 'asc' ?>" 
                           class="flex items-center">
                            Name
                            <?php if ($sort === 'name'): ?>
                                <span class="ml-1"><?= $order === 'asc' ? '↑' : '↓' ?></span>
                            <?php endif; ?>
                        </a>
                    </th>
                    <th class="text-left">Email</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Role</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users['data'] as $user): ?>
                <tr class="border-t">
                    <td class="py-3"><?= htmlspecialchars($user['name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td class="text-center">
                        <span class="px-2 py-1 text-xs rounded-full 
                            <?= $user['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                            <?= ucfirst($user['status']) ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <?= $user['is_admin'] ? 'Admin' : 'User' ?>
                    </td>
                    <td class="text-right">
                        <a href="/admin/users/edit/<?= $user['id'] ?>" 
                           class="text-blue-500 hover:text-blue-700">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($users['total_pages'] > 1): ?>
        <div class="mt-4 flex justify-center">
            <div class="flex space-x-2">
                <?php for ($i = 1; $i <= $users['total_pages']; $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&order=<?= $order ?>" 
                       class="px-3 py-1 rounded <?= $users['current_page'] === $i ? 'bg-blue-500 text-white' : 'bg-gray-200' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>