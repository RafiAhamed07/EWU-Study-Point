<?php

require_once '../config/db.php';
require_once '../includes/functions.php';

$profile_user_id = (int) ($_GET['id'] ?? 0);
if ($profile_user_id <= 0) {
	header('Location: ../index.php');
	exit;
}

$user_stmt = $conn->prepare('SELECT id, name, student_id, department, created_at FROM users WHERE id = ?');
$user_stmt->bind_param('i', $profile_user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$profile_user = $user_result->fetch_assoc();
$user_stmt->close();

if (!$profile_user) {
	header('Location: ../index.php');
	exit;
}

$discussions_stmt = $conn->prepare(
	'SELECT id, title, course_name, department, vote_score, created_at
	 FROM discussions
	 WHERE user_id = ?
	 ORDER BY created_at DESC'
);
$discussions_stmt->bind_param('i', $profile_user_id);
$discussions_stmt->execute();
$discussions_result = $discussions_stmt->get_result();
$user_discussions = $discussions_result->fetch_all(MYSQLI_ASSOC);
$discussions_stmt->close();

$materials_stmt = $conn->prepare(
	'SELECT id, title, course_name, department, material_type, uploaded_at
	 FROM materials
	 WHERE user_id = ?
	 ORDER BY uploaded_at DESC'
);
$materials_stmt->bind_param('i', $profile_user_id);
$materials_stmt->execute();
$materials_result = $materials_stmt->get_result();
$user_materials = $materials_result->fetch_all(MYSQLI_ASSOC);
$materials_stmt->close();

$discussion_votes_stmt = $conn->prepare('SELECT COALESCE(SUM(vote_score), 0) AS total FROM discussions WHERE user_id = ?');
$discussion_votes_stmt->bind_param('i', $profile_user_id);
$discussion_votes_stmt->execute();
$discussion_votes_result = $discussion_votes_stmt->get_result();
$discussion_votes_row = $discussion_votes_result->fetch_assoc();
$discussion_votes_stmt->close();

$comment_votes_stmt = $conn->prepare('SELECT COALESCE(SUM(vote_score), 0) AS total FROM comments WHERE user_id = ?');
$comment_votes_stmt->bind_param('i', $profile_user_id);
$comment_votes_stmt->execute();
$comment_votes_result = $comment_votes_stmt->get_result();
$comment_votes_row = $comment_votes_result->fetch_assoc();
$comment_votes_stmt->close();

$total_vote_score = (int) ($discussion_votes_row['total'] ?? 0) + (int) ($comment_votes_row['total'] ?? 0);
$is_own_profile = isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] === $profile_user_id;

$page_title = $profile_user['name'] . "'s profile — EWU Study Point";
require_once '../includes/header.php';
?>

<main>
	<div class="notice-card">
		<h1><?php echo htmlspecialchars($profile_user['name'], ENT_QUOTES, 'UTF-8'); ?></h1>
		<p>
			Student ID: <?php echo htmlspecialchars($profile_user['student_id'], ENT_QUOTES, 'UTF-8'); ?>
			&middot;
			<?php echo htmlspecialchars($profile_user['department'], ENT_QUOTES, 'UTF-8'); ?>
		</p>
		<div class="reputation-badge"><?php echo (int) $total_vote_score; ?> total votes</div>
		<p>Member since <?php echo htmlspecialchars(date('M Y', strtotime($profile_user['created_at'])), ENT_QUOTES, 'UTF-8'); ?></p>
		<?php if ($is_own_profile): ?>
			<p>This is your profile.</p>
		<?php endif; ?>
	</div>

	<section>
		<h2>Discussions (<?php echo count($user_discussions); ?>)</h2>
		<?php if (empty($user_discussions)): ?>
			<p>No discussions posted yet.</p>
		<?php else: ?>
			<?php foreach ($user_discussions as $discussion): ?>
				<a href="../discussions/view.php?id=<?php echo (int) $discussion['id']; ?>" class="notice-card">
					<h3><?php echo htmlspecialchars($discussion['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
					<p><?php echo htmlspecialchars($discussion['course_name'], ENT_QUOTES, 'UTF-8'); ?> &middot; <?php echo htmlspecialchars($discussion['department'], ENT_QUOTES, 'UTF-8'); ?></p>
					<p><?php echo (int) $discussion['vote_score']; ?> votes</p>
					<p><?php echo htmlspecialchars(date('M j, Y', strtotime($discussion['created_at'])), ENT_QUOTES, 'UTF-8'); ?></p>
				</a>
			<?php endforeach; ?>
		<?php endif; ?>
	</section>

	<section>
		<h2>Study materials (<?php echo count($user_materials); ?>)</h2>
		<?php if (empty($user_materials)): ?>
			<p>No materials uploaded yet.</p>
		<?php else: ?>
			<?php foreach ($user_materials as $material): ?>
				<div class="notice-card">
					<h3><?php echo htmlspecialchars($material['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
					<p><?php echo htmlspecialchars($material['course_name'], ENT_QUOTES, 'UTF-8'); ?> &middot; <?php echo htmlspecialchars($material['department'], ENT_QUOTES, 'UTF-8'); ?></p>
					<p><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $material['material_type'])), ENT_QUOTES, 'UTF-8'); ?></p>
					<p><?php echo htmlspecialchars(date('M j, Y', strtotime($material['uploaded_at'])), ENT_QUOTES, 'UTF-8'); ?></p>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</section>
</main>

<?php require_once '../includes/footer.php'; ?>

