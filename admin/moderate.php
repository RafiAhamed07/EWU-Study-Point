<?php

require_once '../includes/admin_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

function redirect_to_dashboard(): void
{
	header('Location: dashboard.php');
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	redirect_to_dashboard();
}

$content_type = $_POST['content_type'] ?? '';
$content_id = (int) ($_POST['content_id'] ?? 0);

if (!in_array($content_type, ['discussion', 'comment', 'material'], true) || $content_id <= 0) {
	header('Location: reports.php');
    exit;
}

$files_to_unlink = [];
$delete_succeeded = false;

try {
	$conn->begin_transaction();

	if ($content_type === 'discussion') {
		$attachment_stmt = $conn->prepare('SELECT file_path FROM discussion_attachments WHERE discussion_id = ?');
		$attachment_stmt->bind_param('i', $content_id);
		$attachment_stmt->execute();
		$attachment_result = $attachment_stmt->get_result();
		while ($row = $attachment_result->fetch_assoc()) {
			$files_to_unlink[] = (string) $row['file_path'];
		}
		$attachment_stmt->close();

		$review_stmt = $conn->prepare("UPDATE reports SET status = 'reviewed', reviewed_by = ?, reviewed_at = NOW() WHERE discussion_id = ? AND status = 'pending'");
		$reviewed_by = (int) $_SESSION['user_id'];
		$review_stmt->bind_param('ii', $reviewed_by, $content_id);
		$review_stmt->execute();
		$review_stmt->close();

		$delete_stmt = $conn->prepare('DELETE FROM discussions WHERE id = ?');
		$delete_stmt->bind_param('i', $content_id);
		$delete_stmt->execute();
		$delete_stmt->close();
	} elseif ($content_type === 'comment') {
		$attachment_stmt = $conn->prepare('SELECT file_path FROM comment_attachments WHERE comment_id = ?');
		$attachment_stmt->bind_param('i', $content_id);
		$attachment_stmt->execute();
		$attachment_result = $attachment_stmt->get_result();
		while ($row = $attachment_result->fetch_assoc()) {
			$files_to_unlink[] = (string) $row['file_path'];
		}
		$attachment_stmt->close();

		$review_stmt = $conn->prepare("UPDATE reports SET status = 'reviewed', reviewed_by = ?, reviewed_at = NOW() WHERE comment_id = ? AND status = 'pending'");
		$reviewed_by = (int) $_SESSION['user_id'];
		$review_stmt->bind_param('ii', $reviewed_by, $content_id);
		$review_stmt->execute();
		$review_stmt->close();

		$delete_stmt = $conn->prepare('DELETE FROM comments WHERE id = ?');
		$delete_stmt->bind_param('i', $content_id);
		$delete_stmt->execute();
		$delete_stmt->close();
	} else {
		$material_stmt = $conn->prepare('SELECT file_path FROM materials WHERE id = ?');
		$material_stmt->bind_param('i', $content_id);
		$material_stmt->execute();
		$material_result = $material_stmt->get_result();
		$material_row = $material_result->fetch_assoc();
		$material_stmt->close();

		if (!$material_row) {
			throw new RuntimeException('Material not found.');
		}

		$files_to_unlink[] = (string) $material_row['file_path'];

		$review_stmt = $conn->prepare("UPDATE reports SET status = 'reviewed', reviewed_by = ?, reviewed_at = NOW() WHERE material_id = ? AND status = 'pending'");
		$reviewed_by = (int) $_SESSION['user_id'];
		$review_stmt->bind_param('ii', $reviewed_by, $content_id);
		$review_stmt->execute();
		$review_stmt->close();

		$delete_stmt = $conn->prepare('DELETE FROM materials WHERE id = ?');
		$delete_stmt->bind_param('i', $content_id);
		$delete_stmt->execute();
		$delete_stmt->close();
	}

	$conn->commit();
	$delete_succeeded = true;
} catch (Throwable $exception) {
	try {
		$conn->rollback();
	} catch (Throwable $rollback_exception) {
	}
}

if ($delete_succeeded) {
	foreach ($files_to_unlink as $relative_path) {
		$absolute_path = __DIR__ . '/../' . ltrim($relative_path, '/');
		if (file_exists($absolute_path)) {
			unlink($absolute_path);
		}
	}
}

redirect_to_dashboard();
