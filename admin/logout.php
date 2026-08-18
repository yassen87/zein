<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/admin_bootstrap.php';

admin_logout();
header('Location: ' . admin_url('login.php'));
exit;
