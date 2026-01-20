<?php

session_start();
require_once 'config/db.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$user_id = $_SESSION['user_id'];
$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

// Validation
if ($post_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
    exit();
}

// Check if post exists
$stmt = $conn->prepare("SELECT id FROM posts WHERE id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Post not found']);
    exit();
}
$stmt->close();

// Check if user already liked this post
$stmt = $conn->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
$stmt->bind_param("ii", $post_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$already_liked = $result->num_rows > 0;
$stmt->close();

if ($already_liked) {
    // Unlike: Remove the like
    $stmt = $conn->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
    $action = 'unliked';
} else {
    // Like: Add the like
    $stmt = $conn->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
    $action = 'liked';
}

$stmt->close();

// Get updated like count
$stmt = $conn->prepare("SELECT COUNT(*) as like_count FROM likes WHERE post_id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$like_count = $row['like_count'];
$stmt->close();

echo json_encode([
    'success' => true,
    'action' => $action,
    'like_count' => $like_count
]);

$conn->close();
?>