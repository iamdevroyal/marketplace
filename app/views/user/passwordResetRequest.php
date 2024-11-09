<?php
// views/user/password/request.php
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
            
            <form method="POST" action="/password/email">
                <input type="hidden" name="_token" value="<?= $token ?>">
                
                <div class="mb-6">
                    <label class="block text-gray-700 mb-2" for="email">Email Address</label>
                    <input type="email" id="email" name="email" 
                           value="<?= htmlspecialchars($_SESSION['old_input']['email'] ?? '') ?>"
                           class="w-full px-3 py-2 border rounded-lg" required>
                    <p class="text-sm text-gray-600 mt-1">
                        We'll send you a link to reset your password.
                    </p>
                </div>
                
                <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700">
                    Send Reset Link
                </button>
            </form>
            
            <p class="mt-4 text-center">
                <a href="/login" class="text-blue-600 hover:underline">Back to Login</a>
            </p>
        </div>
    </div>
</body>
</html>