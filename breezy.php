<?php
/**
 * Plugin Name: Breezy
 * Description: Breezy map plugin is created by WhooshPro to help WordPress users to generate Singapore map and add location markers easily.
 * Version: 1.0.5
 * Author: WhooshPro
 * Author URI: https://www.whooshpro.com/
 * Text Domain: breezy
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */
 
namespace Breezy;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Breezy {
    private $sub_plugins = [];

    public function __construct() {
        $this->load_sub_plugins();
		$this->register_hooks();
    }

    public function load_sub_plugins() {
        // Register sub plugins here
		require_once plugin_dir_path(__FILE__) . 'breezy-map/BreezyMap.php';
        $this->sub_plugins['BreezyMap'] = new \Breezy\BreezyMap();
    }

    public function activate() {
		// Loop through to activate sub plugins
        foreach ($this->sub_plugins as $sub_plugin) {
            $sub_plugin->activate();
        }
    }

	private function register_hooks() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
		
		// Menu hooks
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_bar_menu', [$this, 'add_toolbar_menu'], 100);
		
		// Script hooks
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
		add_action('wp_ajax_breezy_plugin_toggle', [$this, 'breezy_plugin_toggle']);
		// Remove in the future when there are more than 1 sub plugins
		add_action('current_screen', [$this, 'redirect_to_map']);
	}

    public function deactivate() {
		// Loop through to deactivate sub plugins
        foreach ($this->sub_plugins as $sub_plugin) {
            $sub_plugin->deactivate();
        }
    }
	
	// Enqueue admin scripts
    public function enqueue_admin_scripts($hook) {
		//For the menu icons
		wp_register_style('breezy-menu-icons-style', esc_url(plugin_dir_url(__FILE__)) . 'admin/css/breezy-menu-icons.css', array(), '1.0.1');
		wp_enqueue_style('breezy-menu-icons-style');

		//Styles for the plugin listing
        if ($hook != 'toplevel_page_breezy') {
			return;
		}	
		wp_register_style('breezy-admin-style', esc_url(plugin_dir_url(__FILE__)) . 'admin/css/breezy.css', array(), '1.0.0');
		wp_enqueue_style('breezy-admin-style');

		wp_register_script('breezy-js', esc_url(plugin_dir_url(__FILE__)) . 'admin/js/breezy.js', array('jquery'), '1.0.0', true);
		wp_enqueue_script('breezy-js');

		wp_localize_script('breezy-js', 'breezyAjax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }
	
	// Add admin menu
    public function add_admin_menu() {
        add_menu_page(esc_html__('Breezy', 'breezy'), esc_html__('Breezy', 'breezy'), 'manage_options', 'breezy', [$this, 'breezy_admin_page']);
		if ($this->sub_plugins['BreezyMap']->is_active()) {
			add_submenu_page('breezy', esc_html__('Breezy Map', 'breezy'), esc_html__('Map', 'breezy'), 'manage_options', 'breezy-map', [$this->sub_plugins['BreezyMap'], 'breezy_map_admin_page'], );
		}
    }
	
	// Redirect to map plugin
    public function redirect_to_map() {
		
		$screen = get_current_screen(); 

		if ($screen && $screen->id === "toplevel_page_breezy") {
            $redirect_url = admin_url('admin.php?page=breezy-map');
            wp_redirect($redirect_url);
            exit;
        }
    }
	// Add toolbar menu item
	public function add_toolbar_menu($admin_bar) {
        $admin_bar->add_menu([
            'id' => 'breezy',
            'title' => esc_html__('Breezy', 'breezy'),
            'href' => admin_url('admin.php?page=breezy-map'),
            'meta' => [
                'title' => esc_html__('Breezy', 'breezy'),
            ],
        ]);
		if ($this->sub_plugins['BreezyMap']->is_active()) {
		   $admin_bar->add_menu([
				'id' => 'breezy-map',
				'parent' => 'breezy',
				'title' => esc_html__('Map', 'breezy'),
				'href' => admin_url('admin.php?page=breezy-map'),
				'meta' => [
					'title' => esc_html__('Map', 'breezy'),
				],
			]);
		}
    }
	
	// AJAX to activate plugin 
	public function breezy_plugin_toggle() {
		check_ajax_referer('breezy_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => esc_html__('You do not have permission to perform this action.', 'breezy')));
			wp_die();
		}

		if (isset($_POST['sub_plugin']) && isset($_POST['status'])) {
			$sub_plugin = sanitize_text_field(wp_unslash($_POST['sub_plugin']));
			$status = sanitize_text_field(wp_unslash($_POST['status']));
	
			if ($status === 'Activate') {
				$this->sub_plugins[$sub_plugin]->activate();
				wp_send_json_success(['message' => $sub_plugin . ' activated.', 'status' => 'activated']);
			} else {
				$this->sub_plugins[$sub_plugin]->deactivate();
				wp_send_json_success(['message' => $sub_plugin . ' deactivated.', 'status' => 'deactivated']);
			}
	
		} else {
			wp_send_json_error(array('message' => esc_html__('Failed to activate plugin.', 'breezy')));
		}

		wp_die();
	}
	
	// Activate plugin page
    public function breezy_admin_page() {
		
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Breezy', 'breezy') . '</h1>';
		echo '<div class="notification-wrapper">';
		echo '<p class="message"></p>';
		echo '<span class="close-btn">&times;</span>';
		echo '</div>';
        echo '<form method="post">';
		echo '<input type="hidden" id="breezy_nonce" value="' . esc_html(wp_create_nonce('breezy_nonce')) . '">';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th><strong>Plugins</strong></th><th><strong>Status</strong></th><th><strong>Action</strong></th></tr></thead>';
        echo '<tbody>';

        foreach ($this->sub_plugins as $name => $sub_plugin) {
            $status = $sub_plugin->is_active() ? 'Activated' : 'Deactivated';
            $action = $sub_plugin->is_active() ? 'Deactivate' : 'Activate';
			$actionText = $sub_plugin->is_active() ? esc_html__('Deactivate', 'breezy') : esc_html__('Activate', 'breezy');

			$modified_name = str_replace('Breezy', '', $name);
            echo "<tr><td>" .esc_html($modified_name) . "</td><td>" .esc_html($status) . "</td><td>";
            echo "<button class='toggle-sub-plugin btn' data-plugin='" . esc_attr($name) . "' data-status='" . esc_attr($action) . "' type='submit' name='sub_plugin_toggle' value='1'>" . esc_html($actionText) . "</button>";
            echo "<input type='hidden' name='sub_plugin' value='" . esc_attr($name) . "'/>";
            echo "<input type='hidden' name='status' value='" . esc_attr($action) . "'/>";
            echo '</td></tr>';
        }

        echo '</tbody></table>';
        echo '</form></div>';
    }
}

new Breezy();
