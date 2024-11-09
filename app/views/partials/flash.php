<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        <?= htmlspecialchars($_SESSION['flash_success']) ?>
        <?php unset($_SESSION['flash_success']) ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['flash_error'])): ?>
     <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
         <?= htmlspecialchars($_SESSION['flash_error']) ?>
        <?php unset($_SESSION['flash_error']) ?>
     </div>
<?php endif; ?>                