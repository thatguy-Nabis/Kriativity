<?php
require_once 'includes/session_check.php';
require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin('create-post.php');

$user = getCurrentUser($pdo);

if (!$user) {
    header('Location: logout.php');
    exit;
}

$categories = ['Art', 'Photography', 'Design', 'Technology', 'Music', 'Writing', 'Video', 'Other'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post – Kriativity</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        :root {
            --purple: #CEA1F5;
            --purple-dim: rgba(206, 161, 245, .14);
            --purple-glow: rgba(206, 161, 245, .28);
            --bg: #15051d;
            --bg-card: rgba(255, 255, 255, .025);
            --bg-input: rgba(0, 0, 0, .3);
            --border: rgba(206, 161, 245, .13);
            --border-hover: rgba(206, 161, 245, .35);
            --text: #ede8f5;
            --text-muted: #7a6a8a;
            --red: #ff6b6b;
            --red-dim: rgba(255, 107, 107, .12);
            --green: #4ade80;
            --radius: 14px;
            --ease: .22s ease;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'DM Sans', system-ui, sans-serif;
        }

        /* ── PAGE LAYOUT ── */
        .create-post-container {
            max-width: 740px;
            margin: 2.5rem auto;
            padding: 0 1.5rem 5rem;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--purple), #fff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0 0 .35rem;
        }

        .page-subtitle {
            color: var(--text-muted);
            font-size: .95rem;
            margin: 0;
        }

        /* ── CARD ── */
        .post-form-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 2rem;
            backdrop-filter: blur(12px);
        }

        /* ── CONTENT TYPE SELECTOR ── */
        .section-label {
            display: block;
            font-size: .78rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-bottom: .75rem;
        }

        .content-type-selector {
            display: flex;
            gap: .75rem;
            margin-bottom: 1.75rem;
        }

        .content-type-option {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .5rem;
            padding: 1.1rem .75rem;
            background: var(--bg-input);
            border: 1.5px solid var(--border);
            border-radius: var(--radius);
            cursor: pointer;
            transition: all var(--ease);
            user-select: none;
        }

        .content-type-option input[type="radio"] {
            display: none;
        }

        .content-type-option:hover {
            border-color: var(--border-hover);
            background: var(--purple-dim);
        }

        .content-type-option.active {
            border-color: var(--purple);
            background: var(--purple-dim);
            box-shadow: 0 0 0 3px rgba(206, 161, 245, .1);
        }

        .content-type-icon {
            font-size: 1.6rem;
            line-height: 1;
        }

        .content-type-label {
            font-size: .85rem;
            font-weight: 600;
            color: var(--text-muted);
        }

        .content-type-option.active .content-type-label {
            color: var(--purple);
        }

        /* ── FORM GROUPS ── */
        .form-section {
            margin-bottom: 1.6rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: .45rem;
            margin-bottom: 1.4rem;
        }

        .form-label {
            font-size: .8rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: .07em;
        }

        .form-input,
        .form-textarea,
        .form-select {
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: .85rem 1.1rem;
            font-family: inherit;
            font-size: .95rem;
            color: var(--text);
            transition: border-color var(--ease), box-shadow var(--ease);
            width: 100%;
            box-sizing: border-box;
        }

        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {
            outline: none;
            border-color: var(--purple);
            box-shadow: 0 0 0 3px rgba(206, 161, 245, .12);
        }

        .form-input::placeholder,
        .form-textarea::placeholder {
            color: #3a2f47;
        }

        .form-textarea {
            min-height: 110px;
            resize: vertical;
        }

        .form-select {
            appearance: none;
            cursor: pointer;
        }

        .char-counter {
            font-size: .75rem;
            color: var(--text-muted);
            text-align: right;
            margin-top: .2rem;
        }

        .char-counter.warn {
            color: #f97316;
        }

        .char-counter.over {
            color: var(--red);
        }

        .error-message {
            font-size: .8rem;
            color: var(--red);
            display: none;
        }

        .error-message.show {
            display: block;
        }

        /* ── UPLOAD AREA ── */
        .upload-zone {
            border: 2px dashed var(--border);
            border-radius: var(--radius);
            padding: 2.5rem 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all var(--ease);
            position: relative;
        }

        .upload-zone:hover,
        .upload-zone.dragover {
            border-color: var(--purple);
            background: var(--purple-dim);
        }

        .upload-zone-icon {
            font-size: 2.2rem;
            margin-bottom: .6rem;
        }

        .upload-zone-text {
            font-size: .95rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: .3rem;
        }

        .upload-zone-hint {
            font-size: .8rem;
            color: var(--text-muted);
        }

        /* ── PREVIEW ── */
        .preview-container {
            display: none;
            position: relative;
            border-radius: var(--radius);
            overflow: hidden;
            background: #000;
        }

        .preview-container.active {
            display: block;
        }

        .preview-container img,
        .preview-container video {
            width: 100%;
            max-height: 420px;
            object-fit: contain;
            display: block;
        }

        .preview-remove {
            position: absolute;
            top: .6rem;
            right: .6rem;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: rgba(0, 0, 0, .7);
            border: 1px solid rgba(255, 255, 255, .2);
            color: #fff;
            font-size: 1.1rem;
            line-height: 1;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background var(--ease);
        }

        .preview-remove:hover {
            background: rgba(220, 38, 38, .8);
        }

        .preview-filename {
            padding: .6rem 1rem;
            font-size: .8rem;
            color: var(--text-muted);
            background: rgba(0, 0, 0, .4);
            border-top: 1px solid var(--border);
        }

        /* ── TAGS ── */
        .tags-wrapper {
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: .5rem .75rem;
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
            align-items: center;
            min-height: 48px;
            cursor: text;
            transition: border-color var(--ease), box-shadow var(--ease);
        }

        .tags-wrapper:focus-within {
            border-color: var(--purple);
            box-shadow: 0 0 0 3px rgba(206, 161, 245, .12);
        }

        .tag-chip {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .25rem .65rem;
            background: var(--purple-dim);
            border: 1px solid rgba(206, 161, 245, .3);
            border-radius: 20px;
            font-size: .8rem;
            font-weight: 600;
            color: var(--purple);
            animation: tagPop .15s ease;
        }

        @keyframes tagPop {
            from {
                transform: scale(.8);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .tag-remove {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-muted);
            font-size: .85rem;
            line-height: 1;
            padding: 0;
            transition: color var(--ease);
        }

        .tag-remove:hover {
            color: var(--red);
        }

        .tag-input {
            flex: 1;
            min-width: 120px;
            background: none;
            border: none;
            outline: none;
            color: var(--text);
            font-family: inherit;
            font-size: .9rem;
            padding: .25rem .2rem;
        }

        .tag-input::placeholder {
            color: #3a2f47;
        }

        .tags-hint {
            font-size: .75rem;
            color: var(--text-muted);
        }

        .tags-count {
            font-size: .75rem;
            color: var(--text-muted);
            text-align: right;
        }

        /* ── BUTTONS ── */
        .form-buttons {
            display: flex;
            gap: .85rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            padding: .8rem 2rem;
            border-radius: 50px;
            font-family: inherit;
            font-size: .93rem;
            font-weight: 700;
            cursor: pointer;
            border: none;
            transition: all var(--ease);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--purple), #a66fd9);
            color: #15051d;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(206, 161, 245, .35);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-primary:disabled {
            opacity: .55;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        .btn-secondary {
            background: transparent;
            border: 1.5px solid var(--border-hover);
            color: var(--purple);
        }

        .btn-secondary:hover {
            background: var(--purple-dim);
        }

        /* ── TOAST ── */
        #notification {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            padding: .9rem 1.4rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: .88rem;
            color: #fff;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .4);
            transform: translateY(80px);
            opacity: 0;
            transition: all .35s cubic-bezier(.34, 1.56, .64, 1);
            z-index: 9999;
            pointer-events: none;
        }

        #notification.show {
            transform: translateY(0);
            opacity: 1;
        }

        #notification.success {
            background: linear-gradient(135deg, #22c55e, #16a34a);
        }

        #notification.error {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        @media (max-width: 600px) {
            .post-form-card {
                padding: 1.25rem;
            }

            .form-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
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
            <!--
            FIX 1: action now points to the handler
            FIX 2: method="POST" explicitly set
            FIX 3: enctype="multipart/form-data" preserved
        -->
            <form id="createPostForm" method="POST" action="handlers/create_post_handler.php"
                enctype="multipart/form-data">

                <!-- CONTENT TYPE -->
                <div class="form-section">
                    <span class="section-label">Content Type *</span>
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
                    </div>
                </div>

                <!-- TITLE -->
                <div class="form-group">
                    <label class="form-label" for="title">Title *</label>
                    <input type="text" id="title" name="title" class="form-input" maxlength="255"
                        placeholder="Give your post a title…" required>
                    <div class="char-counter"><span id="titleCount">0</span>/255</div>
                    <span class="error-message" id="titleError"></span>
                </div>

                <!-- DESCRIPTION -->
                <div class="form-group">
                    <label class="form-label" for="description">Description</label>
                    <textarea id="description" name="description" class="form-textarea" maxlength="2000"
                        placeholder="Describe your work…"></textarea>
                    <div class="char-counter"><span id="descCount">0</span>/2000</div>
                </div>

                <!-- CATEGORY -->
                <div class="form-group">
                    <label class="form-label" for="category">Category *</label>
                    <select id="category" name="category" class="form-select" required>
                        <option value="">— Select a category —</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="error-message" id="categoryError"></span>
                </div>

                <!-- TAGS -->
                <div class="form-group">
                    <label class="form-label">Tags</label>

                    <!--
                    Hidden input carries the comma-separated tag string to PHP.
                    JS keeps it in sync whenever tags change.
                -->
                    <input type="hidden" name="tags" id="tagsHidden">

                    <div class="tags-wrapper" id="tagsWrapper">
                        <!-- Tag chips are injected here by JS -->
                        <input type="text" id="tagInput" class="tag-input"
                            placeholder="Type a tag and press Enter or comma…" maxlength="30" autocomplete="off"
                            spellcheck="false">
                    </div>

                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:.3rem;">
                        <span class="tags-hint">Press <kbd
                                style="background:rgba(206,161,245,.15);border:1px solid var(--border);border-radius:4px;padding:.1rem .35rem;font-size:.75rem;">Enter</kbd>
                            or <kbd
                                style="background:rgba(206,161,245,.15);border:1px solid var(--border);border-radius:4px;padding:.1rem .35rem;font-size:.75rem;">,</kbd>
                            to add</span>
                        <span class="tags-count" id="tagsCount">0 / 10 tags</span>
                    </div>
                </div>

                <!-- UPLOAD -->
                <div class="form-group">
                    <label class="form-label">Media</label>

                    <div class="upload-zone" id="uploadArea">
                        <div class="upload-zone-icon">📁</div>
                        <div class="upload-zone-text">Click or drag & drop your file here</div>
                        <div class="upload-zone-hint" id="uploadHint">PNG, JPG, GIF, WebP — up to 10 MB</div>
                    </div>

                    <!--
                    FIX 4: name="media" matches what the handler now reads
                    as $_FILES['media']
                -->
                    <input type="file" id="fileInput" name="media" accept="image/*" hidden>

                    <div class="preview-container" id="previewContainer"></div>

                    <span class="error-message" id="mediaError"></span>
                </div>
                <!-- THUMBNAIL (video only) -->
                <div class="form-group" id="thumbnailGroup" style="display:none;">
                    <label class="form-label">Video Thumbnail * <span
                            style="font-weight:400;text-transform:none;color:var(--text-muted);">— shown on card &
                            before play</span></label>

                    <div class="upload-zone" id="thumbUploadArea">
                        <div class="upload-zone-icon">🖼️</div>
                        <div class="upload-zone-text">Click or drag & drop thumbnail</div>
                        <div class="upload-zone-hint">JPG, PNG, WebP — up to 5 MB</div>
                    </div>

                    <input type="file" id="thumbInput" name="thumbnail" accept="image/jpeg,image/png,image/webp" hidden>
                    <div class="preview-container" id="thumbPreviewContainer"></div>
                    <span class="error-message" id="thumbnailError"></span>
                </div>
                <!-- SUBMIT -->
                <div class="form-buttons">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        🚀 Publish Post
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="location.href='profile.php'">
                        Cancel
                    </button>
                </div>

            </form>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <div id="notification"></div>

    <script>
        // ── ELEMENTS ─────────────────────────────────────────────────────
        const form = document.getElementById('createPostForm');
        const fileInput = document.getElementById('fileInput');
        const uploadArea = document.getElementById('uploadArea');
        const previewCont = document.getElementById('previewContainer');
        const uploadHint = document.getElementById('uploadHint');
        const submitBtn = document.getElementById('submitBtn');
        const notification = document.getElementById('notification');
        const tagInput = document.getElementById('tagInput');
        const tagsWrapper = document.getElementById('tagsWrapper');
        const tagsHidden = document.getElementById('tagsHidden');
        const tagsCount = document.getElementById('tagsCount');

        let currentType = 'image';
        let tags = [];
        const MAX_TAGS = 10;

        // ── CONTENT TYPE SWITCH ───────────────────────────────────────────
        document.querySelectorAll('.content-type-option').forEach(option => {
            option.addEventListener('click', function () {
                document.querySelectorAll('.content-type-option').forEach(o => o.classList.remove('active'));
                this.classList.add('active');
                currentType = this.querySelector('input').value;
                resetUpload();

                if (currentType === 'image') {
                    fileInput.accept = 'image/*';
                    uploadHint.textContent = 'PNG, JPG, GIF, WebP — up to 10 MB';
                } else {
                    fileInput.accept = 'video/*';
                    uploadHint.textContent = 'MP4, WebM, MOV — up to 50 MB';
                }
            });
        });

        // ── UPLOAD AREA ───────────────────────────────────────────────────
        uploadArea.addEventListener('click', () => fileInput.click());

        uploadArea.addEventListener('dragover', e => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('dragover'));

        uploadArea.addEventListener('drop', e => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                // DataTransfer → file input
                const dt = new DataTransfer();
                dt.items.add(e.dataTransfer.files[0]);
                fileInput.files = dt.files;
                handleFile();
            }
        });

        fileInput.addEventListener('change', handleFile);

        function handleFile() {
            const file = fileInput.files[0];
            if (!file) return;

            const imageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            const videoTypes = ['video/mp4', 'video/webm', 'video/quicktime'];

            if (currentType === 'image') {
                if (!imageTypes.includes(file.type)) {
                    showError('mediaError', 'Please select a valid image file (JPG, PNG, GIF, WebP).');
                    return resetUpload();
                }
                if (file.size > 10 * 1024 * 1024) {
                    showError('mediaError', 'Image must be under 10 MB.');
                    return resetUpload();
                }
            }

            if (currentType === 'video') {
                if (!videoTypes.includes(file.type)) {
                    showError('mediaError', 'Please select a valid video file (MP4, WebM, MOV).');
                    return resetUpload();
                }
                if (file.size > 50 * 1024 * 1024) {
                    showError('mediaError', 'Video must be under 50 MB.');
                    return resetUpload();
                }
            }

            clearError('mediaError');
            showPreview(file);
        }

        function showPreview(file) {
            previewCont.innerHTML = '';
            previewCont.classList.add('active');
            uploadArea.style.display = 'none';

            const url = URL.createObjectURL(file);
            const media = currentType === 'image'
                ? `<img src="${url}" alt="Preview">`
                : `<video src="${url}" controls></video>`;

            const removeBtn = `<button type="button" class="preview-remove" onclick="resetUpload()" title="Remove">×</button>`;
            const fname = `<div class="preview-filename">📎 ${escHtml(file.name)} · ${formatBytes(file.size)}</div>`;

            previewCont.innerHTML = media + removeBtn + fname;
        }

        function resetUpload() {
            fileInput.value = '';
            previewCont.innerHTML = '';
            previewCont.classList.remove('active');
            uploadArea.style.display = '';
            clearError('mediaError');
        }

        // ── TAGS ──────────────────────────────────────────────────────────
        tagsWrapper.addEventListener('click', () => tagInput.focus());

        tagInput.addEventListener('keydown', e => {
            // Add tag on Enter or comma
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                addTag(tagInput.value);
            }
            // Delete last tag on Backspace when input is empty
            if (e.key === 'Backspace' && tagInput.value === '' && tags.length > 0) {
                removeTag(tags[tags.length - 1]);
            }
        });

        tagInput.addEventListener('blur', () => {
            if (tagInput.value.trim()) addTag(tagInput.value);
        });

        function addTag(raw) {
            const val = raw.trim().toLowerCase().replace(/[^a-z0-9\-_]/g, '');
            tagInput.value = '';

            if (!val) return;
            if (tags.includes(val)) { showNotif('Tag already added', 'error'); return; }
            if (tags.length >= MAX_TAGS) { showNotif(`Max ${MAX_TAGS} tags allowed`, 'error'); return; }
            if (val.length > 30) { showNotif('Tag too long (max 30 chars)', 'error'); return; }

            tags.push(val);
            renderTags();
        }

        function removeTag(tag) {
            tags = tags.filter(t => t !== tag);
            renderTags();
        }

        function renderTags() {
            // Remove all existing chips (keep the input element)
            tagsWrapper.querySelectorAll('.tag-chip').forEach(el => el.remove());

            tags.forEach(tag => {
                const chip = document.createElement('span');
                chip.className = 'tag-chip';
                chip.innerHTML = `
            #${escHtml(tag)}
            <button type="button" class="tag-remove" onclick="removeTag('${escHtml(tag)}')" title="Remove tag">×</button>
        `;
                tagsWrapper.insertBefore(chip, tagInput);
            });

            // Sync hidden input (comma-separated)
            tagsHidden.value = tags.join(',');
            tagsCount.textContent = `${tags.length} / ${MAX_TAGS} tags`;

            // Disable typing once max reached
            tagInput.disabled = tags.length >= MAX_TAGS;
            tagInput.placeholder = tags.length >= MAX_TAGS
                ? `Max ${MAX_TAGS} tags reached`
                : 'Type a tag and press Enter or comma…';
        }

        // ── CHAR COUNTERS ─────────────────────────────────────────────────
        function bindCounter(inputId, counterId, max) {
            const el = document.getElementById(inputId);
            const ctr = document.getElementById(counterId);
            el.addEventListener('input', () => {
                const len = el.value.length;
                ctr.textContent = len;
                ctr.parentElement.className = 'char-counter' +
                    (len > max * .9 ? ' warn' : '') +
                    (len >= max ? ' over' : '');
            });
        }
        bindCounter('title', 'titleCount', 255);
        bindCounter('description', 'descCount', 2000);

        // ── FORM SUBMIT ───────────────────────────────────────────────────
        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            // Client-side validation
            let valid = true;
            if (!document.getElementById('title').value.trim()) {
                showError('titleError', 'Title is required.');
                valid = false;
            } else { clearError('titleError'); }

            if (!document.getElementById('category').value) {
                showError('categoryError', 'Please select a category.');
                valid = false;
            } else { clearError('categoryError'); }

            if (!valid) return;
if (currentType === 'video' && !thumbInput.files[0]) {
    showError('thumbnailError', 'Please upload a thumbnail for your video.');
    valid = false;
} else {
    clearError('thumbnailError');
}
            const orig = submitBtn.innerHTML;
            submitBtn.innerHTML = '⏳ Publishing…';
            submitBtn.disabled = true;

            try {
                const res = await fetch(this.action, { method: 'POST', body: new FormData(this) });
                const data = await res.json();

                if (data.success) {
                    showNotif('Post published! Redirecting…', 'success');
                    setTimeout(() => {
                        window.location.href = 'post.php?id=' + data.post_id;
                    }, 1200);
                } else {
                    // Surface field errors returned by the handler
                    if (data.errors) {
                        Object.entries(data.errors).forEach(([field, msg]) => {
                            showError(field + 'Error', msg);
                        });
                    }
                    showNotif(data.message || 'Something went wrong.', 'error');
                    submitBtn.innerHTML = orig;
                    submitBtn.disabled = false;
                }
            } catch (err) {
                console.error(err);
                showNotif('Network error. Please try again.', 'error');
                submitBtn.innerHTML = orig;
                submitBtn.disabled = false;
            }
        });

        // ── HELPERS ───────────────────────────────────────────────────────
        function showError(id, msg) {
            const el = document.getElementById(id);
            if (!el) return;
            el.textContent = msg;
            el.classList.add('show');
        }
        function clearError(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.textContent = '';
            el.classList.remove('show');
        }
        function showNotif(msg, type = 'success') {
            notification.textContent = msg;
            notification.className = `${type} show`;
            clearTimeout(notification._t);
            notification._t = setTimeout(() => notification.classList.remove('show'), 3500);
        }
        function escHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
        function formatBytes(b) {
            if (b < 1024) return b + ' B';
            if (b < 1024 * 1024) return (b / 1024).toFixed(1) + ' KB';
            return (b / (1024 * 1024)).toFixed(1) + ' MB';
        }
        // Show/hide thumbnail group when switching to video
        document.querySelectorAll('.content-type-option').forEach(option => {
            option.addEventListener('click', function () {
                const type = this.querySelector('input').value;
                document.getElementById('thumbnailGroup').style.display =
                    type === 'video' ? 'block' : 'none';
                // reset thumbnail preview when switching away
                if (type !== 'video') resetThumb();
            });
        });

        // Thumbnail upload logic
        const thumbInput = document.getElementById('thumbInput');
        const thumbUploadArea = document.getElementById('thumbUploadArea');
        const thumbPreviewCont = document.getElementById('thumbPreviewContainer');

        thumbUploadArea.addEventListener('click', () => thumbInput.click());

        thumbUploadArea.addEventListener('dragover', e => {
            e.preventDefault();
            thumbUploadArea.classList.add('dragover');
        });
        thumbUploadArea.addEventListener('dragleave', () => thumbUploadArea.classList.remove('dragover'));
        thumbUploadArea.addEventListener('drop', e => {
            e.preventDefault();
            thumbUploadArea.classList.remove('dragover');
            if (e.dataTransfer.files[0]) {
                const dt = new DataTransfer();
                dt.items.add(e.dataTransfer.files[0]);
                thumbInput.files = dt.files;
                handleThumb();
            }
        });
        thumbInput.addEventListener('change', handleThumb);

        function handleThumb() {
            const file = thumbInput.files[0];
            if (!file) return;
            const allowed = ['image/jpeg', 'image/png', 'image/webp'];
            if (!allowed.includes(file.type)) {
                showError('thumbnailError', 'Thumbnail must be JPG, PNG, or WebP.');
                return resetThumb();
            }
            if (file.size > 5 * 1024 * 1024) {
                showError('thumbnailError', 'Thumbnail must be under 5 MB.');
                return resetThumb();
            }
            clearError('thumbnailError');

            const url = URL.createObjectURL(file);
            thumbPreviewCont.innerHTML = `
        <img src="${url}" style="width:100%;max-height:200px;object-fit:cover;border-radius:10px;display:block;">
        <button type="button" class="preview-remove" onclick="resetThumb()" title="Remove">×</button>
        <div class="preview-filename">📎 ${escHtml(file.name)} · ${formatBytes(file.size)}</div>
    `;
            thumbPreviewCont.classList.add('active');
            thumbUploadArea.style.display = 'none';
        }

        function resetThumb() {
            thumbInput.value = '';
            thumbPreviewCont.innerHTML = '';
            thumbPreviewCont.classList.remove('active');
            thumbUploadArea.style.display = '';
        }

        // Also validate thumbnail on submit — add inside form submit handler
        // after the existing `valid` checks:
        // if (currentType === 'video' && !thumbInput.files[0]) {
        //     showError('thumbnailError','Please upload a thumbnail for your video.');
        //     valid = false;
        // }
    </script>

</body>

</html>