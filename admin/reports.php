<?php

require_once '../includes/admin_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'dismiss') {
	$report_id = (int) ($_POST['report_id'] ?? 0);

	if ($report_id > 0) {
		$dismiss_stmt = $conn->prepare('UPDATE reports SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?');
		$status = 'dismissed';
		$reviewed_by = (int) $_SESSION['user_id'];
		$dismiss_stmt->bind_param('sii', $status, $reviewed_by, $report_id);
		$dismiss_stmt->execute();
		$dismiss_stmt->close();
	}

	header('Location: reports.php');
	exit;
}

$reports_stmt = $conn->prepare(
	'SELECT r.id, r.reason, r.created_at, r.discussion_id, r.comment_id, r.material_id, reporter.name AS reporter_name
	 FROM reports r
	 JOIN users reporter ON r.reporter_id = reporter.id
	 WHERE r.status = ?
	 ORDER BY r.created_at ASC'
);
$pending_status = 'pending';
$reports_stmt->bind_param('s', $pending_status);
$reports_stmt->execute();
$reports_result = $reports_stmt->get_result();
$reports = $reports_result->fetch_all(MYSQLI_ASSOC);
$reports_stmt->close();

foreach ($reports as &$report) {
	$report['content_type'] = 'Unknown';
	$report['content_preview'] = '[content no longer exists]';
	$report['content_link'] = null;

	if (!empty($report['discussion_id'])) {
		$discussion_stmt = $conn->prepare('SELECT title FROM discussions WHERE id = ?');
		$discussion_id = (int) $report['discussion_id'];
		$discussion_stmt->bind_param('i', $discussion_id);
		$discussion_stmt->execute();
		$discussion_result = $discussion_stmt->get_result();
		$discussion_row = $discussion_result->fetch_assoc();
		$discussion_stmt->close();

		$report['content_type'] = 'Discussion';
		if ($discussion_row) {
			$report['content_preview'] = $discussion_row['title'];
			$report['content_link'] = '../discussions/view.php?id=' . $discussion_id;
		}
	} elseif (!empty($report['comment_id'])) {
		$comment_stmt = $conn->prepare('SELECT content, discussion_id FROM comments WHERE id = ?');
		$comment_id = (int) $report['comment_id'];
		$comment_stmt->bind_param('i', $comment_id);
		$comment_stmt->execute();
		$comment_result = $comment_stmt->get_result();
		$comment_row = $comment_result->fetch_assoc();
		$comment_stmt->close();

		$report['content_type'] = 'Comment';
		if ($comment_row) {
			$report['content_preview'] = mb_strimwidth($comment_row['content'], 0, 80, '...');
			$report['content_link'] = '../discussions/view.php?id=' . (int) $comment_row['discussion_id'];
		}
	} elseif (!empty($report['material_id'])) {
		$material_stmt = $conn->prepare('SELECT title FROM materials WHERE id = ?');
		$material_id = (int) $report['material_id'];
		$material_stmt->bind_param('i', $material_id);
		$material_stmt->execute();
		$material_result = $material_stmt->get_result();
		$material_row = $material_result->fetch_assoc();
		$material_stmt->close();

		$report['content_type'] = 'Material';
		if ($material_row) {
			$report['content_preview'] = $material_row['title'];
			$report['content_link'] = '../materials/index.php';
		}
	}
}
unset($report);

$page_title = 'Reports queue — EWU Study Point';
require_once '../includes/header.php';
?>

<main>
	<div class="admin-links">
		<a href="dashboard.php">Dashboard</a>
		<a href="users.php">Manage users</a>
		<a href="moderate.php">Moderate content</a>
	</div>

	<div class="notice-card">
		<h1>Reports queue</h1>

		<?php if (empty($reports)): ?>
			<p>No pending reports.</p>
		<?php else: ?>
			<div style="overflow-x: auto;">
				<table>
					<thead>
						<tr>
							<th>Reported content</th>
							<th>Reason</th>
							<th>Reporter</th>
							<th>Reported date</th>
							<th>Action</th>
						</tr>
						</thead>
						<tbody>
							<?php foreach ($reports as $report): ?>
								<tr>
									<td>
										<strong><?php echo htmlspecialchars($report['content_type'], ENT_QUOTES, 'UTF-8'); ?>:</strong>
										<?php if ($report['content_link'] !== null): ?>
											<a href="<?php echo htmlspecialchars($report['content_link'], ENT_QUOTES, 'UTF-8'); ?>">
												<?php echo htmlspecialchars($report['content_preview'], ENT_QUOTES, 'UTF-8'); ?>
											</a>
										<?php else: ?>
											<?php echo htmlspecialchars($report['content_preview'], ENT_QUOTES, 'UTF-8'); ?>
										<?php endif; ?>
									</td>
									<td><?php echo htmlspecialchars($report['reason'], ENT_QUOTES, 'UTF-8'); ?></td>
									<td><?php echo htmlspecialchars($report['reporter_name'], ENT_QUOTES, 'UTF-8'); ?></td>
									<td><?php echo htmlspecialchars(date('M j, Y', strtotime($report['created_at'])), ENT_QUOTES, 'UTF-8'); ?></td>
									<td>
										<form method="POST" action="reports.php">
											<input type="hidden" name="action" value="dismiss">
											<input type="hidden" name="report_id" value="<?php echo (int) $report['id']; ?>">
											<button type="submit">Dismiss</button>
										</form>
										<?php if ($report['content_link'] !== null): ?>
											<form method="POST" action="moderate.php" onsubmit="return confirm('Permanently delete this content? This cannot be undone.');">
												<input type="hidden" name="content_type" value="<?php echo strtolower($report['content_type']); ?>">
												<input type="hidden" name="content_id" value="<?php echo (int) ($report['discussion_id'] ?: $report['comment_id'] ?: $report['material_id']); ?>">
												<button type="submit">Delete content</button>
											</form>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
			</div>
		<?php endif; ?>
	</div>
</main>

<?php require_once '../includes/footer.php'; ?>
