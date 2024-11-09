<?php
// views/user/login.php
?>
<!DOCTYPE html>
<html>
<?php include BASE_PATH . '/views/partials/head.php'; ?>
<body>
    <?php include BASE_PATH . '/views/partials/nav.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold mb-6">Login</h1>
            
            <?php include BASE_PATH . '/views/partials/flash.php'; ?>
            
            <form action="/login" method="POST">
                 <!-- Automatically generate CSRF token -->
                <?= csrf_field() ?>
                <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?>">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                        Email Address
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                           type="email" 
                           id="email" 
                           name="email" 
                           value="<?= $_SESSION['old_input']['email'] ?? '' ?>" 
                           required>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                        Password
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline"
                           type="password" 
                           id="password" 
                           name="password" 
                           required>
                </div>
                
                <div class="flex items-center justify-between mb-6">
                    <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                            type="submit">
                        Sign In
                    </button>
                    <a class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800"
                       href="/password/reset">
                        Forgot Password?
                    </a>
                </div>
                
                <p class="text-center text-gray-600 text-sm">
                    Don't have an account? 
                    <a href="/register" class="text-blue-500 hover:text-blue-800">Register here</a>
                </p>
            </form>
        </div>
    </div>
    
    <?php include BASE_PATH . '/views/partials/footer.php'; ?>
</body>
</html>