<?php

require_once '../includes/auth_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

$discussion_id = (int) ($_GET['id'] ?? $_POST['discussion_id'] ?? 0);
if ($discussion_id <= 0) {
	header('Location: index.php');
	exit;
}

$discussion_stmt = $conn->prepare('SELECT * FROM discussions WHERE id = ?');
$discussion_stmt->bind_param('i', $discussion_id);
$discussion_stmt->execute();
$discussion_result = $discussion_stmt->get_result();
$discussion = $discussion_result->fetch_assoc();
$discussion_stmt->close();

if (!$discussion) {
	header('Location: index.php');
	exit;
}

if ((int) $discussion['user_id'] !== (int) $_SESSION['user_id']) {
	header('Location: view.php?id=' . $discussion_id);
	exit;
}

$errors = [];
$title = '';
$description = '';
$department = '';
$course_name = '';
$faculty_name = '';
$topic = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$title = trim($_POST['title'] ?? '');
	$description = trim($_POST['description'] ?? '');
	$department = trim($_POST['department'] ?? '');
	$course_name = trim($_POST['course_name'] ?? '');
	$faculty_name = trim($_POST['faculty_name'] ?? '');
	$topic = trim($_POST['topic'] ?? '');

	if ($title === '') {
		$errors[] = 'Title is required.';
	} elseif (strlen($title) > 200) {
		$errors[] = 'Title must not exceed 200 characters.';
	}

	if ($description === '') {
		$errors[] = 'Description is required.';
	} elseif (strlen($description) > 5000) {
		$errors[] = 'Description must not exceed 5000 characters.';
	}

	if ($department === '') {
		$errors[] = 'Department is required.';
	}

	if ($course_name === '') {
		$errors[] = 'Course name is required.';
	}

	if ($faculty_name === '') {
		$errors[] = 'Faculty name is required.';
	}

	if ($topic === '') {
		$errors[] = 'Topic is required.';
	}

	if (empty($errors)) {
		$current_user_id = (int) $_SESSION['user_id'];
		$update_stmt = $conn->prepare(
			'UPDATE discussions
			 SET title = ?, description = ?, department = ?, course_name = ?, faculty_name = ?, topic = ?, updated_at = NOW()
			 WHERE id = ? AND user_id = ?'
		);
		$update_stmt->bind_param('ssssssii', $title, $description, $department, $course_name, $faculty_name, $topic, $discussion_id, $current_user_id);
		$update_stmt->execute();
		$update_stmt->close();

		header('Location: view.php?id=' . $discussion_id);
		exit;
	}
} else {
	$title = (string) $discussion['title'];
	$description = (string) $discussion['description'];
	$department = (string) $discussion['department'];
	$course_name = (string) $discussion['course_name'];
	$faculty_name = (string) $discussion['faculty_name'];
	$topic = (string) $discussion['topic'];
}

$page_title = 'Edit discussion — EWU Study Point';
require_once '../includes/header.php';
?>

<main>
	<div class="notice-card form-card">
		<h1>Edit discussion</h1>

		<?php if (!empty($errors)): ?>
			<div class="form-errors">
				<ul>
					<?php foreach ($errors as $error): ?>
						<li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<form method="POST" action="edit.php">
			<input type="hidden" name="discussion_id" value="<?php echo (int) $discussion_id; ?>">

			<div>
				<label for="title">Title</label>
				<input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>">
			</div>

			<div>
				<label for="description">Description</label>
				<textarea id="description" name="description"><?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?></textarea>
			</div>

			<div>
				<label for="department">Department</label>
				<input type="text" id="department" name="department" value="<?php echo htmlspecialchars($department, ENT_QUOTES, 'UTF-8'); ?>">
			</div>

			<div>
				<label for="course_name">Course Name</label>
				<input type="text" id="course_name" name="course_name" value="<?php echo htmlspecialchars($course_name, ENT_QUOTES, 'UTF-8'); ?>">
			</div>

			<div>
				<label for="faculty_name">Faculty Name</label>
				<input type="text" id="faculty_name" name="faculty_name" value="<?php echo htmlspecialchars($faculty_name, ENT_QUOTES, 'UTF-8'); ?>">
			</div>

			<div>
				<label for="topic">Topic</label>
				<input type="text" id="topic" name="topic" value="<?php echo htmlspecialchars($topic, ENT_QUOTES, 'UTF-8'); ?>">
			</div>

			<button type="submit">Save changes</button>
		</form>
	</div>
</main>

<?php require_once '../includes/footer.php'; ?>
