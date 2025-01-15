# Dynamic Tags - Elementor Documentation

Dynamic Tags are used to insert customized data based on various sources.

For example, Elementor Pro allows you to add dynamic data based on the page and site parameters, this includes data such as; Post Title, Post Excerpt, Author Info, Site Name, Site Logo, and much more.

Addon developers can create a wide range of dynamic tags, and can even use external APIs to pull data to Elementor.

## How it Works
Dynamic tags interact with controls by extending the control functionality. They turn static controls into smart components. With dynamic tags, users can leverage dynamically generated data on their sites.

You could say that dynamic tags are like functions, the user can define custom parameters to change the output based on different factors.

###Elementor Pro
The dynamic tags functionality is defined in Elementor's core, but the basic version of Elementor does not support active dynamic tags. They are a feature of Elementor Pro, which includes dozens of dynamic tags to choose from.


## Add New Dynamic Tag
 
Elementor offers many built-in dynamic tags out of the box, but it also allows external developers to register new tags.

### Hooks
To do that we simply hook to the elementor/dynamic_tags/register action which provides access to the dynamic tags manager as a parameter. Developers can use the manager to add new tags using the register() method with the dynamic tag instance.

### Registering Dynamic Tags
To register new dynamic tags use the following code:

function register_new_dynamic_tags( $dynamic_tags_manager ) {

	require_once( __DIR__ . '/dynamic-tags/dynamic-tag-1.php' );
	require_once( __DIR__ . '/dynamic-tags/dynamic-tag-2.php' );

	$dynamic_tags_manager->register( new \Elementor_Dynamic_Tag_1() );
	$dynamic_tags_manager->register( new \Elementor_Dynamic_Tag_2() );

}
add_action( 'elementor/dynamic_tags/register', 'register_new_dynamic_tags' );

The manager registers the new tag by accepting the dynamic tags instances. For more information about the dynamic tag class, read about the dynamic tag class structure.

## Remove Dynamic Tags
 
To remove existing dynamic tag developers can simply unregister existing tags by passing the dynamic tag name.

### Hooks
To do that we need to hook to the elementor/dynamic_tags/register action which holds the dynamic tags manager, and pass the dynamic tag name to the unregister() method.

### Unregistering Dynamic Tags
To unregister existing dynamic tags use the following code:

function unregister_dynamic_tags( $dynamic_tags_manager ) {

	$dynamic_tags_manager->unregister( 'dynamic-tag-1' );
	$dynamic_tags_manager->unregister( 'dynamic-tag-2' );

}
add_action( 'elementor/dynamic_tags/register', 'unregister_dynamic_tags' );

Dynamic Tags Structure
 
Each dynamic tag needs to have a few basic settings, such as a unique name. On top of that, there are some advanced settings like dynamic tag controls, which are basically optional fields where users can configure their custom data. There is also a render method that generates the final output based on user settings taken from the dynamic tag’s controls.

Extending Dynamic Tags
To create your own control, you need to extend the dynamic tags control to inherit its methods:

class Elementor_Test_Tag extends \Elementor\Core\DynamicTags\Tag {
}
Dynamic Tags Methods
A simple dynamic tag skeleton will look like this:

class Elementor_Test_Tag extends \Elementor\Core\DynamicTags\Tag {

	public function get_name(): string {}

	public function get_title(): string {}

	public function get_group(): array {}

	public function get_categories(): array {}

	protected function register_controls(): void {}

	public function render(): void {}

}
The dynamic tags methods can be divided into the following groups:

Data
Groups
Categories
Controls
Rendering
Please note that the \Elementor\Core\DynamicTags\Tag class has many more methods, but the methods mentioned above will cover the vast majority of your needs.

Dynamic Tags Data
 
Each dynamic tag requires basic information like the unique ID, title, group and category.

Data Methods
Dynamic tags data is "returned" by these methods:

class Elementor_Test_Tag extends \Elementor\Core\DynamicTags\Tag {

	public function get_name(): string {
		return 'tag-name';
	}

	public function get_title(): string {
		return esc_html__( 'Dynamic Tag Name', 'textdomain' );
	}

	public function get_group(): array {
		return [ 'group-name' ];
	}

	public function get_categories(): array {
		return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ];
	}

}
Dynamic Tag Name - The get_name() method returns a unique ID that will be used in the code.

Dynamic Tag Title – The get_title() method returns the tag label displayed to the user.

Dynamic Tag Group – The get_group() – method returns an array of groups the tag will appear under.

Dynamic Tag Categories – The get_categories() method returns an array of control categories the tag belongs to.

Dynamic Tags Groups
 Dynamic Tags List
To simplify navigation, all tags are arranged into groups. This allows users to quickly scan the list and scroll to the group that suits them.

Available Groups
Elementor Pro adds the following groups:

Post - Post related dynamic data.
Archive - Theme archive related dynamic data.
Site - Site related dynamic data.
Media - Dynamic data based on media files.
Actions - Custom dynamic data.
Author - Post author dynamic data.
Comments - Post comments dynamic data.
If you would like to use the groups added by Elementor Pro, your addons must make sure Elementor Pro was loaded.

Applying Groups
When creating new dynamic tags, you can set the tag group by returning group names with the get_group() method:

class Elementor_Test_Tag extends \Elementor\Core\DynamicTags\Tag {

	public function get_group(): array {
		return [ 'action' ];
	}

}
Creating New Groups
Elementor Pro’s dynamic tags manager lets external developers create custom groups using the elementor/dynamic_tags/register_tags action hook:

/**
 * Register new dynamic tag group
 *
 * @since 1.0.0
 * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager Elementor dynamic tags manager.
 * @return void
 */
function register_new_dynamic_tag_group( $dynamic_tags_manager ) {

	$dynamic_tags_manager->register_group(
		'group-name',
		[
			'title' => esc_html__( 'Group Label', 'textdomain' )
		]
	);

}
add_action( 'elementor/dynamic_tags/register', 'register_new_dynamic_tag_group' );

Dynamic Tags Categories
 
When controls are created, developers can define whether the control can accept dynamic data or not. If the control does accept dynamic data, then a data type (e.g. text values, colors, images) must be set. Dynamic tags, on the other hand, need to define what types of data they return to the control.

This is where categories come in handy. Elementor Pro has a list of categories arranged by data type, which are returned by the dynamic tag to the control.

Available Category Constants
Elementor Pro has the following predefined dynamic tag category constants:

Constant	Value	Info
NUMBER_CATEGORY	number	Dynamic tags for number controls
TEXT_CATEGORY	text	Dynamic tags for text controls
URL_CATEGORY	url	Dynamic tags for URL controls
COLOR_CATEGORY	color	Dynamic tags for color controls
IMAGE_CATEGORY	image	Dynamic tags for image controls
MEDIA_CATEGORY	media	Dynamic tags for media controls
GALLERY_CATEGORY	gallery	Dynamic tags for gallery controls
POST_META_CATEGORY	post_meta	Dynamic tags for post meta controls
Please Note

In older Elementor versions, you could define the returned value as simple text like url, number, text, media etc. As Elementor has evolved, it replaced the text based values with predefined uppercase constants. We strongly recommend you replace old text base values with new category constants.

Applying Categories on Dynamic Tags
When creating new dynamic tags, you need to define what data type the tag will return. This is done with the get_categories() method:

class Elementor_Test_Tag extends \Elementor\Core\DynamicTags\Tag {

	public function get_categories(): array {
		return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ];
	}

}
The method returns an array, meaning that the dynamic tag can return several data types to the control:

class Elementor_Test_Tag extends \Elementor\Core\DynamicTags\Tag {

	public function get_categories(): array {
		return [
			\Elementor\Modules\DynamicTags\Module::URL_CATEGORY,
			\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY,
			\Elementor\Modules\DynamicTags\Module::NUMBER_CATEGORY
		];
	}

}
Using Categories in Controls
On the other hand, when you create controls, you need to support dynamic tags when defining the control settings through the get_default_settings() method and choose the category to display:

class Elementor_Test_Control extends \Elementor\Base_Control {

	public function get_type(): string {}

	public function content_template(): void {}

	protected function get_default_settings(): array {

		return [
			'show_label' => true,
			'label_block' => true,
			'separator' => 'after',
			'dynamic' => [
				'active' => true,
				'categories' => [
					\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY,
					\Elementor\Modules\DynamicTags\Module::NUMBER_CATEGORY
				],
			],
		];

	}

}

Dynamic Tags Controls
 
Simple tags return dynamic data that have no dependencies, such as a random number. More complex tags may have optional fields where users can configure their custom data, such as a random number where the user can set minimum and maximum values. Later, the render method will use those custom fields to generate the final output.

Registering Controls
To set custom controls for dynamic tags, use the register_controls() method as follows:

class Elementor_Test_Tag extends \Elementor\Core\DynamicTags\Tag {

	protected function register_controls(): void {

		$this->add_control(
			'text_param',
			[
				'type' => \Elementor\Controls_Manager::TEXT,
				'label' => esc_html__( 'Text Param', 'textdomain' ),
				'placeholder' => esc_html__( 'Enter your title', 'textdomain' ),
			]
		);

		$this->add_control(
			'number_param',
			[
				'type' => \Elementor\Controls_Manager::NUMBER,
				'label' => esc_html__( 'Number Param', 'textdomain' ),
				'min' => 0,
				'max' => 100,
				'step' => 1,
				'default' => 50,
			]
		);

		$this->add_control(
			'select_param',
			[
				'type' => \Elementor\Controls_Manager::SELECT,
				'label' => esc_html__( 'Select Param', 'textdomain' ),
				'options' => [
					'default' => esc_html__( 'Default', 'textdomain' ),
					'yes' => esc_html__( 'Yes', 'textdomain' ),
					'no' => esc_html__( 'No', 'textdomain' ),
				],
				'default' => 'no',
			]
		);

	}

}
The same control mechanism is used for widget controls.

Dynamic Tags Rendering
 
The render method generates the final output and echoes the data to the control. If the dynamic tag has controls, the render function should use the data while generating the output.

Rendering Methods
To render the dynamic tag output and data echoes, we use the render() method as follows:

class Elementor_Test_Tag extends \Elementor\Core\DynamicTags\Tag {

	public function render(): void {

		echo rand();

	}

}
To extract data from the dynamic tag controls, we can use the get_settings() method:

class Elementor_Test_Tag extends \Elementor\Core\DynamicTags\Tag {

	public function render(): void {
		$param1 = $this->get_settings( 'text_param' );
		$param2 = $this->get_settings( 'number_param' );
		$param3 = $this->get_settings( 'select_param' );

		echo "{$param1} {$param2} {$param3}";
	}

}

# EXAMPLES

## Simple Example

To put it all together, we're going to create a simple dynamic tag which will return a random number. To simplify the example, this dynamic tag won't have controls. But you can enhance the code and add two controls for minimum and maximum limits.

Folder Structure
The addon will have two files. The dynamic tag with its functionality. And the main file will register the tag.

elementor-random-number-dynamic-tag/
|
├─ dynamic-tags/
|  └─ random-number-dynamic-tag.php
|
└─ elementor-random-number-dynamic-tag.php
Plugin Files
elementor-random-number-dynamic-tag.php

<?php
/**
 * Plugin Name: Elementor Random Number Dynamic Tag
 * Description: Add dynamic tag that returns a random number.
 * Plugin URI:  https://elementor.com/
 * Version:     1.0.0
 * Author:      Elementor Developer
 * Author URI:  https://developers.elementor.com/
 * Text Domain: elementor-random-number-dynamic-tag
 *
 * Requires Plugins: elementor
 * Elementor tested up to: 3.25.0
 * Elementor Pro tested up to: 3.25.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Register Random Number Dynamic Tag.
 *
 * Include dynamic tag file and register tag class.
 *
 * @since 1.0.0
 * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager Elementor dynamic tags manager.
 * @return void
 */
function register_random_number_dynamic_tag( $dynamic_tags_manager ) {

	require_once( __DIR__ . '/dynamic-tags/random-number-dynamic-tag.php' );

	$dynamic_tags_manager->register( new \Elementor_Dynamic_Tag_Random_Number );

}
add_action( 'elementor/dynamic_tags/register', 'register_random_number_dynamic_tag' );
dynamic-tags/random-number-dynamic-tag.php

<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Elementor Dynamic Tag - Random Number
 *
 * Elementor dynamic tag that returns a random number.
 *
 * @since 1.0.0
 */
class Elementor_Dynamic_Tag_Random_Number extends \Elementor\Core\DynamicTags\Tag {

	/**
	 * Get dynamic tag name.
	 *
	 * Retrieve the name of the random number tag.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string Dynamic tag name.
	 */
	public function get_name(): string {
		return 'random-number';
	}

	/**
	 * Get dynamic tag title.
	 *
	 * Returns the title of the random number tag.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string Dynamic tag title.
	 */
	public function get_title(): string {
		return esc_html__( 'Random Number', 'elementor-random-number-dynamic-tag' );
	}

	/**
	 * Get dynamic tag groups.
	 *
	 * Retrieve the list of groups the random number tag belongs to.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array Dynamic tag groups.
	 */
	public function get_group(): array {
		return [ 'actions' ];
	}

	/**
	 * Get dynamic tag categories.
	 *
	 * Retrieve the list of categories the random number tag belongs to.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array Dynamic tag categories.
	 */
	public function get_categories(): array {
		return [ \Elementor\Modules\DynamicTags\Module::NUMBER_CATEGORY ];
	}

	/**
	 * Render tag output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function render(): void {
		echo rand();
	}

}

## Advanced Example

This more advanced example will include the use of custom fields, and controls where the user can set fields. The tag will calculate the average of those fields and include a single control where the user can set a comma-separated list of ACF field IDs.

Folder Structure
The addon will have two files. The dynamic tag with its functionality. And the main file will register the tag and tags group.

elementor-acf-average-dynamic-tag/
|
├─ dynamic-tags/
|  └─ acf-average-dynamic-tag.php
|
└─ elementor-acf-average-dynamic-tag.php
Plugin Files
elementor-acf-average-dynamic-tag.php

<?php
/**
 * Plugin Name: Elementor ACF Average Dynamic Tag
 * Description: Add dynamic tag that returns an ACF average.
 * Plugin URI:  https://elementor.com/
 * Version:     1.0.0
 * Author:      Elementor Developer
 * Author URI:  https://developers.elementor.com/
 * Text Domain: elementor-acf-average-dynamic-tag
 *
 * Requires Plugins: elementor
 * Elementor tested up to: 3.25.0
 * Elementor Pro tested up to: 3.25.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Register New Dynamic Tag Group.
 *
 * Register new site group for site-related tags.
 *
 * @since 1.0.0
 * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager Elementor dynamic tags manager.
 * @return void
 */
function register_site_dynamic_tag_group( $dynamic_tags_manager ) {

	$dynamic_tags_manager->register_group(
		'site',
		[
			'title' => esc_html__( 'Site', 'elementor-acf-average-dynamic-tag' )
		]
	);

}
add_action( 'elementor/dynamic_tags/register', 'register_site_dynamic_tag_group' );

/**
 * Register ACF Average Dynamic Tag.
 *
 * Include dynamic tag file and register tag class.
 *
 * @since 1.0.0
 * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager Elementor dynamic tags manager.
 * @return void
 */
function register_acf_average_dynamic_tag( $dynamic_tags_manager ) {

	require_once( __DIR__ . '/dynamic-tags/acf-average-dynamic-tag.php' );

	$dynamic_tags_manager->register( new \Elementor_Dynamic_Tag_ACF_Average );

}
add_action( 'elementor/dynamic_tags/register', 'register_acf_average_dynamic_tag' );
dynamic-tags/acf-average-dynamic-tag.php

<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Elementor Dynamic Tag - ACF Average
 *
 * Elementor dynamic tag that returns an ACF average.
 *
 * @since 1.0.0
 */
class Elementor_Dynamic_Tag_ACF_Average extends \Elementor\Core\DynamicTags\Tag {

	/**
	 * Get dynamic tag name.
	 *
	 * Retrieve the name of the ACF average tag.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string Dynamic tag name.
	 */
	public function get_name(): string {
		return 'acf-average';
	}

	/**
	 * Get dynamic tag title.
	 *
	 * Returns the title of the ACF average tag.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string Dynamic tag title.
	 */
	public function get_title(): string {
		return esc_html__( 'ACF Average', 'elementor-acf-average-dynamic-tag' );
	}

	/**
	 * Get dynamic tag groups.
	 *
	 * Retrieve the list of groups the ACF average tag belongs to.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array Dynamic tag groups.
	 */
	public function get_group(): array {
		return [ 'site' ];
	}

	/**
	 * Get dynamic tag categories.
	 *
	 * Retrieve the list of categories the ACF average tag belongs to.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array Dynamic tag categories.
	 */
	public function get_categories(): array {
		return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ];
	}

	/**
	 * Register dynamic tag controls.
	 *
	 * Add input fields to allow the user to customize the ACF average tag settings.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @return void
	 */
	protected function register_controls(): void {
		$this->add_control(
			'fields',
			[
				'label' => esc_html__( 'Fields', 'elementor-acf-average-dynamic-tag' ),
				'type' => 'text',
			]
		);
	}

	/**
	 * Render tag output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function render(): void {
		$fields = $this->get_settings( 'fields' );
		$sum = 0;
		$count = 0;
		$value = 0;

		// Make sure that ACF if installed and activated
		if ( ! function_exists( 'get_field' ) ) {
			echo 0;
			return;
		}

		foreach ( explode( ',', $fields ) as $index => $field_name ) {
			$field = get_field( $field_name );
			if ( (int) $field > 0 ) {
				$sum += (int) $field;
				$count++;
			}
		}

		if ( 0 !== $count ) {
			$value = $sum / $count;
		}

		echo $value;
	}

}

## Complex Example

Complex Example
 
To showcase a complex dynamic tag, we are going to allow the user to display server variables from a list of available server variables. It will include a custom dynamic tags group and feature a control with a select box containing all the available server variables. The render function will return the variable the user selected and return its value.

Folder Structure
The addon will have two files. The dynamic tag with its functionality. And the main file will register the tag and tags group.

elementor-server-variable-dynamic-tag/
|
├─ dynamic-tags/
|  └─ server-variable-dynamic-tag.php
|
└─ elementor-server-variable-dynamic-tag.php
Plugin Files
elementor-server-variable-dynamic-tag.php

<?php
/**
 * Plugin Name: Elementor Server Variable Dynamic Tag
 * Description: Add dynamic tag that returns an server variable.
 * Plugin URI:  https://elementor.com/
 * Version:     1.0.0
 * Author:      Elementor Developer
 * Author URI:  https://developers.elementor.com/
 * Text Domain: elementor-server-variable-dynamic-tag
 *
 * Requires Plugins: elementor
 * Elementor tested up to: 3.25.0
 * Elementor Pro tested up to: 3.25.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Register Request Variables Dynamic Tag Group.
 *
 * Register new dynamic tag group for Request Variables.
 *
 * @since 1.0.0
 * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager Elementor dynamic tags manager.
 * @return void
 */
function register_request_variables_dynamic_tag_group( $dynamic_tags_manager ) {

	$dynamic_tags_manager->register_group(
		'request-variables',
		[
			'title' => esc_html__( 'Request Variables', 'textdomain' )
		]
	);

}
add_action( 'elementor/dynamic_tags/register', 'register_request_variables_dynamic_tag_group' );

/**
 * Register Server Variable Dynamic Tag.
 *
 * Include dynamic tag file and register tag class.
 *
 * @since 1.0.0
 * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager Elementor dynamic tags manager.
 * @return void
 */
function register_server_variable_dynamic_tag( $dynamic_tags_manager ) {

	require_once( __DIR__ . '/dynamic-tags/server-variable-dynamic-tag.php' );

	$dynamic_tags_manager->register( new \Elementor_Dynamic_Tag_Server_Variable );

}
add_action( 'elementor/dynamic_tags/register', 'register_server_variable_dynamic_tag' );
dynamic-tags/server-variable-dynamic-tag.php

<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Elementor Dynamic Tag - Server Variable
 *
 * Elementor dynamic tag that returns a server variable.
 *
 * @since 1.0.0
 */
class Elementor_Dynamic_Tag_Server_Variable extends \Elementor\Core\DynamicTags\Tag {

	/**
	 * Get dynamic tag name.
	 *
	 * Retrieve the name of the server variable tag.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string Dynamic tag name.
	 */
	public function get_name(): string {
		return 'server-variable';
	}

	/**
	 * Get dynamic tag title.
	 *
	 * Returns the title of the server variable tag.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string Dynamic tag title.
	 */
	public function get_title(): string {
		return esc_html__( 'Server Variable', 'textdomain' );
	}

	/**
	 * Get dynamic tag groups.
	 *
	 * Retrieve the list of groups the server variable tag belongs to.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array Dynamic tag groups.
	 */
	public function get_group(): array {
		return [ 'request-variables' ];
	}

	/**
	 * Get dynamic tag categories.
	 *
	 * Retrieve the list of categories the server variable tag belongs to.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array Dynamic tag categories.
	 */
	public function get_categories(): array {
		return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ];
	}

	/**
	 * Register dynamic tag controls.
	 *
	 * Add input fields to allow the user to customize the server variable tag settings.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @return void
	 */
	protected function register_controls(): void {
		$variables = [];

		foreach ( array_keys( $_SERVER ) as $variable ) {
			$variables[ $variable ] = ucwords( str_replace( '_', ' ', $variable ) );
		}

		$this->add_control(
			'user_selected_variable',
			[
				'type' => \Elementor\Controls_Manager::SELECT,
				'label' => esc_html__( 'Variable', 'textdomain' ),
				'options' => $variables,
			]
		);
	}

	/**
	 * Render tag output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function render(): void {
		$user_selected_variable = $this->get_settings( 'user_selected_variable' );

		if ( ! $user_selected_variable ) {
			return;
		}

		if ( ! isset( $_SERVER[ $user_selected_variable ] ) ) {
			return;
		}

		$value = $_SERVER[ $user_selected_variable ];
		echo wp_kses_post( $value );
	}

}