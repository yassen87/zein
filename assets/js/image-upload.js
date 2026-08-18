document.addEventListener('DOMContentLoaded', function() {
    // Image upload functionality
    const imageUploadContainer = document.createElement('div');
    imageUploadContainer.className = 'image-upload-container';
    imageUploadContainer.innerHTML = `
        <div class="upload-area" id="uploadArea">
            <div class="upload-content">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                <p>Drop image here or click to browse</p>
                <p class="upload-hint">Supports: JPEG, PNG, GIF, WebP (Max 5MB)</p>
            </div>
            <input type="file" id="imageInput" accept="image/*" style="display: none;">
        </div>
        <div class="upload-preview" id="uploadPreview" style="display: none;">
            <img id="previewImage" src="" alt="Preview">
            <div class="preview-info">
                <p id="fileName"></p>
                <p id="fileSize"></p>
                <button type="button" id="copyUrlBtn" class="btn btn-outline btn--sm">Copy URL</button>
                <button type="button" id="removeImageBtn" class="btn btn-outline btn--sm btn--danger">Remove</button>
            </div>
        </div>
        <div class="upload-progress" id="uploadProgress" style="display: none;">
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            <p id="uploadStatus">Uploading...</p>
        </div>
    `;

    // Find the primary_image_key field and insert upload container after it
    const primaryImageField = document.getElementById('primary_image_key');
    if (primaryImageField && primaryImageField.parentNode) {
        primaryImageField.parentNode.insertBefore(imageUploadContainer, primaryImageField.nextSibling);
    }

    const uploadArea = document.getElementById('uploadArea');
    const imageInput = document.getElementById('imageInput');
    const uploadPreview = document.getElementById('uploadPreview');
    const previewImage = document.getElementById('previewImage');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const copyUrlBtn = document.getElementById('copyUrlBtn');
    const removeImageBtn = document.getElementById('removeImageBtn');
    const uploadProgress = document.getElementById('uploadProgress');
    const progressFill = document.getElementById('progressFill');
    const uploadStatus = document.getElementById('uploadStatus');

    let uploadedImageUrl = '';

    // Click to upload
    uploadArea.addEventListener('click', () => {
        imageInput.click();
    });

    // Drag and drop
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('drag-over');
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('drag-over');
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('drag-over');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFileUpload(files[0]);
        }
    });

    // File input change
    imageInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            handleFileUpload(e.target.files[0]);
        }
    });

    // Copy URL button
    copyUrlBtn.addEventListener('click', () => {
        navigator.clipboard.writeText(uploadedImageUrl).then(() => {
            copyUrlBtn.textContent = 'Copied!';
            setTimeout(() => {
                copyUrlBtn.textContent = 'Copy URL';
            }, 2000);
        });
    });

    // Remove image button
    removeImageBtn.addEventListener('click', () => {
        uploadPreview.style.display = 'none';
        uploadArea.style.display = 'block';
        uploadedImageUrl = '';
        imageInput.value = '';
    });

    function handleFileUpload(file) {
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            alert('Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.');
            return;
        }

        // Validate file size (5MB max)
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            alert('File too large. Maximum size is 5MB.');
            return;
        }

        // Show preview
        const reader = new FileReader();
        reader.onload = (e) => {
            previewImage.src = e.target.result;
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            uploadPreview.style.display = 'block';
            uploadArea.style.display = 'none';
        };
        reader.readAsDataURL(file);

        // Upload file
        uploadFile(file);
    }

    function uploadFile(file) {
        const formData = new FormData();
        formData.append('image', file);

        uploadProgress.style.display = 'block';
        uploadStatus.textContent = 'Uploading...';

        fetch('upload_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            uploadProgress.style.display = 'none';
            
            if (data.success) {
                uploadedImageUrl = data.url;
                // Update the primary_image_key field with the filename
                const primaryImageField = document.getElementById('primary_image_key');
                if (primaryImageField) {
                    primaryImageField.value = data.filename;
                }
                console.log('Upload successful:', data);
            } else {
                alert('Upload failed: ' + data.error);
                removeImageBtn.click();
            }
        })
        .catch(error => {
            uploadProgress.style.display = 'none';
            console.error('Upload error:', error);
            alert('Upload failed. Please try again.');
            removeImageBtn.click();
        });
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
});

// Add CSS styles
const styles = `
<style>
.image-upload-container {
    margin: 1rem 0;
}

.upload-area {
    border: 2px dashed var(--border);
    border-radius: var(--radius-lg);
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: var(--bg-card);
}

.upload-area:hover,
.upload-area.drag-over {
    border-color: var(--gold);
    background: var(--bg-card-hover);
}

.upload-content svg {
    color: var(--gold);
    margin-bottom: 1rem;
}

.upload-content p {
    margin: 0.5rem 0;
    color: var(--ink);
}

.upload-hint {
    font-size: 0.875rem;
    color: var(--ink-muted);
}

.upload-preview {
    display: flex;
    gap: 1rem;
    align-items: center;
    padding: 1rem;
    background: var(--bg-card);
    border-radius: var(--radius-lg);
    border: 1px solid var(--border);
}

.upload-preview img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: var(--radius);
}

.preview-info {
    flex: 1;
}

.preview-info p {
    margin: 0.25rem 0;
    color: var(--ink);
}

.upload-progress {
    padding: 1rem;
    background: var(--bg-card);
    border-radius: var(--radius-lg);
    text-align: center;
}

.progress-bar {
    width: 100%;
    height: 4px;
    background: var(--border);
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.progress-fill {
    height: 100%;
    background: var(--gold);
    width: 0%;
    transition: width 0.3s ease;
}

.btn--sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

.btn--danger {
    color: #dc3545;
    border-color: #dc3545;
}

.btn--danger:hover {
    background: #dc3545;
    color: white;
}
</style>
`;

document.head.insertAdjacentHTML('beforeend', styles);
