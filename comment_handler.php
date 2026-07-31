<?php

require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$discussion_id = (int) ($_POST['discussion_id'] ?? 0);
if ($discussion_id <= 0) {
    header('Location: index.php');
    exit;
}

$parent_comment_id = null;
if (isset($_POST['parent_comment_id']) && trim((string) $_POST['parent_comment_id']) !== '') {
    $parent_comment_id = (int) $_POST['parent_comment_id'];
    if ($parent_comment_id <= 0) {
        header('Location: discussions/view.php?id=' . $discussion_id . '#comment-' . $conn->insert_id);
        exit;
    }
}

$content = trim($_POST['content'] ?? '');
if ($content === '' || strlen($content) > 3000) {
    header('Location: discussions/view.php?id=' . $discussion_id . '#comment-' . $conn->insert_id);
    exit;
}

$discussion_exists_stmt = $conn->prepare('SELECT id FROM discussions WHERE id = ?');
$discussion_exists_stmt->bind_param('i', $discussion_id);
$discussion_exists_stmt->execute();
$discussion_exists_result = $discussion_exists_stmt->get_result();
$discussion_exists = $discussion_exists_result->fetch_assoc();
$discussion_exists_stmt->close();

if (!$discussion_exists) {
    header('Location: index.php');
    exit;
}

if ($parent_comment_id !== null) {
    $parent_check_stmt = $conn->prepare('SELECT id FROM comments WHERE id = ? AND discussion_id = ?');
    $parent_check_stmt->bind_param('ii', $parent_comment_id, $discussion_id);
    $parent_check_stmt->execute();
    $parent_check_result = $parent_check_stmt->get_result();
    $parent_exists = $parent_check_result->fetch_assoc();
    $parent_check_stmt->close();

    if (!$parent_exists) {
        header('Location: discussions/view.php?id=' . $discussion_id . '#comment-' . $conn->insert_id);
        exit;
    }
}

$user_id = (int) $_SESSION['user_id'];
$insert_stmt = $conn->prepare('INSERT INTO comments (discussion_id, user_id, parent_comment_id, content) VALUES (?, ?, ?, ?)');
$insert_stmt->bind_param('iiis', $discussion_id, $user_id, $parent_comment_id, $content);
$insert_stmt->execute();
$insert_stmt->close();

header('Location: discussions/view.php?id=' . $discussion_id);
exit;
