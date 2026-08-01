<?php

require_once '../config/db.php';
require_once '../includes/functions.php';

function bind_material_stmt_params(mysqli_stmt $stmt, string $types, array $params): void
{
	$bind_values = [$types];

	foreach ($params as $index => $value) {
		$bind_values[] = &$params[$index];
	}

	call_user_func_array([$stmt, 'bind_param'], $bind_values);
}

$current_page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$per_page = 12;
$offset = ($current_page - 1) * $per_page;

$department = trim($_GET['department'] ?? '');
$course = trim($_GET['course'] ?? '');
$material_type = trim($_GET['material_type'] ?? '');
$search = trim($_GET['search'] ?? '');

$allowed_material_types = [
	'hand_notes',
	'lecture_sheet',
	'lecture_slide',
	'term_paper',
	'previous_question',
	'other',
];

$where_clauses = [];
$params = [];
$types = '';

if ($department !== '') {
	$where_clauses[] = 'm.department = ?';
	$params[] = $department;
	$types .= 's';
}

if ($course !== '') {
	$where_clauses[] = 'm.course_name = ?';
	$params[] = $course;
	$types .= 's';
}

if ($material_type !== '' && in_array($material_type, $allowed_material_types, true)) {
	$where_clauses[] = 'm.material_type = ?';
	$params[] = $material_type;
	$types .= 's';
}

if ($search !== '') {
	$where_clauses[] = 'm.title LIKE ?';
	$params[] = '%' . $search . '%';
	$types .= 's';
}

$where_sql = '';
if (!empty($where_clauses)) {
	$where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

$count_sql = 'SELECT COUNT(*) AS total_count FROM materials m ' . $where_sql;
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
	bind_material_stmt_params($count_stmt, $types, $params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_rows = (int) ($count_result->fetch_assoc()['total_count'] ?? 0);
$count_stmt->close();

$total_pages = (int) max(1, ceil($total_rows / $per_page));

$materials_sql = '
	SELECT
		m.id,
		m.title,
		m.department,
		m.course_name,
		m.faculty_name,
		m.material_type,
		m.file_path,
		m.uploaded_at,
		u.name AS uploader_name
	FROM materials m
	JOIN users u ON m.user_id = u.id
	' . $where_sql . '
	ORDER BY m.uploaded_at DESC
	LIMIT ? OFFSET ?
';

$materials_stmt = $conn->prepare($materials_sql);
$materials_params = $params;
$materials_types = $types . 'ii';
$materials_params[] = $per_page;
$materials_params[] = $offset;
bind_material_stmt_params($materials_stmt, $materials_types, $materials_params);
$materials_stmt->execute();
$materials_result = $materials_stmt->get_result();
$materials = $materials_result->fetch_all(MYSQLI_ASSOC);
$materials_stmt->close();

$page_title = 'Study Materials — EWU Study Point';
require_once '../includes/header.php';
?>

<main>
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
			<label for="material_type">Material type</label>
			<select id="material_type" name="material_type">
				<option value="" <?php echo $material_type === '' ? 'selected' : ''; ?>>All types</option>
				<option value="hand_notes" <?php echo $material_type === 'hand_notes' ? 'selected' : ''; ?>>Hand notes</option>
				<option value="lecture_sheet" <?php echo $material_type === 'lecture_sheet' ? 'selected' : ''; ?>>Lecture sheet</option>
				<option value="lecture_slide" <?php echo $material_type === 'lecture_slide' ? 'selected' : ''; ?>>Lecture slide</option>
				<option value="term_paper" <?php echo $material_type === 'term_paper' ? 'selected' : ''; ?>>Term paper</option>
				<option value="previous_question" <?php echo $material_type === 'previous_question' ? 'selected' : ''; ?>>Previous question</option>
				<option value="other" <?php echo $material_type === 'other' ? 'selected' : ''; ?>>Other</option>
			</select>
		</div>

		<div>
			<label for="search">Search</label>
			<input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
		</div>

		<button type="submit">Filter</button>
		<a href="index.php">Clear filters</a>
	</form>

	<section>
		<?php if (empty($materials)): ?>
			<p>No materials found. Be the first to upload.</p>
			<p><a href="upload.php">Upload study material</a></p>
		<?php else: ?>
			<?php foreach ($materials as $m): ?>
				<div class="notice-card material-card">
					<span class="tape-tab tape-tab-green"><?php echo htmlspecialchars($m['course_name'], ENT_QUOTES, 'UTF-8'); ?></span>
					<p class="notice-title"><?php echo htmlspecialchars($m['title'], ENT_QUOTES, 'UTF-8'); ?></p>
					<p class="notice-excerpt"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $m['material_type'])), ENT_QUOTES, 'UTF-8'); ?></p>
					<div class="notice-meta">
						<span><?php echo htmlspecialchars($m['faculty_name'], ENT_QUOTES, 'UTF-8'); ?> &middot; <?php echo htmlspecialchars($m['department'], ENT_QUOTES, 'UTF-8'); ?></span>
						<span class="notice-stats"><?php echo htmlspecialchars($m['uploader_name'], ENT_QUOTES, 'UTF-8'); ?></span>
					</div>
					<div class="material-actions">
						<a href="<?php echo htmlspecialchars('../' . ltrim($m['file_path'], '/'), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" download>Download</a>
						<?php if (isset($_SESSION['user_id'])): ?>
							<form method="POST" action="../report_handler.php">
								<input type="hidden" name="material_id" value="<?php echo (int) $m['id']; ?>">
								<button type="submit">Report</button>
							</form>
						<?php endif; ?>
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
		if ($material_type !== '') {
			$filter_params['material_type'] = $material_type;
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

