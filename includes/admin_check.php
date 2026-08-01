<?php

require_once __DIR__ . '/auth_check.php';

if (!is_admin()) {
	redirect('../index.php');
}
