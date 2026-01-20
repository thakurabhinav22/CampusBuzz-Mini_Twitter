/**
 * CampusBuzz - Threads Style JavaScript
 * Handles post creation, likes, and UI interactions
 */

// Character counter for thread composer
const threadContent = document.getElementById('threadContent');
const charCount = document.getElementById('charCount');

if (threadContent && charCount) {
    threadContent.addEventListener('input', function() {
        const length = this.value.length;
        charCount.textContent = `${length}/280`;
        
        // Change color based on character count
        if (length > 260) {
            charCount.classList.add('error');
            charCount.classList.remove('warning');
        } else if (length > 240) {
            charCount.classList.add('warning');
            charCount.classList.remove('error');
        } else {
            charCount.classList.remove('warning', 'error');
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
        const postMessage = document.getElementById('postMessage');
        
        if (!content) {
            showAlert('Please write something before posting.', 'error');
            return;
        }
        
        if (content.length > 280) {
            showAlert('Thread is too long. Maximum 280 characters allowed.', 'error');
            return;
        }
        
        // Disable submit button
        const submitBtn = this.querySelector('.post-button');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="loading-spinner"></span>';
        
        try {
            const formData = new FormData();
            formData.append('content', content);
            formData.append('tag', tag);
            
            const response = await fetch('api/create_post.php', {
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
                
                showAlert('Thread posted successfully!', 'success');
                
                // Reload page after short delay to show new post
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showAlert(data.message || 'Failed to post thread. Please try again.', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showAlert('Network error. Please check your connection.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
}

// Toggle like on a post
async function toggleLike(postId) {
    const postElement = document.querySelector(`[data-post-id="${postId}"]`);
    const likeButton = postElement.querySelector('.action-button');
    const likeIcon = likeButton.querySelector('i');
    const likeCount = likeButton.querySelector('.like-count');
    
    try {
        const formData = new FormData();
        formData.append('post_id', postId);
        
        const response = await fetch('api/toggle_like.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Update UI
            likeCount.textContent = data.like_count;
            
            if (data.action === 'liked') {
                likeButton.classList.add('liked');
                likeIcon.classList.remove('far');
                likeIcon.classList.add('fas');
            } else {
                likeButton.classList.remove('liked');
                likeIcon.classList.remove('fas');
                likeIcon.classList.add('far');
            }
        } else {
            showAlert(data.message || 'Failed to update like.', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Network error. Please try again.', 'error');
    }
}

// Show alert message
function showAlert(message, type = 'success') {
    const postMessage = document.getElementById('postMessage');
    if (!postMessage) {
        alert(message);
        return;
    }
    
    postMessage.textContent = message;
    postMessage.className = `alert alert-${type}`;
    postMessage.style.display = 'flex';
    
    // Add icon
    const icon = document.createElement('i');
    icon.className = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
    postMessage.insertBefore(icon, postMessage.firstChild);
    
    // Hide after 5 seconds
    setTimeout(() => {
        postMessage.style.display = 'none';
    }, 5000);
}

// Open new thread modal (placeholder for future modal implementation)
function openNewThreadModal() {
    const threadContent = document.getElementById('threadContent');
    if (threadContent) {
        threadContent.focus();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

// Auto-resize textarea
if (threadContent) {
    threadContent.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
}

// Smooth scroll for navigation
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Handle window resize for responsive layout
let resizeTimer;
window.addEventListener('resize', function() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function() {
        // Add any resize-specific logic here
    }, 250);
});

// Prevent double-click on buttons
document.addEventListener('DOMContentLoaded', function() {
    const buttons = document.querySelectorAll('button');
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            if (this.classList.contains('processing')) {
                return false;
            }
            this.classList.add('processing');
            setTimeout(() => {
                this.classList.remove('processing');
            }, 1000);
        });
    });
});

console.log('CampusBuzz Threads UI loaded successfully!');