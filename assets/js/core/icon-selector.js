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

    // Define icon categories with core, reliable Font Awesome icons
    const icons = {
        'fas': [ // Solid - Core UI Icons
            'home', 'user', 'users', 'cog', 'wrench', 'search',
            'plus', 'minus', 'times', 'check', 'exclamation', 'question',
            'info', 'bell', 'calendar', 'clock', 'folder', 'file',
            'envelope', 'lock', 'unlock', 'key', 'tag', 'tags',
            'bookmark', 'print', 'camera', 'video', 'image', 'map',
            'map-marker', 'map-pin', 'comment', 'comments', 'phone',
            'desktop', 'laptop', 'tablet', 'mobile', 'server', 'save',
            'star', 'heart', 'thumbs-up', 'thumbs-down', 'edit', 'trash',
            'download', 'upload', 'sync', 'redo', 'undo', 'link',
            'external-link-alt', 'share', 'eye', 'eye-slash', 'bars', 'list',
            'th', 'th-list', 'filter', 'sort', 'chart-bar', 'chart-line',
            'chart-pie', 'globe', 'flag', 'shield-alt', 'play', 'pause'
        ],
        'far': [ // Regular - Most common regular icons
            'bell', 'bookmark', 'calendar', 'chart-bar', 'clock', 'comment',
            'envelope', 'eye', 'file', 'folder', 'heart', 'image',
            'star', 'user', 'circle', 'square', 'check-square', 'save',
            'edit', 'trash-alt', 'clipboard', 'copy', 'list-alt', 'question-circle',
            'check-circle', 'times-circle', 'info-circle', 'play-circle', 'pause-circle', 'stop-circle'
        ],
        'fab': [ // Brands - Most common brand icons
            'android', 'apple', 'chrome', 'facebook', 'facebook-f', 'github',
            'google', 'instagram', 'linkedin', 'twitter', 'whatsapp', 'youtube',
            'wordpress', 'amazon', 'dropbox', 'slack', 'spotify', 'windows'
        ]
    };
    
    // Add basic shapes and common UI elements to ensure grid consistency
    const basicShapes = [
        'square', 'circle', 'dot-circle', 'cube', 'cubes', 'box',
        'arrow-up', 'arrow-right', 'arrow-down', 'arrow-left',
        'chevron-up', 'chevron-right', 'chevron-down', 'chevron-left',
        'caret-up', 'caret-right', 'caret-down', 'caret-left'
    ];
    
    // Add these basic shapes to the solid icons
    icons.fas = [...icons.fas, ...basicShapes];

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
            
            // DEBUG: Log constructor initialization
            sh_debug_log('IconSelector constructor', {
                'message': 'Initializing IconSelector',
                'source': {
                    'file': 'icon-selector.js',
                    'line': 'constructor',
                    'function': 'constructor'
                },
                'data': {
                    'container': options.container ? true : false,
                    'inputField': options.inputField ? options.inputField.id : null,
                    'inputValue': options.inputField ? options.inputField.value : null
                },
                'debug': true
            });
            
            // DIRECT FIX: Enhanced input field value handling
            if (this.inputField && this.inputField.value) {
                const inputValue = this.inputField.value;
                
                // DEBUG: Log initial input value
                sh_debug_log('Initial input value', {
                    'message': 'Initial input field value',
                    'source': {
                        'file': 'icon-selector.js',
                        'line': 'constructor',
                        'function': 'constructor'
                    },
                    'data': {
                        'inputValue': inputValue,
                        'valueType': typeof inputValue,
                        'starts_with_fa': typeof inputValue === 'string' && inputValue.startsWith('fa'),
                        'starts_with_json': typeof inputValue === 'string' && (inputValue.startsWith('{') || inputValue.startsWith('['))
                    },
                    'debug': true
                });
                
                // Case 1: Try parsing as JSON
                if (typeof inputValue === 'string' && (inputValue.startsWith('{') || inputValue.startsWith('['))) {
                    try {
                        const iconData = JSON.parse(inputValue);
                        this.currentValue = iconData;
                        
                        // DEBUG: Log parsed input value
                        sh_debug_log('Parsed JSON input value', {
                            'message': 'Successfully parsed JSON input value',
                            'source': {
                                'file': 'icon-selector.js',
                                'line': 'constructor',
                                'function': 'constructor'
                            },
                            'data': {
                                'inputValue': inputValue,
                                'parsedValue': iconData
                            },
                            'debug': true
                        });
                    } catch (e) {
                        sh_debug_log('JSON parse error', {
                            'message': 'Error parsing JSON input value',
                            'source': {
                                'file': 'icon-selector.js',
                                'line': 'constructor',
                                'function': 'constructor'
                            },
                            'data': {
                                'error': e.message,
                                'inputValue': inputValue
                            },
                            'debug': true
                        });
                    }
                }
                // Case 2: Direct Font Awesome class
                else if (typeof inputValue === 'string' && inputValue.startsWith('fa')) {
                    this.currentValue = {
                        type: 'fontawesome',
                        name: inputValue
                    };
                    
                    // DEBUG: Log direct FA class
                    sh_debug_log('Direct FA class', {
                        'message': 'Found direct Font Awesome class',
                        'source': {
                            'file': 'icon-selector.js',
                            'line': 'constructor',
                            'function': 'constructor'
                        },
                        'data': {
                            'inputValue': inputValue,
                            'currentValue': this.currentValue
                        },
                        'debug': true
                    });
                    
                    // Update the input field with proper JSON format
                    this.inputField.value = JSON.stringify(this.currentValue);
                }
                // Case 3: Any other non-empty string
                else if (typeof inputValue === 'string' && inputValue.trim() !== '') {
                    // Try to make sense of it as a fallback
                    this.currentValue = {
                        type: 'fontawesome',
                        name: inputValue.startsWith('fa') ? inputValue : 'fas fa-' + inputValue
                    };
                    
                    sh_debug_log('Fallback icon parsing', {
                        'message': 'Using fallback parsing for unknown icon format',
                        'source': {
                            'file': 'icon-selector.js',
                            'line': 'constructor',
                            'function': 'constructor'
                        },
                        'data': {
                            'inputValue': inputValue,
                            'currentValue': this.currentValue
                        },
                        'debug': true
                    });
                }
            }

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
            
            // Always set the dropdown to the first option and force the change event
            this.typeSelect.value = '';
            
            // Force a reset of the type selector to ensure it's at the default state
            // This is needed because some browsers might retain previous values
            this.typeSelect.selectedIndex = 0;
            
            // Force a change event to ensure the UI updates
            const changeEvent = new Event('change');
            this.typeSelect.dispatchEvent(changeEvent);
            
            // Create reset button and attach events before updating preview
            this.createResetButton();
            this.attachEvents();
            
            // Update preview if we have a current value
            // Use setTimeout to ensure the dropdown is fully initialized first
            if (this.currentValue) {
                setTimeout(() => {
                    this.updatePreview();
                }, 0);
            }
            
            // DEBUG: Log initialization state
            sh_debug_log('Icon selector initialized', {
                'message': 'Icon selector initialized with default dropdown',
                'source': {
                    'file': 'icon-selector.js',
                    'line': 'init',
                    'function': 'init'
                },
                'data': {
                    'typeSelect_value': this.typeSelect.value,
                    'typeSelect_selectedIndex': this.typeSelect.selectedIndex,
                    'currentValue': this.currentValue
                },
                'debug': true
            });
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
                
                // DEBUG: Log type selector change
                sh_debug_log('Type selector changed', {
                    'message': 'Icon type selector changed',
                    'source': {
                        'file': 'icon-selector.js',
                        'line': 'attachEvents',
                        'function': 'change event'
                    },
                    'data': {
                        'selected_type': type,
                        'current_value': this.currentValue
                    },
                    'debug': true
                });
                
                if (type === '') {
                    selectorContainer.style.display = 'none';
                    return;
                }
                
                selectorContainer.style.display = 'block';
                if (type === 'custom') {
                    this.showCustomUpload();
                } else if (type === 'fontawesome') {
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

        /**
         * Validates if an icon class is likely to be valid
         * @param {string} iconClass - The icon class to validate
         * @return {boolean} - Whether the icon class is valid
         */
        isValidIconClass(iconClass) {
            if (!iconClass) return false;
            
            // Check if it's a string
            if (typeof iconClass !== 'string') return false;
            
            // Basic pattern validation for Font Awesome icons
            const validPattern = /^(fas|far|fab)\s+fa-[a-z0-9-]+$/;
            return validPattern.test(iconClass);
        }
        
        /**
         * Adds Font Awesome icons to the icon grid
         * @param {HTMLElement} iconsGrid - The grid element to add icons to
         */
        addFontAwesomeIcons(iconsGrid) {
            if (!iconsGrid) {
                console.error('IconSelector: icons grid not found');
                return;
            }

            // DEBUG: Log icon grid initialization
            sh_debug_log('Icon grid initialization', {
                'message': 'Initializing icon grid',
                'source': {
                    'file': 'icon-selector.js',
                    'line': 'addFontAwesomeIcons',
                    'function': 'addFontAwesomeIcons'
                },
                'data': {
                    'categories': Object.keys(icons),
                    'total_icons': Object.values(icons).reduce((total, arr) => total + arr.length, 0)
                },
                'debug': true
            });

            // Create document fragment for better performance
            const fragment = document.createDocumentFragment();
            let validIconCount = 0;
            let invalidIconCount = 0;

            // Add icons for each category
            Object.entries(icons).forEach(([prefix, iconNames]) => {
                iconNames.forEach(name => {
                    const iconClass = `${prefix} fa-${name}`;
                    
                    // Validate the icon class before adding
                    if (!this.isValidIconClass(iconClass)) {
                        // DEBUG: Log invalid icon class
                        sh_debug_log('Invalid Icon Class', {
                            'message': 'Skipping invalid icon class',
                            'source': {
                                'file': 'icon-selector.js',
                                'line': 'addFontAwesomeIcons',
                                'function': 'addFontAwesomeIcons'
                            },
                            'data': {
                                'icon_class': iconClass
                            },
                            'debug': true
                        });
                        invalidIconCount++;
                        return; // Skip this icon
                    }
                    
                    validIconCount++;
                    const iconOption = document.createElement('div');
                    iconOption.className = 'icon-option';
                    iconOption.setAttribute('data-icon', prefix);
                    iconOption.setAttribute('data-name', name);

                    // Create icon element with improved loading check
                    const icon = document.createElement('i');
                    icon.className = iconClass;
                    
                    // Add a fallback icon immediately to ensure grid consistency
                    const fallbackIcon = document.createElement('i');
                    fallbackIcon.className = 'fas fa-cube'; // More reliable fallback icon
                    fallbackIcon.style.opacity = '0.5'; // Slightly more visible
                    fallbackIcon.style.position = 'absolute';
                    fallbackIcon.style.top = '50%';
                    fallbackIcon.style.left = '50%';
                    fallbackIcon.style.transform = 'translate(-50%, -50%)';
                    fallbackIcon.style.color = '#aaaaaa'; // Light gray color
                    
                    // Set position relative to allow absolute positioning of fallback
                    iconOption.style.position = 'relative';
                    
                    // Add the fallback first
                    iconOption.appendChild(fallbackIcon);
                    
                    // Then add the actual icon (hidden initially)
                    icon.style.visibility = 'hidden';
                    icon.style.position = 'relative';
                    icon.style.zIndex = '2'; // Ensure it's above the fallback
                    iconOption.appendChild(icon);

                    // Check if the icon loaded properly with improved detection
                    const checkIcon = () => {
                        // Multiple checks for icon loading
                        const style = window.getComputedStyle(icon);
                        const fontFamily = style.getPropertyValue('font-family');
                        const width = icon.offsetWidth;
                        const height = icon.offsetHeight;
                        
                        const iconFailed = (
                            fontFamily === 'serif' || 
                            fontFamily === 'sans-serif' || 
                            width === 0 || 
                            height === 0 ||
                            !icon.getClientRects().length
                        );
                        
                        if (iconFailed) {
                            // If icon failed to load, show a generic icon instead of error
                            icon.className = 'fas fa-cube';
                            icon.style.color = '#aaaaaa';
                            
                            // DEBUG: Log the failed icon
                            sh_debug_log('Icon load failure', {
                                'message': 'Failed to load icon, using fallback',
                                'source': {
                                    'file': 'icon-selector.js',
                                    'line': 'addFontAwesomeIcons',
                                    'function': 'checkIcon'
                                },
                                'data': {
                                    'failed_icon': iconClass,
                                    'fallback': 'fas fa-cube',
                                    'fontFamily': fontFamily,
                                    'width': width,
                                    'height': height
                                },
                                'debug': true
                            });
                        } else {
                            // If icon loaded successfully, hide the fallback
                            if (fallbackIcon.parentNode) {
                                fallbackIcon.style.display = 'none';
                            }
                        }
                        
                        // Make the icon visible regardless
                        icon.style.visibility = 'visible';
                    };

                    // Slightly longer timeout to ensure fonts are loaded
                    setTimeout(checkIcon, 150);

                    // Check if this is the currently selected icon
                    if (this.currentValue && 
                        this.currentValue.type === 'fontawesome' && 
                        this.currentValue.name === iconClass) {
                        iconOption.classList.add('selected');
                    }

                    // Add click handler
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
            
            // DEBUG: Log icon grid completion
            sh_debug_log('Icon grid completion', {
                'message': 'Finished adding icons to grid',
                'source': {
                    'file': 'icon-selector.js',
                    'line': 'addFontAwesomeIcons',
                    'function': 'addFontAwesomeIcons'
                },
                'data': {
                    'valid_icons': validIconCount,
                    'invalid_icons': invalidIconCount,
                    'total_added': iconsGrid.querySelectorAll('.icon-option').length
                },
                'debug': true
            });
        }

        openMediaUploader() {
            // DEBUG: Log media uploader opening
            sh_debug_log('Opening Media Uploader', {
                'message': 'Opening WordPress Media Uploader',
                'source': {
                    'file': 'icon-selector.js',
                    'line': 'openMediaUploader',
                    'function': 'openMediaUploader'
                },
                'data': {
                    'currentValue': this.currentValue
                },
                'debug': true
            });
            
            // Check if Media Uploader API exists
            if (!wp || !wp.media) {
                console.error('WordPress Media Uploader API not available');
                return;
            }
            
            // If an uploader instance already exists, reopen it
            if (this.mediaUploader) {
                this.mediaUploader.open();
                return;
            }
            
            // Create a new media frame
            this.mediaUploader = wp.media({
                title: 'Select or Upload Icon Image',
                button: {
                    text: 'Use this icon'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });
            
            // When an image is selected in the media frame...
            this.mediaUploader.on('select', () => {
                // Get media attachment details from the frame state
                const attachment = this.mediaUploader.state().get('selection').first().toJSON();
                
                // DEBUG: Log selected attachment
                sh_debug_log('Media attachment selected', {
                    'message': 'Media attachment selected from library',
                    'source': {
                        'file': 'icon-selector.js',
                        'line': 'openMediaUploader',
                        'function': 'select callback'
                    },
                    'data': {
                        'attachment': {
                            'id': attachment.id,
                            'url': attachment.url,
                            'type': attachment.type,
                            'subtype': attachment.subtype,
                            'sizes': attachment.sizes ? Object.keys(attachment.sizes) : []
                        }
                    },
                    'debug': true
                });
                
                // Use the icon URL (prefer thumbnail size if available)
                let iconUrl = attachment.url;
                if (attachment.sizes && attachment.sizes.thumbnail) {
                    iconUrl = attachment.sizes.thumbnail.url;
                }
                
                // Set the icon with the custom type and URL
                this.setIcon('custom', {
                    type: 'custom',
                    name: attachment.title || 'Custom Icon',
                    url: iconUrl,
                    id: attachment.id
                });
            });
            
            // Open the uploader dialog
            this.mediaUploader.open();
        }

        /**
         * Sets the icon based on the provided parameters
         * Handles multiple input formats and normalizes them
         * @param {string|object} type - Icon type or complete icon object
         * @param {string|object} name - Icon name or complete icon object
         * @param {string|null} url - URL for custom icons
         */
        setIcon(type, name, url = null) {
            // DEBUG: Log icon data being set
            sh_debug_log('Setting Icon Data', {
                'message': 'Setting new icon data',
                'source': {
                    'file': 'icon-selector.js',
                    'line': 'setIcon',
                    'function': 'setIcon'
                },
                'data': {
                    'type': type,
                    'name': name,
                    'url': url,
                    'name_type': typeof name
                },
                'debug': true
            });
            
            // ENHANCED ICON HANDLING: Comprehensive approach to handling all icon formats
            let processedType = type;
            let processedName = name;
            let processedUrl = url;
            
            // Case 1: Handle when first parameter is a complete icon object
            if (typeof processedType === 'object' && processedType !== null) {
                sh_debug_log('Type is a complete icon object', {
                    'message': 'Type parameter is a complete icon object',
                    'source': {
                        'file': 'icon-selector.js',
                        'line': 'setIcon',
                        'function': 'setIcon'
                    },
                    'data': {
                        'icon_object': processedType
                    },
                    'debug': true
                });
                
                // Extract values from the object
                processedUrl = processedType.url || null;
                processedName = processedType.name || '';
                processedType = processedType.type || 'fontawesome';
            }
            
            // Case 2: Handle when name is a complete icon object
            else if (typeof processedName === 'object' && processedName !== null) {
                sh_debug_log('Name is a complete icon object', {
                    'message': 'Name parameter is a complete icon object',
                    'source': {
                        'file': 'icon-selector.js',
                        'line': 'setIcon',
                        'function': 'setIcon'
                    },
                    'data': {
                        'icon_object': processedName
                    },
                    'debug': true
                });
                
                // Extract values from the object
                processedUrl = processedName.url || processedUrl;
                processedType = processedName.type || processedType;
                processedName = processedName.name || '';
            }
            
            // Case 3: Handle when name is a JSON string
            else if (typeof processedName === 'string' && (processedName.startsWith('{') || processedName.startsWith('['))) {
                try {
                    const parsedIcon = JSON.parse(processedName);
                    
                    sh_debug_log('Parsed JSON icon from name', {
                        'message': 'Successfully parsed JSON icon from name parameter',
                        'source': {
                            'file': 'icon-selector.js',
                            'line': 'setIcon',
                            'function': 'setIcon'
                        },
                        'data': {
                            'parsed_icon': parsedIcon
                        },
                        'debug': true
                    });
                    
                    // Extract values from the parsed object
                    processedUrl = parsedIcon.url || processedUrl;
                    processedType = parsedIcon.type || processedType;
                    processedName = parsedIcon.name || '';
                } catch (e) {
                    sh_debug_log('JSON parse error in name', {
                        'message': 'Error parsing JSON in name parameter',
                        'source': {
                            'file': 'icon-selector.js',
                            'line': 'setIcon',
                            'function': 'setIcon'
                        },
                        'data': {
                            'error': e.message,
                            'name_value': processedName
                        },
                        'debug': true
                    });
                }
            }
            
            // Case 4: Handle when type is 'fontawesome' but name doesn't have proper format
            if (processedType === 'fontawesome' && typeof processedName === 'string') {
                // If name doesn't start with 'fa', add 'fas fa-' prefix
                if (!processedName.startsWith('fa')) {
                    // If it already has 'fa-' prefix, just add 'fas '
                    if (processedName.startsWith('fa-')) {
                        processedName = 'fas ' + processedName;
                    } else {
                        // Otherwise add the full prefix
                        processedName = 'fas fa-' + processedName;
                    }
                    
                    sh_debug_log('Fixed FA icon name', {
                        'message': 'Fixed Font Awesome icon name format',
                        'source': {
                            'file': 'icon-selector.js',
                            'line': 'setIcon',
                            'function': 'setIcon'
                        },
                        'data': {
                            'fixed_name': processedName
                        },
                        'debug': true
                    });
                }
                
                // Validate the icon class
                if (!this.isValidIconClass(processedName)) {
                    sh_debug_log('Invalid icon class', {
                        'message': 'Invalid icon class detected, using fallback',
                        'source': {
                            'file': 'icon-selector.js',
                            'line': 'setIcon',
                            'function': 'setIcon'
                        },
                        'data': {
                            'invalid_name': processedName
                        },
                        'debug': true
                    });
                    
                    // Use a fallback icon
                    processedName = 'fas fa-cube';
                }
            }

            // Create the standardized icon object
            this.currentValue = { 
                type: processedType || 'fontawesome', 
                name: processedName || 'fas fa-image', // Default fallback
                url: processedUrl || null 
            };
            
            sh_debug_log('Final icon value', {
                'message': 'Final icon value after processing',
                'source': {
                    'file': 'icon-selector.js',
                    'line': 'setIcon',
                    'function': 'setIcon'
                },
                'data': {
                    'currentValue': this.currentValue
                },
                'debug': true
            });
            
            // Update the input field with the standardized format
            if (this.inputField) {
                this.inputField.value = JSON.stringify(this.currentValue);
            }
            
            // Update the UI
            this.updatePreview();
            this.onChange(this.currentValue);
            
            // Update the type selector to match
            const typeSelector = this.container.querySelector('.icon-type-selector');
            if (typeSelector) {
                // First reset to default option
                typeSelector.selectedIndex = 0;
                typeSelector.value = '';
                
                // Then set to the correct type
                setTimeout(() => {
                    typeSelector.value = this.currentValue.type;
                    
                    // Force a change event to ensure the UI updates
                    const changeEvent = new Event('change');
                    typeSelector.dispatchEvent(changeEvent);
                    
                    // DEBUG: Log type selector update
                    sh_debug_log('Updated type selector in setIcon', {
                        'message': 'Updated type selector to match current value',
                        'source': {
                            'file': 'icon-selector.js',
                            'line': 'setIcon',
                            'function': 'setIcon'
                        },
                        'data': {
                            'typeSelector_value': typeSelector.value,
                            'typeSelector_selectedIndex': typeSelector.selectedIndex,
                            'currentValue_type': this.currentValue.type
                        },
                        'debug': true
                    });
                }, 0);
            }
            
            return this.currentValue; // Return the processed icon data
        }

        updatePreview() {
            const previewContainer = this.container.querySelector('.icon-preview');
            if (!previewContainer) return;
            
            // Log the dropdown state before updating preview
            const typeSelector = this.container.querySelector('.icon-type-selector');
            const dropdownStateBefore = {
                value: typeSelector ? typeSelector.value : null,
                selectedIndex: typeSelector ? typeSelector.selectedIndex : null
            };
            
            // DEBUG: Log preview update with dropdown state
            sh_debug_log('Updating icon preview', {
                'message': 'Updating icon preview with current value',
                'source': {
                    'file': 'icon-selector.js',
                    'line': 'updatePreview',
                    'function': 'updatePreview'
                },
                'data': {
                    'currentValue': this.currentValue,
                    'previewContainer': previewContainer ? true : false,
                    'dropdownStateBefore': dropdownStateBefore
                },
                'debug': true
            });

            // Case 1: No current value - show empty state
            if (!this.currentValue) {
                previewContainer.innerHTML = '<i class="fas fa-image"></i>';
                previewContainer.classList.add('empty');
                return;
            }

            // Remove empty class since we have a value to display
            previewContainer.classList.remove('empty');
            
            // ENHANCED PREVIEW HANDLING: Comprehensive approach to rendering all icon formats
            
            // Case 2: Handle Font Awesome icons
            if (this.currentValue.type === 'fontawesome') {
                // Make sure we have a valid icon class
                let iconClass = this.currentValue.name;
                
                // If the icon class doesn't start with 'fa', it's not valid
                if (!iconClass.startsWith('fa')) {
                    // If it already has 'fa-' prefix, just add 'fas '
                    if (iconClass.startsWith('fa-')) {
                        iconClass = 'fas ' + iconClass;
                    } else {
                        // Otherwise add the full prefix
                        iconClass = 'fas fa-' + iconClass;
                    }
                }
                
                // DEBUG: Log fontawesome icon being set
                sh_debug_log('Setting FontAwesome icon in preview', {
                    'message': 'Setting FontAwesome icon in preview',
                    'source': {
                        'file': 'icon-selector.js',
                        'line': 'updatePreview',
                        'function': 'updatePreview'
                    },
                    'data': {
                        'original_icon_class': this.currentValue.name,
                        'final_icon_class': iconClass
                    },
                    'debug': true
                });
                
                previewContainer.innerHTML = `<i class="${iconClass}"></i>`;
                
                // Do not update the type selector - let the user control it
                // This ensures the dropdown state remains consistent with user selection
            } 
            // Case 3: Handle custom image icons
            else if (this.currentValue.type === 'custom' && this.currentValue.url) {
                previewContainer.innerHTML = `<img src="${this.currentValue.url}" alt="Custom Icon">`;
                
                // Do not update the type selector - let the user control it
                // This ensures the dropdown state remains consistent with user selection
            } 
            // Case 4: Handle direct string value (legacy format)
            else if (typeof this.currentValue === 'string' || 
                     (typeof this.currentValue === 'object' && this.currentValue !== null && !this.currentValue.type)) {
                
                let iconValue = typeof this.currentValue === 'string' ? 
                               this.currentValue : 
                               JSON.stringify(this.currentValue);
                
                sh_debug_log('Legacy icon format', {
                    'message': 'Handling legacy icon format',
                    'source': {
                        'file': 'icon-selector.js',
                        'line': 'updatePreview',
                        'function': 'updatePreview'
                    },
                    'data': {
                        'iconValue': iconValue,
                        'valueType': typeof this.currentValue
                    },
                    'debug': true
                });
                
                // Try to render something meaningful
                if (iconValue.startsWith('fa')) {
                    previewContainer.innerHTML = `<i class="${iconValue}"></i>`;
                    
                    // Update the current value to the standardized format
                    this.currentValue = {
                        type: 'fontawesome',
                        name: iconValue,
                        url: null
                    };
                    
                    // Do not update the type selector - let the user control it
                    // This ensures the dropdown state remains consistent with user selection
                    
                    // Update the input field
                    if (this.inputField) {
                        this.inputField.value = JSON.stringify(this.currentValue);
                    }
                } else {
                    // Just display as text
                    previewContainer.innerHTML = `<span>${iconValue}</span>`;
                }
            }
            // Case 5: Fallback for any other cases
            else {
                sh_debug_log('Icon preview fallback', {
                    'message': 'Using fallback for unknown icon format',
                    'source': {
                        'file': 'icon-selector.js',
                        'line': 'updatePreview',
                        'function': 'updatePreview'
                    },
                    'data': {
                        'currentValue': this.currentValue
                    },
                    'debug': true
                });
                
                // Last resort fallback
                previewContainer.innerHTML = '<i class="fas fa-image"></i>';
                previewContainer.classList.add('empty');
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
