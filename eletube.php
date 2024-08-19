<?php

namespace Eletube;

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.hennewelt.de
 * @since             1.0.0
 * @package           Eletube
 *
 * @wordpress-plugin
 * Plugin Name:       Eletube
 * Plugin URI:
 * Description:       Eletube adds a Widget for Youtube-Lists to Elementor.
 * Version:           1.0.0
 * Author:            RTO GmbH
 * Author URI:        https://www.rto.de
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       eletube
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
    die;
}

define( 'Eletube_VERSION', '1.0,0' );

define( 'Eletube_DIR', str_replace( '\\', '/', __DIR__ ) );
define( 'Eletube_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );

define( 'Eletube_CACHE_DIR', wp_upload_dir()['basedir'] . '/eletube-cache' );


class Eletube {
    private function __construct() {

        if ( !is_dir( Eletube_CACHE_DIR ) ) {
            mkdir( Eletube_CACHE_DIR );
        }
        if ( !is_dir( Eletube_CACHE_DIR . '/thumbnails' ) ) {
            mkdir( Eletube_CACHE_DIR . '/thumbnails' );
        }

        add_action( 'elementor/widgets/widgets_registered', [ $this, 'registerElementorWidgets' ], 15 );

        add_action( 'wp_enqueue_scripts', [ $this, 'registerScriptsAndStyle' ] );
    }

    public function registerElementorWidgets( $widgetsManager ) {
        require_once( Eletube_DIR . '/widgets/EletubeWidget.php' );
        $widgetsManager->register_widget_type( new EletubeWidget() );
    }

    public function registerScriptsAndStyle() {
        wp_enqueue_script( 'eletube-js', Eletube_URL . '/js/eletube.js' );
        wp_enqueue_style( 'eletube-css', Eletube_URL . '/css/eletube.css' );
        #wp_enqueue_style( 'font-awesome' );
    }

    public static function run() {
        new self();
    }
}


Eletube::run();