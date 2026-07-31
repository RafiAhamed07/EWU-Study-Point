<?php
$page_title = $page_title ?? 'EWU Study Point';
$site_root = '/ewu_study_point/ewu-study-point';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>
	<link rel="stylesheet" href="<?php echo $site_root; ?>/assets/css/style.css">
</head>
<body>
<nav class="site-nav">
	<a class="site-brand" href="<?php echo $site_root; ?>/index.php">EWU Study Point</a>

	<div class="site-nav-links">
		<?php if (!empty($_SESSION['user_id'])): ?>
			<a href="<?php echo $site_root; ?>/discussions/index.php">Discussions</a>
			<a href="<?php echo $site_root; ?>/materials/index.php">Study Materials</a>
			<a href="<?php echo $site_root; ?>/profile/view.php?id=<?php echo (int) $_SESSION['user_id']; ?>">Profile</a>
			<?php if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
				<a href="<?php echo $site_root; ?>/admin/dashboard.php">Admin</a>
			<?php endif; ?>
			<a href="<?php echo $site_root; ?>/auth/logout.php">Logout</a>
		<?php else: ?>
			<a href="<?php echo $site_root; ?>/auth/login.php">Login</a>
			<a href="<?php echo $site_root; ?>/auth/register.php">Register</a>
		<?php endif; ?>
	</div>
</nav>
