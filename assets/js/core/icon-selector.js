// Define the IconSelector class in the global scope
window.IconSelector = class IconSelector {
    constructor(options) {
        this.container = options.container;
        // Store the instance on the container element
        if (this.container) {
            this.container._iconSelector = this;
        } else {
            console.error('IconSelector: container element not found');
            return;
        }
        
        this.inputField = options.inputField;
        if (!this.inputField) {
            console.error('IconSelector: input field not found');
            return;
        }
        
        this.previewContainer = options.previewContainer;
        if (!this.previewContainer) {
            console.error('IconSelector: preview container not found');
            return;
        }
        
        this.onChange = options.onChange || (() => {});
        
        let initialValue = this.inputField.value || '';
        
        // Try parsing as JSON first
        try {
            const parsed = JSON.parse(initialValue);
            if (parsed.type === 'fontawesome' && parsed.name) {
                // Handle FontAwesome icon
                this.currentValue = parsed;
            } else if (parsed.url) {
                // Handle custom uploaded icon
                let url = parsed.url;
                if (!url.startsWith('http') && !url.startsWith('/wp-content')) {
                    if (typeof window.shortcutsHubData !== 'undefined' && window.shortcutsHubData.uploads_url) {
                        url = window.shortcutsHubData.uploads_url + '/' + url;
                    }
                }
                this.currentValue = {
                    type: 'custom',
                    name: parsed.name || '',
                    url: url
                };
            }
        } catch (e) {
            // Not JSON, check if it's a URL or FontAwesome class
            if (initialValue.includes('fa-')) {
                // It's a FontAwesome class
                this.currentValue = {
                    type: 'fontawesome',
                    name: initialValue.replace(/\+/g, ' '),
                    url: null
                };
            } else if (initialValue) {
                // Assume it's a URL
                let url = initialValue;
                if (!url.startsWith('http') && !url.startsWith('/wp-content')) {
                    if (typeof window.shortcutsHubData !== 'undefined' && window.shortcutsHubData.uploads_url) {
                        url = window.shortcutsHubData.uploads_url + '/' + url;
                    }
                }
                this.currentValue = {
                    type: 'custom',
                    name: '',
                    url: url
                };
            } else {
                // Empty or invalid value
                this.currentValue = {
                    type: 'fontawesome',
                    name: '',
                    url: null
                };
            }
        }
        
        this.init();
    }

    init() {
        this.typeSelect = document.getElementById('icon-type-selector');
        if (!this.typeSelect) {
            console.error('IconSelector: type selector not found');
            return;
        }
        
        this.attachEvents();
        this.createResetButton();
        
        // Initialize based on current type
        if (this.currentValue.type === 'custom') {
            this.typeSelect.value = 'custom';
            this.showCustomUpload();
        } else {
            this.typeSelect.value = 'fontawesome';
            this.showFontAwesomePicker();
        }
        
        this.updatePreview();
    }

    createResetButton() {
        if (!this.previewContainer) return;
        
        // Remove existing reset button if any
        const existingButton = this.previewContainer.parentNode.querySelector('.icon-reset-button');
        if (existingButton) {
            existingButton.remove();
        }
        
        const resetButton = document.createElement('button');
        resetButton.type = 'button';
        resetButton.className = 'icon-reset-button';
        resetButton.innerHTML = '<i class="fas fa-times"></i>';
        resetButton.title = 'Reset Icon';
        
        resetButton.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.resetIcon();
        });
        
        // Add reset button next to preview
        this.previewContainer.parentNode.appendChild(resetButton);
    }

    resetIcon() {
        // Clear the value but maintain the current type
        const currentType = this.typeSelect ? this.typeSelect.value : 'fontawesome';
        this.currentValue = {
            type: currentType,
            name: '',
            url: null
        };
        this.inputField.value = JSON.stringify(this.currentValue);
        this.updatePreview();
        
        // Don't change the type selector or clear the container
        // Just update the selection state in the current view
        if (currentType === 'fontawesome') {
            const options = this.container.querySelectorAll('.icon-option');
            options.forEach(opt => opt.classList.remove('selected'));
        }
        
        this.onChange(this.currentValue);
    }

    attachEvents() {
        if (this.typeSelect) {
            this.typeSelect.addEventListener('change', (e) => {
                const type = e.target.value;
                if (type === 'custom') {
                    this.showCustomUpload();
                } else {
                    this.showFontAwesomePicker();
                }
            });
        }
    }

    showFontAwesomePicker() {
        if (!this.container) {
            console.error('IconSelector: container not found');
            return;
        }
        
        this.container.innerHTML = `
            <div class="icon-search-wrapper">
                <input type="text" class="icon-search" placeholder="Search icons...">
            </div>
            <div class="icons-grid"></div>
        `;
        
        // Show the container
        this.container.style.display = 'block';
        
        const searchInput = this.container.querySelector('.icon-search');
        const iconsGrid = this.container.querySelector('.icons-grid');
        
        // Add font awesome icons
        this.addFontAwesomeIcons(iconsGrid);
        
        // Add search functionality
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                const searchTerm = e.target.value.toLowerCase();
                const icons = iconsGrid.querySelectorAll('.icon-option');
                
                icons.forEach(icon => {
                    const iconName = icon.getAttribute('data-icon').toLowerCase();
                    if (iconName.includes(searchTerm)) {
                        icon.style.display = 'flex';
                    } else {
                        icon.style.display = 'none';
                    }
                });
            });
        }
    }

    showCustomUpload() {
        if (!this.container) {
            console.error('IconSelector: container not found');
            return;
        }
        
        this.container.innerHTML = `
            <div class="custom-upload-container">
                <button type="button" class="upload-image-button">Upload Custom Icon</button>
            </div>
        `;
        
        // Hide the container since we don't need the grid for custom upload
        this.container.style.display = 'none';
        
        const uploadButton = this.container.querySelector('.upload-image-button');
        if (uploadButton) {
            uploadButton.addEventListener('click', () => this.openMediaUploader());
        }
    }

    addFontAwesomeIcons(iconsGrid) {
        if (!iconsGrid) {
            console.error('IconSelector: icons grid not found');
            return;
        }
        
        const popularIcons = [
            'fas fa-mobile-alt', 'fas fa-laptop', 'fas fa-desktop', 
            'fas fa-tablet-alt', 'fas fa-keyboard', 'fas fa-mouse',
            'fas fa-tv', 'fas fa-camera', 'fas fa-video',
            'fas fa-headphones', 'fas fa-microphone', 'fas fa-speaker',
            'fas fa-print', 'fas fa-scanner', 'fas fa-gamepad',
            'fas fa-server', 'fas fa-network-wired', 'fas fa-wifi',
            'fas fa-bluetooth', 'fas fa-battery-full', 'fas fa-power-off',
            'fas fa-hdd', 'fas fa-memory', 'fas fa-microchip',
            'fas fa-usb', 'fas fa-ethernet', 'fas fa-router',
            'fas fa-satellite-dish', 'fas fa-broadcast-tower', 'fas fa-rss'
        ];
        
        popularIcons.forEach(icon => {
            const iconOption = document.createElement('div');
            iconOption.className = 'icon-option';
            iconOption.setAttribute('data-icon', icon);
            
            // Check if this icon is currently selected
            if (this.currentValue && this.currentValue.type === 'fontawesome' && this.currentValue.name === icon) {
                iconOption.classList.add('selected');
            }
            
            iconOption.innerHTML = `<i class="${icon}"></i>`;
            
            iconOption.addEventListener('click', () => {
                // Remove selected class from all options
                iconsGrid.querySelectorAll('.icon-option').forEach(opt => opt.classList.remove('selected'));
                // Add selected class to clicked option
                iconOption.classList.add('selected');
                
                this.setIcon('fontawesome', icon);
            });
            
            iconsGrid.appendChild(iconOption);
        });
    }

    openMediaUploader() {
        // Create the media frame
        const frame = wp.media({
            title: 'Select or Upload Icon',
            button: {
                text: 'Use this icon'
            },
            multiple: false
        });
        
        // When an image is selected in the media frame...
        frame.on('select', () => {
            // Get media attachment details from the frame state
            const attachment = frame.state().get('selection').first().toJSON();
            
            // Set the icon data
            this.setIcon('custom', attachment.title || `image-${Date.now()}`, attachment.url);
        });
        
        // Finally, open the modal on click
        frame.open();
    }

    setIcon(type, name, url = null) {
        this.currentValue = {
            type: type,
            name: name,
            url: url
        };
        
        // Update the hidden input
        this.inputField.value = JSON.stringify(this.currentValue);
        
        // Update the preview
        this.updatePreview();
        
        // Update the type selector
        if (this.typeSelect) {
            this.typeSelect.value = type;
        }
        
        // Trigger onChange callback
        this.onChange(this.currentValue);
    }

    updatePreview() {
        if (!this.previewContainer) return;
        
        // Clear existing preview
        this.previewContainer.innerHTML = '';
        
        if (this.currentValue) {
            if (this.currentValue.type === 'fontawesome' && this.currentValue.name) {
                // Show FontAwesome icon
                const icon = document.createElement('i');
                icon.className = this.currentValue.name;
                this.previewContainer.appendChild(icon);
                this.previewContainer.style.display = 'flex';
            } else if (this.currentValue.type === 'custom' && this.currentValue.url) {
                // Show custom icon
                const img = document.createElement('img');
                img.src = this.currentValue.url;
                img.alt = this.currentValue.name || 'Custom Icon';
                this.previewContainer.appendChild(img);
                this.previewContainer.style.display = 'flex';
            } else {
                // No valid icon
                this.previewContainer.style.display = 'none';
            }
        } else {
            // No icon data
            this.previewContainer.style.display = 'none';
        }
    }
}
