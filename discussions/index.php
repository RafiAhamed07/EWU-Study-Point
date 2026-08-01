<?php

require_once '../config/db.php';
require_once '../includes/functions.php';

function bind_stmt_params(mysqli_stmt $stmt, string $types, array $params): void
{
	$bind_params = [$types];

	foreach ($params as $index => $value) {
		$bind_params[$index + 1] = &$params[$index];
	}

	call_user_func_array([$stmt, 'bind_param'], $bind_params);
}

$current_page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$per_page = 10;
$offset = ($current_page - 1) * $per_page;

$where_clauses = [];
$params = [];
$types = '';

$department = trim($_GET['department'] ?? '');
$course = trim($_GET['course'] ?? '');
$search = trim($_GET['search'] ?? '');

if ($department !== '') {
	$where_clauses[] = 'd.department = ?';
	$params[] = $department;
	$types .= 's';
}

if ($course !== '') {
	$where_clauses[] = 'd.course_name = ?';
	$params[] = $course;
	$types .= 's';
}

if ($search !== '') {
	$where_clauses[] = '(d.title LIKE ? OR d.description LIKE ?)';
	$search_term = '%' . $search . '%';
	$params[] = $search_term;
	$params[] = $search_term;
	$types .= 'ss';
}

$where_sql = '';
if (!empty($where_clauses)) {
	$where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

$count_sql = 'SELECT COUNT(*) AS total_count FROM discussions d ' . $where_sql;
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
	bind_stmt_params($count_stmt, $types, $params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_rows = (int) ($count_result->fetch_assoc()['total_count'] ?? 0);
$count_stmt->close();

$total_pages = (int) max(1, ceil($total_rows / $per_page));

$discussions_sql = '
	SELECT
		d.id,
		d.title,
		d.description,
		d.department,
		d.course_name,
		d.faculty_name,
		d.topic,
		d.vote_score,
		d.created_at,
		u.name AS author_name
	FROM discussions d
	JOIN users u ON d.user_id = u.id
	' . $where_sql . '
	ORDER BY d.created_at DESC
	LIMIT ? OFFSET ?
';

$discussions_stmt = $conn->prepare($discussions_sql);
$discussion_params = $params;
$discussion_types = $types . 'ii';
$discussion_params[] = $per_page;
$discussion_params[] = $offset;
bind_stmt_params($discussions_stmt, $discussion_types, $discussion_params);
$discussions_stmt->execute();
$discussions_result = $discussions_stmt->get_result();
$discussions = $discussions_result->fetch_all(MYSQLI_ASSOC);
$discussions_stmt->close();

$page_title = 'Discussions — EWU Study Point';
require_once '../includes/header.php';
?>

<main>
	<div class="page-header">
		<h1>Discussions</h1>
		<a href="create.php" class="btn-primary">Ask a question</a>
	</div>

	<form method="GET">
		<div>
			<label for="department">Department</label>
			<input type="text" id="department" name="department" value="<?php echo htmlspecialchars($department, ENT_QUOTES, 'UTF-8'); ?>">
		</div>

		<div>
			<label for="course">Course</label>
			<input type="text" id="course" name="course" value="<?php echo htmlspecialchars($course, ENT_QUOTES, 'UTF-8'); ?>">
		</div>

		<div>
			<label for="search">Search</label>
			<input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
		</div>

		<button type="submit">Filter</button>
		<a href="index.php">Clear filters</a>
	</form>

	<section>
		<?php if (empty($discussions)): ?>
			<p>No discussions found. Be the first to ask.</p>
			<p><a href="create.php">Create a discussion</a></p>
		<?php else: ?>
			<?php foreach ($discussions as $d): ?>
				<div class="notice-card">
					<span class="tape-tab"><?php echo htmlspecialchars($d['course_name'], ENT_QUOTES, 'UTF-8'); ?></span>
					<a href="view.php?id=<?php echo (int) $d['id']; ?>" class="notice-title"><?php echo htmlspecialchars($d['title'], ENT_QUOTES, 'UTF-8'); ?></a>
					<p class="notice-excerpt"><?php echo htmlspecialchars(mb_strimwidth($d['description'], 0, 120, '...'), ENT_QUOTES, 'UTF-8'); ?></p>
					<div class="notice-meta">
						<span><?php echo htmlspecialchars($d['faculty_name'], ENT_QUOTES, 'UTF-8'); ?> &middot; <?php echo htmlspecialchars($d['department'], ENT_QUOTES, 'UTF-8'); ?></span>
						<span class="notice-stats">
							<span><?php echo (int) $d['vote_score']; ?> votes</span>
							<span><?php echo htmlspecialchars($d['author_name'], ENT_QUOTES, 'UTF-8'); ?></span>
						</span>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</section>

	<?php if ($total_pages > 1): ?>
		<?php
		$filter_params = [];
		if ($department !== '') {
			$filter_params['department'] = $department;
		}
		if ($course !== '') {
			$filter_params['course'] = $course;
		}
		if ($search !== '') {
			$filter_params['search'] = $search;
		}
		?>
		<nav aria-label="Pagination">
			<?php if ($current_page > 1): ?>
				<?php $prev_params = array_merge($filter_params, ['page' => $current_page - 1]); ?>
				<a href="?<?php echo htmlspecialchars(http_build_query($prev_params), ENT_QUOTES, 'UTF-8'); ?>">Prev</a>
			<?php endif; ?>

			<?php for ($page = 1; $page <= $total_pages; $page++): ?>
				<?php $page_params = array_merge($filter_params, ['page' => $page]); ?>
				<?php if ($page === $current_page): ?>
					<span class="current"><?php echo $page; ?></span>
				<?php else: ?>
					<a href="?<?php echo htmlspecialchars(http_build_query($page_params), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $page; ?></a>
				<?php endif; ?>
			<?php endfor; ?>

			<?php if ($current_page < $total_pages): ?>
				<?php $next_params = array_merge($filter_params, ['page' => $current_page + 1]); ?>
				<a href="?<?php echo htmlspecialchars(http_build_query($next_params), ENT_QUOTES, 'UTF-8'); ?>">Next</a>
			<?php endif; ?>
		</nav>
	<?php endif; ?>
</main>

<?php require_once '../includes/footer.php'; ?>
