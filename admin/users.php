<?php

require_once '../includes/admin_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

$search = trim($_GET['search'] ?? '');
$filter = trim($_GET['filter'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$target_user_id = (int) ($_POST['user_id'] ?? 0);
	$return_filter = trim($_POST['filter'] ?? '');

	if ($target_user_id > 0 && $target_user_id !== (int) $_SESSION['user_id']) {
		$toggle_stmt = $conn->prepare('UPDATE users SET is_banned = NOT is_banned WHERE id = ?');
		$toggle_stmt->bind_param('i', $target_user_id);
		$toggle_stmt->execute();
		$toggle_stmt->close();
	}

	$redirect_url = 'users.php' . ($return_filter !== '' ? '?filter=' . urlencode($return_filter) : '');
	header('Location: ' . $redirect_url);
	exit;
}

$where_clauses = [];
$users_params = [];
$users_types = '';

if ($filter === 'banned') {
	$where_clauses[] = 'is_banned = 1';
} elseif ($filter === 'active') {
	$where_clauses[] = 'is_banned = 0';
}

if ($search !== '') {
	$where_clauses[] = '(name LIKE ? OR email LIKE ? OR student_id LIKE ?)';
	$search_term = '%' . $search . '%';
	$users_params[] = $search_term;
	$users_params[] = $search_term;
	$users_params[] = $search_term;
	$users_types .= 'sss';
}

$where_sql = '';
if (!empty($where_clauses)) {
	$where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

$users_stmt = $conn->prepare(
	'SELECT id, name, student_id, email, department, role, is_banned, created_at
	 FROM users
	 ' . $where_sql . '
	 ORDER BY created_at DESC'
);
if (!empty($users_params)) {
	$bind_values = [$users_types];
	foreach ($users_params as $index => $value) {
		$bind_values[] = &$users_params[$index];
	}
	call_user_func_array([$users_stmt, 'bind_param'], $bind_values);
}
$users_stmt->execute();
$users_result = $users_stmt->get_result();
$users = $users_result->fetch_all(MYSQLI_ASSOC);
$users_stmt->close();

$page_title = 'Manage users — EWU Study Point';
require_once '../includes/header.php';
?>

<main>
	<div class="admin-links">
		<a href="dashboard.php">Dashboard</a>
		<a href="reports.php">Reports queue</a>
		<a href="moderate.php">Moderate content</a>
	</div>

	<div class="notice-card">
		<h1>Manage users</h1>

		<div style="display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap;">
			<a href="users.php" class="<?php echo $filter === '' ? 'btn-primary' : 'btn-secondary'; ?>" style="padding: 6px 14px; font-size: 13px;">All Users</a>
			<a href="users.php?filter=active" class="<?php echo $filter === 'active' ? 'btn-primary' : 'btn-secondary'; ?>" style="padding: 6px 14px; font-size: 13px;">Active Users</a>
			<a href="users.php?filter=banned" class="<?php echo $filter === 'banned' ? 'btn-primary' : 'btn-secondary'; ?>" style="padding: 6px 14px; font-size: 13px;">Banned Users</a>
		</div>

		<form method="GET" action="users.php">
			<?php if ($filter !== ''): ?>
				<input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter, ENT_QUOTES, 'UTF-8'); ?>">
			<?php endif; ?>
			<div>
				<label for="search">Search</label>
				<input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search by name, student ID, or email">
			</div>
			<button type="submit">Filter</button>
			<a href="users.php<?php echo $filter !== '' ? '?filter=' . urlencode($filter) : ''; ?>">Clear search</a>
		</form>
		<div style="overflow-x: auto;">
			<table>
				<thead>
					<tr>
						<th>Name</th>
						<th>Student ID</th>
						<th>Email</th>
						<th>Department</th>
						<th>Role</th>
						<th>Status</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($users as $user): ?>
						<tr>
							<td><a href="../profile/view.php?id=<?php echo (int) $user['id']; ?>"><?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?></a></td>
							<td><?php echo htmlspecialchars($user['student_id'], ENT_QUOTES, 'UTF-8'); ?></td>
							<td><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></td>
							<td><?php echo htmlspecialchars($user['department'], ENT_QUOTES, 'UTF-8'); ?></td>
							<td><?php echo htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8'); ?></td>
							<td>
								<span class="<?php echo (int) $user['is_banned'] === 1 ? 'status-banned' : 'status-active'; ?>">
									<?php echo (int) $user['is_banned'] === 1 ? 'Banned' : 'Active'; ?>
								</span>
							</td>
							<td>
								<?php if ((int) $user['id'] === (int) $_SESSION['user_id']): ?>
									<span>—</span>
								<?php else: ?>
									<form method="POST" action="users.php" onsubmit="return confirm('Change this user\'s ban status?');">
										<input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
										<input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter, ENT_QUOTES, 'UTF-8'); ?>">
										<button type="submit"><?php echo (int) $user['is_banned'] === 1 ? 'Unban' : 'Ban'; ?></button>
									</form>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</main>

<?php require_once '../includes/footer.php'; ?>
