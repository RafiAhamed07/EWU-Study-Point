<?php

require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: index.php');
	exit;
}

$comment_id = (int) ($_POST['comment_id'] ?? 0);
$discussion_id = (int) ($_POST['discussion_id'] ?? 0);

if ($comment_id <= 0 || $discussion_id <= 0) {
	header('Location: index.php');
	exit;
}

$comment_stmt = $conn->prepare('SELECT user_id FROM comments WHERE id = ?');
$comment_stmt->bind_param('i', $comment_id);
$comment_stmt->execute();
$comment_result = $comment_stmt->get_result();
$comment = $comment_result->fetch_assoc();
$comment_stmt->close();

if (!$comment) {
	header('Location: discussions/view.php?id=' . $discussion_id);
	exit;
}

if ((int) $comment['user_id'] !== (int) $_SESSION['user_id']) {
	header('Location: discussions/view.php?id=' . $discussion_id);
	exit;
}

$attachment_paths = [];
$attachments_stmt = $conn->prepare('SELECT file_path FROM comment_attachments WHERE comment_id = ?');
$attachments_stmt->bind_param('i', $comment_id);
$attachments_stmt->execute();
$attachments_result = $attachments_stmt->get_result();
while ($row = $attachments_result->fetch_assoc()) {
	$attachment_paths[] = (string) $row['file_path'];
}
$attachments_stmt->close();

$delete_succeeded = false;

try {
	$conn->begin_transaction();

	$reviewed_by = (int) $_SESSION['user_id'];
	$review_stmt = $conn->prepare("UPDATE reports SET status = 'reviewed', reviewed_by = ?, reviewed_at = NOW() WHERE comment_id = ? AND status = 'pending'");
	$review_stmt->bind_param('ii', $reviewed_by, $comment_id);
	$review_stmt->execute();
	$review_stmt->close();

	$current_user_id = (int) $_SESSION['user_id'];
	$delete_stmt = $conn->prepare('DELETE FROM comments WHERE id = ? AND user_id = ?');
	$delete_stmt->bind_param('ii', $comment_id, $current_user_id);
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
		$absolute_path = __DIR__ . '/' . ltrim($relative_path, '/');
		if (file_exists($absolute_path)) {
			unlink($absolute_path);
		}
	}
}

header('Location: discussions/view.php?id=' . $discussion_id);
exit;