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

$pending_reports_result = $conn->query("SELECT COUNT(*) AS total FROM reports WHERE status = 'pending'");
$pending_reports = (int) ($pending_reports_result->fetch_assoc()['total'] ?? 0);

$banned_users_result = $conn->query('SELECT COUNT(*) AS total FROM users WHERE is_banned = 1');
$banned_users = (int) ($banned_users_result->fetch_assoc()['total'] ?? 0);

$page_title = 'Admin dashboard — EWU Study Point';
require_once '../includes/header.php';
?>

<main>
	<div class="admin-links">
		<a href="users.php">Manage users</a>
		<a href="reports.php">Reports queue</a>
		<a href="moderate.php">Moderate content</a>
	</div>

	<div class="admin-stats-grid">
		<div class="notice-card stat-card">
			<div class="stat-number"><?php echo $total_users; ?></div>
			<div class="stat-label">Total users</div>
		</div>

		<div class="notice-card stat-card">
			<div class="stat-number"><?php echo $total_discussions; ?></div>
			<div class="stat-label">Total discussions</div>
		</div>

		<div class="notice-card stat-card">
			<div class="stat-number"><?php echo $total_materials; ?></div>
			<div class="stat-label">Total materials</div>
		</div>

		<a class="notice-card stat-card stat-card-danger" href="reports.php">
			<div class="stat-number"><?php echo $pending_reports; ?></div>
			<div class="stat-label">Pending reports</div>
		</a>

		<div class="notice-card stat-card">
			<div class="stat-number"><?php echo $banned_users; ?></div>
			<div class="stat-label">Banned users</div>
		</div>
	</div>
</main>

<?php require_once '../includes/footer.php'; ?>
