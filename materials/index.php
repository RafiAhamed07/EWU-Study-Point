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
	'book' => 'Books & Textbooks',
	'hand_notes' => 'Hand Notes',
	'lecture_sheet' => 'Lecture Sheets',
	'lecture_slide' => 'Lecture Slides',
	'term_paper' => 'Term Papers',
	'previous_question' => 'Previous Questions',
	'other' => 'Other Materials',
];

$type_icons = [
	'book' => '📚',
	'hand_notes' => '📝',
	'lecture_sheet' => '📄',
	'lecture_slide' => '📑',
	'term_paper' => '📋',
	'previous_question' => '❓',
	'other' => '📎',
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

if ($material_type !== '' && array_key_exists($material_type, $allowed_material_types)) {
	$where_clauses[] = 'm.material_type = ?';
	$params[] = $material_type;
	$types .= 's';
}

if ($search !== '') {
	$where_clauses[] = '(m.title LIKE ? OR m.course_name LIKE ? OR m.department LIKE ? OR m.faculty_name LIKE ?)';
	$search_term = '%' . $search . '%';
	$params[] = $search_term;
	$params[] = $search_term;
	$params[] = $search_term;
	$params[] = $search_term;
	$types .= 'ssss';
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
$report_success = $_SESSION['report_success'] ?? '';
unset($_SESSION['report_success']);
require_once '../includes/header.php';
?>

<main>
	<?php if (!empty($report_success)): ?>
		<div style="background: #E6F6ED; border: 1px solid #1B6E3F; color: #1B6E3F; border-radius: 6px; padding: 12px 16px; margin-bottom: 20px; font-weight: 600;">
			<?php echo htmlspecialchars($report_success, ENT_QUOTES, 'UTF-8'); ?>
		</div>
	<?php endif; ?>

	<div class="page-header">
		<div>
			<h1>Study Materials &amp; Academic Resources</h1>
			<p class="page-subtitle">Download lecture notes, textbooks, slides, and previous exam question papers shared by EWU students.</p>
		</div>
		<a href="upload.php" class="btn-primary" style="padding: 10px 20px; font-size: 14px;">+ Upload Material</a>
	</div>

	<!-- Material Type Quick Filter Pills -->
	<div style="display: flex; gap: 8px; margin-bottom: 20px; overflow-x: auto; padding-bottom: 6px;">
		<a href="index.php" class="<?php echo $material_type === '' ? 'btn-primary' : 'btn-secondary'; ?>" style="padding: 7px 14px; font-size: 13px; white-space: nowrap;">
			📑 All Materials
		</a>
		<?php foreach ($allowed_material_types as $key => $label): ?>
			<a href="index.php?material_type=<?php echo $key; ?>" class="<?php echo $material_type === $key ? 'btn-primary' : 'btn-secondary'; ?>" style="padding: 7px 14px; font-size: 13px; white-space: nowrap;">
				<?php echo $type_icons[$key] ?? '📄'; ?> <?php echo $label; ?>
			</a>
		<?php endforeach; ?>
	</div>

	<!-- Filter Search Bar -->
	<form method="GET" action="index.php" class="filter-bar">
		<?php if ($material_type !== ''): ?>
			<input type="hidden" name="material_type" value="<?php echo htmlspecialchars($material_type, ENT_QUOTES, 'UTF-8'); ?>">
		<?php endif; ?>

		<div class="form-group" style="flex: 2;">
			<label for="search">Keyword Search</label>
			<input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search title, course code (e.g. CSE106), or faculty...">
		</div>

		<div class="form-group">
			<label for="department">Department</label>
			<input type="text" id="department" name="department" value="<?php echo htmlspecialchars($department, ENT_QUOTES, 'UTF-8'); ?>" placeholder="e.g. CSE, EEE, BBA">
		</div>

		<div class="form-group">
			<label for="course">Course Name / Code</label>
			<input type="text" id="course" name="course" value="<?php echo htmlspecialchars($course, ENT_QUOTES, 'UTF-8'); ?>" placeholder="e.g. MAT101">
		</div>

		<button type="submit" class="btn-primary">Filter</button>
		<a href="index.php" class="btn-secondary">Clear</a>
	</form>

	<!-- Materials Grid -->
	<?php if (empty($materials)): ?>
		<div class="form-card" style="text-align: center; max-width: 600px; margin: 40px auto;">
			<div style="font-size: 48px; margin-bottom: 12px;">📚</div>
			<h2 style="font-size: 18px; color: var(--ewu-blue); margin: 0 0 8px;">No study materials found</h2>
			<p style="color: var(--text-muted); font-size: 14px; margin-bottom: 20px;">
				No materials found matching your search. Be the first to help your fellow classmates!
			</p>
			<a href="upload.php" class="btn-primary">+ Upload Material</a>
		</div>
	<?php else: ?>
		<div class="card-grid">
			<?php foreach ($materials as $m): ?>
				<?php
				$m_type = $m['material_type'];
				$m_icon = $type_icons[$m_type] ?? '📄';
				$m_label = $allowed_material_types[$m_type] ?? ucwords(str_replace('_', ' ', $m_type));
				?>
				<div class="item-card">
					<div class="card-body">
						<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
							<span class="badge badge-blue" style="font-weight: 800;"><?php echo htmlspecialchars($m['course_name'], ENT_QUOTES, 'UTF-8'); ?></span>
							<span class="badge badge-gray"><?php echo $m_icon; ?> <?php echo htmlspecialchars($m_label, ENT_QUOTES, 'UTF-8'); ?></span>
						</div>

						<h3 class="card-title" title="<?php echo htmlspecialchars($m['title'], ENT_QUOTES, 'UTF-8'); ?>">
							<?php echo htmlspecialchars($m['title'], ENT_QUOTES, 'UTF-8'); ?>
						</h3>

						<div style="font-size: 13px; color: var(--text-body); margin-bottom: 12px;">
							<div><strong>Faculty:</strong> <?php echo htmlspecialchars($m['faculty_name'], ENT_QUOTES, 'UTF-8'); ?></div>
							<div><strong>Dept:</strong> <?php echo htmlspecialchars($m['department'], ENT_QUOTES, 'UTF-8'); ?></div>
						</div>

						<div class="card-meta">
							<span>By: <?php echo htmlspecialchars($m['uploader_name'], ENT_QUOTES, 'UTF-8'); ?></span>
							<span><?php echo date('M j, Y', strtotime($m['uploaded_at'])); ?></span>
						</div>
					</div>

					<div class="card-actions">
						<a href="<?php echo htmlspecialchars('../' . ltrim($m['file_path'], '/'), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="btn-primary" style="flex: 1; padding: 7px 12px; font-size: 13px;">
							📥 Download File
						</a>
						<?php if (isset($_SESSION['user_id'])): ?>
							<form method="POST" action="../report_handler.php" onsubmit="return confirmReport(this);" style="margin: 0;">
								<input type="hidden" name="material_id" value="<?php echo (int) $m['id']; ?>">
								<input type="hidden" name="reason" value="Inappropriate study material">
								<button type="submit" class="btn-outline-danger" style="padding: 7px 10px;" title="Report this material">
									🚩
								</button>
							</form>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<script>
	function confirmReport(form) {
		var reason = prompt('Please enter a reason for reporting this material (optional):', 'Inappropriate or incorrect study material');
		if (reason === null) {
			return false;
		}
		if (reason.trim() !== '') {
			form.querySelector('input[name="reason"]').value = reason.trim();
		}
		return true;
	}
	</script>

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
