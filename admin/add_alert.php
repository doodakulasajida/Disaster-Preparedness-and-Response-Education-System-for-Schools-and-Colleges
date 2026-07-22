<?php
include("../config/db.php");

$success = false;
$error = false;

if (isset($_POST['send'])) {
    $msg = mysqli_real_escape_string($conn, $_POST['msg']);
    
    if (!empty(trim($msg))) {
        $conn->query("INSERT INTO alerts (message, created_at) VALUES ('$msg', NOW())");
        $success = true;
    } else {
        $error = "Alert message cannot be empty!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Alert - Disaster Management System</title>
    <link rel="stylesheet" href="css/add-alert.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="alert-container">
        <div class="alert-header">
            <div class="alert-icon">🔔</div>
            <h2>Send Emergency Alert</h2>
            <p>Broadcast important safety information to all users</p>
        </div>
        
        <div class="alert-form">
            <?php if ($success): ?>
                <div class="success-message" id="successMessage">
                    <i class="fas fa-check-circle"></i>
                    <span>Alert sent successfully! All users will see this notification.</span>
                    <i class="fas fa-times close-btn" onclick="this.parentElement.style.display='none'"></i>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="success-message" style="background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%); border-left-color: #dc3545; color: #721c24;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                    <i class="fas fa-times close-btn" onclick="this.parentElement.style.display='none'"></i>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="alertForm">
                <div class="form-group">
                    <label for="msg">
                        <i class="fas fa-comment-dots"></i> Alert Message
                    </label>
                    <textarea 
                        id="msg" 
                        name="msg" 
                        class="alert-textarea" 
                        placeholder="Type your alert message here...&#10;&#10;Example: &#10;⚠️ Heavy rainfall expected tomorrow. Please stay indoors and avoid low-lying areas.&#10;&#10;⚠️ Earthquake safety drill tomorrow at 10 AM. Please participate."
                        maxlength="500"
                        required
                    ><?php echo isset($_POST['msg']) ? htmlspecialchars($_POST['msg']) : ''; ?></textarea>
                    
                    <div class="char-counter">
                        <span><i class="fas fa-info-circle"></i> Maximum 500 characters</span>
                        <span class="char-count" id="charCount">0 / 500</span>
                    </div>
                </div>
                
                <div class="preview-section">
                    <h4>
                        <i class="fas fa-eye"></i> Live Preview
                    </h4>
                    <div class="preview-content" id="preview">
                        <i class="fas fa-bell"></i> Your message will appear here...
                    </div>
                </div>
                
                <button type="submit" name="send" class="submit-btn" id="submitBtn">
                    <i class="fas fa-paper-plane"></i> Send Alert
                </button>
            </form>
            
            <div class="tips-section">
                <h5>
                    <i class="fas fa-lightbulb"></i> Quick Tips
                </h5>
                <ul>
                    <li><i class="fas fa-check-circle"></i> Keep messages clear and concise</li>
                    <li><i class="fas fa-check-circle"></i> Use emojis (⚠️, 🚨, 🔔) for better visibility</li>
                    <li><i class="fas fa-check-circle"></i> Include specific actions users should take</li>
                    <li><i class="fas fa-check-circle"></i> Add urgency indicators for critical alerts</li>
                    <li><i class="fas fa-check-circle"></i> Avoid technical jargon - keep it simple</li>
                </ul>
            </div>
            
            <a href="dashboard.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <script>
        // Character counter
        const textarea = document.getElementById('msg');
        const charCount = document.getElementById('charCount');
        const preview = document.getElementById('preview');
        const maxChars = 500;
        
        function updateCharCount() {
            const length = textarea.value.length;
            charCount.textContent = `${length} / ${maxChars}`;
            
            // Add warning classes based on character count
            if (length > maxChars * 0.9) {
                charCount.classList.add('danger');
                charCount.classList.remove('warning');
            } else if (length > maxChars * 0.7) {
                charCount.classList.add('warning');
                charCount.classList.remove('danger');
            } else {
                charCount.classList.remove('warning', 'danger');
            }
            
            // Update preview
            if (textarea.value.trim() === '') {
                preview.innerHTML = '<i class="fas fa-bell"></i> Your message will appear here...';
            } else {
                let previewText = textarea.value;
                // Replace newlines with <br> for preview
                previewText = previewText.replace(/\n/g, '<br>');
                preview.innerHTML = `<i class="fas fa-bell"></i> ${previewText}`;
            }
        }
        
        textarea.addEventListener('input', updateCharCount);
        updateCharCount();
        
        // Form validation
        document.getElementById('alertForm').addEventListener('submit', function(e) {
            const msg = textarea.value.trim();
            const submitBtn = document.getElementById('submitBtn');
            
            if (msg === '') {
                e.preventDefault();
                showError('Please enter an alert message');
                return false;
            }
            
            if (msg.length > maxChars) {
                e.preventDefault();
                showError(`Message exceeds ${maxChars} character limit`);
                return false;
            }
            
            // Show loading state
            submitBtn.classList.add('loading');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending Alert...';
        });
        
        // Show error message
        function showError(message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'success-message';
            errorDiv.style.background = 'linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%)';
            errorDiv.style.borderLeftColor = '#dc3545';
            errorDiv.style.color = '#721c24';
            errorDiv.innerHTML = `
                <i class="fas fa-exclamation-triangle"></i>
                <span>${message}</span>
                <i class="fas fa-times close-btn" onclick="this.parentElement.style.display='none'"></i>
            `;
            
            const form = document.getElementById('alertForm');
            form.parentNode.insertBefore(errorDiv, form);
            
            setTimeout(() => {
                if (errorDiv) errorDiv.style.display = 'none';
            }, 5000);
        }
        
        // Auto-hide success message after 5 seconds
        setTimeout(() => {
            const successMsg = document.getElementById('successMessage');
            if (successMsg) {
                successMsg.style.opacity = '0';
                setTimeout(() => {
                    if (successMsg) successMsg.style.display = 'none';
                }, 300);
            }
        }, 5000);
        
        // Prevent accidental form resubmission
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + Enter to submit
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('submitBtn').click();
            }
            
            // Ctrl + Shift + C to clear textarea
            if (e.ctrlKey && e.shiftKey && e.key === 'C') {
                e.preventDefault();
                textarea.value = '';
                updateCharCount();
                showNotification('Textarea cleared', 'info');
            }
        });
        
        // Show notification
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: ${type === 'info' ? '#3498db' : '#27ae60'};
                color: white;
                padding: 10px 20px;
                border-radius: 5px;
                z-index: 1000;
                animation: slideInRight 0.3s ease;
            `;
            notification.innerHTML = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
        
        // Warn before leaving if form has unsaved changes
        let formChanged = false;
        textarea.addEventListener('input', function() {
            formChanged = true;
        });
        
        window.addEventListener('beforeunload', function(e) {
            if (formChanged && textarea.value.trim() !== '') {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                return e.returnValue;
            }
        });
        
        // Focus on textarea on page load
        textarea.focus();
        
        // Add animation to the alert icon
        // const alertIcon = document.querySelector('.alert-icon');
        // setInterval(() => {
        //     alertIcon.style.transform = 'scale(1.1)';
        //     setTimeout(() => {
        //         alertIcon.style.transform = 'scale(1)';
        //     }, 300);
        // }, 3000);
    </script>
</body>
</html>