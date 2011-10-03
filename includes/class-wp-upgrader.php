<?php if ( ! defined( 'EWPU_PLUGIN_VER') ) exit( 'No direct script access allowed' );
/**
 * Include the parent class
 */
include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );

/**
 * Envato Theme Upgrader class to extend the WordPress Theme_Upgrader class.
 *
 * @package     Envato WordPress Updater
 * @author      Derek Herman <derek@valendesigns.com>
 * @since       1.0
 */
class Envato_Theme_Upgrader extends Theme_Upgrader {
  function upgrade_strings() {
    parent::upgrade_strings();
    $this->strings['downloading_package'] = __( 'Downloading install package from the Envato API&#8230;' );
  }

  function install_strings() {
    parent::install_strings();
    $this->strings['downloading_package'] = __( 'Downloading install package from the Envato API&#8230;' );
  }
  
  function upgrade( $theme, $package ) {

    $this->init();
    $this->upgrade_strings();

    $options = array(
      'package' => $package,
      'destination' => WP_CONTENT_DIR . '/themes',
      'clear_destination' => true,
      'clear_working' => true,
      'hook_extra' => array(
        'theme' => $theme
      )
    );

    $this->run( $options );

    if ( ! $this->result || is_wp_error($this->result) )
      return $this->result;

    return true;
  }
}

/* End of file class-wp-upgrader.php */
/* Location: ./includes/class-wp-upgrader.php */