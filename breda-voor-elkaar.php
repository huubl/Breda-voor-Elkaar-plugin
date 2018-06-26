<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://bytecode.nl
 * @since             1.0.0
 * @package           Breda_Voor_Elkaar
 *
 * @wordpress-plugin
 * Plugin Name:       Breda voor Elkaar
 * Plugin URI:        https://bytecode.nl
 * Description:       Adds all custom functionality for Breda voor Elkaar
 * Version:           1.0.0
 * Author:            Bytecode Digital Agency B.V.
 * Author URI:        https://bytecode.nl
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       breda-voor-elkaar
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 0.1.0 and use SemVer - https://semver.org
 */
define('PLUGIN_NAME_VERSION', '0.1.0');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-breda-voor-elkaar-activator.php
 */
function activate_breda_voor_elkaar() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-breda-voor-elkaar-activator.php';
    Breda_Voor_Elkaar_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-breda-voor-elkaar-deactivator.php
 */
function deactivate_breda_voor_elkaar() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-breda-voor-elkaar-deactivator.php';
    Breda_Voor_Elkaar_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_breda_voor_elkaar');
register_deactivation_hook(__FILE__, 'deactivate_breda_voor_elkaar');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-breda-voor-elkaar.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_breda_voor_elkaar() {
    $plugin = new Breda_Voor_Elkaar();
    $plugin->run();
}
run_breda_voor_elkaar();

/**
 * Change author slug
 */
add_action('init', 'change_author_slug');
function change_author_slug() {
    global $wp_rewrite;
    $author_slug = 'profile'; // change slug name
    $wp_rewrite->author_base = $author_slug;
}

/**
 * Change author template single
 *
 * @param $template represents the template as it came in through the template_include hook
 */
function change_template_single_author($template) {
    if (is_author()) {
        $author_id = get_query_var('author');
        $author_meta = get_userdata($author_id);
        $author_roles = $author_meta->roles;
        if (in_array('organisation', $author_roles)) {
            $template = plugin_dir_path(__FILE__) . 'structure/organisations/single.php';
        } elseif (in_array('volunteer', $author_roles)) {
            $template = plugin_dir_path(__FILE__) . 'structure/volunteers/single.php';
        } else {
            echo 'user was not an organisation nor a volunteer';
        }
    }
    return $template;
}
add_filter('template_include', 'change_template_single_author');

/**
 * Add Google Maps API to ACF.
 */
function my_acf_init() {
    // fill in Google Maps API key here
    //acf_update_setting('google_api_key', '');
}

add_action('acf/init', 'my_acf_init');

/**
 * Modify WP Query to use ACF filters as per ACF's documentation.
 */
// array of filters (field key => field name)
$GLOBALS['my_query_filters'] = array( 
    'field_5b06d097c1efe'	=> 'frequentie', 
    'field_5b06d0e7c1f00'	=> 'opleidingsniveau',
    'field_5b06da1440f4e'   => 'vergoeding'
);

function use_acf_filters_in_query( $query ) {
    // bail early if is in admin
    if( is_admin() ) return;
    
    // bail early if not main query
    // - allows custom code / plugins to continue working
    if( !$query->is_main_query() ) return;
    
    // get meta query
    if(!$query->meta_query){
        $meta_query = array();
    } else{
        $meta_query = $query->get('meta_query');
    }

    // loop over filters
    foreach( $GLOBALS['my_query_filters'] as $key => $name ) {
        // continue if not found in url
        if( empty($_GET[ $name ]) ) {
            continue;
        }
        // get the value for this filter
        $value = explode(',', $_GET[ $name ]);
        // append meta query
        $meta_query[] = array(
            'key'		=> $name,
            'value'		=> $value,
            'compare'	=> 'IN',
        );    
    } 
    // update meta query
    $query->set('meta_query', $meta_query);
}
add_action('pre_get_posts', 'use_acf_filters_in_query', 10, 1);

/**
 * Output pagination for posts and users.
 */
function numeric_pagination($current_page, $num_pages) {
    echo '<div class="pagination">';
    $start_number = $current_page - 2;
    $end_number = $current_page + 2;

    if (($start_number - 1) < 1) {
        $start_number = 1;
        $end_number = min($num_pages, $start_number + 4);
    }
    
    if (($end_number + 1) > $num_pages) {
        $end_number = $num_pages;
        $start_number = max(1, $num_pages - 4);
    }

    if ($start_number > 1) {
        echo " 1 ... ";
    }

    for ($i = $start_number; $i <= $end_number; $i++) {
        if ($i === $current_page) {
            echo '<a href="?page='.$i.'">';
            echo " [{$i}] ";
            echo '</a>';
        } else {
            echo '<a href="?page='.$i.'">';
            echo " {$i} ";
            echo '</a>';
        }
    }

    if ($end_number < $num_pages) {
        echo " ... {$num_pages} ";
    }
    echo '</div>';
}