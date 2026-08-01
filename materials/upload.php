<?php

require_once '../includes/auth_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

$errors = [];
$title = '';
$department = '';
$course_name = '';
$faculty_name = '';
$material_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$title = trim($_POST['title'] ?? '');
	$department = trim($_POST['department'] ?? '');
	$course_name = trim($_POST['course_name'] ?? '');
	$faculty_name = trim($_POST['faculty_name'] ?? '');
	$material_type = $_POST['material_type'] ?? '';

	$allowed_material_types = [
		'hand_notes',
		'lecture_sheet',
		'lecture_slide',
		'term_paper',
		'previous_question',
		'other',
	];

	$allowed_mime_types = [
		'application/pdf' => '.pdf',
		'image/jpeg' => '.jpg',
		'image/png' => '.png',
		'application/zip' => '.zip',
		'application/x-zip-compressed' => '.zip',
	];

	if ($title === '') {
		$errors[] = 'Title is required.';
	} elseif (strlen($title) > 200) {
		$errors[] = 'Title must not exceed 200 characters.';
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

	if ($material_type === '') {
		$errors[] = 'Material type is required.';
	} elseif (!in_array($material_type, $allowed_material_types, true)) {
		$errors[] = 'Please select a valid material type.';
	}

	if (!isset($_FILES['material_file']) || ($_FILES['material_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
		$errors[] = 'A PDF file is required.';
	} elseif (($_FILES['material_file']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
		$errors[] = 'The uploaded file could not be processed.';
	} else {
		$file_size = (int) ($_FILES['material_file']['size'] ?? 0);
		if ($file_size > 100 * 1024 * 1024) {
			$errors[] = 'The file must be 100MB or smaller.';
		}

		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		if ($finfo === false) {
			$errors[] = 'Unable to inspect the uploaded file.';
		} else {
			$tmp_name = $_FILES['material_file']['tmp_name'] ?? '';
			$detected_mime_type = $tmp_name !== '' ? finfo_file($finfo, $tmp_name) : false;
			if ($detected_mime_type === false || !array_key_exists($detected_mime_type, $allowed_mime_types)) {
				$errors[] = 'Only PDF, JPEG, PNG, or ZIP files are allowed.';
			}

			finfo_close($finfo);
		}
	}

	if (empty($errors)) {
		try {
			$generated_filename = bin2hex(random_bytes(16)) . $allowed_mime_types[$detected_mime_type];
		} catch (Exception $exception) {
			$errors[] = 'Unable to generate a filename for the uploaded file.';
		}

		if (empty($errors)) {
			$target_path = __DIR__ . '/../uploads/materials/' . $generated_filename;
			$relative_path = 'uploads/materials/' . $generated_filename;

			if (!move_uploaded_file($_FILES['material_file']['tmp_name'], $target_path)) {
				$errors[] = 'Unable to save the uploaded file.';
			} else {
				try {
					$user_id = (int) $_SESSION['user_id'];
					$insert_stmt = $conn->prepare('INSERT INTO materials (user_id, title, department, course_name, faculty_name, material_type, file_path) VALUES (?, ?, ?, ?, ?, ?, ?)');
					$insert_stmt->bind_param('issssss', $user_id, $title, $department, $course_name, $faculty_name, $material_type, $relative_path);
					$insert_stmt->execute();
					$insert_stmt->close();

					header('Location: index.php');
					exit;
				} catch (Throwable $exception) {
					if (file_exists($target_path)) {
						unlink($target_path);
					}
					$errors[] = 'Unable to upload the material. Please try again.';
				}
			}
		}
	}
}

$page_title = 'Upload study material — EWU Study Point';
require_once '../includes/header.php';
?>

<main>
	<div class="notice-card form-card">
		<h1>Upload study material</h1>

		<?php if (!empty($errors)): ?>
			<div class="form-errors">
				<ul>
					<?php foreach ($errors as $error): ?>
						<li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<form method="POST" enctype="multipart/form-data" action="upload.php">
			<div>
				<label for="title">Title</label>
				<input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>">
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
				<label for="material_type">Material Type</label>
				<select id="material_type" name="material_type">
					<option value="hand_notes" <?php echo $material_type === 'hand_notes' ? 'selected' : ''; ?>>Hand notes</option>
					<option value="lecture_sheet" <?php echo $material_type === 'lecture_sheet' ? 'selected' : ''; ?>>Lecture sheet</option>
					<option value="lecture_slide" <?php echo $material_type === 'lecture_slide' ? 'selected' : ''; ?>>Lecture slide</option>
					<option value="term_paper" <?php echo $material_type === 'term_paper' ? 'selected' : ''; ?>>Term paper</option>
					<option value="previous_question" <?php echo $material_type === 'previous_question' ? 'selected' : ''; ?>>Previous question</option>
					<option value="other" <?php echo $material_type === 'other' ? 'selected' : ''; ?>>Other</option>
				</select>
			</div>

			<div>
				<label for="material_file">Upload file - PDF, image, or ZIP (required, max 100MB)</label>
				<input type="file" id="material_file" name="material_file" accept="application/pdf,image/jpeg,image/png,application/zip" required>
			</div>

			<button type="submit">Upload material</button>
		</form>
	</div>
</main>

<?php require_once '../includes/footer.php'; ?>
