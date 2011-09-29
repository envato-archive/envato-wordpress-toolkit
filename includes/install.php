<?php if ( ! defined( 'EWPU_PLUGIN_VER') ) exit( 'No direct script access allowed' );
/**
 * Envato Install
 *
 * This class is used to install themes via the Envato Marketplace API.
 *
 * @package     Envato WordPress Updater
 * @author      Derek Herman <derek@valendesigns.com>
 * @copyright   Copyright (c) 2011, Derek Herman
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */
class Envato_Install {
  
  protected $api;
  
  /**
   * PHP5 constructor method.
   *
   * This method adds other methods to specific hooks within WordPress.
   *
   * @access    public
   * @since     1.0
   *
   * @uses      add_action()
   *
   * @return    void
   */
	function __construct( $api ) {
    $this->api = $api;
  }


  function install_theme( $theme ) {
    if ( ! current_user_can( 'install_themes' ) )
      wp_die( __( 'You do not have sufficient permissions to install themes for this site.' ) );

		check_admin_referer( 'install-theme_' . $theme );
            
    include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

    foreach( $this->api->wp_list_themes() as $t ) {
      if ( $theme == $t->item_id ) {
        $item_name = $t->item_name;
        $version = $t->version;
        continue;
      }
    }
    
		$title = sprintf( __( 'Installing Theme: %s' ), $item_name . ' ' . $version );
		$nonce = 'install-theme_' . $theme;
		$url = 'update.php?action=install-theme&theme=' . $theme;
		$type = 'web';
		
		$upgrader = new Theme_Upgrader( new Theme_Installer_Skin( compact( 'title', 'url', 'nonce' ) ) );
		$upgrader->install( $this->api->wp_download( $theme ) );
  }

}
/* End of file install.php */
/* Location: ./motif/core/includes/install.php */