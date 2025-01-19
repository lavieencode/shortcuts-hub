(function($) {
    'use strict';

    class ColorSelector {
        constructor(options) {
            if (!options.container) {
                console.error('ColorSelector: container is required');
                return;
            }

            this.container = options.container;
            this.inputField = options.inputField;
            this.onChange = options.onChange || (() => {});
            this.currentValue = options.value || '#909CFE';
            this.hasSelected = false;

            this.init();
        }

        init() {
            // Cache DOM elements
            this.valueInput = this.container.querySelector('.color-value');
            this.colorPicker = this.container.querySelector('.color-picker');

            // Set initial values
            this.colorPicker.value = this.currentValue;
            this.valueInput.value = 'Select a color...';
            
            // Don't set initial background color - let CSS handle it
            this.attachEvents();
        }

        attachEvents() {
            // Color picker change event
            this.colorPicker.addEventListener('input', (e) => {
                const color = e.target.value;
                this.hasSelected = true;
                this.updateValue(color);
            });

            // Click on text field opens color picker
            this.valueInput.addEventListener('click', () => {
                this.colorPicker.click();
            });
        }

        updateValue(color) {
            this.currentValue = color;
            
            if (this.hasSelected) {
                this.valueInput.value = color.toUpperCase();
                this.valueInput.style.setProperty('background-color', color, 'important');
                this.updatePreview(color);
            } else {
                this.valueInput.value = 'Select a color...';
                // Let CSS handle the background color and text color
                this.valueInput.style.removeProperty('background-color');
                this.valueInput.style.removeProperty('color');
            }
            
            this.colorPicker.value = color;

            // Update hidden input
            if (this.inputField) {
                this.inputField.value = color;
            }

            // Trigger onChange callback
            this.onChange(color);
        }

        updatePreview(color) {
            if (!this.hasSelected) return;
            
            // Calculate luminance to determine text color
            const rgb = this.hexToRgb(color);
            const luminance = (0.299 * rgb.r + 0.587 * rgb.g + 0.114 * rgb.b) / 255;
            
            this.valueInput.style.setProperty('color', luminance > 0.5 ? '#252525' : '#CACACA', 'important');
        }

        hexToRgb(hex) {
            const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
            return result ? {
                r: parseInt(result[1], 16),
                g: parseInt(result[2], 16),
                b: parseInt(result[3], 16)
            } : null;
        }
    }

    // Make ColorSelector available globally
    window.ColorSelector = ColorSelector;

    // Initialize when document is ready
    $(document).ready(() => {
        $('.color-selector-container').each((_, container) => {
            const hiddenInput = container.parentElement.querySelector('input[type="hidden"]');
            new ColorSelector({
                container: container,
                inputField: hiddenInput,
                value: hiddenInput.value
            });
        });
    });
})(jQuery);
