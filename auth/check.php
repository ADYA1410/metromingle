<?php
/**
 * Include at the top of protected pages, then call require_role('user'|'owner'|'admin').
 */
require_once __DIR__ . '/../includes/bootstrap.php';

require_login();
