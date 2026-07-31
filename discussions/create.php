<?php

require_once '../includes/auth_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

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

	$validated_uploads = [];

	if (isset($_FILES['attachments']) && is_array($_FILES['attachments']['name'])) {
		$submitted_file_count = 0;
		$file_indexes = array_keys($_FILES['attachments']['name']);

		foreach ($file_indexes as $index) {
			if (($_FILES['attachments']['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
				$submitted_file_count++;
			}
		}

		if ($submitted_file_count > 5) {
			$errors[] = 'You can attach a maximum of 5 files.';
		} else {
			$allowed_mime_types = [
				'image/jpeg' => '.jpg',
				'image/png' => '.png',
				'application/pdf' => '.pdf',
			];

			$finfo = finfo_open(FILEINFO_MIME_TYPE);

			if ($finfo === false) {
				$errors[] = 'Unable to inspect uploaded files.';
			} else {
				foreach ($file_indexes as $index) {
					$error_code = $_FILES['attachments']['error'][$index] ?? UPLOAD_ERR_NO_FILE;

					if ($error_code === UPLOAD_ERR_NO_FILE) {
						continue;
					}

					if ($error_code !== UPLOAD_ERR_OK) {
						$errors[] = 'One of the uploaded files could not be processed.';
						continue;
					}

					$file_size = (int) ($_FILES['attachments']['size'][$index] ?? 0);
					if ($file_size > 5 * 1024 * 1024) {
						$errors[] = 'Each file must be 5MB or smaller.';
						continue;
					}

					$tmp_name = $_FILES['attachments']['tmp_name'][$index] ?? '';
					$detected_mime_type = $tmp_name !== '' ? finfo_file($finfo, $tmp_name) : false;

					if ($detected_mime_type === false || !array_key_exists($detected_mime_type, $allowed_mime_types)) {
						$errors[] = 'Only JPEG images, PNG images, and PDF files are allowed.';
						continue;
					}

					try {
						$generated_filename = bin2hex(random_bytes(16)) . $allowed_mime_types[$detected_mime_type];
					} catch (Exception $exception) {
						$errors[] = 'Unable to generate a filename for one of the uploaded files.';
						continue;
					}

					$target_path = __DIR__ . '/../uploads/discussions/' . $generated_filename;
					$relative_path = 'uploads/discussions/' . $generated_filename;

					$validated_uploads[] = [
						'tmp_name' => $tmp_name,
						'target_path' => $target_path,
						'relative_path' => $relative_path,
						'file_type' => $detected_mime_type,
					];
				}

				finfo_close($finfo);
			}
		}
	}

	if (empty($errors)) {
		$moved_files = [];

		foreach ($validated_uploads as $upload) {
			if (!move_uploaded_file($upload['tmp_name'], $upload['target_path'])) {
				$errors[] = 'One of the uploaded files could not be saved.';
				break;
			}

			$moved_files[] = $upload['target_path'];
		}

		if (!empty($errors)) {
			foreach ($moved_files as $moved_file) {
				if (is_file($moved_file)) {
					unlink($moved_file);
				}
			}
		} else {
			try {
				$conn->begin_transaction();

				$user_id = (int) $_SESSION['user_id'];
				$stmt = $conn->prepare('INSERT INTO discussions (user_id, title, description, department, course_name, faculty_name, topic) VALUES (?, ?, ?, ?, ?, ?, ?)');
				$stmt->bind_param('issssss', $user_id, $title, $description, $department, $course_name, $faculty_name, $topic);
				$stmt->execute();
				$new_discussion_id = $conn->insert_id;
				$stmt->close();

			if (!empty($validated_uploads)) {
					$attachment_stmt = $conn->prepare('INSERT INTO discussion_attachments (discussion_id, file_path, file_type) VALUES (?, ?, ?)');

					foreach ($validated_uploads as $upload) {
						$attachment_stmt->bind_param('iss', $new_discussion_id, $upload['relative_path'], $upload['file_type']);
						$attachment_stmt->execute();
					}

					$attachment_stmt->close();
				}

				$conn->commit();

				header('Location: view.php?id=' . $new_discussion_id);
				exit;
			} catch (Throwable $exception) {
				try {
					$conn->rollback();
				} catch (Throwable $rollback_exception) {
				}

				foreach ($moved_files as $moved_file) {
					if (is_file($moved_file)) {
						unlink($moved_file);
					}
				}

				$errors[] = 'Unable to create the discussion. Please try again.';
			}
		}
	}
}

$page_title = 'Ask a question — EWU Study Point';
require_once '../includes/header.php';
?>

<main>
	<div class="notice-card form-card">
		<h1>Ask a question</h1>

		<?php if (!empty($errors)): ?>
			<div class="form-errors">
				<ul>
					<?php foreach ($errors as $error): ?>
						<li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<form method="POST" enctype="multipart/form-data" action="create.php">
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

			<div>
				<label for="attachments">Attach images or PDFs (optional, up to 5 files, 5MB each)</label>
				<input type="file" id="attachments" name="attachments[]" multiple accept="image/jpeg,image/png,application/pdf">
			</div>

			<button type="submit">Post discussion</button>
		</form>
	</div>
</main>

<?php require_once '../includes/footer.php'; ?>
