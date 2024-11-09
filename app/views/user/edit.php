<?php
// views/user/edit.php
?>
<!DOCTYPE html>
<html>
<?php include BASE_PATH . '/views/partials/head.php'; ?>
<body>
    <?php include BASE_PATH . '/views/partials/nav.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold mb-6">Edit Profile</h1>
            
            <?php include BASE_PATH . '/views/partials/flash.php'; ?>
            
            <form action="/account/update" method="POST">
                <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?>">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                        Full Name
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                           type="text" 
                           id="name" 
                           name="name" 
                           value="<?= htmlspecialchars($user['name']) ?>" 
                           required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                        Email Address
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                           type="email" 
                           id="email" 
                           name="email" 
                           value="<?= htmlspecialchars($user['email']) ?>" 
                           required>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="new_password">
                        New Password (leave blank to keep current)
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                           type="password" 
                           id="new_password" 
                           name="new_password">
                </div>
                
                <div class="flex items-center justify-between">
                    <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                            type="submit">
                        Save Changes
                    </button>
                    <a href="/account" 
                       class="inline-block align-baseline font-bold text-sm text-gray-600 hover:text-gray-800">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <?php include BASE_PATH . '/views/partials/footer.php'; ?>
</body>
</html>