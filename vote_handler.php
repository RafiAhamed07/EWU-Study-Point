<?php

require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$target_type = $_POST['target_type'] ?? '';
$target_id = (int) ($_POST['target_id'] ?? 0);
$vote_type = $_POST['vote_type'] ?? '';
$discussion_id = (int) ($_POST['discussion_id'] ?? 0);

if (($target_type !== 'discussion' && $target_type !== 'comment')
    || $target_id <= 0
    || ($vote_type !== 'up' && $vote_type !== 'down')
    || $discussion_id <= 0
) {
    header('Location: index.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$submitted_vote_value = $vote_type === 'up' ? 1 : -1;

try {
    $conn->begin_transaction();

    if ($target_type === 'discussion') {
        $existing_vote_stmt = $conn->prepare('SELECT vote_type FROM discussion_votes WHERE discussion_id = ? AND user_id = ?');
        $existing_vote_stmt->bind_param('ii', $target_id, $user_id);
        $existing_vote_stmt->execute();
        $existing_vote_result = $existing_vote_stmt->get_result();
        $existing_vote = $existing_vote_result->fetch_assoc();
        $existing_vote_stmt->close();

        if (!$existing_vote) {
            $insert_vote_stmt = $conn->prepare('INSERT INTO discussion_votes (discussion_id, user_id, vote_type) VALUES (?, ?, ?)');
            $insert_vote_stmt->bind_param('iis', $target_id, $user_id, $vote_type);
            $insert_vote_stmt->execute();
            $insert_vote_stmt->close();

            $update_score_stmt = $conn->prepare('UPDATE discussions SET vote_score = vote_score + ? WHERE id = ?');
            $update_score_stmt->bind_param('ii', $submitted_vote_value, $target_id);
            $update_score_stmt->execute();
            $update_score_stmt->close();
        } elseif ($existing_vote['vote_type'] === $vote_type) {
            $delete_vote_stmt = $conn->prepare('DELETE FROM discussion_votes WHERE discussion_id = ? AND user_id = ?');
            $delete_vote_stmt->bind_param('ii', $target_id, $user_id);
            $delete_vote_stmt->execute();
            $delete_vote_stmt->close();

            $score_delta = -$submitted_vote_value;
            $update_score_stmt = $conn->prepare('UPDATE discussions SET vote_score = vote_score + ? WHERE id = ?');
            $update_score_stmt->bind_param('ii', $score_delta, $target_id);
            $update_score_stmt->execute();
            $update_score_stmt->close();
        } else {
            $switch_delta = ($existing_vote['vote_type'] === 'up' && $vote_type === 'down') ? -2 : 2;

            $update_vote_stmt = $conn->prepare('UPDATE discussion_votes SET vote_type = ? WHERE discussion_id = ? AND user_id = ?');
            $update_vote_stmt->bind_param('sii', $vote_type, $target_id, $user_id);
            $update_vote_stmt->execute();
            $update_vote_stmt->close();

            $update_score_stmt = $conn->prepare('UPDATE discussions SET vote_score = vote_score + ? WHERE id = ?');
            $update_score_stmt->bind_param('ii', $switch_delta, $target_id);
            $update_score_stmt->execute();
            $update_score_stmt->close();
        }
    } else {
        $existing_vote_stmt = $conn->prepare('SELECT vote_type FROM comment_votes WHERE comment_id = ? AND user_id = ?');
        $existing_vote_stmt->bind_param('ii', $target_id, $user_id);
        $existing_vote_stmt->execute();
        $existing_vote_result = $existing_vote_stmt->get_result();
        $existing_vote = $existing_vote_result->fetch_assoc();
        $existing_vote_stmt->close();

        if (!$existing_vote) {
            $insert_vote_stmt = $conn->prepare('INSERT INTO comment_votes (comment_id, user_id, vote_type) VALUES (?, ?, ?)');
            $insert_vote_stmt->bind_param('iis', $target_id, $user_id, $vote_type);
            $insert_vote_stmt->execute();
            $insert_vote_stmt->close();

            $update_score_stmt = $conn->prepare('UPDATE comments SET vote_score = vote_score + ? WHERE id = ?');
            $update_score_stmt->bind_param('ii', $submitted_vote_value, $target_id);
            $update_score_stmt->execute();
            $update_score_stmt->close();
        } elseif ($existing_vote['vote_type'] === $vote_type) {
            $delete_vote_stmt = $conn->prepare('DELETE FROM comment_votes WHERE comment_id = ? AND user_id = ?');
            $delete_vote_stmt->bind_param('ii', $target_id, $user_id);
            $delete_vote_stmt->execute();
            $delete_vote_stmt->close();

            $score_delta = -$submitted_vote_value;
            $update_score_stmt = $conn->prepare('UPDATE comments SET vote_score = vote_score + ? WHERE id = ?');
            $update_score_stmt->bind_param('ii', $score_delta, $target_id);
            $update_score_stmt->execute();
            $update_score_stmt->close();
        } else {
            $switch_delta = ($existing_vote['vote_type'] === 'up' && $vote_type === 'down') ? -2 : 2;

            $update_vote_stmt = $conn->prepare('UPDATE comment_votes SET vote_type = ? WHERE comment_id = ? AND user_id = ?');
            $update_vote_stmt->bind_param('sii', $vote_type, $target_id, $user_id);
            $update_vote_stmt->execute();
            $update_vote_stmt->close();

            $update_score_stmt = $conn->prepare('UPDATE comments SET vote_score = vote_score + ? WHERE id = ?');
            $update_score_stmt->bind_param('ii', $switch_delta, $target_id);
            $update_score_stmt->execute();
            $update_score_stmt->close();
        }
    }

    $conn->commit();
} catch (Throwable $exception) {
    try {
        $conn->rollback();
    } catch (Throwable $rollback_exception) {
    }
}

header('Location: discussions/view.php?id=' . $discussion_id);
exit;
