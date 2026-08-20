<?php

require_once '../includes/admin_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

$total_users_result = $conn->query('SELECT COUNT(*) AS total FROM users');
$total_users = (int) ($total_users_result->fetch_assoc()['total'] ?? 0);

$total_discussions_result = $conn->query('SELECT COUNT(*) AS total FROM discussions');
$total_discussions = (int) ($total_discussions_result->fetch_assoc()['total'] ?? 0);

$total_materials_result = $conn->query('SELECT COUNT(*) AS total FROM materials');
$total_materials = (int) ($total_materials_result->fetch_assoc()['total'] ?? 0);

$total_stationary_result = $conn->query('SELECT COUNT(*) AS total FROM stationary_items');
$total_stationary = (int) ($total_stationary_result->fetch_assoc()['total'] ?? 0);

$pending_reports_result = $conn->query("SELECT COUNT(*) AS total FROM reports WHERE status = 'pending'");
$pending_reports = (int) ($pending_reports_result->fetch_assoc()['total'] ?? 0);

$banned_users_result = $conn->query('SELECT COUNT(*) AS total FROM users WHERE is_banned = 1');
$banned_users = (int) ($banned_users_result->fetch_assoc()['total'] ?? 0);

$page_title = 'Admin Dashboard — EWU Study Point';
require_once '../includes/header.php';
?>

<main>
	<div class="page-header">
		<div>
			<h1>Admin Administration Dashboard</h1>
			<p class="page-subtitle">Overview of platform metrics, moderation queue, user management, and campus resources.</p>
		</div>
	</div>

	<div class="admin-links">
		<a href="dashboard.php" class="active">Dashboard Overview</a>
		<a href="users.php">Manage Users</a>
		<a href="reports.php">Reports Queue (<?php echo $pending_reports; ?>)</a>
		<a href="moderate.php">Moderate Content</a>
		<a href="../materials/upload.php">+ Upload Material</a>
		<a href="../stationary/create.php">+ Post Stationery</a>
	</div>

	<div class="admin-stats-grid">
		<a class="stat-card" href="users.php">
			<div class="stat-number"><?php echo $total_users; ?></div>
			<div class="stat-label">Total Users</div>
		</a>

		<a class="stat-card" href="../discussions/index.php">
			<div class="stat-number"><?php echo $total_discussions; ?></div>
			<div class="stat-label">Total Discussions</div>
		</a>

		<a class="stat-card" href="../materials/index.php">
			<div class="stat-number"><?php echo $total_materials; ?></div>
			<div class="stat-label">Study Materials</div>
		</a>

		<a class="stat-card" href="../stationary/index.php">
			<div class="stat-number"><?php echo $total_stationary; ?></div>
			<div class="stat-label">Stationery Items</div>
		</a>

		<a class="stat-card stat-card-danger" href="reports.php">
			<div class="stat-number"><?php echo $pending_reports; ?></div>
			<div class="stat-label">Pending Reports</div>
		</a>

		<a class="stat-card stat-card-warning" href="users.php?filter=banned">
			<div class="stat-number"><?php echo $banned_users; ?></div>
			<div class="stat-label">Banned Users</div>
		</a>
	</div>
</main>

<?php require_once '../includes/footer.php'; ?>
