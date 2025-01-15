<?php
namespace ShortcutsHub\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Icons_Manager;

class Shortcuts_Icon_Widget extends Widget_Base {
    public function get_name() {
        return 'icon_widget';
    }

    public function get_title() {
        return __('Shortcut Icon', 'shortcuts-hub');
    }

    public function get_icon() {
        return 'eicon-favorite';
    }

    public function get_categories() {
        return ['shortcuts-hub'];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'section_icon',
            [
                'label' => __('Icon Settings', 'shortcuts-hub'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'selected_icon',
            [
                'label' => __('Icon', 'shortcuts-hub'),
                'type' => Controls_Manager::ICONS,
                'fa4compatibility' => 'icon',
                'default' => [
                    'value' => 'fas fa-star',
                    'library' => 'fa-solid',
                ],
            ]
        );

        $this->add_control(
            'size',
            [
                'label' => __('Size', 'shortcuts-hub'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 6,
                        'max' => 300,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .elementor-icon' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'icon_color',
            [
                'label' => __('Color', 'shortcuts-hub'),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .elementor-icon' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        echo '<div class="elementor-icon-wrapper">';
        echo '<div class="elementor-icon">';
        Icons_Manager::render_icon($settings['selected_icon'], ['aria-hidden' => 'true']);
        echo '</div>';
        echo '</div>';
    }

    protected function content_template() {
        ?>
        <# var iconHTML = elementor.helpers.renderIcon( view, settings.selected_icon, { 'aria-hidden': true }, 'i' , 'object' ); #>
        <div class="elementor-icon-wrapper">
            <div class="elementor-icon">
                {{{ iconHTML.value }}}
            </div>
        </div>
        <?php
    }
}