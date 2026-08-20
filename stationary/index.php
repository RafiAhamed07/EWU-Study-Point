<?php

require_once '../config/db.php';
require_once '../includes/functions.php';

$category = trim($_GET['category'] ?? '');
$condition = trim($_GET['condition'] ?? '');
$search = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? 'all'); // all, available, sold

$allowed_categories = [
	'books' => 'Books & Textbooks',
	'calculator' => 'Scientific Calculators',
	'drawing_tools' => 'Drawing & Drafting',
	'lab_coat' => 'Lab Coats',
	'electronics' => 'Electronics & Kits',
	'stationery' => 'Stationery & Notebooks',
	'other' => 'Other Campus Essentials',
];

$where_clauses = [];
$params = [];
$types = '';

if ($category !== '' && array_key_exists($category, $allowed_categories)) {
	$where_clauses[] = 's.category = ?';
	$params[] = $category;
	$types .= 's';
}

if ($condition !== '' && in_array($condition, ['new', 'like_new', 'used'], true)) {
	$where_clauses[] = 's.condition_type = ?';
	$params[] = $condition;
	$types .= 's';
}

if ($status_filter === 'available') {
	$where_clauses[] = "s.status = 'available'";
} elseif ($status_filter === 'sold') {
	$where_clauses[] = "s.status = 'sold'";
}

if ($search !== '') {
	$where_clauses[] = '(s.title LIKE ? OR s.description LIKE ?)';
	$search_term = '%' . $search . '%';
	$params[] = $search_term;
	$params[] = $search_term;
	$types .= 'ss';
}

$where_sql = '';
if (!empty($where_clauses)) {
	$where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

$sql = "
	SELECT
		s.*,
		u.name AS seller_name,
		u.department AS seller_dept
	FROM stationary_items s
	JOIN users u ON s.user_id = u.id
	$where_sql
	ORDER BY s.status ASC, s.created_at DESC
";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
	$bind_values = [$types];
	foreach ($params as $index => $value) {
		$bind_values[] = &$params[$index];
	}
	call_user_func_array([$stmt, 'bind_param'], $bind_values);
}
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$category_icons = [
	'books' => '📚',
	'calculator' => '🔢',
	'drawing_tools' => '📐',
	'lab_coat' => '🥼',
	'electronics' => '💻',
	'stationery' => '✏️',
	'other' => '📦',
];

$page_title = 'Stationery & Student Marketplace — EWU Study Point';
require_once '../includes/header.php';
?>

<main>
	<div class="page-header">
		<div>
			<h1>Stationery &amp; Student Marketplace</h1>
			<p class="page-subtitle">Buy and sell course books, scientific calculators, lab coats, and campus essentials with fellow EWU students.</p>
		</div>
		<a href="create.php" class="btn-accent" style="font-size: 14px; padding: 10px 20px;">+ Post an Item for Sale</a>
	</div>

	<!-- Category Filter Tabs -->
	<div style="display: flex; gap: 8px; margin-bottom: 20px; overflow-x: auto; padding-bottom: 6px;">
		<a href="index.php" class="<?php echo $category === '' ? 'btn-primary' : 'btn-secondary'; ?>" style="padding: 7px 14px; font-size: 13px; white-space: nowrap;">
			🛒 All Items
		</a>
		<?php foreach ($allowed_categories as $key => $label): ?>
			<a href="index.php?category=<?php echo $key; ?>" class="<?php echo $category === $key ? 'btn-primary' : 'btn-secondary'; ?>" style="padding: 7px 14px; font-size: 13px; white-space: nowrap;">
				<?php echo $category_icons[$key] ?? '📦'; ?> <?php echo $label; ?>
			</a>
		<?php endforeach; ?>
	</div>

	<!-- Filter Search Bar -->
	<form method="GET" action="index.php" class="filter-bar">
		<?php if ($category !== ''): ?>
			<input type="hidden" name="category" value="<?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>">
		<?php endif; ?>

		<div class="form-group" style="flex: 2;">
			<label for="search">Keyword Search</label>
			<input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="e.g. Casio fx-991EX, Lab Coat, Thomas Calculus...">
		</div>

		<div class="form-group">
			<label for="condition">Item Condition</label>
			<select id="condition" name="condition">
				<option value="">All Conditions</option>
				<option value="new" <?php echo $condition === 'new' ? 'selected' : ''; ?>>Brand New</option>
				<option value="like_new" <?php echo $condition === 'like_new' ? 'selected' : ''; ?>>Like New</option>
				<option value="used" <?php echo $condition === 'used' ? 'selected' : ''; ?>>Used / Pre-owned</option>
			</select>
		</div>

		<div class="form-group">
			<label for="status">Availability</label>
			<select id="status" name="status">
				<option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Listings</option>
				<option value="available" <?php echo $status_filter === 'available' ? 'selected' : ''; ?>>Available Only</option>
				<option value="sold" <?php echo $status_filter === 'sold' ? 'selected' : ''; ?>>Sold Items</option>
			</select>
		</div>

		<button type="submit" class="btn-primary">Filter Items</button>
		<a href="index.php" class="btn-secondary">Reset</a>
	</form>

	<!-- Items Grid -->
	<?php if (empty($items)): ?>
		<div class="form-card" style="text-align: center; max-width: 600px; margin: 40px auto;">
			<div style="font-size: 48px; margin-bottom: 12px;">📦</div>
			<h2 style="font-size: 18px; color: var(--ewu-blue); margin: 0 0 8px;">No items found</h2>
			<p style="color: var(--text-muted); font-size: 14px; margin-bottom: 20px;">
				No listings matched your criteria. Be the first student to post an item in this category!
			</p>
			<a href="create.php" class="btn-accent">+ Post an Item</a>
		</div>
	<?php else: ?>
		<div class="card-grid">
			<?php foreach ($items as $item): ?>
				<?php
				$cat_key = $item['category'];
				$cat_name = $allowed_categories[$cat_key] ?? ucfirst($cat_key);
				$cat_icon = $category_icons[$cat_key] ?? '📦';
				$is_sold = $item['status'] === 'sold';
				$condition_labels = [
					'new' => 'Brand New',
					'like_new' => 'Like New',
					'used' => 'Used'
				];
				$cond_name = $condition_labels[$item['condition_type']] ?? ucfirst($item['condition_type']);
				?>
				<div class="item-card" onclick="window.location='view.php?id=<?php echo (int) $item['id']; ?>'">
					<div class="card-img-wrapper">
						<?php if (!empty($item['image_path'])): ?>
							<img src="<?php echo htmlspecialchars('../' . ltrim($item['image_path'], '/'), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?>" class="card-img">
						<?php else: ?>
							<div class="card-img-placeholder">
								<span><?php echo $cat_icon; ?></span>
								<span style="font-size: 12px; color: #94A3B8; font-weight: 600;"><?php echo $cat_name; ?></span>
							</div>
						<?php endif; ?>

						<?php if ($is_sold): ?>
							<span class="badge badge-red" style="position: absolute; top: 10px; right: 10px; z-index: 3; font-weight: 800;">SOLD</span>
						<?php else: ?>
							<span class="badge badge-green" style="position: absolute; top: 10px; right: 10px; z-index: 3;">Available</span>
						<?php endif; ?>

						<span class="badge badge-blue" style="position: absolute; bottom: 10px; left: 10px; z-index: 3;">
							<?php echo $cat_icon; ?> <?php echo $cat_name; ?>
						</span>
					</div>

					<div class="card-body">
						<div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 6px;">
							<span class="card-price">&#2547; <?php echo number_format($item['price'], 0); ?></span>
							<span class="badge badge-gray"><?php echo $cond_name; ?></span>
						</div>

						<a href="view.php?id=<?php echo (int) $item['id']; ?>" class="card-title">
							<?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?>
						</a>

						<p class="card-excerpt">
							<?php echo htmlspecialchars(mb_strimwidth($item['description'], 0, 110, '...'), ENT_QUOTES, 'UTF-8'); ?>
						</p>

						<div class="card-meta">
							<span><strong>Seller:</strong> <?php echo htmlspecialchars($item['seller_name'], ENT_QUOTES, 'UTF-8'); ?></span>
							<span><?php echo date('M j, Y', strtotime($item['created_at'])); ?></span>
						</div>
					</div>

					<div class="card-actions">
						<a href="view.php?id=<?php echo (int) $item['id']; ?>" class="btn-primary" style="flex: 1; padding: 7px 12px; font-size: 13px;">
							View Details &amp; Contact
						</a>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</main>

<?php require_once '../includes/footer.php'; ?>
