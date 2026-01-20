<?php

session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';

// Fetch all posts
$stmt = $conn->prepare("
    SELECT p.id, p.content, p.tag, p.created_at,
           u.name as author_name,
           COUNT(DISTINCT l.id) AS like_count,
           MAX(CASE WHEN l.user_id = ? THEN 1 ELSE 0 END) AS user_liked
    FROM posts p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN likes l ON p.id = l.post_id
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$posts = $stmt->get_result();

function time_elapsed(string $datetime): string {
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

function get_initials(string $name): string {
    $words = preg_split('/\s+/', trim($name));
    if (count($words) >= 2) {
        return strtoupper($words[0][0] . $words[1][0]);
    }
    return strtoupper(substr($name, 0, 2)) ?: '??';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusBuzz</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
                <a href="index.php"       class="side-nav-item active"><i class="fas fa-home"></i><span>Home</span></a>
                <a href="explore.php"     class="side-nav-item"><i class="fas fa-search"></i><span>Explore</span></a>
                <a href="profile.php"     class="side-nav-item"><i class="far fa-user"></i><span>Profile</span></a>
                <a href="logout.php"      class="side-nav-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </nav>

            <div class="side-nav-footer">
                <a href="profile.php" class="side-nav-user">
                    <div class="side-nav-avatar"><?= htmlspecialchars(get_initials($user_name)) ?></div>
                    <div class="side-nav-user-info">
                        <span class="side-nav-user-name"><?= htmlspecialchars($user_name) ?></span>
                        <span class="side-nav-user-handle">@<?= strtolower(str_replace(' ', '', $user_name)) ?></span>
                    </div>
                </a>
            </div>
        </aside>

        <!-- Top bar (mobile only) -->
        <nav class="top-nav">
            <div class="nav-container">
                <a href="index.php" class="nav-logo">
                    <i class="fas fa-graduation-cap"></i>
                    <span class="nav-logo-text">CampusBuzz</span>
                </a>
                <div class="nav-actions">
                    <a href="profile.php" class="nav-icon"><i class="far fa-user"></i></a>
                </div>
            </div>
        </nav>

        <!-- Main content – feed only -->
        <main class="main-content">
            <div class="threads-feed" id="threadsContainer">
                <?php if ($posts->num_rows === 0): ?>
                    <div class="empty-state">
                        <i class="far fa-comment-dots"></i>
                        <p>No threads yet.<br>Be the first to post something!</p>
                    </div>
                <?php else: ?>
                    <?php while ($post = $posts->fetch_assoc()): ?>
                        <article class="thread-item" data-post-id="<?= $post['id'] ?>">
                            <div class="thread-header">
                                <div class="thread-avatar"><?= htmlspecialchars(get_initials($post['author_name'])) ?></div>
                                <div class="thread-body">
                                    <div class="thread-user-info">
                                        <span class="thread-username"><?= htmlspecialchars($post['author_name']) ?></span>
                                        <span class="thread-time">· <?= time_elapsed($post['created_at']) ?></span>
                                        <?php if ($post['tag']): ?>
                                            <span class="thread-tag tag-<?= strtolower(htmlspecialchars($post['tag'])) ?>">
                                                #<?= htmlspecialchars($post['tag']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="thread-content">
                                        <?= nl2br(htmlspecialchars($post['content'])) ?>
                                    </div>

                                    <div class="thread-actions">
                                        <button class="action-button <?= $post['user_liked'] ? 'liked' : '' ?>" 
                                                data-post-id="<?= $post['id'] ?>">
                                            <i class="<?= $post['user_liked'] ? 'fas' : 'far' ?> fa-heart"></i>
                                            <span class="like-count"><?= $post['like_count'] ?: '0' ?></span>
                                        </button>
                                        <button class="action-button">
                                            <i class="far fa-comment"></i>
                                            <span>Reply</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </main>

        <!-- Floating Action Button -->
        <button class="floating-action-btn" title="Create new post" onclick="openPostModal()">
            <i class="fas fa-feather-alt"></i>
        </button>

        <!-- Bottom Navigation (mobile) -->
        <nav class="bottom-nav">
            <a href="index.php"       class="bottom-nav-item active"><i class="fas fa-home"></i></a>
            <a href="explore.php"     class="bottom-nav-item"><i class="fas fa-search"></i></a>
            <button class="bottom-nav-item" onclick="openPostModal()"><i class="fas fa-plus-square"></i></button>
            <a href="profile.php"     class="bottom-nav-item"><i class="far fa-user"></i></a>
            <a href="logout.php"      class="bottom-nav-item"><i class="fas fa-sign-out-alt"></i></a>
        </nav>

    </div>

    <!-- Post Modal -->
    <div id="postModal" class="post-modal" style="display:none;">
        <div class="modal-backdrop" onclick="closePostModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <button class="modal-close-btn" onclick="closePostModal()">
                    <i class="fas fa-times"></i>
                </button>
                <h2>Create post</h2>
            </div>

            <div class="modal-body">
                <div class="composer-header">
                    <div class="composer-avatar"><?= htmlspecialchars(get_initials($user_name)) ?></div>
                    <div class="composer-content">
                        <div class="composer-user"><?= htmlspecialchars($user_name) ?></div>

                        <textarea id="modalContent" class="composer-textarea" 
                                  placeholder="Start a thread..." maxlength="280" rows="5" required></textarea>

                        <div class="composer-toolbar">
                            <select id="modalTag" class="tag-select">
                                <option value="">Add tag</option>
                                <option value="Exam">📝 Exam</option>
                                <option value="Fest">🎉 Fest</option>
                                <option value="Notice">📢 Notice</option>
                                <option value="Study">📚 Study</option>
                                <option value="Project">💼 Project</option>
                                <option value="Sports">⚽ Sports</option>
                                <option value="Event">🎪 Event</option>
                            </select>

                            <div class="composer-actions">
                                <span id="modalCharCount" class="char-counter">0/280</span>
                                <button id="modalPostBtn" class="post-button" disabled>Post</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript (all functionality) -->
    <script>
    // Modal controls
    function openPostModal() {
        document.getElementById('postModal').style.display = 'flex';
        document.getElementById('modalContent')?.focus();
    }

    function closePostModal() {
        document.getElementById('postModal').style.display = 'none';
        document.getElementById('modalContent').value = '';
        document.getElementById('modalCharCount').textContent = '0/280';
        document.getElementById('modalPostBtn').disabled = true;
        document.getElementById('modalPostBtn').textContent = 'Post';
    }

    // Character counter for modal
    const ta = document.getElementById('modalContent');
    const cnt = document.getElementById('modalCharCount');
    const postBtn = document.getElementById('modalPostBtn');

    function updateCharCount() {
        if (!ta || !cnt || !postBtn) return;
        const len = ta.value.length;
        cnt.textContent = len + '/280';

        if (len > 280) {
            cnt.className = 'char-counter error';
            postBtn.disabled = true;
        } else if (len > 240) {
            cnt.className = 'char-counter warning';
            postBtn.disabled = false;
        } else {
            cnt.className = 'char-counter';
            postBtn.disabled = len === 0;
        }
    }

    if (ta) ta.addEventListener('input', updateCharCount);

    // Post submission from modal
    if (postBtn) {
        postBtn.addEventListener('click', async () => {
            const content = ta.value.trim();
            const tag = document.getElementById('modalTag')?.value || '';

            if (!content) return;

            postBtn.disabled = true;
            postBtn.textContent = 'Posting...';

            try {
                const formData = new FormData();
                formData.append('content', content);
                formData.append('tag', tag);

                const response = await fetch('create_post.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    closePostModal();
                    location.reload(); 
                } else {
                    alert(data.message || 'Failed to post');
                }
            } catch (err) {
                console.error(err);
                alert('Error connecting to server');
            } finally {
                postBtn.disabled = false;
                postBtn.textContent = 'Post';
            }
        });
    }

    // Like toggle
    async function toggleLike(postId, button) {
        try {
            const formData = new FormData();
            formData.append('post_id', postId);

            const response = await fetch('like.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                const icon = button.querySelector('i');
                const count = button.querySelector('.like-count');

                if (data.action === 'liked') {
                    button.classList.add('liked');
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                } else {
                    button.classList.remove('liked');
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                }

                count.textContent = data.like_count || '0';
            } else {
                alert(data.message || 'Failed to update like');
            }
        } catch (err) {
            console.error(err);
            alert('Error connecting to server');
        }
    }

    // Attach like handlers
    document.querySelectorAll('.action-button[data-post-id]').forEach(btn => {
        btn.addEventListener('click', () => {
            const postId = btn.getAttribute('data-post-id');
            toggleLike(postId, btn);
        });
    });

    // Close modal with Esc
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closePostModal();
    });
    </script>

</body>
</html>

<?php $stmt->close(); $conn->close(); ?>