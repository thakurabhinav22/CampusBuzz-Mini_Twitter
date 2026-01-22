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

// Fetch trending tags
$trending_stmt = $conn->prepare("
    SELECT tag, COUNT(*) as count 
    FROM posts 
    WHERE tag IS NOT NULL AND tag != ''
    GROUP BY tag 
    ORDER BY count DESC 
    LIMIT 5
");
$trending_stmt->execute();
$trending_tags = $trending_stmt->get_result();

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

function get_tag_emoji(string $tag): string {
    $emojis = [
        'Exam' => '📝',
        'Fest' => '🎉',
        'Notice' => '📢',
        'Study' => '📚',
        'Project' => '💼',
        'Sports' => '⚽',
        'Event' => '🎪'
    ];
    return $emojis[$tag] ?? '🔖';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusBuzz - Home</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <div class="threads-layout">

        <!-- Side Navigation -->
        <aside class="side-nav">
            <div class="side-nav-header">
                <a href="index.php" class="side-nav-logo">
                    <div class="logo-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="logo-text-container">
                        <span class="logo-title">CampusBuzz</span>
                    </div>
                </a>
            </div>

            <nav class="side-nav-menu">
                <a href="index.php" class="side-nav-item active">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <a href="explore.php" class="side-nav-item">
                    <i class="fas fa-compass"></i>
                    <span>Explore</span>
                </a>
                <a href="profile.php" class="side-nav-item">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
                                <a href="logout.php" class="side-nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>

            <div class="side-nav-footer">
                <a href="profile.php" class="side-nav-user">
                    <div class="side-nav-avatar"><?= htmlspecialchars(get_initials($user_name)) ?></div>
                    <div class="side-nav-user-info">
                        <span class="side-nav-user-name"><?= htmlspecialchars($user_name) ?></span>
                        <span class="side-nav-user-handle">Computer Science '25</span>
                    </div>
                    <i class="fas fa-ellipsis-h side-nav-more"></i>
                </a>
            </div>
        </aside>

        <!-- Top bar (mobile only) -->
        <nav class="top-nav">
            <div class="nav-container">
                <a href="index.php" class="nav-logo">
                    <i class="fas fa-graduation-cap"></i>
                    <span class="nav-logo-text">College Buzz</span>
                </a>
                <div class="nav-actions">
                    <a href="profile.php" class="nav-icon"><i class="far fa-user"></i></a>
                </div>
            </div>
        </nav>

        <!-- Main content – feed only -->
        <main class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <h1>Home</h1>
                <button class="header-icon-btn">
                    <i class="fas fa-sliders-h"></i>
                </button>
            </div>

            <!-- Composer Section -->
            <div class="thread-composer">
                <div class="composer-header">
                    <div class="composer-avatar"><?= htmlspecialchars(get_initials($user_name)) ?></div>
                    <div class="composer-placeholder" onclick="openPostModal()">
                        What's buzzing?
                    </div>
                </div>
                <div class="composer-actions-bar">
                    <button class="post-button-small" onclick="openPostModal()">Post</button>
                </div>
            </div>

            <!-- Feed -->
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
                                        <span class="thread-verified"><i class="fas fa-check-circle"></i></span>
                                        <span class="thread-handle">@<?= strtolower(str_replace(' ', '', $post['author_name'])) ?></span>
                                        <span class="thread-time">· <?= time_elapsed($post['created_at']) ?></span>
                                    </div>

                                    <div class="thread-content">
                                        <?= nl2br(htmlspecialchars($post['content'])) ?>
                                        <?php if ($post['tag']): ?>
                                            <div class="thread-tags">
                                                <span class="thread-hashtag">#<?= htmlspecialchars($post['tag']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="thread-actions">
                                        <button class="action-button comment-btn">
                                            <i class="far fa-comment"></i>
                                            <span>12</span>
                                        </button>
                                        <button class="action-button repost-btn">
                                            <i class="fas fa-retweet"></i>
                                            <span>24</span>
                                        </button>
                                        <button class="action-button like-btn <?= $post['user_liked'] ? 'liked' : '' ?>" 
                                                data-post-id="<?= $post['id'] ?>">
                                            <i class="<?= $post['user_liked'] ? 'fas' : 'far' ?> fa-heart"></i>
                                            <span class="like-count"><?= $post['like_count'] ?: '0' ?></span>
                                        </button>
                                        <button class="action-button share-btn">
                                            <i class="fas fa-share"></i>
                                            <span>4.5K</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </main>

        <!-- Right Sidebar -->
        <aside class="right-sidebar">
            <!-- Search Bar -->
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-input" placeholder="Search Buzz">
            </div>

            <!-- Trending Section -->
            <div class="sidebar-card">
                <h2 class="sidebar-card-title">Trending in Colleges</h2>
                <?php 
                $trending_data = [
                    ['category' => 'Trending in Tech', 'tag' => 'Hackathon', 'count' => '2.4k'],
                    ['category' => 'Sports · Trending', 'tag' => 'Inter-College Finals', 'count' => '892'],
                    ['category' => 'Entertainment', 'tag' => 'FestAuditions', 'count' => '1.2k'],
                    ['category' => 'Academics', 'tag' => 'LibraryHours', 'count' => '458']
                ];
                foreach ($trending_data as $trend): 
                ?>
                <div class="trending-item">
                    <div class="trending-content">
                        <div class="trending-category"><?= $trend['category'] ?></div>
                        <div class="trending-tag">#<?= $trend['tag'] ?></div>
                        <div class="trending-count"><?= $trend['count'] ?> Posts</div>
                    </div>
                    <button class="trending-more"><i class="fas fa-ellipsis-h"></i></button>
                </div>
                <?php endforeach; ?>
                <a href="explore.php" class="sidebar-show-more">Show more</a>
            </div>

            <!-- Upcoming Events -->
            <div class="sidebar-card">
                <h2 class="sidebar-card-title">Upcoming Events</h2>
                
                <div class="event-item">
                    <div class="event-date">
                        <span class="event-month">OCT</span>
                        <span class="event-day">12</span>
                    </div>
                    <div class="event-details">
                        <div class="event-title">Career Fair 2024</div>
                        <div class="event-location">
                            <i class="fas fa-map-marker-alt"></i> Main Plaza
                        </div>
                    </div>
                </div>

                <div class="event-item">
                    <div class="event-date">
                        <span class="event-month">OCT</span>
                        <span class="event-day">15</span>
                    </div>
                    <div class="event-details">
                        <div class="event-title">Film Club Screening</div>
                        <div class="event-location">
                            <i class="fas fa-map-marker-alt"></i> Media Lab 2
                        </div>
                    </div>
                </div>

                <a href="#" class="sidebar-show-more">View all events</a>
            </div>

            <!-- Footer Links -->
            <div class="sidebar-footer">
                <a href="#">Terms</a>
                <a href="#">Privacy</a>
                <a href="#">About</a>
                <a href="#">Help</a>
                <span class="copyright">© 2024 CampusBuzz</span>
            </div>
        </aside>

        <!-- Floating Action Button -->
        <button class="floating-action-btn" title="Create new post" onclick="openPostModal()">
            <i class="fas fa-feather-alt"></i>
        </button>

        <!-- Bottom Navigation (mobile) -->
        <nav class="bottom-nav">
            <a href="index.php" class="bottom-nav-item active"><i class="fas fa-home"></i></a>
            <a href="explore.php" class="bottom-nav-item"><i class="fas fa-compass"></i></a>
            <button class="bottom-nav-item" onclick="openPostModal()"><i class="fas fa-plus-square"></i></button>
            <a href="#" class="bottom-nav-item"><i class="fas fa-bell"></i></a>
            <a href="profile.php" class="bottom-nav-item"><i class="far fa-user"></i></a>
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

    <!-- JavaScript -->
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

    // Character counter
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

    // Post submission
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
            }
        } catch (err) {
            console.error(err);
        }
    }

    // Attach like handlers
    document.querySelectorAll('.like-btn[data-post-id]').forEach(btn => {
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

<?php $stmt->close(); $trending_stmt->close(); $conn->close(); ?>