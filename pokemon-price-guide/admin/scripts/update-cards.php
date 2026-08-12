<?php
require_once '_guard.php';

primetime_price_guide_run_admin_updater();

// Stop the legacy browser loop after the single bounded server-side run.
echo 'EOL';
?>
