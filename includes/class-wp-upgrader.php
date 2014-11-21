<?php if ( ! defined( 'EWPT_PLUGIN_VER') ) exit( 'No direct script access allowed' );
/**
 * Include the parent class
 */
if ( ! class_exists( 'Theme_Upgrader' ) && isset( $_GET['page'] ) && $_GET['page'] == EWPT_PLUGIN_SLUG )
  include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );

/**
 * Extends the WordPress Theme_Upgrader class.
 *
 * This class exists to make modifications to the text during download &
 * to alter the upgrade option array before fetching them for download.
 *
 * @package     Envato WordPress Toolkit
 * @author      Derek Herman <derek@valendesigns.com>
 * @since       1.0
 */
if ( class_exists( 'Theme_Upgrader' ) ) {
  class Envato_Theme_Upgrader extends Theme_Upgrader {
    function upgrade_strings() {
      parent::upgrade_strings();
      $this->strings['downloading_package'] = __( 'Downloading upgrade package from the Envato API&#8230;', 'envato-wordpress-toolkit' );
      
      $options = get_option( EWPT_PLUGIN_SLUG );
      if ( ! isset( $options['skip_theme_backup'] ) ) {
        $this->strings['remove_old'] = __( 'Backing up & removing the old version of the theme&#8230;', 'envato-wordpress-toolkit' );
      }        
    }
  
    function install_strings() {
      parent::install_strings();
      $this->strings['downloading_package'] = __( 'Downloading install package from the Envato API&#8230;', 'envato-wordpress-toolkit' );
    }
    
    function upgrade( $theme, $package = array() ) {
  
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
}

/* End of file class-wp-upgrader.php */
/* Location: ./includes/class-wp-upgrader.php */