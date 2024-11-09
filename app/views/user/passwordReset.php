<?php
// views/user/password/reset.php
?>
<!DOCTYPE html>
<html>
<?php include BASE_PATH . '/views/partials/head.php'; ?>
<body>
    <?php include BASE_PATH . '/views/partials/nav.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold mb-6">Reset Password</h1>
            
            <?php include BASE_PATH . '/views/partials/flash.php'; ?>
            
            <form method="POST" action="/password/reset">
                <input type="hidden" name="_token" value="<?= $token ?>">
                <input type="hidden" name="token" value="<?= $reset_token ?>">
                <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2" for="password">New Password</label>
                    <input type="password" id="password" name="password" 
                           class="w-full px-3 py-2 border rounded-lg" required>
                    <p class="text-sm text-gray-600 mt-1">
                        Must be at least 8 characters with 1 uppercase, 1 lowercase, and 1 number
                    </p>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 mb-2" for="password_confirm">Confirm New Password</label>
                    <input type="password" id="password_confirm" name="password_confirm" 
                           class="w-full px-3 py-2 border rounded-lg" required>
                </div>
                
                <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700">
                    Reset Password
                </button>
            </form>
        </div>
    </div>
</body>
</html>