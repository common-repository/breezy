<?php
/**
 * Sub Plugin Name: Breezy Map
 * File: breezy-map/BreezyMap.php
 * Description: Breezy map plugin is created by WhooshPro to help WordPress users to generate Singapore map (OneMap) and add location markers easily.
 * Version: 1.0.5
 * Author: WhooshPro
 * Author URI: https://www.whooshpro.com/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Breezy;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BreezyMap {
    private $option_name = 'breezy_map_active';
	public $hooks_registered = false;
	 

    public function __construct() {
		// Register all hooks if activated
        if ($this->is_active()) {
            $this->register_hooks();
        }
    }

    public function activate() {
		// Update active flag
        update_option($this->option_name, true);
        $this->register_hooks();
    }

    public function deactivate() {
        update_option($this->option_name, false);
        // Clean up resources
		unregister_post_type('breezy_map_locations');
		unregister_post_type('breezy_map_maps');
		flush_rewrite_rules();
    }

    public function is_active() {
        return get_option($this->option_name, false);
    }

    private function register_hooks() {
		
		
		// Register CPTs
        add_action('init', [$this, 'register_custom_post_type']);
		
		// Exclude from search
        add_action('pre_get_posts', [$this, 'modify_query']);
		
		// Meta boxes
		add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
		
		// Save post handler
        add_action('save_post', [$this, 'breezy_map_save_meta_data']);
		add_action('save_post', [$this, 'breezy_map_save_markers_meta_data']);
		
		// Enqueue scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_scripts']);
		
		
		// AJAX Endpoints
		add_action('wp_ajax_breezy_map_location_search', [$this, 'breezy_map_location_search']);
		add_action('wp_ajax_breezy_map_create', [$this, 'breezy_map_create']);
		
		add_action('wp_ajax_breezy_map_load_maps', [$this, 'breezy_map_load_maps']);
		add_action('wp_ajax_nopriv_breezy_map_load_maps', [$this, 'breezy_map_load_maps']);
		
		add_action('wp_ajax_breezy_map_load_map_data', [$this, 'breezy_map_load_map_data']);
		add_action('wp_ajax_nopriv_breezy_map_load_map_data', [$this, 'breezy_map_load_map_data']);
		
		add_action('wp_ajax_breezy_map_update_title', [$this, 'breezy_map_update_title']);
		add_action('wp_ajax_breezy_map_update', [$this, 'breezy_map_update']);
		add_action('wp_ajax_breezy_map_remove_map', [$this, 'breezy_map_remove_map']);
		add_action('wp_ajax_breezy_map_save_marker', [$this, 'breezy_map_save_marker']);
		add_action('wp_ajax_breezy_map_update_marker', [$this, 'breezy_map_update_marker']);
		add_action('wp_ajax_breezy_map_remove_marker', [$this, 'breezy_map_remove_marker']);

		// Custom shortcode
        add_shortcode('breezy_map', [$this, 'breezy_map_shortcode']);
    }

	public function register_custom_post_type() {
        // Register custom post type.
		$args = array(
			'public' => true,
			'label'  => 'Locations',
			'supports' => array('title', 'editor', 'custom-fields'),
			//'show_ui' => true,
			'show_in_rest' => true,
			'show_in_menu' => false,
			'publicly_queryable' => false,
			'rewrite' => array('slug' => 'breezy-map-locations')
		);
		register_post_type('breezy_map_locations', $args);
		
		$maps_args = array(
			'public' => true,
			'label'  => 'Maps',
			'supports' => array('title', 'editor', 'custom-fields'),
			//'show_ui' => true,
			'show_in_rest' => true,
			'show_in_menu' => false,
			'publicly_queryable' => false,
			'rewrite' => array('slug' => 'breezy-map-maps')
		);
		register_post_type('breezy_map_maps', $maps_args);
	}

    public function modify_query($query) {
		//Exclude cpts from search
		if ($query->is_search && $query->is_main_query()) {
			$post_types = $query->get('post_type');

			if (empty($post_types)) {
				$post_types = get_post_types(array('public' => true));
			}
			$cpts_to_exclude = array('breezy_map_locations', 'breezy_map_maps');
			$filtered_post_types = array_diff($post_types, $cpts_to_exclude);

			$query->set('post_type', $filtered_post_types);
		}
    }

	// Add meta boxes
	public function add_meta_boxes() {
		add_meta_box('breezy_map_meta_box', 'Map Details', [$this, 'breezy_map_meta_box_callback'], 'breezy_map_maps', 'normal', 'high');
		add_meta_box('breezy_map_marker_meta_box', 'Marker Details', [$this, 'breezy_map_markers_meta_box_callback'], 'breezy_map_locations', 'normal', 'high');
	}
		
	// Meta boxes callback for Map
	public function breezy_map_meta_box_callback($post) {
		$map_type = get_post_meta($post->ID, '_breezy_map_map_type', true);

		$address = get_post_meta($post->ID, '_breezy_map_map_address', true);
		$longitude = get_post_meta($post->ID, '_breezy_map_map_longitude', true);
		$latitude = get_post_meta($post->ID, '_breezy_map_map_latitude', true);
		
		$zoom = get_post_meta($post->ID, '_breezy_map_map_zoom', true);
		$height = get_post_meta($post->ID, '_breezy_map_map_height', true);

		
		$shortcode = '[breezy_map id="' . $post->ID . '"]';

		wp_nonce_field('breezy_map_nonce_action', 'breezy_map_nonce');

		// Add other map type in the future
		echo '<label>' . esc_html__('Map type', 'breezy') . ':</label>';
		echo '<select name="breezy_map_map_type" id="map_type">';
		echo '<option value="one_map" ' . selected(esc_attr($map_type), 'one_map') . '>OneMap</option>';
		echo '</select>';

		echo '<label>' . esc_html__('Address', 'breezy') . ':</label>';
		echo '<input type="text" name="breezy_map_map_address" value="' . esc_attr($address) . '" class="widefat">';
		
		echo '<label>' . esc_html__('Longitude', 'breezy') . ':</label>';
		echo '<input type="text" name="breezy_map_map_longitude" value="' . esc_attr($longitude) . '" class="widefat">';
		
		echo '<label>' . esc_html__('Latitude', 'breezy') . ':</label>';
		echo '<input type="text" name="breezy_map_map_latitude" value="' . esc_attr($latitude) . '" class="widefat">';
		
		echo '<label>' . esc_html__('Zoom Level', 'breezy') . ':</label>';
		echo '<input type="text" name="breezy_map_map_zoom" value="' . esc_attr($zoom) . '" class="widefat">';
		
		echo '<label>' . esc_html__('Height', 'breezy') . ':</label>';
		echo '<input type="text" name="breezy_map_map_height" value="' . esc_attr($height) . '" class="widefat">';
		
		echo '<label>' . esc_html__('Shortcode', 'breezy') . ':</label>';
		echo '<input type="text" value="' . esc_attr($shortcode) . '" class="widefat" readonly>';
	}
	
	// Map's meta boxes update functions
    public function breezy_map_save_meta_data($post_id) {

		if (!isset($_POST['breezy_map_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['breezy_map_nonce'])), 'breezy_map_nonce_action')) {
			return; 
		}

		if (array_key_exists('breezy_map_map_type', $_POST) && isset($_POST['breezy_map_map_type'])) {
			update_post_meta($post_id, '_breezy_map_map_type', sanitize_text_field(wp_unslash($_POST['breezy_map_map_type'])));
		}
		if (array_key_exists('breezy_map_map_address', $_POST) && isset($_POST['breezy_map_map_address'])) {
			update_post_meta($post_id, '_breezy_map_map_address', sanitize_text_field(wp_unslash($_POST['breezy_map_map_address'])));
		}
		if (array_key_exists('breezy_map_map_longitude', $_POST) && isset($_POST['breezy_map_map_longitude'])) {
			update_post_meta($post_id, '_breezy_map_map_longitude', sanitize_text_field(wp_unslash($_POST['breezy_map_map_longitude'])));
		}
		if (array_key_exists('breezy_map_map_latitude', $_POST) && isset($_POST['breezy_map_map_latitude'])) {
			update_post_meta($post_id, '_breezy_map_map_latitude', sanitize_text_field(wp_unslash($_POST['breezy_map_map_latitude'])));
		}
		if (array_key_exists('breezy_map_map_zoom', $_POST) && isset($_POST['breezy_map_map_zoom'])) {
			update_post_meta($post_id, '_breezy_map_map_zoom', sanitize_text_field(wp_unslash($_POST['breezy_map_map_zoom'])));
		}
		if (array_key_exists('breezy_map_map_height', $_POST) && isset($_POST['breezy_map_map_height'])) {
			update_post_meta($post_id, '_breezy_map_map_height', sanitize_text_field(wp_unslash($_POST['breezy_map_map_height'])));
		}
    }
		
	// Meta boxes callback for Locations
	public function breezy_map_markers_meta_box_callback($post) {
		$address = get_post_meta($post->ID, '_breezy_map_marker_address', true);
		$longitude = get_post_meta($post->ID, '_breezy_map_marker_longitude', true);
		$latitude = get_post_meta($post->ID, '_breezy_map_marker_latitude', true);
		$map_id = get_post_meta($post->ID, '_breezy_map_marker_map_id', true);
		$description = get_post_meta($post->ID, '_breezy_map_marker_description', true);
		$image = get_post_meta($post->ID, '_breezy_map_marker_image', true);
		
		$maps = get_posts(array(
			'post_type' => 'breezy_map_maps',
			'posts_per_page' => -1
		));

		wp_nonce_field('breezy_map_markers_nonce_action', 'breezy_map_markers_nonce');
		
		echo '<label>' . esc_html__('Address', 'breezy') . ':</label>';
		echo '<input type="text" name="breezy_map_marker_address" value="' . esc_attr($address) . '" class="widefat">';
		
		echo '<label>' . esc_html__('Longitude', 'breezy') . ':</label>';
		echo '<input type="text" name="breezy_map_marker_longitude" value="' . esc_attr($longitude) . '" class="widefat">';
		
		echo '<label>' . esc_html__('Latitude', 'breezy') . ':</label>';
		echo '<input type="text" name="breezy_map_marker_latitude" value="' . esc_attr($latitude) . '" class="widefat">';


		echo '<label>' . esc_html__('Map', 'breezy') . ':</label>';
		echo '<select name="breezy_map_marker_map_id" class="widefat">';
		foreach ($maps as $map) {
			$selected = ($map->ID == $map_id) ? 'selected="selected"' : '';
			echo '<option value="' . esc_attr($map->ID) . '" ' . esc_attr($selected) . '>' . esc_html($map->post_title) . '</option>';
		}
		echo '</select>';
			
		echo '<label>' . esc_html__('Description', 'breezy') . ':</label>';
		wp_editor(
			$description, 
			'breezy_map_marker_description', 
			array(
				'textarea_name' => 'breezy_map_marker_description',
				'textarea_rows' => 10, 
				'media_buttons' => true, 
				'teeny' => false,
				'quicktags' => true,
			)
		);

		echo '<label>' . esc_html__('Image', 'breezy') . ':</label>';
		echo '<input type="text" name="breezy_map_marker_image" id="marker_image" value="' . esc_attr($image) . '" class="widefat">';
		echo '<button type="button" id="upload_image_button" class="button">Upload Image</button>';
	}
		
	// Locations meta fields update functions
	public function breezy_map_save_markers_meta_data($post_id) {


		if (!isset($_POST['breezy_map_markers_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['breezy_map_markers_nonce'])), 'breezy_map_markers_nonce_action')) {
			return; 
		}

		if (array_key_exists('breezy_map_map_address', $_POST) && isset($_POST['breezy_map_map_address'])) {
			update_post_meta($post_id, '_breezy_map_marker_address', sanitize_text_field(wp_unslash($_POST['breezy_map_map_address'])));
		}
		if (array_key_exists('breezy_map_map_longitude', $_POST) && isset($_POST['breezy_map_map_longitude'])) {
			update_post_meta($post_id, '_breezy_map_marker_longitude', sanitize_text_field(wp_unslash($_POST['breezy_map_map_longitude'])));
		}
		if (array_key_exists('breezy_map_map_latitude', $_POST) && isset($_POST['breezy_map_map_latitude'])) {
			update_post_meta($post_id, '_breezy_map_marker_latitude', sanitize_text_field(wp_unslash($_POST['breezy_map_map_latitude'])));
		}
	   if (array_key_exists('breezy_map_marker_map_id', $_POST) && isset($_POST['breezy_map_marker_map_id'])) {
			update_post_meta($post_id, '_breezy_map_marker_map_id', sanitize_text_field(wp_unslash($_POST['breezy_map_marker_map_id'])));
		}
	   if (array_key_exists('breezy_map_marker_map_description', $_POST) && isset($_POST['breezy_map_marker_description'])) {
			update_post_meta($post_id, '_breezy_map_marker_map_description', sanitize_text_field(wp_unslash($_POST['breezy_map_marker_description'])));
		}
	   if (array_key_exists('breezy_map_marker_map_image', $_POST) && isset($_POST['breezy_map_marker_image'])) {
			update_post_meta($post_id, '_breezy_map_marker_map_image', sanitize_text_field(wp_unslash($_POST['breezy_map_marker_image'])));
		}
	}

	// Enqueue admin scripts
    public function enqueue_admin_scripts($hook) {
        if ($hook != 'breezy_page_breezy-map') {
			return;
		}	
		wp_enqueue_media();

		wp_register_style('breezy-map-leaflet-css', 'https://www.onemap.gov.sg/web-assets/libs/leaflet/leaflet.css', array(), '1.0.0');

		wp_register_style('breezy-map-css', esc_url(plugin_dir_url(__FILE__)) . 'admin/css/breezy-map.css', array(), '1.0.0');

		
		wp_register_style('breezy-map-jquery-ui-css',  esc_url(plugin_dir_url(__FILE__)) . 'admin/utils/jquery-ui/jquery-ui.min.css', array(), '1.0.0');

		// Enqueue Style
		wp_enqueue_style(array('breezy-map-leaflet-css', 'breezy-map-css', 'breezy-map-jquery-ui-css'));
		
		wp_register_script('breezy-map-leaflet-js', 'https://www.onemap.gov.sg/web-assets/libs/leaflet/onemap-leaflet.js', array(), '1.0.0', true);
		
		wp_register_script('breezy-map-js', esc_url(plugin_dir_url(__FILE__)) . 'admin/js/breezy-map.js', array('jquery', 'jquery-ui-core'), '1.0.0', true);
		
		wp_localize_script('breezy-map-js', 'breezyMapAjax', array('ajaxurl' => admin_url('admin-ajax.php')));
		
		wp_register_script('breezy-map-marker-cpt-js', esc_url(plugin_dir_url(__FILE__)) . 'admin/js/breezy-map-marker-cpt.js', array('jquery'), '1.0.0', true);

		//Enqueue script
		wp_enqueue_script(array('jquery-ui-core', 'breezy-map-leaflet-js', 'breezy-map-js', 'breezy-map-marker-cpt-js'));

		
    }

	// Enqueue public scripts
    public function enqueue_public_scripts() {

		if (has_shortcode(get_post()->post_content, 'breezy_map')) {
			wp_register_style('breezy-map-leaflet-frontend-css', 'https://www.onemap.gov.sg/web-assets/libs/leaflet/leaflet.css', array(), '1.0.0');
			
			wp_register_style('breezy-map-frontend-css', esc_url(plugin_dir_url(__FILE__)) . 'public/css/breezy-map.css', array(), '1.0.0');

			// Enqueue Style
			wp_enqueue_style(array('breezy-map-leaflet-frontend-css', 'breezy-map-frontend-css'));

			wp_register_script('breezy-map-leaflet-frontend-js', 'https://www.onemap.gov.sg/web-assets/libs/leaflet/onemap-leaflet.js', array(), '1.0.0', true);
			

			wp_register_script('breezy-map-frontend-js', esc_url(plugin_dir_url(__FILE__)) . 'public/js/breezy-map.js', array('jquery', 'breezy-map-leaflet-frontend-js'), '1.0.0', true);

			// Enqueue Script
			wp_enqueue_script(array('breezy-map-leaflet-frontend-js', 'breezy-map-frontend-js'));
			
			wp_localize_script('breezy-map-frontend-js', 'breezyMapFrontendAjax', array('ajaxurl' => admin_url('admin-ajax.php')));
		}

    }

	// Map shortcode
	public function breezy_map_shortcode($atts) {
		$atts = shortcode_atts(array(
			'id' => ''
		), $atts);

		if (empty(sanitize_text_field($atts['id']))) {
			return esc_html__('Map ID is required.', 'breezy');
		}

		// Get map data
		$map_id = intval(sanitize_text_field($atts['id']));
		$map_post = get_post($map_id);

		if (!$map_post || $map_post->post_type !== 'breezy_map_maps') {
			return esc_html__('Invalid Map ID.', 'breezy');
		}

		ob_start();
		
		if (is_admin()) {
		?>
		<div class="breezy-map-preview">
			<div class="inner-wrapper">
				<?php
					esc_html__('Preview the page to view map', 'breezy');
				?>
			</div>
		</div>

		<?php
		} else {
		?>
			<div class="breezy-map" data-id="<?php echo esc_attr($map_id); ?>"></div>
			<input type="hidden" id="breezy_map_load_map_data_nonce" value="<?php echo esc_html(wp_create_nonce('breezy_map_load_map_data_nonce')); ?>">
		<?php		
		}


		return ob_get_clean();
	}

	// Map admin page
    public function breezy_map_admin_page() {
		?>
		<div class="wrap">
			<h1>Map</h1>
			<div class="notification-wrapper">
				<p class="message"></p>
				<span class="close-btn">&times;</span>
			</div>
			
			<div id="tabs">
				<ul class="tab-links">
					<li class="active"><a href="#tab-1">
						<?php
							echo esc_html__('Maps', 'breezy');
						?>
					</a></li>
					<li><a href="#tab-2">
						<?php
							echo esc_html__('About', 'breezy');
						?>
					</a></li>
				</ul>
				<div id="tab-1" class="tab active">
					<div id="edit-map-wrapper">
						<div class="tab-inner-nav-wrapper">
					
							<h2>
								<?php
									echo esc_html__('Create Map', 'breezy');
								?>
							</h2>				
						</div>	
						<div class="tab-inner-wrapper">
						
							<div class="breezy-map-wrapper">
								<div class="left-wrapper">
									<div class="field-wrapper">
										<label for="map-type"><?php echo esc_html__('Map Type', 'breezy');?>:</label><br>
										<select name="map-type" id="map-type">
											<option value="one_map" selected>OneMap</option>
										</select>
									</div>
									<div class="field-wrapper">
										<label for="map-title"><?php echo esc_html__('Map Title', 'breezy');?>:</label>
										<input type="text" id="map-title" placeholder="Map title" required>
									</div>
									<div class="field-wrapper">
										<label for="map-zoom"><?php echo esc_html__('Map Zoom', 'breezy');?>: <span id="map-zoom-value"></span></label>
										<input type="range" id="map-zoom" placeholder="Map zoom" min="11" max="19" required>
									</div>
									<div class="field-wrapper">
										<label for="map-height"><?php echo esc_html__('Map Height', 'breezy');?> (px):</label>
										<input type="number" id="map-height" placeholder="Map height" min="400" max="600" required>
									</div>
									<div class="field-wrapper">
										<label for="location-search"><?php echo esc_html__('Map Center Location', 'breezy');?>:</label>
										<input type="text" id="location-search" placeholder="Search Center Location" required>
									</div>
									<div class="field-wrapper">
										<label for="location-latitude"><?php echo esc_html__('Center Latitude', 'breezy');?>:</label>
										<input type="text" id="location-latitude" disabled="disabled" placeholder="Latitude">
									</div>
									<div class="field-wrapper">
										 <label for="location-longitude"><?php echo esc_html__('Center Longitude', 'breezy');?>:</label>
										<input type="text" id="location-longitude" disabled="disabled" placeholder="Longitude">
									</div>
									 <input type="hidden" id="breezy_map_nonce" value="<?php echo esc_html(wp_create_nonce('breezy_map_nonce')); ?>">

									<div class="btn-wrapper">
										<div id="create-map" class="btn"><?php echo esc_html__('Create Map', 'breezy');?></div>
									</div>
									 
									
								</div>
								<div class="right-wrapper">
									<p class="instruction-msg"><?php echo esc_html__('Please create a map using fields on the left.', 'breezy');?></p> 
									<div id="mapdiv" style="height: 600px;"></div>
								</div>					
							</div>
							
							
							<h2><?php echo esc_html__('Manage Maps', 'breezy');?></h2>			
							<input type="hidden" id="breezy_map_remove_map_nonce" value="<?php echo esc_html(wp_create_nonce('breezy_map_remove_map_nonce')); ?>">
														
							<table id="maps-table">
								<thead>
									<tr>
										<th><?php echo esc_html__('Title', 'breezy');?></th>
										<th><?php echo esc_html__('Shortcode', 'breezy');?></th>
										<th><?php echo esc_html__('Type', 'breezy');?></th>
										<th><?php echo esc_html__('Actions', 'breezy');?></th>
										<th><?php echo esc_html__('Date Created', 'breezy');?></th>
									</tr>
								</thead>
								<tbody>
								
								</tbody>
							</table>
							
						</div>
					</div>					
					<div class="tab-inner-wrapper marker-parent-wrapper">
						<div id="marker-form">
							<div class="tab-inner-nav-wrapper">
								<a id="back-to-maps" class="back-btn"><?php echo esc_html__('Back to Maps', 'breezy');?></a>
								
								<div class="breezy-map-wrapper">
								
									<div class="left-wrapper">
										<div class="field-wrapper">
											<label for="marker-map-title"><?php echo esc_html__('Map title', 'breezy');?>:</label>
											<div class="map-title-field-wrapper">
												<input type="text" disabled="disabled" id="marker-map-title" placeholder="Map ID">
												<span class="map-title-edit-btn"></span>								
												<div class="btn-wrapper update-map-title-btn-wrapper">
													<div class="update-map-title-btn btn"><?php echo esc_html__('Save', 'breezy');?></div>
												</div>
												<input type="hidden" id="breezy_map_save_map_title_nonce" value="<?php echo esc_html(wp_create_nonce('breezy_map_save_map_title_nonce')); ?>">
									 
											</div>
											
											
											
										</div>	
									</div>	
									<div class="right-wrapper">
									</div>
								</div>
							
											
							</div>					
							<div class="breezy-map-wrapper">
							
								<div class="left-wrapper">
								

									<h3><?php echo esc_html__('Add Marker to Map', 'breezy');?></h3>
									<div class="map-edit-field-wrapper">
										<div class="field-wrapper">
											<label for="marker-map-type"><?php echo esc_html__('Map Type', 'breezy');?>:</label><br>
											<select name="marker-map-type" id="marker-map-type">
												<option value="one_map" selected>One Map</option>
											</select>
										</div>
										<div class="field-wrapper">
											<label for="marker-map-zoom"><?php echo esc_html__('Map Zoom', 'breezy');?>: <span id="marker-map-zoom-value"></span></label>
											<input type="range" id="marker-map-zoom" placeholder="Map zoom" min="11" max="19" required>
										</div>
										<div class="field-wrapper">
											<label for="marker-map-height"><?php echo esc_html__('Map Height', 'breezy');?> (px):</label>
											<input type="number" id="marker-map-height" placeholder="Map height" min="400" max="600" required>
										</div>
										<div class="field-wrapper">
											<label for="marker-map-location-search"><?php echo esc_html__('Map Center Location', 'breezy');?>:</label>
											<input type="text" id="marker-map-location-search" placeholder="Search Center Location" required>
										</div>
										<div class="field-wrapper">
											<label for="marker-map-location-latitude"><?php echo esc_html__('Center Latitude', 'breezy');?>:</label>
											<input type="text" id="marker-map-location-latitude" disabled="disabled" placeholder="Latitude">
										</div>
										<div class="field-wrapper">                                    
											<label for="marker-map-location-longitude"><?php echo esc_html__('Center Longitude', 'breezy');?>:</label>
											<input type="text" id="marker-map-location-longitude" disabled="disabled" placeholder="Longitude">
										</div>    
										<div class="btn-wrapper">
											<div id="map-edit-save" class="btn"><?php echo esc_html__('Save Main Marker', 'breezy');?></div>
										</div>                      
									</div>

									<div class="map-marker-edit-field-wrapper">
										<div class="field-wrapper">
											<label for="marker-map-title"><?php echo esc_html__('Location title', 'breezy');?>:</label>
											<input type="text" id="marker-title" placeholder="Location title" required>
										</div>
										
										<div class="field-wrapper">
											<label for="marker-image"><?php echo esc_html__('Location description', 'breezy');?>:</label><br/>
											<?php
												wp_editor(
													'', 
													'marker_description',
													array(
														'textarea_name' => 'marker_description', 
														'textarea_rows' => 10, 
														'media_buttons' => true,
														'teeny' => false, 
														'quicktags' => true, 
													)
												);
											?>
											
										</div>
										<div class="field-wrapper">
											<label for="marker-image"><?php echo esc_html__('Location thumbnail', 'breezy');?>:</label><br/>
											<input type="hidden" id="marker-image" name="marker_image">
											<button type="button" id="upload-image-button" class="button"><?php echo esc_html__('Upload Location Thumbnail', 'breezy');?></button>
											<img id="marker-image-preview" src="" alt="Image Preview" style="max-width: 100%; display: none;">
											<button type="button" id="clear-image-button" class="button" style="display: none;">Clear Thumbnail</button>
										</div>
										
										<div class="field-wrapper">
											<label for="marker-map-title"><?php echo esc_html__('Search location', 'breezy');?>:</label>
											<input type="text" id="marker-search" name="marker_search" placeholder="Search location" required>
										</div>
										<div class="field-wrapper">
											<label for="marker-map-title"><?php echo esc_html__('Location Latitude', 'breezy');?>:</label>
											<input type="text" id="marker-latitude" disabled="disabled" name="marker_latitude" placeholder="Latitude">
										</div>
										<div class="field-wrapper">
											<label for="marker-map-title"><?php echo esc_html__('Location Longitude', 'breezy');?>:</label>
											<input type="text" id="marker-longitude" disabled="disabled" name="marker_longitude" placeholder="Longitude">
										</div>
										<div class="btn-wrapper">
											<div id="save-marker" class="btn"><?php echo esc_html__('Save Marker', 'breezy');?></div>
										</div>
									</div>
									<input type="hidden" id="marker-id">
									
									 <input type="hidden" id="breezy_map_save_marker_nonce" value="<?php echo esc_html(wp_create_nonce('breezy_map_save_marker_nonce')); ?>">
									 
									
									 
									
								</div>
								<div class="right-wrapper">
									<div id="mapdivadd" style="height: 800px;"></div>
								</div>
								
							
							</div>
							<h3><?php echo esc_html__('List of markers', 'breezy');?></h3>
							<input type="hidden" id="breezy_map_remove_marker_nonce" value="<?php echo esc_html(wp_create_nonce('breezy_map_remove_marker_nonce')); ?>">
							<table id="list-of-markers">
								
								
							</table>
						</div>
						
					</div>
		
				</div>
				<div id="tab-2" class="tab">
					<div class="tab-inner-nav-wrapper">
						<h2><?php echo esc_html__('About', 'breezy');?></h2>
					</div>
					<div class="tab-inner-wrapper">
						<p>
							<?php echo '<a target="_blank" href="https://www.breezyplugins.com/onemap/">Breezy map plugin</a> ' . esc_html__('is created by ', 'breezy') . '<a href="https://www.whooshpro.com/" target="_blank">WhooshPro</a> ' . esc_html__('to help WordPress users to generate Singapore map (OneMap) and add location markers easily.', 'breezy');?>	
						</p>
					</div>
				</div>
				
			</div>
			<input type="hidden" id="breezy_map_load_map_data_nonce" value="<?php echo esc_html(wp_create_nonce('breezy_map_load_map_data_nonce')); ?>">
			<input type="hidden" id="breezy_map_location_nonce" value="<?php echo esc_html(wp_create_nonce('breezy_map_location_nonce')); ?>">
		</div>
		<?php
	}
		
	// AJAX for location search using OneMap API
	public function breezy_map_location_search() {
		check_ajax_referer('breezy_map_location_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => esc_html__('You do not have permission to perform this action.', 'breezy')));
			wp_die();
		}
	
		if (isset($_POST['search_val'])) {
			$search_val = sanitize_text_field(wp_unslash($_POST['search_val']));
			$response = wp_remote_get("https://www.onemap.gov.sg/api/common/elastic/search?searchVal=$search_val&returnGeom=Y&getAddrDetails=Y&pageNum=1");
			
			if (is_wp_error($response)) {
				wp_send_json_error('Failed to retrieve data');
			}
			
			$body = wp_remote_retrieve_body($response);
			$data = json_decode($body, true);
			
			wp_send_json_success($data['results']);
	
		} else {
			wp_send_json_error(esc_html__('Failed to retrieve data', 'breezy'));
		}

		wp_die();

	}
	

	// AJAX to create map post
	public function breezy_map_create() {
	   
		check_ajax_referer('breezy_map_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => esc_html__('You do not have permission to perform this action.', 'breezy')));
			wp_die();
		}

		if (isset($_POST['type']) && isset($_POST['title']) && isset($_POST['address']) && isset($_POST['longitude']) && isset($_POST['latitude']) && isset($_POST['height']) && isset($_POST['zoom'])) {
			$type = sanitize_text_field(wp_unslash($_POST['type']));
			$title = sanitize_text_field(wp_unslash($_POST['title']));
			$address = sanitize_text_field(wp_unslash($_POST['address']));
			$longitude = sanitize_text_field(wp_unslash($_POST['longitude']));
			$latitude = sanitize_text_field(wp_unslash($_POST['latitude']));
			$zoom = sanitize_text_field(wp_unslash($_POST['zoom']));
			$height = sanitize_text_field(wp_unslash($_POST['height']));
	
			$post_data = array(
				'post_title'   => $title,
				'post_type'    => 'breezy_map_maps',
				'post_status'  => 'publish',
			);
	
			$post_id = wp_insert_post($post_data);
	
			if ($post_id) {
				update_post_meta($post_id, '_breezy_map_map_type', $type);
				update_post_meta($post_id, '_breezy_map_map_address', $address);
				update_post_meta($post_id, '_breezy_map_map_longitude', $longitude);
				update_post_meta($post_id, '_breezy_map_map_latitude', $latitude);
				update_post_meta($post_id, '_breezy_map_map_zoom', $zoom);
				update_post_meta($post_id, '_breezy_map_map_height', $height);
	
				wp_send_json_success(array('message' => esc_html__('Map created successfully!', 'breezy'), 'post_id' => $post_id));
			} else {
				wp_send_json_error(array('message' => esc_html__('Failed to create the map.', 'breezy')));
			}
		} else {
			wp_send_json_error(array('message' => esc_html__('Failed to create the map.', 'breezy')));
		}

		wp_die();

	}

	


	// AJAX to load all maps
	public function breezy_map_load_maps() {

		$args = array(
			'post_type' => 'breezy_map_maps',
			'posts_per_page' => -1,
			'post_status' => 'publish'
		);
		$maps_query = new \WP_Query($args);
		
		$maps = array();
		if ($maps_query->have_posts()) {

			while ($maps_query->have_posts()) {
				$maps_query->the_post();
				$post_id = get_the_ID();

				$post = get_post($post_id);
				$map_type = get_post_meta($post_id, '_breezy_map_map_type', true);

				$maps[] = array(
					'map_title' => get_the_title(),
					'map_id' => $post_id,
					'map_type' => $map_type,
					'created_date' => $post->post_date
				);
			}

		} else {
			wp_send_json_error(array('message' => esc_html__('No map found.', 'breezy')));
		}

		wp_reset_postdata();


		wp_send_json_success(array(
			'maps' => $maps
		));

		wp_die();
		
	}



	// AJAX to load map's data
	public function breezy_map_load_map_data() {

		check_ajax_referer('breezy_map_load_map_data_nonce', 'nonce');

		if (isset($_POST['map_id'])) {
			$map_id = intval(sanitize_text_field(wp_unslash($_POST['map_id'])));

			if ($map_id) {
		  
				$type = get_post_meta($map_id, '_breezy_map_map_type', true);
				$title = get_the_title($map_id);
				$address = get_post_meta($map_id, '_breezy_map_map_address', true);
				$latitude = get_post_meta($map_id, '_breezy_map_map_latitude', true);
				$longitude = get_post_meta($map_id, '_breezy_map_map_longitude', true);
				$zoom = get_post_meta($map_id, '_breezy_map_map_zoom', true);
				$height = get_post_meta($map_id, '_breezy_map_map_height', true);
				$shortcode = '[breezy_map id="' . esc_attr($map_id) . '"]';
	
				$markers_query = new \WP_Query(array(
					'post_type' => 'breezy_map_locations',
					'meta_query' => array(
						array(
							'key' => '_breezy_map_marker_map_id',
							'value' => $map_id,
							'compare' => '='
						)
					),
					'post_status' => 'publish',
					'orderby' => 'title',
					'order' => 'ASC',
					'posts_per_page' => -1
				));
				
	
				$markers = array();
	
				if ($markers_query->have_posts()) {
					while ($markers_query->have_posts()) {
						$markers_query->the_post();
	
						$markers[] = array(
							'marker_title' => get_the_title(),
							'marker_address' => get_post_meta(get_the_ID(), '_breezy_map_marker_address', true),
							'marker_latitude' => get_post_meta(get_the_ID(), '_breezy_map_marker_latitude', true),
							'marker_longitude' => get_post_meta(get_the_ID(), '_breezy_map_marker_longitude', true),
							'marker_description' => get_post_meta(get_the_ID(), '_breezy_map_marker_description', true),
							'marker_image' => get_post_meta(get_the_ID(), '_breezy_map_marker_image', true),
							'post_id' => get_the_ID()
						);
					}
					wp_reset_postdata();
				}
	
				$map_type_titles = array(
					'one_map' => 'One Map',
				);
	
			 
	
				wp_send_json_success(array(
					'type' => $type,
					'title' => $title,
					'address' => $address,
					'latitude' => $latitude,
					'longitude' => $longitude,
					'zoom' => $zoom,
					'height' => $height,
					'shortcode' => $shortcode,
					'markers' => $markers
				));
			} else {
				wp_send_json_error(esc_html__('Invalid map ID.', 'breezy'));
			}
		} else {
			wp_send_json_error(esc_html__('Map ID missing.', 'breezy'));
		}
		wp_die();
		
	}


	// AJAX for map title update
	public function breezy_map_update_title() {
		check_ajax_referer('breezy_map_save_map_title_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => esc_html__('You do not have permission to perform this action.', 'breezy')));
			wp_die();
		}

		if (isset($_POST['map_id']) && isset($_POST['title'])) {
			$map_id = intval(sanitize_text_field(wp_unslash($_POST['map_id'])));
			$title = sanitize_text_field(wp_unslash($_POST['title']));
			
			if ($map_id) {
	
				$post = get_post($map_id);
	
				if ($post) {
					$post->post_title = $title;
	
					wp_update_post($post);
	
					
					wp_send_json_success(array('message' => esc_html__('Map title updated successfully!', 'breezy'), 'post_id' => $map_id));
				} else {
					wp_send_json_error(array('message' => esc_html__('Failed to update the map title.', 'breezy')));
				}
			}
		} else {
			wp_send_json_error(array('message' => esc_html__('Failed to update the map title.', 'breezy')));
		}
		wp_die();
		
		
	}

	// AJAX for map update
	public function breezy_map_update() {
		check_ajax_referer('breezy_map_save_map_title_nonce', 'nonce'); 

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => esc_html__('You do not have permission to perform this action.', 'breezy')));
			wp_die();
		}

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => esc_html__('You do not have permission to perform this action.', 'breezy')));
			wp_die();
		}

		if (isset($_POST['map_id']) && isset($_POST['type']) && isset($_POST['address']) && isset($_POST['longitude']) && isset($_POST['latitude']) && isset($_POST['zoom']) && isset($_POST['height'])) {
			$map_id = intval(sanitize_text_field(wp_unslash($_POST['map_id'])));
			$type = sanitize_text_field(wp_unslash($_POST['type']));
			$address = sanitize_text_field(wp_unslash($_POST['address']));
			$longitude = sanitize_text_field(wp_unslash($_POST['longitude']));
			$latitude = sanitize_text_field(wp_unslash($_POST['latitude']));
			$zoom = sanitize_text_field(wp_unslash($_POST['zoom']));
			$height = sanitize_text_field(wp_unslash($_POST['height']));
			
			if ($map_id) {
	
				$post = get_post($map_id);
	
				if ($post) {
	
					// Update post meta
					update_post_meta($map_id, '_breezy_map_map_type', $type);
					update_post_meta($map_id, '_breezy_map_map_address', $address);
					update_post_meta($map_id, '_breezy_map_map_longitude', $longitude);
					update_post_meta($map_id, '_breezy_map_map_latitude', $latitude);
					update_post_meta($map_id, '_breezy_map_map_zoom', $zoom);
					update_post_meta($map_id, '_breezy_map_map_height', $height);
					
					wp_send_json_success(array('message' => esc_html__('Map updated successfully!', 'breezy'), 'post_id' => $map_id));
				} else {
					wp_send_json_error(array('message' => esc_html__('Failed to update the map.', 'breezy')));
				}
			}
		} else {
			wp_send_json_error(array('message' => esc_html__('Failed to update the map.', 'breezy')));
		}
		wp_die();

		
	}

	// AJAX for map post removal
	public function breezy_map_remove_map() {
		// Verify the nonce
		check_ajax_referer('breezy_map_remove_map_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => esc_html__('You do not have permission to perform this action.', 'breezy')));
			wp_die();
		}
		
		if (isset($_POST['map_id'])) {
			$map_id = intval(sanitize_text_field(wp_unslash($_POST['map_id'])));
			
			$markers_query = new \WP_Query(array(
				'post_type' => 'breezy_map_locations',
				'meta_query' => array(
					array(
						'key' => '_breezy_map_marker_map_id',
						'value' => $map_id,
						'compare' => '='
					)
				),
				'posts_per_page' => -1
			));
	
			if ($markers_query->have_posts()) {
				while ($markers_query->have_posts()) {
					$markers_query->the_post();
					
					$marker_id = get_the_ID();
					
					wp_delete_post($marker_id, true);
				}
	
				wp_reset_postdata();
			} 
			
			$map_deleted = wp_delete_post($map_id, true); 
	
			if ($map_deleted) {
				wp_send_json_success(array('message' => esc_html__('Map and associated markers have been deleted successfully.', 'breezy')));
			} else {
				wp_send_json_error(array('message' => esc_html__('Failed to delete the map.', 'breezy')));
			}
			
		} else {
			wp_send_json_error(array('message' => esc_html__('Failed to delete the map.', 'breezy')));
		}

		wp_die();

		
	}


	// AJAX for location post creation
	public function breezy_map_save_marker() {
		
		check_ajax_referer('breezy_map_save_marker_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => esc_html__('You do not have permission to perform this action.', 'breezy')));
			wp_die();
		}

		if (isset($_POST['address']) && isset($_POST['latitude']) && isset($_POST['longitude']) && isset($_POST['map_id']) && isset($_POST['label'])) {
			$address = sanitize_text_field(wp_unslash($_POST['address']));
			$latitude = sanitize_text_field(wp_unslash($_POST['latitude']));
			$longitude = sanitize_text_field(wp_unslash($_POST['longitude']));
			$map_id = intval(sanitize_text_field(wp_unslash($_POST['map_id'])));
			$label = sanitize_text_field(wp_unslash($_POST['label']));
			$description = isset($_POST['description']) ? sanitize_text_field(wp_unslash($_POST['description'])) : "";
			$image = isset($_POST['image']) ? sanitize_text_field(wp_unslash($_POST['image'])) : "";
	
			$marker_post = array(
				'post_title'  => $label,
				'post_type'   => 'breezy_map_locations',
				'post_status' => 'publish'
			);
	
			$marker_id = wp_insert_post($marker_post);
		
	
			if ($marker_id) {
				update_post_meta($marker_id, '_breezy_map_marker_address', $address);
				update_post_meta($marker_id, '_breezy_map_marker_longitude', $longitude);
				update_post_meta($marker_id, '_breezy_map_marker_latitude', $latitude);
				update_post_meta($marker_id, '_breezy_map_marker_map_id', $map_id);
				update_post_meta($marker_id, '_breezy_map_marker_description', $description); 
				update_post_meta($marker_id, '_breezy_map_marker_image', $image); 
	
	
				wp_send_json_success(array('message' => esc_html__('Marker saved successfully!', 'breezy'), 'marker_id' => $marker_id));
			} else {
				wp_send_json_error(array('message' => esc_html__('Failed to save marker.', 'breezy')));
			}
			
	
		} else {
			wp_send_json_error(array('message' => esc_html__('Failed to save marker.', 'breezy')));
		}

		wp_die();

	}


	// AJAX for location post update
	public function breezy_map_update_marker() {

		check_ajax_referer('breezy_map_save_marker_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => esc_html__('You do not have permission to perform this action.', 'breezy')));
			wp_die();
		}
		
		if (isset($_POST['markerPostId']) && isset($_POST['address']) && isset($_POST['latitude']) && isset($_POST['longitude']) && isset($_POST['map_id']) && isset($_POST['label'])) { 

			$marker_id = intval(sanitize_text_field(wp_unslash($_POST['markerPostId'])));
			$address = sanitize_text_field(wp_unslash($_POST['address']));
			$latitude = sanitize_text_field(wp_unslash($_POST['latitude']));
			$longitude = sanitize_text_field(wp_unslash($_POST['longitude']));
			$map_id = intval(sanitize_text_field(wp_unslash($_POST['map_id'])));
			$label = sanitize_text_field(wp_unslash($_POST['label']));
			$description = isset($_POST['description']) ? sanitize_text_field(wp_unslash($_POST['description'])) : "";
			$image = isset($_POST['image']) ? sanitize_text_field(wp_unslash($_POST['image'])) : "";
	
			if (get_post($marker_id)) {
	
				$marker_post = array(
					'ID'          => $marker_id,
					'post_title'  => $label,
				);
	
				$updated_marker_id = wp_update_post($marker_post);
	
				if ($updated_marker_id) {
					update_post_meta($marker_id, '_breezy_map_marker_address', $address);
					update_post_meta($marker_id, '_breezy_map_marker_longitude', $longitude);
					update_post_meta($marker_id, '_breezy_map_marker_latitude', $latitude);
					update_post_meta($marker_id, '_breezy_map_marker_map_id', $map_id);
					update_post_meta($marker_id, '_breezy_map_marker_description', $description);
					update_post_meta($marker_id, '_breezy_map_marker_image', $image);
	
					wp_send_json_success(array('message' => esc_html__('Marker updated successfully!', 'breezy'), 'marker_id' => $updated_marker_id));
				} else {
					wp_send_json_error(array('message' => esc_html__('Failed to update marker.', 'breezy')));
				}
			} else {
				wp_send_json_error(array('message' => esc_html__('Marker not found.', 'breezy')));
			}
	
		} else {
			wp_send_json_error(array('message' => esc_html__('Failed to update marker.', 'breezy')));
		}

		wp_die();

		
	}


	// AJAX for location post removal
	public function breezy_map_remove_marker() {
		// Verify the nonce
		check_ajax_referer('breezy_map_remove_marker_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => esc_html__('You do not have permission to perform this action.', 'breezy')));
			wp_die();
		}
		
		if (isset($_POST['map_id']) && isset($_POST['marker_id'])) { 
			$map_id = intval(sanitize_text_field(wp_unslash($_POST['map_id'])));
			$marker_id = intval(sanitize_text_field(wp_unslash($_POST['marker_id'])));
			
			$marker_post = get_post($marker_id);
			
			if ($marker_post && get_post_type($marker_id) === 'breezy_map_locations') {
	
				$marker_map_id = get_post_meta($marker_id, '_breezy_map_marker_map_id', true);
				
				if ($marker_map_id == $map_id) {
					$deleted = wp_delete_post($marker_id, false);
					
					if ($deleted) {
						wp_send_json_success(array('message' => esc_html__('Marker removed successfully!', 'breezy'), 'marker_id' => $marker_id));
					} else {
						wp_send_json_error(array('message' => esc_html__('Failed to remove marker.', 'breezy')));
					}
				} else {
					wp_send_json_error(array('message' => esc_html__('Map ID does not match.', 'breezy')));
				}
			} else {
				wp_send_json_error(array('message' => esc_html__('Marker not found or invalid type.', 'breezy')));
			}
		} else {
			wp_send_json_error(array('message' => esc_html__('Failed to remove marker.', 'breezy')));
		}

		
		
		wp_die();
	}

	
	
	
}