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
$per_page = 12;
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
	$where_clauses[] = '(d.title LIKE ? OR d.description LIKE ? OR d.topic LIKE ?)';
	$search_term = '%' . $search . '%';
	$params[] = $search_term;
	$params[] = $search_term;
	$params[] = $search_term;
	$types .= 'sss';
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
		u.name AS author_name,
		(SELECT COUNT(*) FROM comments c WHERE c.discussion_id = d.id) AS comment_count
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

$page_title = 'Community Discussions — EWU Study Point';
require_once '../includes/header.php';
?>

<main>
	<div class="page-header">
		<div>
			<h1>Community Discussions &amp; Q&amp;A</h1>
			<p class="page-subtitle">Ask questions, share problem solutions, and get help from fellow East West University peers.</p>
		</div>
		<a href="create.php" class="btn-accent" style="padding: 10px 20px; font-size: 14px;">+ Ask a Question</a>
	</div>

	<!-- Filter Search Bar -->
	<form method="GET" action="index.php" class="filter-bar">
		<div class="form-group" style="flex: 2;">
			<label for="search">Keyword Search</label>
			<input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search discussion topics, questions, concepts...">
		</div>

		<div class="form-group">
			<label for="department">Department</label>
			<input type="text" id="department" name="department" value="<?php echo htmlspecialchars($department, ENT_QUOTES, 'UTF-8'); ?>" placeholder="e.g. CSE, EEE, BBA">
		</div>

		<div class="form-group">
			<label for="course">Course Code</label>
			<input type="text" id="course" name="course" value="<?php echo htmlspecialchars($course, ENT_QUOTES, 'UTF-8'); ?>" placeholder="e.g. MAT101, CSE207">
		</div>

		<button type="submit" class="btn-primary">Filter</button>
		<a href="index.php" class="btn-secondary">Clear</a>
	</form>

	<!-- Discussions Grid -->
	<?php if (empty($discussions)): ?>
		<div class="form-card" style="text-align: center; max-width: 600px; margin: 40px auto;">
			<div style="font-size: 48px; margin-bottom: 12px;">💬</div>
			<h2 style="font-size: 18px; color: var(--ewu-blue); margin: 0 0 8px;">No discussions found</h2>
			<p style="color: var(--text-muted); font-size: 14px; margin-bottom: 20px;">
				No questions matched your search criteria. Start a new thread to get help!
			</p>
			<a href="create.php" class="btn-accent">+ Start Discussion</a>
		</div>
	<?php else: ?>
		<div class="card-grid">
			<?php foreach ($discussions as $d): ?>
				<div class="item-card" onclick="window.location='view.php?id=<?php echo (int) $d['id']; ?>'">
					<div class="card-body">
						<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
							<span class="badge badge-blue" style="font-weight: 800;"><?php echo htmlspecialchars($d['course_name'], ENT_QUOTES, 'UTF-8'); ?></span>
							<span class="badge badge-gray"><?php echo htmlspecialchars($d['topic'], ENT_QUOTES, 'UTF-8'); ?></span>
						</div>

						<a href="view.php?id=<?php echo (int) $d['id']; ?>" class="card-title">
							<?php echo htmlspecialchars($d['title'], ENT_QUOTES, 'UTF-8'); ?>
						</a>

						<p class="card-excerpt">
							<?php echo htmlspecialchars(mb_strimwidth($d['description'], 0, 120, '...'), ENT_QUOTES, 'UTF-8'); ?>
						</p>

						<div style="font-size: 12px; color: var(--text-muted); margin-bottom: 10px;">
							Faculty: <?php echo htmlspecialchars($d['faculty_name'], ENT_QUOTES, 'UTF-8'); ?> &middot; <?php echo htmlspecialchars($d['department'], ENT_QUOTES, 'UTF-8'); ?>
						</div>

						<div class="card-meta">
							<span>By: <?php echo htmlspecialchars($d['author_name'], ENT_QUOTES, 'UTF-8'); ?></span>
							<span><?php echo date('M j, Y', strtotime($d['created_at'])); ?></span>
						</div>
					</div>

					<div class="card-actions" style="justify-content: space-between;">
						<div style="display: flex; gap: 8px; align-items: center; font-size: 12.5px; font-weight: 600; color: var(--text-muted);">
							<span class="badge badge-gold">▲ <?php echo (int) $d['vote_score']; ?> votes</span>
							<span>💬 <?php echo (int) $d['comment_count']; ?> replies</span>
						</div>
						<a href="view.php?id=<?php echo (int) $d['id']; ?>" class="btn-secondary" style="padding: 5px 12px; font-size: 12.5px;">
							Join Discussion &rarr;
						</a>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

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
				<a href="?<?php echo htmlspecialchars(http_build_query($prev_params), ENT_QUOTES, 'UTF-8'); ?>">&larr; Prev</a>
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
				<a href="?<?php echo htmlspecialchars(http_build_query($next_params), ENT_QUOTES, 'UTF-8'); ?>">Next &rarr;</a>
			<?php endif; ?>
		</nav>
	<?php endif; ?>
</main>

<?php require_once '../includes/footer.php'; ?>
