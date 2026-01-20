<?php
/**
 * Post Creation Handler
 * AJAX endpoint for creating new posts
 */

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
$content = isset($_POST['content']) ? trim($_POST['content']) : '';
$tag = isset($_POST['tag']) ? trim($_POST['tag']) : null;

// Validation
if (empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Content cannot be empty']);
    exit();
}

if (strlen($content) > 280) {
    echo json_encode(['success' => false, 'message' => 'Content exceeds 280 characters']);
    exit();
}

// Sanitize inputs
$content = sanitize($content);
if (!empty($tag)) {
    $tag = sanitize($tag);
}

// Insert post into database
$stmt = $conn->prepare("INSERT INTO posts (user_id, content, tag) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $user_id, $content, $tag);

if ($stmt->execute()) {
    $post_id = $stmt->insert_id;
    
    // Fetch the newly created post with user info
    $query = "SELECT p.id, p.content, p.tag, p.created_at,
              u.name as author_name, u.id as author_id
              FROM posts p
              JOIN users u ON p.user_id = u.id
              WHERE p.id = ?";
    
    $stmt2 = $conn->prepare($query);
    $stmt2->bind_param("i", $post_id);
    $stmt2->execute();
    $result = $stmt2->get_result();
    $post = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'message' => 'Post created successfully',
        'post' => $post
    ]);
    
    $stmt2->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to create post']);
}

$stmt->close();
$conn->close();
?>