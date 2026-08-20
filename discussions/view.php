<?php

require_once '../config/db.php';
require_once '../includes/functions.php';

function bind_dynamic_params(mysqli_stmt $stmt, string $types, array $params): void
{
	$bind_values = [$types];

	foreach ($params as $index => $value) {
		$bind_values[] = &$params[$index];
	}

	call_user_func_array([$stmt, 'bind_param'], $bind_values);
}

$discussion_id = (int) ($_GET['id'] ?? 0);
if ($discussion_id <= 0) {
	header('Location: index.php');
	exit;
}

$discussion_stmt = $conn->prepare(
	'SELECT d.*, u.name AS author_name
	 FROM discussions d
	 JOIN users u ON d.user_id = u.id
	 WHERE d.id = ?'
);
$discussion_stmt->bind_param('i', $discussion_id);
$discussion_stmt->execute();
$discussion_result = $discussion_stmt->get_result();
$discussion = $discussion_result->fetch_assoc();
$discussion_stmt->close();

if (!$discussion) {
	header('Location: index.php');
	exit;
}

$attachment_stmt = $conn->prepare(
	'SELECT id, file_path, file_type
	 FROM discussion_attachments
	 WHERE discussion_id = ?'
);
$attachment_stmt->bind_param('i', $discussion_id);
$attachment_stmt->execute();
$attachment_result = $attachment_stmt->get_result();
$attachments = $attachment_result->fetch_all(MYSQLI_ASSOC);
$attachment_stmt->close();

$comments_stmt = $conn->prepare(
	'SELECT c.*, u.name AS author_name
	 FROM comments c
	 JOIN users u ON c.user_id = u.id
	 WHERE c.discussion_id = ?
	 ORDER BY c.created_at ASC'
);
$comments_stmt->bind_param('i', $discussion_id);
$comments_stmt->execute();
$comments_result = $comments_stmt->get_result();
$comments = $comments_result->fetch_all(MYSQLI_ASSOC);
$comments_stmt->close();

$comments_by_parent = [];
foreach ($comments as $comment) {
	$parent_key = (int) ($comment['parent_comment_id'] ?? 0);
	if (!isset($comments_by_parent[$parent_key])) {
		$comments_by_parent[$parent_key] = [];
	}
	$comments_by_parent[$parent_key][] = $comment;
}

$is_logged_in = isset($_SESSION['user_id']);
$is_owner = $is_logged_in && (int) $_SESSION['user_id'] === (int) $discussion['user_id'];

$user_discussion_vote = null;
$user_comment_votes = [];

if ($is_logged_in) {
	$user_id = (int) $_SESSION['user_id'];

	$discussion_vote_stmt = $conn->prepare(
		'SELECT vote_type
		 FROM discussion_votes
		 WHERE discussion_id = ? AND user_id = ?'
	);
	$discussion_vote_stmt->bind_param('ii', $discussion_id, $user_id);
	$discussion_vote_stmt->execute();
	$discussion_vote_result = $discussion_vote_stmt->get_result();
	$discussion_vote_row = $discussion_vote_result->fetch_assoc();
	if ($discussion_vote_row) {
		$user_discussion_vote = $discussion_vote_row['vote_type'];
	}
	$discussion_vote_stmt->close();

	if (!empty($comments)) {
		$comment_ids = array_map(static function (array $comment): int {
			return (int) $comment['id'];
		}, $comments);

		$placeholders = implode(',', array_fill(0, count($comment_ids), '?'));
		$comment_votes_sql =
			'SELECT comment_id, vote_type
			 FROM comment_votes
			 WHERE user_id = ? AND comment_id IN (' . $placeholders . ')';
		$comment_votes_stmt = $conn->prepare($comment_votes_sql);

		$types = 'i' . str_repeat('i', count($comment_ids));
		$params = array_merge([$user_id], $comment_ids);
		bind_dynamic_params($comment_votes_stmt, $types, $params);

		$comment_votes_stmt->execute();
		$comment_votes_result = $comment_votes_stmt->get_result();

		while ($vote_row = $comment_votes_result->fetch_assoc()) {
			$user_comment_votes[(int) $vote_row['comment_id']] = $vote_row['vote_type'];
		}

		$comment_votes_stmt->close();
	}
}

function render_comment_thread(array $comment, array $comments_by_parent, array $user_comment_votes, bool $is_logged_in, int $depth = 0): void
{
	global $discussion_id;

	$comment_id = (int) $comment['id'];
	$current_vote = $user_comment_votes[$comment_id] ?? null;
	$formatted_date = date('M j, Y', strtotime($comment['created_at']));
	$safe_content = nl2br(htmlspecialchars($comment['content'], ENT_QUOTES, 'UTF-8'));
	$children = $comments_by_parent[$comment_id] ?? [];
	?>
	<div class="comment" style="margin-left: <?php echo min($depth, 5) * 24; ?>px">
		<div>
			<strong><?php echo htmlspecialchars($comment['author_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
			<span><?php echo htmlspecialchars($formatted_date, ENT_QUOTES, 'UTF-8'); ?></span>
		</div>

		<div><?php echo $safe_content; ?></div>

		<div class="notice-stats">
			<?php if ($is_logged_in): ?>
				<form method="POST" action="../vote_handler.php">
                    <input type="hidden" name="target_type" value="comment">
                    <input type="hidden" name="target_id" value="<?php echo $comment_id; ?>">
                    <input type="hidden" name="discussion_id" value="<?php echo (int) $discussion_id; ?>">
                    <input type="hidden" name="vote_type" value="up">
					<button type="submit" class="<?php echo $current_vote === 'up' ? 'active' : ''; ?>">Upvote</button>
				</form>

				<span><?php echo (int) $comment['vote_score']; ?></span>

				<form method="POST" action="../vote_handler.php">
					<input type="hidden" name="target_type" value="comment">
					<input type="hidden" name="target_id" value="<?php echo $comment_id; ?>">
					<input type="hidden" name="discussion_id" value="<?php echo (int) $discussion_id; ?>">
					<input type="hidden" name="vote_type" value="down">
					<button type="submit" class="<?php echo $current_vote === 'down' ? 'active' : ''; ?>">Downvote</button>
				</form>
			<?php else: ?>
				<span><?php echo (int) $comment['vote_score']; ?> votes</span>
			<?php endif; ?>
		</div>

		<?php if ($is_logged_in): ?>
			<div>
				<p>Reply</p>
				<form method="POST" action="../comment_handler.php">
					<input type="hidden" name="discussion_id" value="<?php echo $discussion_id; ?>">
					<input type="hidden" name="parent_comment_id" value="<?php echo $comment_id; ?>">
					<textarea name="content"></textarea>
					<button type="submit">Post reply</button>
				</form>
						<?php if ((int) $comment['user_id'] === (int) ($_SESSION['user_id'] ?? 0)): ?>
							<form method="POST" action="../comment_delete.php" onsubmit="return confirm('Delete this comment and all its replies? This cannot be undone.');">
								<input type="hidden" name="comment_id" value="<?php echo $comment_id; ?>">
								<input type="hidden" name="discussion_id" value="<?php echo (int) $discussion_id; ?>">
								<button type="submit">Delete</button>
							</form>
						<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php foreach ($children as $child): ?>
			<?php render_comment_thread($child, $comments_by_parent, $user_comment_votes, $is_logged_in, $depth + 1); ?>
		<?php endforeach; ?>
	</div>
	<?php
}

$page_title = $discussion['title'] . ' — EWU Study Point';
$report_success = $_SESSION['report_success'] ?? '';
unset($_SESSION['report_success']);
require_once '../includes/header.php';
?>

<main>
	<?php if (!empty($report_success)): ?>
		<div style="background: rgba(47, 111, 78, 0.1); border: 1px solid var(--green); color: var(--green-text); border-radius: 4px; padding: 12px 16px; margin-bottom: 20px;">
			<?php echo htmlspecialchars($report_success, ENT_QUOTES, 'UTF-8'); ?>
		</div>
	<?php endif; ?>

	<div class="notice-card">
		<span class="tape-tab"><?php echo htmlspecialchars($discussion['course_name'], ENT_QUOTES, 'UTF-8'); ?></span>
		<h1><?php echo htmlspecialchars($discussion['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
		<p>
			<?php echo htmlspecialchars($discussion['faculty_name'], ENT_QUOTES, 'UTF-8'); ?>
			&middot;
			<?php echo htmlspecialchars($discussion['department'], ENT_QUOTES, 'UTF-8'); ?>
			&middot;
			<?php echo htmlspecialchars($discussion['topic'], ENT_QUOTES, 'UTF-8'); ?>
			&middot;
			<?php echo htmlspecialchars(date('M j, Y', strtotime($discussion['created_at'])), ENT_QUOTES, 'UTF-8'); ?>
		</p>

		<div>
			<?php echo nl2br(htmlspecialchars($discussion['description'], ENT_QUOTES, 'UTF-8')); ?>
		</div>

		<?php if (!empty($attachments)): ?>
			<div>
				<?php foreach ($attachments as $attachment): ?>
					<?php
					$attachment_url = '../' . ltrim($attachment['file_path'], '/');
					$is_image = strpos((string) $attachment['file_type'], 'image/') === 0;
					?>
					<?php if ($is_image): ?>
						<a href="<?php echo htmlspecialchars($attachment_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
							<img src="<?php echo htmlspecialchars($attachment_url, ENT_QUOTES, 'UTF-8'); ?>" alt="Discussion attachment">
						</a>
					<?php else: ?>
						<a href="<?php echo htmlspecialchars($attachment_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">View PDF</a>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="notice-stats">
			<?php if ($is_logged_in): ?>
				<form method="POST" action="../vote_handler.php">
					<input type="hidden" name="target_type" value="discussion">
					<input type="hidden" name="target_id" value="<?php echo (int) $discussion['id']; ?>">
					<input type="hidden" name="discussion_id" value="<?php echo (int) $discussion['id']; ?>">
					<input type="hidden" name="vote_type" value="up">
					<button type="submit" class="<?php echo $user_discussion_vote === 'up' ? 'active' : ''; ?>">Upvote</button>
				</form>

				<span><?php echo (int) $discussion['vote_score']; ?></span>

				<form method="POST" action="../vote_handler.php">
					<input type="hidden" name="target_type" value="discussion">
					<input type="hidden" name="target_id" value="<?php echo (int) $discussion['id']; ?>">
					<input type="hidden" name="discussion_id" value="<?php echo (int) $discussion['id']; ?>">
					<input type="hidden" name="vote_type" value="down">
					<button type="submit" class="<?php echo $user_discussion_vote === 'down' ? 'active' : ''; ?>">Downvote</button>
				</form>
							<?php else: ?>
				<span><?php echo (int) $discussion['vote_score']; ?> votes</span>
			<?php endif; ?>
		</div>

		<?php if ($is_logged_in): ?>
			<div>
				<?php if ($is_owner): ?>
					<a href="edit.php?id=<?php echo (int) $discussion['id']; ?>">Edit</a>
					<form method="POST" action="delete.php" onsubmit="return confirm('Delete this discussion? This cannot be undone.');">
						<input type="hidden" name="id" value="<?php echo (int) $discussion['id']; ?>">
						<button type="submit">Delete</button>
					</form>
				<?php else: ?>
					<form method="POST" action="../report_handler.php" onsubmit="return confirmReport(this);">
						<input type="hidden" name="discussion_id" value="<?php echo (int) $discussion['id']; ?>">
						<input type="hidden" name="reason" value="Inappropriate discussion content">
						<button type="submit">Report</button>
					</form>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<script>
		function confirmReport(form) {
			var reason = prompt('Please enter a reason for reporting this discussion (optional):', 'Inappropriate or offensive discussion content');
			if (reason === null) {
				return false;
			}
			if (reason.trim() !== '') {
				form.querySelector('input[name="reason"]').value = reason.trim();
			}
			return true;
		}
		</script>

		<section>
			<h2><?php echo count($comments); ?> comments</h2>

			<?php if ($is_logged_in): ?>
				<form method="POST" action="../comment_handler.php">
					<input type="hidden" name="discussion_id" value="<?php echo (int) $discussion_id; ?>">
					<textarea name="content"></textarea>
					<button type="submit">Add a comment</button>
				</form>
			<?php else: ?>
				<p><a href="../auth/login.php">Log in to join the discussion</a></p>
			<?php endif; ?>

			<div>
				<?php foreach ($comments_by_parent[0] ?? [] as $top_level_comment): ?>
					<?php render_comment_thread($top_level_comment, $comments_by_parent, $user_comment_votes, $is_logged_in, 0); ?>
				<?php endforeach; ?>
			</div>
		</section>
	</div>
</main>

<?php require_once '../includes/footer.php'; ?>

