<?php
/**
 * Explore Page - Threads Style
 * Shows trending tags and posts by tag
 */

session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get selected tag filter
$selected_tag = isset($_GET['tag']) ? trim($_GET['tag']) : '';

// Fetch trending tags (tags with most posts)
$trending_query = "SELECT tag, COUNT(*) as count 
                   FROM posts 
                   WHERE tag IS NOT NULL AND tag != ''
                   GROUP BY tag 
                   ORDER BY count DESC 
                   LIMIT 10";
$trending_result = $conn->query($trending_query);

// Fetch posts (filtered by tag if selected)
if (!empty($selected_tag)) {
    $query = "SELECT p.id, p.content, p.tag, p.created_at, 
              u.name as author_name, u.id as author_id,
              COUNT(DISTINCT l.id) as like_count,
              MAX(CASE WHEN l.user_id = ? THEN 1 ELSE 0 END) as user_liked
              FROM posts p
              JOIN users u ON p.user_id = u.id
              LEFT JOIN likes l ON p.id = l.post_id
              WHERE p.tag = ?
              GROUP BY p.id
              ORDER BY p.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $user_id, $selected_tag);
} else {
    $query = "SELECT p.id, p.content, p.tag, p.created_at, 
              u.name as author_name, u.id as author_id,
              COUNT(DISTINCT l.id) as like_count,
              MAX(CASE WHEN l.user_id = ? THEN 1 ELSE 0 END) as user_liked
              FROM posts p
              JOIN users u ON p.user_id = u.id
              LEFT JOIN likes l ON p.id = l.post_id
              GROUP BY p.id
              ORDER BY p.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$posts = $stmt->get_result();

// Time elapsed function
function time_elapsed($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->y > 0) return $diff->y . 'y';
    if ($diff->m > 0) return $diff->m . 'mo';
    if ($diff->d > 0) return $diff->d . 'd';
    if ($diff->h > 0) return $diff->h . 'h';
    if ($diff->i > 0) return $diff->i . 'm';
    return 'now';
}

// Get user initials for avatar
function get_initials($name) {
    $words = explode(' ', trim($name));
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($name, 0, 2));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explore - CampusBuzz</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="threads-layout">
        <!-- Side Navigation -->
        <aside class="side-nav">
            <div class="side-nav-header">
                <a href="index.php" class="side-nav-logo">
                    <i class="fas fa-graduation-cap"></i>
                    <span class="side-nav-logo-text">CampusBuzz</span>
                </a>
            </div>
            
            <nav class="side-nav-menu">
                <a href="index.php" class="side-nav-item">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <a href="explore.php" class="side-nav-item active">
                    <i class="fas fa-search"></i>
                    <span>Explore</span>
                </a>
                <a href="profile.php" class="side-nav-item">
                    <i class="far fa-user"></i>
                    <span>Profile</span>
                </a>
                <a href="logout.php" class="side-nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
                
                <!-- <button class="side-nav-post-btn" onclick="window.location.href='index.php'">
                    <span>Post</span>
                </button> -->
            </nav>
            
            <div class="side-nav-footer">
                <a href="profile.php" class="side-nav-user">
                    <div class="side-nav-avatar">
                        <?php echo get_initials($user_name); ?>
                    </div>
                    <div class="side-nav-user-info">
                        <span class="side-nav-user-name"><?php echo htmlspecialchars($user_name); ?></span>
                        <span class="side-nav-user-handle">@<?php echo strtolower(str_replace(' ', '', $user_name)); ?></span>
                    </div>
                </a>
            </div>
        </aside>

        <!-- Top Navigation (Mobile) -->
        <nav class="top-nav">
            <div class="nav-container">
                <a href="index.php" class="nav-logo">
                    <i class="fas fa-graduation-cap"></i>
                    <span class="nav-logo-text">CampusBuzz</span>
                </a>
                <div class="nav-actions">
                    <a href="profile.php" class="nav-icon">
                        <i class="far fa-user"></i>
                    </a>
                    <a href="index.php" class="nav-icon">
                        <i class="far fa-edit"></i>
                    </a>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Explore Header -->
            <div class="explore-header">
                <h2>
                    <i class="fas fa-compass"></i> 
                    <?php echo !empty($selected_tag) ? '#' . htmlspecialchars($selected_tag) : 'Explore'; ?>
                </h2>
                <div class="trending-tags">
                    <a href="explore.php" class="trending-tag <?php echo empty($selected_tag) ? 'active' : ''; ?>">
                        All Posts
                    </a>
                    
                    <?php while ($trend = $trending_result->fetch_assoc()): ?>
                        <a href="explore.php?tag=<?php echo urlencode($trend['tag']); ?>" 
                           class="trending-tag <?php echo $selected_tag === $trend['tag'] ? 'active' : ''; ?>">
                            #<?php echo htmlspecialchars($trend['tag']); ?>
                            <span class="tag-count"><?php echo $trend['count']; ?></span>
                        </a>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Threads Feed -->
            <div class="threads-feed" id="threadsContainer">
                <?php if ($posts->num_rows > 0): ?>
                    <?php while ($post = $posts->fetch_assoc()): ?>
                        <article class="thread-item" data-post-id="<?php echo $post['id']; ?>">
                            <div class="thread-header">
                                <div class="thread-avatar">
                                    <?php echo get_initials($post['author_name']); ?>
                                </div>
                                <div class="thread-body">
                                    <div class="thread-user-info">
                                        <span class="thread-username"><?php echo htmlspecialchars($post['author_name']); ?></span>
                                        <span class="thread-time"><?php echo time_elapsed($post['created_at']); ?></span>
                                        <?php if ($post['tag']): ?>
                                            <span class="thread-tag tag-<?php echo strtolower($post['tag']); ?>">
                                                #<?php echo htmlspecialchars($post['tag']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="thread-content">
                                        <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                                    </div>
                                    
                                    <div class="thread-actions">
                                        <button class="action-button <?php echo $post['user_liked'] ? 'liked' : ''; ?>" 
                                                onclick="toggleLike(<?php echo $post['id']; ?>)">
                                            <i class="<?php echo $post['user_liked'] ? 'fas' : 'far'; ?> fa-heart"></i>
                                            <span class="like-count"><?php echo $post['like_count']; ?></span>
                                        </button>
                                        <button class="action-button">
                                            <i class="far fa-comment"></i>
                                            <span>Reply</span>
                                        </button>
                                        <button class="action-button">
                                            <i class="far fa-paper-plane"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <p>No threads found<?php echo !empty($selected_tag) ? ' for #' . htmlspecialchars($selected_tag) : ''; ?>.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <!-- Bottom Navigation (Mobile) -->
        <nav class="bottom-nav">
            <a href="index.php" class="bottom-nav-item">
                <i class="fas fa-home"></i>
            </a>
            <a href="explore.php" class="bottom-nav-item active">
                <i class="fas fa-search"></i>
            </a>
            <a href="index.php" class="bottom-nav-item">
                <i class="fas fa-plus-square"></i>
            </a>
            <a href="profile.php" class="bottom-nav-item">
                <i class="far fa-user"></i>
            </a>
            <a href="logout.php" class="bottom-nav-item">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </nav>
    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>