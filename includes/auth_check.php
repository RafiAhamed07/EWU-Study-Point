<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in()) {
	redirect('/ewu-study-point/auth/login.php');
}
