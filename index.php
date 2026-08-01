<?php

require_once 'config/db.php';
require_once 'includes/functions.php';

if (isset($_SESSION['user_id'])) {
	header('Location: discussions/index.php');
	exit;
}

$page_title = 'EWU Study Point';
require_once 'includes/header.php';
?>

<main>
	<div class="hero-banner">
	<h1>EWU Study Point</h1>
	<p>Ask questions, share study materials, and help fellow East West University students all in one place.</p>
	<div class="material-actions">
		<a href="auth/login.php" class="btn-primary">Log in</a>
		<a href="auth/register.php" class="btn-secondary">Register</a>
	</div>
</div>
</main>

<?php require_once 'includes/footer.php'; ?>
