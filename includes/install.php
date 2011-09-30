<?php if ( ! defined( 'EWPU_PLUGIN_VER') ) exit( 'No direct script access allowed' );
/**
 * Envato Install
 *
 * This class is used to install themes via the Envato Marketplace API.
 *
 * @package     Envato WordPress Updater
 * @author      Derek Herman <derek@valendesigns.com>
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

  /**
   * Manually installs a theme from the Envato API.
   *
   * @since     1.0
   * @access    private
   */
  function install_theme( $theme ) {
    global $current_screen;
    
    if ( ! current_user_can( 'install_themes' ) )
      wp_die( __( 'You do not have sufficient permissions to install themes for this site.' ) );

    check_admin_referer( 'install-theme_' . $theme );
    
    /* setup theme info in $api array */
    $api = (object) array();
    foreach( $this->api->wp_list_themes() as $t ) {
      if ( $theme == $t->item_id ) {
        $api->name = $t->item_name;
        $api->version = $t->version;
        continue;
      }
    }
    
    $title = sprintf( __( 'Installing Theme: %s' ), $api->name . ' ' . $api->version );
    $nonce = 'install-theme_' . $theme;
    $url = 'admin.php?page=envato-wordpress-updater&action=install-theme&theme=' . $theme;
    $type = 'web';
    
    /* trick WP into thinking it's the themes page for the icon32 */
    $current_screen->parent_base = 'themes';
    
    /* new Envato_Theme_Upgrader */
    $upgrader = new Envato_Theme_Upgrader( new Theme_Installer_Skin( compact( 'title', 'url', 'api', 'nonce' ) ) );
    
    /* install the theme */
    $upgrader->install( $this->api->wp_download( $theme ) );
  }

}
/* End of file install.php */
/* Location: ./includes/install.php */