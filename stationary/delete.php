<?php

require_once '../includes/auth_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: index.php');
	exit;
}

$item_id = (int) ($_POST['id'] ?? 0);
if ($item_id <= 0) {
	header('Location: index.php');
	exit;
}

$stmt = $conn->prepare('SELECT user_id, image_path FROM stationary_items WHERE id = ?');
$stmt->bind_param('i', $item_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($item) {
	$is_owner = (int) $item['user_id'] === (int) $_SESSION['user_id'];
	if ($is_owner || is_admin()) {
		if (!empty($item['image_path'])) {
			$img_path = __DIR__ . '/../' . ltrim($item['image_path'], '/');
			if (file_exists($img_path)) {
				@unlink($img_path);
			}
		}

		$del_stmt = $conn->prepare('DELETE FROM stationary_items WHERE id = ?');
		$del_stmt->bind_param('i', $item_id);
		$del_stmt->execute();
		$del_stmt->close();
	}
}

header('Location: index.php');
exit;
