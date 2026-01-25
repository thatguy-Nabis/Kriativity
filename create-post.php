<?php
// ============================================
// Create Post Page
// Primary Color: #CEA1F5 (Purple)
// Secondary Color: #15051d (Dark Purple)
// ============================================

// Include session check
require_once 'includes/session_check.php';
require_once 'config/database.php';
require_once 'includes/auth.php';

// Require user to be logged in
requireLogin('create-post.php');

// Get current user data
$user = getCurrentUser($pdo);

if (!$user) {
    header('Location: logout.php');
    exit;
}

// Get categories
$categories = ['Art', 'Photography', 'Design', 'Technology', 'Music', 'Writing', 'Video', 'Other'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post - Content Discovery Platform</title>
    <link rel="stylesheet" href="styles.css">
    
    <style>
        .create-post-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #CEA1F5 0%, #ffffff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #a0a0a0;
            font-size: 1rem;
        }

        .post-form-card {
            background: linear-gradient(135deg, rgba(206, 161, 245, 0.05) 0%, rgba(21, 5, 29, 0.8) 100%);
            border: 1px solid rgba(206, 161, 245, 0.15);
            border-radius: 16px;
            padding: 2.5rem;
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .section-label {
            font-size: 1.1rem;
            font-weight: 600;
            color: #CEA1F5;
            margin-bottom: 1rem;
            display: block;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #CEA1F5;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .required {
            color: #ff6b6b;
        }

        .form-input,
        .form-textarea,
        .form-select {
            width: 100%;
            padding: 0.875rem 1.25rem;
            background-color: rgba(206, 161, 245, 0.08);
            border: 1px solid rgba(206, 161, 245, 0.2);
            border-radius: 10px;
            color: #e0e0e0;
            font-size: 0.95rem;
            font-family: inherit;
            transition: all 0.3s ease;
        }

        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {
            outline: none;
            background-color: rgba(206, 161, 245, 0.12);
            border-color: #CEA1F5;
            box-shadow: 0 0 15px rgba(206, 161, 245, 0.2);
        }

        .form-textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-select {
            cursor: pointer;
        }

        .image-upload-area {
            border: 2px dashed rgba(206, 161, 245, 0.3);
            border-radius: 12px;
            padding: 3rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(206, 161, 245, 0.03);
        }

        .image-upload-area:hover {
            border-color: #CEA1F5;
            background: rgba(206, 161, 245, 0.08);
        }

        .image-upload-area.dragover {
            border-color: #CEA1F5;
            background: rgba(206, 161, 245, 0.15);
        }

        .upload-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #CEA1F5;
        }

        .upload-text {
            font-size: 1rem;
            color: #d0d0d0;
            margin-bottom: 0.5rem;
        }

        .upload-hint {
            font-size: 0.85rem;
            color: #a0a0a0;
        }

        .image-preview-container {
            display: none;
            position: relative;
            margin-top: 1rem;
        }

        .image-preview-container.active {
            display: block;
        }

        .image-preview {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 12px;
            border: 1px solid rgba(206, 161, 245, 0.2);
        }

        .remove-image-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255, 107, 107, 0.9);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .remove-image-btn:hover {
            background: #ff5252;
            transform: scale(1.1);
        }

        .content-type-selector {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .content-type-option {
            padding: 1rem;
            border: 2px solid rgba(206, 161, 245, 0.2);
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(206, 161, 245, 0.03);
        }

        .content-type-option:hover {
            border-color: #CEA1F5;
            background: rgba(206, 161, 245, 0.08);
        }

        .content-type-option.active {
            border-color: #CEA1F5;
            background: rgba(206, 161, 245, 0.15);
        }

        .content-type-option input[type="radio"] {
            display: none;
        }

        .content-type-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .content-type-label {
            font-size: 0.9rem;
            color: #d0d0d0;
        }

        .char-counter {
            font-size: 0.8rem;
            color: #a0a0a0;
            text-align: right;
            margin-top: 0.5rem;
        }

        .form-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.875rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-size: 1rem;
            flex: 1;
        }

        .btn-primary {
            background: linear-gradient(135deg, #CEA1F5 0%, #a66fd9 100%);
            color: #15051d;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(206, 161, 245, 0.4);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-secondary {
            background: transparent;
            border: 2px solid rgba(206, 161, 245, 0.5);
            color: #CEA1F5;
        }

        .btn-secondary:hover {
            background: rgba(206, 161, 245, 0.1);
            border-color: #CEA1F5;
        }

        .error-message {
            color: #ff6b6b;
            font-size: 0.85rem;
            margin-top: 0.5rem;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        .form-input.error,
        .form-textarea.error,
        .form-select.error {
            border-color: #ff6b6b;
        }

        .notification {
            position: fixed;
            top: 100px;
            right: 2rem;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
            transform: translateX(400px);
            transition: transform 0.3s ease;
            z-index: 1001;
            max-width: 400px;
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification.success {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
        }

        .notification.error {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            color: white;
        }

        @media (max-width: 768px) {
            .create-post-container {
                padding: 0 1rem;
            }

            .post-form-card {
                padding: 1.5rem;
            }

            .page-title {
                font-size: 2rem;
            }

            .content-type-selector {
                grid-template-columns: repeat(2, 1fr);
            }

            .form-buttons {
                flex-direction: column;
            }

            .notification {
                right: 1rem;
                left: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="create-post-container">
        <div class="page-header">
            <h1 class="page-title">Create New Post</h1>
            <p class="page-subtitle">Share your creativity with the world</p>
        </div>

        <div class="post-form-card">
            <form id="createPostForm" enctype="multipart/form-data">
                <!-- Content Type Selection -->
                <div class="form-section">
                    <span class="section-label">Content Type <span class="required">*</span></span>
                    <div class="content-type-selector">
                        <label class="content-type-option active">
                            <input type="radio" name="content_type" value="image" checked>
                            <div class="content-type-icon">🖼️</div>
                            <div class="content-type-label">Image</div>
                        </label>
                        <label class="content-type-option">
                            <input type="radio" name="content_type" value="video">
                            <div class="content-type-icon">🎥</div>
                            <div class="content-type-label">Video</div>
                        </label>
                        <label class="content-type-option">
                            <input type="radio" name="content_type" value="article">
                            <div class="content-type-icon">📝</div>
                            <div class="content-type-label">Article</div>
                        </label>
                        <label class="content-type-option">
                            <input type="radio" name="content_type" value="audio">
                            <div class="content-type-icon">🎵</div>
                            <div class="content-type-label">Audio</div>
                        </label>
                    </div>
                </div>

                <!-- Title -->
                <div class="form-group">
                    <label class="form-label" for="title">Title <span class="required">*</span></label>
                    <input 
                        type="text" 
                        id="title" 
                        name="title" 
                        class="form-input" 
                        placeholder="Give your post a catchy title"
                        maxlength="255"
                        required
                    >
                    <div class="char-counter"><span id="titleCount">0</span>/255</div>
                    <span class="error-message" id="titleError"></span>
                </div>

                <!-- Description -->
                <div class="form-group">
                    <label class="form-label" for="description">Description</label>
                    <textarea 
                        id="description" 
                        name="description" 
                        class="form-textarea" 
                        placeholder="Describe your content..."
                        maxlength="2000"
                    ></textarea>
                    <div class="char-counter"><span id="descCount">0</span>/2000</div>
                    <span class="error-message" id="descriptionError"></span>
                </div>

                <!-- Category -->
                <div class="form-group">
                    <label class="form-label" for="category">Category <span class="required">*</span></label>
                    <select id="category" name="category" class="form-select" required>
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="error-message" id="categoryError"></span>
                </div>

                <!-- Image Upload -->
                <div class="form-group">
                    <label class="form-label">Upload Image</label>
                    <div class="image-upload-area" id="uploadArea">
                        <div class="upload-icon">📁</div>
                        <div class="upload-text">Click to upload or drag and drop</div>
                        <div class="upload-hint">PNG, JPG, GIF up to 10MB</div>
                    </div>
                    <input 
                        type="file" 
                        id="imageFile" 
                        name="image" 
                        accept="image/*" 
                        style="display: none;"
                    >
                    <div class="image-preview-container" id="previewContainer">
                        <img id="imagePreview" class="image-preview" alt="Preview">
                        <button type="button" class="remove-image-btn" id="removeImageBtn">×</button>
                    </div>
                    <span class="error-message" id="imageError"></span>
                </div>

                <!-- Form Buttons -->
                <div class="form-buttons">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        Publish Post
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='profile.php'">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <div class="notification" id="notification"></div>

    <script>
        // DOM Elements
        const form = document.getElementById('createPostForm');
        const uploadArea = document.getElementById('uploadArea');
        const imageFile = document.getElementById('imageFile');
        const previewContainer = document.getElementById('previewContainer');
        const imagePreview = document.getElementById('imagePreview');
        const removeImageBtn = document.getElementById('removeImageBtn');
        const titleInput = document.getElementById('title');
        const descInput = document.getElementById('description');
        const titleCount = document.getElementById('titleCount');
        const descCount = document.getElementById('descCount');
        const notification = document.getElementById('notification');
        const submitBtn = document.getElementById('submitBtn');

        // Content type selector
        document.querySelectorAll('.content-type-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.content-type-option').forEach(opt => {
                    opt.classList.remove('active');
                });
                this.classList.add('active');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });

        // Character counters
        titleInput.addEventListener('input', () => {
            titleCount.textContent = titleInput.value.length;
        });

        descInput.addEventListener('input', () => {
            descCount.textContent = descInput.value.length;
        });

        // Image upload click
        uploadArea.addEventListener('click', () => {
            imageFile.click();
        });

        // Image file change
        imageFile.addEventListener('change', handleImageSelect);

        // Drag and drop
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                imageFile.files = files;
                handleImageSelect();
            }
        });

        // Handle image selection
        function handleImageSelect() {
            const file = imageFile.files[0];
            
            if (file) {
                // Validate file size (10MB)
                if (file.size > 10 * 1024 * 1024) {
                    showNotification('Image size must be less than 10MB', 'error');
                    imageFile.value = '';
                    return;
                }

                // Validate file type
                if (!file.type.startsWith('image/')) {
                    showNotification('Please upload an image file', 'error');
                    imageFile.value = '';
                    return;
                }

                // Show preview
                const reader = new FileReader();
                reader.onload = (e) => {
                    imagePreview.src = e.target.result;
                    previewContainer.classList.add('active');
                    uploadArea.style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        }

        // Remove image
        removeImageBtn.addEventListener('click', () => {
            imageFile.value = '';
            previewContainer.classList.remove('active');
            uploadArea.style.display = 'block';
        });

        // Show notification
        function showNotification(message, type = 'success') {
            notification.textContent = message;
            notification.className = `notification ${type}`;
            notification.classList.add('show');

            setTimeout(() => {
                notification.classList.remove('show');
            }, 4000);
        }

        // Clear errors
        function clearErrors() {
            document.querySelectorAll('.error-message').forEach(el => {
                el.classList.remove('show');
            });
            document.querySelectorAll('.form-input, .form-textarea, .form-select').forEach(el => {
                el.classList.remove('error');
            });
        }

        // Display errors
        function displayError(field, message) {
            const errorEl = document.getElementById(field + 'Error');
            const inputEl = document.getElementById(field);
            
            if (errorEl && inputEl) {
                errorEl.textContent = message;
                errorEl.classList.add('show');
                inputEl.classList.add('error');
            }
        }

        // Form submission
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearErrors();

            // Client-side validation
            const title = titleInput.value.trim();
            const category = document.getElementById('category').value;

            let hasError = false;

            if (!title) {
                displayError('title', 'Title is required');
                hasError = true;
            }

            if (!category) {
                displayError('category', 'Please select a category');
                hasError = true;
            }

            if (hasError) {
                return;
            }

            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.textContent = 'Publishing...';

            try {
                const formData = new FormData(form);

                const response = await fetch('handlers/create_post_handler.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showNotification(result.message, 'success');
                    
                    // Redirect after 1.5 seconds
                    setTimeout(() => {
                        window.location.href = 'profile.php';
                    }, 1500);
                } else {
                    showNotification(result.message, 'error');
                    
                    if (result.errors) {
                        Object.keys(result.errors).forEach(field => {
                            displayError(field, result.errors[field]);
                        });
                    }
                    
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Publish Post';
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Failed to create post. Please try again.', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Publish Post';
            }
        });
    </script>
</body>
</html>