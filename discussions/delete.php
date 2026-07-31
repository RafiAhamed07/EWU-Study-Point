<?php

require_once '../includes/auth_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: index.php');
	exit;
}

$discussion_id = (int) ($_POST['id'] ?? 0);
if ($discussion_id <= 0) {
	header('Location: index.php');
	exit;
}

$discussion_stmt = $conn->prepare('SELECT user_id FROM discussions WHERE id = ?');
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

$attachment_paths = [];
$attachments_stmt = $conn->prepare('SELECT file_path FROM discussion_attachments WHERE discussion_id = ?');
$attachments_stmt->bind_param('i', $discussion_id);
$attachments_stmt->execute();
$attachments_result = $attachments_stmt->get_result();
while ($row = $attachments_result->fetch_assoc()) {
	$attachment_paths[] = (string) $row['file_path'];
}
$attachments_stmt->close();

$delete_succeeded = false;

try {
	$conn->begin_transaction();

	$current_user_id = (int) $_SESSION['user_id'];
	$delete_stmt = $conn->prepare('DELETE FROM discussions WHERE id = ? AND user_id = ?');
	$delete_stmt->bind_param('ii', $discussion_id, $current_user_id);
	$delete_stmt->execute();
	$delete_stmt->close();

	$conn->commit();
	$delete_succeeded = true;
} catch (Throwable $exception) {
	try {
		$conn->rollback();
	} catch (Throwable $rollback_exception) {
	}
}

if ($delete_succeeded) {
	foreach ($attachment_paths as $relative_path) {
		$absolute_path = __DIR__ . '/../' . ltrim($relative_path, '/');
		if (file_exists($absolute_path)) {
			unlink($absolute_path);
		}
	}
}

header('Location: index.php');
exit;
