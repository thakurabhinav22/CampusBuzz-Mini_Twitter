/**
 * CampusBuzz - Main JavaScript
 */

// Character counter for thread composer
const threadContent = document.getElementById('threadContent');
const charCount = document.getElementById('charCount');
const postButton = document.querySelector('.post-button');

if (threadContent && charCount) {
    threadContent.addEventListener('input', function() {
        const length = this.value.length;
        charCount.textContent = `${length}/280`;
        
        // Update counter styling
        charCount.classList.remove('warning', 'error');
        if (length > 250 && length <= 280) {
            charCount.classList.add('warning');
        } else if (length > 280) {
            charCount.classList.add('error');
        }
        
        // Disable/enable post button
        if (postButton) {
            postButton.disabled = length === 0 || length > 280;
        }
    });
}

// Handle thread form submission
const threadForm = document.getElementById('threadForm');
if (threadForm) {
    threadForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const content = threadContent.value.trim();
        const tag = document.getElementById('tag').value;
        
        // Validation
        if (!content) {
            showMessage('Please write something before posting', 'error');
            return;
        }
        
        if (content.length > 280) {
            showMessage('Content exceeds 280 characters', 'error');
            return;
        }
        
        // Disable button during submission
        postButton.disabled = true;
        postButton.textContent = 'Posting...';
        
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
                // Clear form
                threadContent.value = '';
                document.getElementById('tag').value = '';
                charCount.textContent = '0/280';
                charCount.classList.remove('warning', 'error');
                
                // Show success message
                showMessage('Post created successfully!', 'success');
                
                // Add new post to feed
                addPostToFeed(data.post);
                
            } else {
                showMessage(data.message || 'Failed to create post', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage('An error occurred. Please try again.', 'error');
        } finally {
            postButton.disabled = false;
            postButton.textContent = 'Post';
        }
    });
}

// Show message alert
function showMessage(message, type) {
    const messageDiv = document.getElementById('postMessage');
    if (!messageDiv) return;
    
    messageDiv.textContent = message;
    messageDiv.className = `alert alert-${type}`;
    messageDiv.style.display = 'flex';
    
    // Auto-hide after 3 seconds
    setTimeout(() => {
        messageDiv.style.display = 'none';
    }, 3000);
}

// Add new post to feed
function addPostToFeed(post) {
    const threadsContainer = document.getElementById('threadsContainer');
    if (!threadsContainer) return;
    
    // Remove empty state if it exists
    const emptyState = threadsContainer.querySelector('.empty-state');
    if (emptyState) {
        emptyState.remove();
    }
    
    // Get user initials
    const initials = getInitials(post.author_name);
    
    // Create post HTML
    const postHTML = `
        <article class="thread-item" data-post-id="${post.id}">
            <div class="thread-header">
                <div class="thread-avatar">${initials}</div>
                <div class="thread-body">
                    <div class="thread-user-info">
                        <span class="thread-username">${escapeHtml(post.author_name)}</span>
                        <span class="thread-time">now</span>
                        ${post.tag ? `<span class="thread-tag tag-${post.tag.toLowerCase()}">#${escapeHtml(post.tag)}</span>` : ''}
                    </div>
                    <div class="thread-content">${escapeHtml(post.content).replace(/\n/g, '<br>')}</div>
                    <div class="thread-actions">
                        <button class="action-button" onclick="toggleLike(${post.id})">
                            <i class="far fa-heart"></i>
                            <span class="like-count">0</span>
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
    `;
    
    // Insert at the beginning
    threadsContainer.insertAdjacentHTML('afterbegin', postHTML);
}

// Get initials from name
function getInitials(name) {
    const words = name.trim().split(' ');
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Toggle like on a post
async function toggleLike(postId) {
    const button = document.querySelector(`[data-post-id="${postId}"] .action-button`);
    if (!button) return;
    
    const icon = button.querySelector('i');
    const countSpan = button.querySelector('.like-count');
    const isLiked = button.classList.contains('liked');
    
    try {
        const formData = new FormData();
        formData.append('post_id', postId);
        
        const response = await fetch('toggle_like.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Update UI
            button.classList.toggle('liked');
            
            if (data.liked) {
                icon.classList.remove('far');
                icon.classList.add('fas');
            } else {
                icon.classList.remove('fas');
                icon.classList.add('far');
            }
            
            countSpan.textContent = data.like_count;
        }
    } catch (error) {
        console.error('Error toggling like:', error);
    }
}

// Auto-resize textarea
if (threadContent) {
    threadContent.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.max(100, this.scrollHeight) + 'px';
    });
}