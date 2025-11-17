<?php
/**
 * Media Browser Modal - Drupal-style media management for TinyMCE
 * @var string $csrf_token
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Browser</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .media-item {
            cursor: pointer;
            transition: all 0.2s;
        }
        .media-item:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .media-item.selected {
            ring: 3px solid #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5);
        }
        .media-thumbnail {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3b82f6;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="h-screen flex flex-col">
        <!-- Header -->
        <div class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800">Media Browser</h1>
            <button onclick="window.parent.postMessage({action: 'close'}, '*')"
                    class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Toolbar -->
        <div class="bg-white border-b border-gray-200 px-6 py-3">
            <div class="flex items-center justify-between gap-4">
                <!-- Search -->
                <div class="flex-1 max-w-md">
                    <input type="text" id="searchInput" placeholder="Search by filename, alt text, or title..."
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Filters -->
                <div class="flex items-center gap-2">
                    <select id="typeFilter" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">All Types</option>
                        <option value="tinymce">TinyMCE</option>
                        <option value="dual_display">Display Images</option>
                        <option value="dual_modal">Modal Images</option>
                        <option value="featured">Featured</option>
                        <option value="teaser">Teaser</option>
                    </select>

                    <label class="flex items-center gap-2 px-3 py-2 bg-gray-100 rounded-lg cursor-pointer hover:bg-gray-200">
                        <input type="checkbox" id="groupDual" class="rounded">
                        <span class="text-sm font-medium text-gray-700">Group Dual Images</span>
                    </label>

                    <button id="uploadBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        Upload
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-hidden flex">
            <!-- Media Grid -->
            <div class="flex-1 overflow-y-auto p-6">
                <div id="loadingIndicator" class="flex items-center justify-center h-64">
                    <div class="loading-spinner"></div>
                </div>

                <div id="mediaGrid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 hidden">
                    <!-- Media items will be inserted here -->
                </div>

                <div id="emptyState" class="hidden flex flex-col items-center justify-center h-64 text-gray-500">
                    <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <p class="text-lg font-medium">No images found</p>
                    <p class="text-sm">Upload an image to get started</p>
                </div>

                <!-- Pagination -->
                <div id="pagination" class="mt-6 flex items-center justify-center gap-2 hidden">
                    <!-- Pagination buttons will be inserted here -->
                </div>
            </div>

            <!-- Details Sidebar -->
            <div id="detailsSidebar" class="hidden w-96 bg-white border-l border-gray-200 overflow-y-auto">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Image Details</h2>

                    <!-- Preview -->
                    <div class="mb-6">
                        <img id="detailsImage" src="" alt="" class="w-full h-auto rounded-lg border border-gray-200">
                    </div>

                    <!-- Metadata Form -->
                    <form id="metadataForm" class="space-y-4">
                        <input type="hidden" id="selectedImageId" value="">

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Filename</label>
                            <p id="detailsFilename" class="text-sm text-gray-600 break-all"></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Alt Text</label>
                            <input type="text" id="altText" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                            <input type="text" id="titleText" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Caption</label>
                            <textarea id="captionText" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Width</label>
                                <p id="detailsWidth" class="text-sm text-gray-600"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Height</label>
                                <p id="detailsHeight" class="text-sm text-gray-600"></p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">File Size</label>
                            <p id="detailsSize" class="text-sm text-gray-600"></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Uploaded</label>
                            <p id="detailsDate" class="text-sm text-gray-600"></p>
                        </div>

                        <div class="flex gap-2 pt-4 border-t">
                            <button type="button" id="saveMetadataBtn" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                Save Metadata
                            </button>
                            <button type="button" id="insertImageBtn" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                Insert Image
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Upload Modal -->
        <div id="uploadModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Upload Images</h3>
                    <button onclick="closeUploadModal()" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <form id="uploadForm" enctype="multipart/form-data" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Images</label>
                        <input type="file" name="images[]" id="imageFiles" multiple accept="image/*"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <p class="mt-1 text-xs text-gray-500">You can select multiple images</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Upload Type</label>
                        <select name="upload_type" id="uploadType" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="tinymce">Regular (TinyMCE)</option>
                            <option value="dual_display">Dual Display</option>
                            <option value="featured">Featured Image</option>
                            <option value="teaser">Teaser Image</option>
                        </select>
                    </div>

                    <div class="flex gap-2 pt-4">
                        <button type="button" onclick="closeUploadModal()" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Upload
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const CSRF_TOKEN = '<?= $this->escape($csrf_token) ?>';
        let currentPage = 1;
        let selectedImage = null;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadMedia();
            setupEventListeners();
        });

        function setupEventListeners() {
            // Search
            let searchTimeout;
            document.getElementById('searchInput').addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    currentPage = 1;
                    loadMedia();
                }, 500);
            });

            // Filters
            document.getElementById('typeFilter').addEventListener('change', () => {
                currentPage = 1;
                loadMedia();
            });

            document.getElementById('groupDual').addEventListener('change', loadMedia);

            // Upload
            document.getElementById('uploadBtn').addEventListener('click', () => {
                document.getElementById('uploadModal').classList.remove('hidden');
            });

            document.getElementById('uploadForm').addEventListener('submit', handleUpload);

            // Save metadata
            document.getElementById('saveMetadataBtn').addEventListener('click', saveMetadata);

            // Insert image
            document.getElementById('insertImageBtn').addEventListener('click', insertImage);
        }

        async function loadMedia() {
            const search = document.getElementById('searchInput').value;
            const type = document.getElementById('typeFilter').value;
            const groupDual = document.getElementById('groupDual').checked;

            const params = new URLSearchParams({
                page: currentPage,
                limit: 24,
                ...(search && { search }),
                ...(type && { type }),
                ...(groupDual && { group_by: 'dual' }),
                _: Date.now() // Cache buster
            });

            try {
                document.getElementById('loadingIndicator').classList.remove('hidden');
                document.getElementById('mediaGrid').classList.add('hidden');

                const response = await fetch(`/admin/media/api/list?${params}`, {
                    credentials: 'same-origin', // Include cookies for authentication
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await response.json();

                if (data.success) {
                    renderMedia(data.data, groupDual);
                    renderPagination(data.pagination);
                }
            } catch (error) {
                console.error('Failed to load media:', error);
                alert('Failed to load media. Please try again.');
            } finally {
                document.getElementById('loadingIndicator').classList.add('hidden');
            }
        }

        function renderMedia(items, isGrouped) {
            const grid = document.getElementById('mediaGrid');
            const emptyState = document.getElementById('emptyState');

            if (items.length === 0) {
                grid.classList.add('hidden');
                emptyState.classList.remove('hidden');
                return;
            }

            emptyState.classList.add('hidden');
            grid.classList.remove('hidden');
            grid.innerHTML = '';

            items.forEach(item => {
                if (isGrouped && item.type === 'dual') {
                    grid.appendChild(createDualImageCard(item));
                } else if (!isGrouped || item.type === 'single') {
                    const image = isGrouped ? item.image : item;
                    grid.appendChild(createImageCard(image));
                }
            });
        }

        function createImageCard(image) {
            const card = document.createElement('div');
            card.className = 'media-item bg-white rounded-lg shadow overflow-hidden';
            card.dataset.imageId = image.id;
            card.onclick = () => selectImage(image);

            card.innerHTML = `
                <img src="${image.filepath}" alt="${image.alt_text || ''}" class="media-thumbnail">
                <div class="p-2">
                    <p class="text-xs text-gray-600 truncate">${image.original_filename || image.filename}</p>
                    <p class="text-xs text-gray-400">${formatFileSize(image.file_size)}</p>
                </div>
            `;

            return card;
        }

        function createDualImageCard(dualItem) {
            const card = document.createElement('div');
            card.className = 'media-item bg-white rounded-lg shadow overflow-hidden border-2 border-purple-300';
            card.dataset.imageId = dualItem.display.id;
            card.onclick = () => selectDualImage(dualItem);

            card.innerHTML = `
                <div class="relative">
                    <img src="${dualItem.display.filepath}" alt="${dualItem.display.alt_text || ''}" class="media-thumbnail">
                    <div class="absolute top-2 right-2 bg-purple-600 text-white text-xs px-2 py-1 rounded">DUAL</div>
                </div>
                <div class="p-2">
                    <p class="text-xs text-gray-600 truncate">${dualItem.display.original_filename || dualItem.display.filename}</p>
                    <p class="text-xs text-purple-600">Display + Modal</p>
                </div>
            `;

            return card;
        }

        function selectImage(image) {
            selectedImage = { type: 'single', data: image };

            // Highlight selected
            document.querySelectorAll('.media-item').forEach(el => el.classList.remove('selected'));
            document.querySelector(`[data-image-id="${image.id}"]`).classList.add('selected');

            // Show details
            showImageDetails(image);
        }

        function selectDualImage(dualItem) {
            selectedImage = { type: 'dual', data: dualItem };

            // Highlight selected
            document.querySelectorAll('.media-item').forEach(el => el.classList.remove('selected'));
            document.querySelector(`[data-image-id="${dualItem.display.id}"]`).classList.add('selected');

            // Show details for display image
            showImageDetails(dualItem.display, dualItem);
        }

        function showImageDetails(image, dualItem = null) {
            const sidebar = document.getElementById('detailsSidebar');
            sidebar.classList.remove('hidden');

            document.getElementById('selectedImageId').value = image.id;
            document.getElementById('detailsImage').src = image.filepath;
            document.getElementById('detailsFilename').textContent = image.original_filename || image.filename;
            document.getElementById('altText').value = image.alt_text || '';
            document.getElementById('titleText').value = image.title || '';
            document.getElementById('captionText').value = image.caption || '';
            document.getElementById('detailsWidth').textContent = image.width ? `${image.width}px` : 'N/A';
            document.getElementById('detailsHeight').textContent = image.height ? `${image.height}px` : 'N/A';
            document.getElementById('detailsSize').textContent = formatFileSize(image.file_size);
            document.getElementById('detailsDate').textContent = new Date(image.created_at).toLocaleDateString();

            if (dualItem) {
                document.getElementById('detailsFilename').textContent += ' (Dual Image Set)';
            }
        }

        async function saveMetadata() {
            const id = document.getElementById('selectedImageId').value;
            const altText = document.getElementById('altText').value;
            const title = document.getElementById('titleText').value;
            const caption = document.getElementById('captionText').value;

            try {
                const response = await fetch(`/admin/media/api/${id}/metadata`, {
                    method: 'POST',
                    credentials: 'same-origin', // Include cookies for authentication
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        _token: CSRF_TOKEN,
                        alt_text: altText,
                        title: title,
                        caption: caption
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert('Metadata saved successfully!');
                    loadMedia(); // Reload to show updated data
                } else {
                    alert('Failed to save metadata: ' + data.error);
                }
            } catch (error) {
                console.error('Failed to save metadata:', error);
                alert('Failed to save metadata. Please try again.');
            }
        }

        function insertImage() {
            if (!selectedImage) {
                alert('Please select an image first');
                return;
            }

            const altText = document.getElementById('altText').value;
            const title = document.getElementById('titleText').value;

            let imageData;

            if (selectedImage.type === 'dual') {
                imageData = {
                    type: 'dual',
                    displaySrc: selectedImage.data.display.filepath,
                    modalSrc: selectedImage.data.modal ? selectedImage.data.modal.filepath : selectedImage.data.display.filepath,
                    alt: altText,
                    title: title
                };
            } else {
                imageData = {
                    type: 'single',
                    src: selectedImage.data.filepath,
                    alt: altText,
                    title: title,
                    width: selectedImage.data.width,
                    height: selectedImage.data.height
                };
            }

            // Send message to parent window (TinyMCE)
            window.parent.postMessage({
                action: 'insertImage',
                image: imageData
            }, '*');
        }

        async function handleUpload(e) {
            e.preventDefault();

            const formData = new FormData(e.target);
            const submitBtn = e.target.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Uploading...';

            try {
                const response = await fetch('/admin/upload/tinymce', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.location) {
                    alert('Upload successful!');
                    closeUploadModal();
                    loadMedia(); // Reload media grid
                } else {
                    alert('Upload failed: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Upload error:', error);
                alert('Upload failed. Please try again.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Upload';
            }
        }

        function closeUploadModal() {
            document.getElementById('uploadModal').classList.add('hidden');
            document.getElementById('uploadForm').reset();
        }

        function renderPagination(pagination) {
            const container = document.getElementById('pagination');

            if (pagination.pages <= 1) {
                container.classList.add('hidden');
                return;
            }

            container.classList.remove('hidden');
            container.innerHTML = '';

            // Previous button
            if (pagination.page > 1) {
                container.appendChild(createPageButton('«', pagination.page - 1));
            }

            // Page numbers
            for (let i = 1; i <= pagination.pages; i++) {
                if (i === 1 || i === pagination.pages || (i >= pagination.page - 2 && i <= pagination.page + 2)) {
                    container.appendChild(createPageButton(i, i, i === pagination.page));
                } else if (i === pagination.page - 3 || i === pagination.page + 3) {
                    container.appendChild(createPageButton('...', null));
                }
            }

            // Next button
            if (pagination.page < pagination.pages) {
                container.appendChild(createPageButton('»', pagination.page + 1));
            }
        }

        function createPageButton(label, page, active = false) {
            const button = document.createElement('button');
            button.textContent = label;
            button.className = `px-3 py-1 rounded ${active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'} ${page ? '' : 'cursor-default'}`;

            if (page) {
                button.onclick = () => {
                    currentPage = page;
                    loadMedia();
                };
            }

            return button;
        }

        function formatFileSize(bytes) {
            if (!bytes) return 'N/A';
            const kb = bytes / 1024;
            if (kb < 1024) return `${kb.toFixed(1)} KB`;
            const mb = kb / 1024;
            return `${mb.toFixed(1)} MB`;
        }
    </script>
</body>
</html>
