<?php

require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

function redirect_and_exit(string $location): void
{
    header('Location: ' . $location);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_and_exit('index.php');
}

$discussion_id = (int) ($_POST['discussion_id'] ?? 0);
$comment_id = (int) ($_POST['comment_id'] ?? 0);
$material_id = (int) ($_POST['material_id'] ?? 0);

$targets = [];
if ($discussion_id > 0) {
    $targets['discussion'] = $discussion_id;
}
if ($comment_id > 0) {
    $targets['comment'] = $comment_id;
}
if ($material_id > 0) {
    $targets['material'] = $material_id;
}

if (count($targets) !== 1) {
    redirect_and_exit('index.php');
}

$target_type = array_key_first($targets);
$target_id = $targets[$target_type];

$redirect_location = 'index.php';
$report_discussion_id = null;
$report_comment_id = null;
$report_material_id = null;

if ($target_type === 'discussion') {
    $redirect_location = 'discussions/view.php?id=' . $target_id;
    $report_discussion_id = $target_id;
} elseif ($target_type === 'comment') {
    $comment_lookup_stmt = $conn->prepare('SELECT id, discussion_id FROM comments WHERE id = ?');
    $comment_lookup_stmt->bind_param('i', $target_id);
    $comment_lookup_stmt->execute();
    $comment_lookup_result = $comment_lookup_stmt->get_result();
    $comment_row = $comment_lookup_result->fetch_assoc();
    $comment_lookup_stmt->close();

    if (!$comment_row) {
        redirect_and_exit('index.php');
    }

    $parent_discussion_id = (int) $comment_row['discussion_id'];
    $redirect_location = 'discussions/view.php?id=' . $parent_discussion_id;
    $report_comment_id = $target_id;
} else {
    $redirect_location = 'materials/index.php';
    $report_material_id = $target_id;
}

$reason = trim($_POST['reason'] ?? '');
if ($reason === '') {
    $reason = 'Inappropriate content or guideline violation';
} elseif (strlen($reason) > 500) {
    $reason = substr($reason, 0, 500);
}

if ($target_type === 'discussion') {
    $discussion_exists_stmt = $conn->prepare('SELECT id FROM discussions WHERE id = ?');
    $discussion_exists_stmt->bind_param('i', $target_id);
    $discussion_exists_stmt->execute();
    $discussion_exists_result = $discussion_exists_stmt->get_result();
    $discussion_exists = $discussion_exists_result->fetch_assoc();
    $discussion_exists_stmt->close();

    if (!$discussion_exists) {
        redirect_and_exit('index.php');
    }
} elseif ($target_type === 'material') {
    $material_exists_stmt = $conn->prepare('SELECT id FROM materials WHERE id = ?');
    $material_exists_stmt->bind_param('i', $target_id);
    $material_exists_stmt->execute();
    $material_exists_result = $material_exists_stmt->get_result();
    $material_exists = $material_exists_result->fetch_assoc();
    $material_exists_stmt->close();

    if (!$material_exists) {
        redirect_and_exit('index.php');
    }
}

$reporter_id = (int) $_SESSION['user_id'];
$insert_stmt = $conn->prepare('INSERT INTO reports (reporter_id, discussion_id, comment_id, material_id, reason) VALUES (?, ?, ?, ?, ?)');
$insert_stmt->bind_param('iiiis', $reporter_id, $report_discussion_id, $report_comment_id, $report_material_id, $reason);
$insert_stmt->execute();
$insert_stmt->close();

$_SESSION['report_success'] = 'Thank you. Content has been reported and submitted to admin for review.';

redirect_and_exit($redirect_location);

