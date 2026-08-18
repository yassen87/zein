</main>
</div>
<script src="<?= esc(admin_asset('assets/js/admin-theme.js?v=' . filemtime(__DIR__ . '/../assets/js/admin-theme.js'))) ?>" defer></script>
<?php if (strpos($_SERVER['PHP_SELF'], 'product_edit.php') !== false): ?>
<script src="<?= esc(admin_asset('../assets/js/image-upload.js?v=' . filemtime(__DIR__ . '/../assets/js/image-upload.js'))) ?>" defer></script>
<?php endif; ?>
</body>
</html>
