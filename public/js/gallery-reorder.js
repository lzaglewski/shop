/**
 * Gallery Reorder Script
 * Handles drag-and-drop reordering of gallery images
 */

document.addEventListener('DOMContentLoaded', function() {
    const galleryList = document.getElementById('galleryList');

    if (!galleryList) {
        return; // Gallery list not found, exit
    }

    let draggedElement = null;
    let draggedOverElement = null;

    // Add drag event listeners to gallery items
    const galleryItems = galleryList.querySelectorAll('.gallery-item');

    galleryItems.forEach(item => {
        item.draggable = true;

        item.addEventListener('dragstart', handleDragStart);
        item.addEventListener('dragend', handleDragEnd);
        item.addEventListener('dragover', handleDragOver);
        item.addEventListener('drop', handleDrop);
        item.addEventListener('dragenter', handleDragEnter);
        item.addEventListener('dragleave', handleDragLeave);
    });

    function handleDragStart(e) {
        draggedElement = this;
        this.style.opacity = '0.5';
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/html', this.innerHTML);
    }

    function handleDragEnd(e) {
        this.style.opacity = '1';

        // Remove all drag-over classes
        document.querySelectorAll('.gallery-item').forEach(item => {
            item.classList.remove('drag-over');
        });
    }

    function handleDragOver(e) {
        if (e.preventDefault) {
            e.preventDefault();
        }
        e.dataTransfer.dropEffect = 'move';
        return false;
    }

    function handleDragEnter(e) {
        if (this !== draggedElement) {
            this.classList.add('drag-over');
            draggedOverElement = this;
        }
    }

    function handleDragLeave(e) {
        if (e.target === this) {
            this.classList.remove('drag-over');
        }
    }

    function handleDrop(e) {
        if (e.stopPropagation) {
            e.stopPropagation();
        }

        if (draggedElement !== this) {
            // Swap elements
            const allItems = Array.from(galleryList.querySelectorAll('.gallery-item'));
            const draggedIndex = allItems.indexOf(draggedElement);
            const targetIndex = allItems.indexOf(this);

            if (draggedIndex < targetIndex) {
                this.parentNode.insertBefore(draggedElement, this.nextSibling);
            } else {
                this.parentNode.insertBefore(draggedElement, this);
            }

            // Update positions in UI
            updatePositions();

            // Send new positions to server
            sendPositionsToServer();
        }

        this.classList.remove('drag-over');
        return false;
    }

    function updatePositions() {
        const items = galleryList.querySelectorAll('.gallery-item');
        items.forEach((item, index) => {
            item.setAttribute('data-position', index);
            const positionBadge = item.querySelector('.position-badge');
            if (positionBadge) {
                positionBadge.textContent = index;
            }
        });
    }

    function sendPositionsToServer() {
        const items = galleryList.querySelectorAll('.gallery-item');
        const positions = {};

        items.forEach((item, index) => {
            const id = item.getAttribute('data-id');
            positions[id] = index;
        });

        // Send via fetch API
        fetch(document.querySelector('form[action*="gallery/reorder"]')?.getAttribute('action') || '/admin/settings/gallery/reorder', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(positions)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Gallery order updated successfully');
            } else {
                console.error('Error updating gallery order:', data.message);
                // Optionally show an error message to the user
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    // Add CSS for drag-over state
    const style = document.createElement('style');
    style.textContent = `
        .gallery-item {
            transition: background-color 0.2s ease;
        }

        .gallery-item.drag-over {
            background-color: #f0f0f0 !important;
            border: 2px dashed #007bff;
        }

        .drag-handle {
            transition: color 0.2s ease;
        }

        .gallery-item:hover .drag-handle {
            color: #495057 !important;
        }
    `;
    document.head.appendChild(style);
});
