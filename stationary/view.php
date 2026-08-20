<?php

require_once '../config/db.php';
require_once '../includes/functions.php';

$item_id = (int) ($_GET['id'] ?? 0);
if ($item_id <= 0) {
	header('Location: index.php');
	exit;
}

// Handle Status Toggle (Mark as Sold / Available)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status']) && isset($_SESSION['user_id'])) {
	$check_stmt = $conn->prepare('SELECT user_id, status FROM stationary_items WHERE id = ?');
	$check_stmt->bind_param('i', $item_id);
	$check_stmt->execute();
	$item_check = $check_stmt->get_result()->fetch_assoc();
	$check_stmt->close();

	if ($item_check && ((int) $item_check['user_id'] === (int) $_SESSION['user_id'] || is_admin())) {
		$new_status = $item_check['status'] === 'available' ? 'sold' : 'available';
		$upd_stmt = $conn->prepare('UPDATE stationary_items SET status = ? WHERE id = ?');
		$upd_stmt->bind_param('si', $new_status, $item_id);
		$upd_stmt->execute();
		$upd_stmt->close();
	}

	header('Location: view.php?id=' . $item_id);
	exit;
}

// Fetch Item details
$stmt = $conn->prepare('
	SELECT
		s.*,
		u.id AS seller_user_id,
		u.name AS seller_name,
		u.email AS seller_email,
		u.department AS seller_dept,
		u.student_id AS seller_student_id
	FROM stationary_items s
	JOIN users u ON s.user_id = u.id
	WHERE s.id = ?
');
$stmt->bind_param('i', $item_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
	header('Location: index.php');
	exit;
}

$allowed_categories = [
	'books' => 'Books & Textbooks',
	'calculator' => 'Scientific Calculators',
	'drawing_tools' => 'Drawing & Drafting Tools',
	'lab_coat' => 'Lab Coats',
	'electronics' => 'Electronics & Project Kits',
	'stationery' => 'Notebooks & General Stationery',
	'other' => 'Other Campus Essentials',
];

$condition_labels = [
	'new' => 'Brand New',
	'like_new' => 'Like New (Barely Used)',
	'used' => 'Used / Pre-owned'
];

$cat_name = $allowed_categories[$item['category']] ?? ucfirst($item['category']);
$cond_name = $condition_labels[$item['condition_type']] ?? ucfirst($item['condition_type']);
$is_owner = isset($_SESSION['user_id']) && ((int) $_SESSION['user_id'] === (int) $item['seller_user_id']);
$can_manage = $is_owner || is_admin();
$is_sold = $item['status'] === 'sold';

$page_title = $item['title'] . ' — Stationery Marketplace';
require_once '../includes/header.php';
?>

<main>
	<div style="margin-bottom: 16px;">
		<a href="index.php" style="color: var(--text-muted); font-size: 13.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
			&larr; Back to Stationery Marketplace
		</a>
	</div>

	<div style="display: grid; grid-template-columns: 1fr 340px; gap: 28px; align-items: start;">
		<!-- Left: Item details and photo -->
		<div class="form-card" style="margin: 0; max-width: 100%; padding: 28px;">
			<div style="display: flex; gap: 8px; margin-bottom: 12px; flex-wrap: wrap;">
				<span class="badge badge-blue"><?php echo htmlspecialchars($cat_name, ENT_QUOTES, 'UTF-8'); ?></span>
				<span class="badge badge-gray"><?php echo htmlspecialchars($cond_name, ENT_QUOTES, 'UTF-8'); ?></span>
				<?php if ($is_sold): ?>
					<span class="badge badge-red" style="font-weight: 800;">STATUS: SOLD</span>
				<?php else: ?>
					<span class="badge badge-green">STATUS: AVAILABLE</span>
				<?php endif; ?>
			</div>

			<h1 style="font-size: 24px; margin: 0 0 16px; color: var(--ewu-blue);">
				<?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?>
			</h1>

			<!-- Photo Gallery / Preview -->
			<?php if (!empty($item['image_path'])): ?>
				<div style="width: 100%; max-height: 420px; border-radius: 8px; overflow: hidden; background: #000; margin-bottom: 24px; border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center;">
					<img src="<?php echo htmlspecialchars('../' . ltrim($item['image_path'], '/'), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?>" style="max-width: 100%; max-height: 420px; object-fit: contain;">
				</div>
			<?php endif; ?>

			<h3 style="font-size: 16px; font-weight: 700; color: var(--ewu-blue); margin: 0 0 8px;">Item Description</h3>
			<div style="font-size: 14.5px; line-height: 1.7; color: var(--text-body); margin-bottom: 24px; white-space: pre-wrap; background: var(--bg-subtle); padding: 16px; border-radius: 6px; border: 1px solid var(--border-color);"><?php echo htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8'); ?></div>

			<div style="display: flex; justify-content: space-between; font-size: 12.5px; color: var(--text-muted); padding-top: 12px; border-top: 1px solid var(--border-color);">
				<span>Listed on <?php echo date('F j, Y, g:i A', strtotime($item['created_at'])); ?></span>
				<span>Listing ID: #EWU-ST-<?php echo (int) $item['id']; ?></span>
			</div>
		</div>

		<!-- Right: Price, Seller & Contact Information -->
		<div style="display: flex; flex-direction: column; gap: 20px;">
			<!-- Price & Contact Card -->
			<div class="form-card" style="margin: 0; max-width: 100%; padding: 24px;">
				<div style="font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Asking Price</div>
				<div style="font-size: 32px; font-weight: 800; color: var(--ewu-red); margin: 4px 0 16px;">
					&#2547; <?php echo number_format($item['price'], 0); ?>
				</div>

				<div style="background: #E6F0FA; border: 1px solid #B8D5F2; border-radius: 6px; padding: 14px; margin-bottom: 16px;">
					<div style="font-size: 12px; font-weight: 700; color: var(--ewu-blue); text-transform: uppercase; margin-bottom: 4px;">Seller Contact Information</div>
					<div style="font-size: 15px; font-weight: 700; color: var(--ewu-blue); word-break: break-word;">
						📞 <?php echo htmlspecialchars($item['contact_info'], ENT_QUOTES, 'UTF-8'); ?>
					</div>
					<div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
						✉️ <?php echo htmlspecialchars($item['seller_email'], ENT_QUOTES, 'UTF-8'); ?>
					</div>
				</div>

				<!-- Seller Profile Box -->
				<div style="display: flex; align-items: center; gap: 12px; padding: 12px 0; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); margin-bottom: 16px;">
					<div style="width: 42px; height: 42px; border-radius: 50%; background: var(--ewu-blue); color: #FFF; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 16px;">
						<?php echo strtoupper(substr(trim($item['seller_name']), 0, 1)); ?>
					</div>
					<div>
						<div style="font-weight: 700; color: var(--text-main); font-size: 14px;">
							<a href="../profile/view.php?id=<?php echo (int) $item['seller_user_id']; ?>">
								<?php echo htmlspecialchars($item['seller_name'], ENT_QUOTES, 'UTF-8'); ?>
							</a>
						</div>
						<div style="font-size: 12px; color: var(--text-muted);">
							<?php echo htmlspecialchars($item['seller_dept'], ENT_QUOTES, 'UTF-8'); ?> &middot; Student ID: <?php echo htmlspecialchars($item['seller_student_id'], ENT_QUOTES, 'UTF-8'); ?>
						</div>
					</div>
				</div>

				<a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $item['contact_info']); ?>" class="btn-accent" style="width: 100%; padding: 10px; font-size: 14px; text-align: center; margin-bottom: 8px;">
					Call Seller
				</a>

				<a href="mailto:<?php echo htmlspecialchars($item['seller_email'], ENT_QUOTES, 'UTF-8'); ?>?subject=Inquiry%20about%20<?php echo urlencode($item['title']); ?>" class="btn-secondary" style="width: 100%; padding: 9px; font-size: 13px; text-align: center;">
					Email Seller
				</a>
			</div>

			<!-- Owner Management Card (if owner/admin) -->
			<?php if ($can_manage): ?>
				<div class="form-card" style="margin: 0; max-width: 100%; padding: 20px; border-left: 4px solid var(--ewu-gold);">
					<div style="font-size: 13px; font-weight: 700; color: var(--ewu-blue); text-transform: uppercase; margin-bottom: 12px;">Listing Owner Controls</div>
					<form method="POST" action="view.php?id=<?php echo (int) $item['id']; ?>" style="margin-bottom: 10px;">
						<input type="hidden" name="toggle_status" value="1">
						<button type="submit" class="btn-secondary" style="width: 100%; padding: 8px; font-size: 13px;">
							<?php echo $is_sold ? '🔄 Mark as Available' : '✅ Mark as Sold'; ?>
						</button>
					</form>

					<form method="POST" action="delete.php" onsubmit="return confirm('Are you sure you want to permanently delete this listing?');">
						<input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>">
						<button type="submit" class="btn-outline-danger" style="width: 100%; padding: 8px; font-size: 13px;">
							🗑️ Delete Listing
						</button>
					</form>
				</div>
			<?php endif; ?>
		</div>
	</div>
</main>

<?php require_once '../includes/footer.php'; ?>
