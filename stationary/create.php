<?php

require_once '../includes/auth_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

$errors = [];
$title = '';
$category = '';
$price = '';
$condition_type = 'used';
$contact_info = '';
$description = '';

$allowed_categories = [
	'books' => 'Books & Textbooks',
	'calculator' => 'Scientific Calculators',
	'drawing_tools' => 'Drawing & Drafting Tools',
	'lab_coat' => 'Lab Coats (CHE/PHY/BIO)',
	'electronics' => 'Electronics & Project Kits',
	'stationery' => 'Notebooks & General Stationery',
	'other' => 'Other Campus Essentials',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$title = trim($_POST['title'] ?? '');
	$category = trim($_POST['category'] ?? '');
	$price = trim($_POST['price'] ?? '0');
	$condition_type = trim($_POST['condition_type'] ?? 'used');
	$contact_info = trim($_POST['contact_info'] ?? '');
	$description = trim($_POST['description'] ?? '');

	if ($title === '') {
		$errors[] = 'Please enter an item title.';
	} elseif (strlen($title) > 200) {
		$errors[] = 'Item title cannot exceed 200 characters.';
	}

	if (!array_key_exists($category, $allowed_categories)) {
		$errors[] = 'Please select a valid category.';
	}

	if (!is_numeric($price) || (float) $price < 0) {
		$errors[] = 'Please enter a valid price (e.g. 350).';
	}

	if (!in_array($condition_type, ['new', 'like_new', 'used'], true)) {
		$errors[] = 'Please select a valid item condition.';
	}

	if ($contact_info === '') {
		$errors[] = 'Please provide contact information (Phone number, WhatsApp, or Email) so buyers can reach you.';
	}

	if ($description === '') {
		$errors[] = 'Please provide a short description of the item.';
	}

	$uploaded_image_path = null;

	if (isset($_FILES['item_image']) && ($_FILES['item_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
		$file = $_FILES['item_image'];
		if ($file['error'] !== UPLOAD_ERR_OK) {
			$errors[] = 'Failed to process the uploaded photo.';
		} else {
			$allowed_img_types = ['image/jpeg' => '.jpg', 'image/png' => '.png', 'image/webp' => '.webp'];
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$detected_type = finfo_file($finfo, $file['tmp_name']);
			finfo_close($finfo);

			if (!array_key_exists($detected_type, $allowed_img_types)) {
				$errors[] = 'Item photo must be a JPG, PNG, or WebP image.';
			} elseif ($file['size'] > 10 * 1024 * 1024) {
				$errors[] = 'Item photo must be less than 10MB.';
			} else {
				$ext = $allowed_img_types[$detected_type];
				$filename = 'item_' . bin2hex(random_bytes(8)) . '_' . time() . $ext;
				$target_dir = __DIR__ . '/../uploads/stationary/';
				if (!is_dir($target_dir)) {
					mkdir($target_dir, 0777, true);
				}
				$target_path = $target_dir . $filename;
				if (move_uploaded_file($file['tmp_name'], $target_path)) {
					$uploaded_image_path = 'uploads/stationary/' . $filename;
				} else {
					$errors[] = 'Could not save the uploaded image.';
				}
			}
		}
	}

	if (empty($errors)) {
		$user_id = (int) $_SESSION['user_id'];
		$price_float = (float) $price;

		$stmt = $conn->prepare('INSERT INTO stationary_items (user_id, title, category, price, condition_type, contact_info, description, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
		$stmt->bind_param('issdssss', $user_id, $title, $category, $price_float, $condition_type, $contact_info, $description, $uploaded_image_path);
		$stmt->execute();
		$new_id = $stmt->insert_id;
		$stmt->close();

		header('Location: view.php?id=' . $new_id);
		exit;
	}
}

$page_title = 'Post an Item for Sale — EWU Study Point';
require_once '../includes/header.php';
?>

<main>
	<div class="form-card" style="max-width: 650px;">
		<h1>Post an Item for Sale</h1>
		<p style="color: var(--text-muted); font-size: 13.5px; margin-top: -12px; margin-bottom: 20px;">
			List your used books, calculators, lab equipment, or campus essentials for other East West University students to buy.
		</p>

		<?php if (!empty($errors)): ?>
			<div class="form-errors">
				<ul>
					<?php foreach ($errors as $error): ?>
						<li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<form method="POST" action="create.php" enctype="multipart/form-data">
			<div class="form-group">
				<label for="title">Item Title *</label>
				<input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>" placeholder="e.g. Casio fx-991EX ClassWiz Calculator" required>
			</div>

			<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
				<div class="form-group">
					<label for="category">Category *</label>
					<select id="category" name="category" class="form-control" required>
						<option value="">Select Category</option>
						<?php foreach ($allowed_categories as $key => $label): ?>
							<option value="<?php echo $key; ?>" <?php echo $category === $key ? 'selected' : ''; ?>>
								<?php echo $label; ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="form-group">
					<label for="condition_type">Condition *</label>
					<select id="condition_type" name="condition_type" class="form-control" required>
						<option value="new" <?php echo $condition_type === 'new' ? 'selected' : ''; ?>>Brand New</option>
						<option value="like_new" <?php echo $condition_type === 'like_new' ? 'selected' : ''; ?>>Like New (Barely Used)</option>
						<option value="used" <?php echo $condition_type === 'used' ? 'selected' : ''; ?>>Used / Pre-owned</option>
					</select>
				</div>
			</div>

			<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
				<div class="form-group">
					<label for="price">Asking Price (BDT &#2547;) *</label>
					<input type="number" step="1" min="0" id="price" name="price" class="form-control" value="<?php echo htmlspecialchars($price, ENT_QUOTES, 'UTF-8'); ?>" placeholder="e.g. 500" required>
				</div>

				<div class="form-group">
					<label for="contact_info">Contact Phone / WhatsApp *</label>
					<input type="text" id="contact_info" name="contact_info" class="form-control" value="<?php echo htmlspecialchars($contact_info, ENT_QUOTES, 'UTF-8'); ?>" placeholder="e.g. 01711223344 (Call/WhatsApp)" required>
				</div>
			</div>

			<div class="form-group">
				<label for="description">Item Description &amp; Details *</label>
				<textarea id="description" name="description" class="form-control" rows="4" placeholder="Mention item condition, which course/semester it was used for, included accessories, pickup spot (e.g. EWU Cafeteria / Library)..." required><?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?></textarea>
			</div>

			<div class="form-group">
				<label for="item_image">Upload Item Photo (Optional, max 10MB)</label>
				<input type="file" id="item_image" name="item_image" accept="image/jpeg,image/png,image/webp" class="form-control">
			</div>

			<div style="display: flex; gap: 12px; margin-top: 8px;">
				<button type="submit" class="btn-accent" style="flex: 1; padding: 12px;">Publish Item for Sale</button>
				<a href="index.php" class="btn-secondary" style="padding: 12px 20px;">Cancel</a>
			</div>
		</form>
	</div>
</main>

<?php require_once '../includes/footer.php'; ?>
