/**
 * Icon Selector Refactor Plan
 * -------------------------
 * Based on analysis of Premium Addons for Elementor's implementation
 * Reference: /wp-content/plugins/premium-addons-for-elementor/admin/assets/js/jquery-fonticonpicker.js
 * 
 * 1. File Structure
 * ----------------
 * - icon-selector.js (main functionality)
 * - icon-selector.css (styles)
 * - icon-categories.js (icon categorization data)
 * 
 * 2. HTML Structure
 * ----------------
 * <div class="icon-selector">
 *   <div class="selector-preview">
 *     <span class="selected-icon"><i class="fa fa-icon"></i></span>
 *     <span class="selector-button"><i class="fa fa-chevron-down"></i></span>
 *   </div>
 *   <div class="selector-popup">
 *     <div class="selector-search">
 *       <input type="text" class="search-input" placeholder="Search icons...">
 *     </div>
 *     <div class="selector-categories">
 *       <select class="category-select"></select>
 *     </div>
 *     <div class="icons-container"></div>
 *     <div class="selector-pagination">
 *       <span class="page-info">1/10</span>
 *       <button class="prev-page">←</button>
 *       <button class="next-page">→</button>
 *     </div>
 *   </div>
 * </div>
 * 
 * 3. Core Features
 * ---------------
 * a) Icon Loading & Display
 *    - Load icons in paginated chunks (20 per page)
 *    - Cache loaded icons for performance
 *    - Use event delegation for icon clicks
 * 
 * b) Search Functionality
 *    - Debounced search input (300ms)
 *    - Search in both icon names and categories
 *    - Cache search results
 *    - Show/hide clear search button
 * 
 * c) Category Management
 *    - Group icons by category (Solid, Regular, Brands)
 *    - Quick category switching
 *    - Remember last selected category
 * 
 * d) Popup Management
 *    - Position popup relative to button
 *    - Handle window resize and scroll
 *    - Close on outside click
 *    - Keyboard navigation (arrows, enter, escape)
 * 
 * 4. Performance Optimizations
 * --------------------------
 * - Use DocumentFragment for bulk DOM updates
 * - Virtualize long lists of icons
 * - Debounce search and scroll handlers
 * - Cache DOM queries
 * - Lazy load categories
 * 
 * 5. CSS Requirements
 * -----------------
 * - Responsive design (mobile-friendly)
 * - Smooth transitions for popup
 * - Loading states and animations
 * - Clear hover and active states
 * - Accessible focus states
 * 
 * 6. Implementation Steps
 * ---------------------
 * 1. Create basic HTML structure and CSS
 * 2. Implement core icon display and selection
 * 3. Add pagination system
 * 4. Implement search functionality
 * 5. Add category filtering
 * 6. Add keyboard navigation
 * 7. Optimize performance
 * 8. Add loading states and error handling
 * 
 * 7. Dependencies
 * -------------
 * - jQuery (for DOM manipulation)
 * - Font Awesome (icon library)
 * - Lodash/Underscore (for debounce/throttle)
 * 
 * 8. Browser Support
 * ----------------
 * - Modern browsers (Chrome, Firefox, Safari, Edge)
 * - IE11 support if needed (requires polyfills)
 */

(function($) {
    'use strict';

    // Define icon categories with expanded icon sets
    const icons = {
        'fas': [ // Solid
            'mobile-alt', 'laptop', 'desktop', 'tablet-alt', 'keyboard', 'mouse',
            'tv', 'camera', 'video', 'headphones', 'microphone', 'speaker',
            'print', 'scanner', 'gamepad', 'server', 'network-wired', 'wifi',
            'bluetooth', 'battery-full', 'power-off', 'hdd', 'memory', 'microchip',
            'usb', 'ethernet', 'router', 'satellite-dish', 'broadcast-tower', 'rss',
            'cog', 'cogs', 'wrench', 'tools', 'hammer', 'screwdriver',
            'home', 'building', 'store', 'warehouse', 'industry', 'factory',
            'car', 'truck', 'bus', 'plane', 'train', 'subway',
            'search', 'plus', 'minus', 'times', 'check', 'exclamation',
            'question', 'info', 'bell', 'calendar', 'clock', 'folder',
            'file', 'user', 'users', 'chart-bar', 'database', 'globe',
            'map-marker', 'envelope', 'lock', 'unlock', 'shield', 'key'
        ],
        'far': [ // Regular
            'bell', 'bookmark', 'calendar', 'chart-bar', 'clock', 'comment',
            'compass', 'envelope', 'eye', 'file', 'folder', 'heart',
            'image', 'keyboard', 'lightbulb', 'list-alt', 'map', 'moon',
            'paper-plane', 'save', 'share-square', 'star', 'sun', 'thumbs-up',
            'trash-alt', 'user', 'window-maximize', 'window-minimize',
            'edit', 'copy', 'paste', 'cut', 'clone', 'file-alt',
            'folder-open', 'smile', 'frown', 'meh', 'laugh', 'angry',
            'check-circle', 'times-circle', 'question-circle', 'info-circle',
            'comment-dots', 'hand-point-right', 'hand-point-left', 'hand-point-up',
            'hand-point-down', 'credit-card', 'flag', 'gem', 'life-ring', 'play-circle'
        ],
        'fab': [ // Brands
            'android', 'apple', 'chrome', 'discord', 'docker', 'dropbox',
            'edge', 'firefox', 'github', 'google', 'instagram', 'java',
            'jira', 'js', 'linux', 'microsoft', 'npm', 'php',
            'python', 'react', 'sass', 'slack', 'spotify', 'stack-overflow',
            'telegram', 'trello', 'twitter', 'ubuntu', 'vuejs', 'wordpress',
            'aws', 'bootstrap', 'css3', 'html5', 'node', 'yarn',
            'facebook', 'linkedin', 'youtube', 'whatsapp', 'skype', 'twitch',
            'medium', 'pinterest', 'reddit', 'vimeo', 'behance', 'dribbble'
        ]
    };

    class IconSelector {
        constructor(options) {
            if (!options.container) {
                console.error('IconSelector: container is required');
                return;
            }

            this.container = options.container;
            this.inputField = options.inputField;
            this.onChange = options.onChange || (() => {});
            this.currentValue = null;

            // Store the instance on the container element
            this.container._iconSelector = this;

            // Create the HTML structure
            this.container.innerHTML = `
                <div class="icon-input-row">
                    <select class="icon-type-selector">
                        <option value="">Select Icon Type</option>
                        <option value="fontawesome">Font Awesome Icon</option>
                        <option value="custom">Custom Upload</option>
                    </select>
                    <div class="icon-preview-container">
                        <div class="icon-preview empty">
                            <i class="fas fa-image"></i>
                        </div>
                    </div>
                    <button type="button" class="icon-reset">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="icon-selector">
                    <div class="selector-popup">
                        <div class="selector-controls">
                            <input type="text" class="search-input" placeholder="Search icons...">
                            <select class="category-select">
                                <option value="all">All Icons</option>
                                <option value="fas">Solid</option>
                                <option value="far">Regular</option>
                                <option value="fab">Brands</option>
                            </select>
                        </div>
                        <div class="icons-grid"></div>
                    </div>
                </div>
            `;

            this.init();
        }

        init() {
            // Cache DOM elements
            this.typeSelect = this.container.querySelector('.icon-type-selector');
            
            // Initially hide the selector container
            const selectorContainer = this.container.querySelector('.icon-selector');
            if (selectorContainer) {
                selectorContainer.style.display = 'none';
            }
            
            // Initialize based on current type
            if (this.currentValue && this.currentValue.type === 'custom') {
                this.typeSelect.value = 'custom';
                this.showCustomUpload();
            } else if (this.currentValue && this.currentValue.type === 'fontawesome') {
                this.typeSelect.value = 'fontawesome';
                this.showFontAwesomePicker();
            }
            
            this.updatePreview();
            this.createResetButton();
            this.attachEvents();
        }

        createResetButton() {
            const resetButton = this.container.querySelector('.icon-reset');
            if (!resetButton) return;
            
            resetButton.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.resetIcon();
            });
        }

        resetIcon() {
            this.currentValue = null;
            
            // Reset the preview
            const previewContainer = this.container.querySelector('.icon-preview');
            if (previewContainer) {
                previewContainer.innerHTML = '<i class="fas fa-image"></i>';
                previewContainer.classList.add('empty');
            }
            
            // Clear the input value if it exists
            if (this.inputField) {
                this.inputField.value = '';
            }
            
            // Trigger the onChange callback
            this.onChange(null);
        }

        attachEvents() {
            // Type selector change event
            this.typeSelect.addEventListener('change', () => {
                const type = this.typeSelect.value;
                const selectorContainer = this.container.querySelector('.icon-selector');
                
                if (type === '') {
                    selectorContainer.style.display = 'none';
                    return;
                }
                
                selectorContainer.style.display = 'block';
                if (type === 'custom') {
                    this.showCustomUpload();
                } else {
                    this.showFontAwesomePicker();
                }
            });
        }

        showFontAwesomePicker() {
            const selectorContainer = this.container.querySelector('.icon-selector');
            if (!selectorContainer) return;

            // Show the container
            selectorContainer.style.display = 'block';

            // Restore the original HTML structure if needed
            if (!selectorContainer.querySelector('.selector-controls')) {
                selectorContainer.innerHTML = `
                    <div class="selector-popup">
                        <div class="selector-controls">
                            <input type="text" class="search-input" placeholder="Search icons...">
                            <select class="category-select">
                                <option value="all">All Icons</option>
                                <option value="fas">Solid</option>
                                <option value="far">Regular</option>
                                <option value="fab">Brands</option>
                            </select>
                        </div>
                        <div class="icons-grid"></div>
                    </div>
                `;
            }

            const iconsGrid = selectorContainer.querySelector('.icons-grid');
            if (!iconsGrid) return;

            // Show the font awesome selector elements
            const selectorControls = selectorContainer.querySelector('.selector-controls');
            if (iconsGrid) iconsGrid.style.display = 'grid';
            if (selectorControls) selectorControls.style.display = 'flex';

            // Add icons to the grid
            this.addFontAwesomeIcons(iconsGrid);

            // Add search functionality
            const searchInput = selectorContainer.querySelector('.search-input');
            const categorySelect = selectorContainer.querySelector('.category-select');
            
            if (searchInput && categorySelect) {
                const filterIcons = () => {
                    const searchTerm = searchInput.value.toLowerCase();
                    const category = categorySelect.value;
                    
                    const options = iconsGrid.querySelectorAll('.icon-option');
                    options.forEach(option => {
                        const iconName = option.getAttribute('data-name');
                        const iconPrefix = option.getAttribute('data-icon');
                        
                        const matchesSearch = iconName.includes(searchTerm);
                        const matchesCategory = category === 'all' || iconPrefix === category;
                        
                        option.style.display = matchesSearch && matchesCategory ? 'flex' : 'none';
                    });
                };
                
                searchInput.addEventListener('input', filterIcons);
                categorySelect.addEventListener('change', filterIcons);
            }

            // Show the selector
        }

        showCustomUpload() {
            const selectorContainer = this.container.querySelector('.icon-selector');
            if (!selectorContainer) return;

            // Show the container
            selectorContainer.style.display = 'block';

            // Hide the font awesome selector
            const iconsGrid = selectorContainer.querySelector('.icons-grid');
            const selectorControls = selectorContainer.querySelector('.selector-controls');
            if (iconsGrid) iconsGrid.style.display = 'none';
            if (selectorControls) selectorControls.style.display = 'none';

            // Show the custom upload interface
            selectorContainer.innerHTML = `
                <div class="selector-popup">
                    <div class="upload-container">
                        <button type="button" class="upload-button">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span>Click or drag to upload an image</span>
                        </button>
                    </div>
                </div>
            `;

            // Add click handler for upload button
            const uploadButton = selectorContainer.querySelector('.upload-button');
            if (uploadButton) {
                uploadButton.addEventListener('click', () => this.openMediaUploader());
            }
        }

        addFontAwesomeIcons(iconsGrid) {
            if (!iconsGrid) {
                console.error('IconSelector: icons grid not found');
                return;
            }

            // Create document fragment for better performance
            const fragment = document.createDocumentFragment();

            // Add icons for each category
            Object.entries(icons).forEach(([prefix, iconNames]) => {
                iconNames.forEach(name => {
                    const iconClass = `${prefix} fa-${name}`;
                    const iconOption = document.createElement('div');
                    iconOption.className = 'icon-option';
                    iconOption.setAttribute('data-icon', prefix);
                    iconOption.setAttribute('data-name', name);

                    // Create icon element with loading check
                    const icon = document.createElement('i');
                    icon.className = iconClass;
                    icon.style.visibility = 'hidden';

                    const checkIcon = () => {
                        const style = window.getComputedStyle(icon);
                        const fontFamily = style.getPropertyValue('font-family');
                        
                        if (fontFamily === 'serif' || fontFamily === 'sans-serif') {
                            icon.className = 'fas fa-exclamation-circle';
                            icon.style.color = '#ff0000';
                        }
                        
                        icon.style.visibility = 'visible';
                    };

                    setTimeout(checkIcon, 100);
                    iconOption.appendChild(icon);

                    if (this.currentValue && 
                        this.currentValue.type === 'fontawesome' && 
                        this.currentValue.name === iconClass) {
                        iconOption.classList.add('selected');
                    }

                    iconOption.addEventListener('click', () => {
                        iconsGrid.querySelectorAll('.icon-option').forEach(opt => {
                            opt.classList.remove('selected');
                        });
                        iconOption.classList.add('selected');
                        this.setIcon('fontawesome', iconClass);
                    });

                    fragment.appendChild(iconOption);
                });
            });

            iconsGrid.innerHTML = '';
            iconsGrid.appendChild(fragment);
        }

        openMediaUploader() {
            // WordPress media uploader functionality would go here
            console.log('Custom upload not implemented');
        }

        setIcon(type, name, url = null) {
            // DEBUG: Log icon data being set
            sh_debug_log('Setting Icon Data', {
                'message': 'Setting new icon data',
                'source': {
                    'file': 'icon-selector.js',
                    'line': __LINE__,
                    'function': 'setIcon'
                },
                'data': {
                    'type': type,
                    'name': name,
                    'url': url
                },
                'debug': true
            });

            this.currentValue = { type, name, url };
            
            if (this.inputField) {
                this.inputField.value = JSON.stringify({
                    type: type,
                    name: name,
                    url: url
                });
            }
            
            this.updatePreview();
            this.onChange(this.currentValue);
        }

        updatePreview() {
            const previewContainer = this.container.querySelector('.icon-preview');
            if (!previewContainer) return;

            if (!this.currentValue) {
                previewContainer.innerHTML = '<i class="fas fa-image"></i>';
                previewContainer.classList.add('empty');
                return;
            }

            previewContainer.classList.remove('empty');
            
            if (this.currentValue.type === 'fontawesome') {
                previewContainer.innerHTML = `<i class="${this.currentValue.name}"></i>`;
            } else if (this.currentValue.type === 'custom' && this.currentValue.url) {
                previewContainer.innerHTML = `<img src="${this.currentValue.url}" alt="Custom Icon">`;
            }
        }
    }

    // Make IconSelector available globally
    window.IconSelector = IconSelector;

    // Initialize when document is ready
    jQuery(document).ready(function($) {
        $('.icon-selector-container').each(function() {
            const container = $(this);
            const input = container.find('input[type="hidden"]');
            
            new IconSelector({
                container: this,
                inputField: input[0],
                onChange: function(value) {
                    // Optional: Add any onChange handling here
                }
            });
        });
    });
})(jQuery);
