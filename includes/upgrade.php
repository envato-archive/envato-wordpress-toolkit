<?php if ( ! defined( 'EWPU_PLUGIN_VER') ) exit( 'No direct script access allowed' );
/**
 * Envato Upgrade
 *
 * This class is used to upgrade themes via the Envato Marketplace API.
 *
 * @package     Envato WordPress Updater
 * @author      Derek Herman <derek@valendesigns.com>
 * @since       1.0
 */
class Envato_Upgrade {
  /**
   * The API object
   *
   * @since   1.0
   * @access  private
   *
   * @var     object
   */
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
   * Manually upgrades a theme from the Envato API.
   *
   * @access    private
   * @since     1.0
   *
   * @param     string    Theme slug
   * @param     string    Theme item_id from ThemeForests
   * @return    void
   */
  function upgrade_theme( $theme, $item_id ) {
    global $current_screen;
    
    check_admin_referer( 'upgrade-theme_' . $theme );
    
    if ( ! current_user_can( 'update_themes' ) )
      wp_die( __( 'You do not have sufficient permissions to update themes for this site.' ) );
    
    $title = __( 'Update Theme' );
    $nonce = 'upgrade-theme_' . $theme;
    $url = 'admin.php?page=' . EWPU_PLUGIN_SLUG . '&action=upgrade-theme&theme=' . $theme . '&item_id=' . $item_id;
    
    /* trick WP into thinking it's the themes page for the icon32 */
    $current_screen->parent_base = 'themes';
    
    /* new Envato_Theme_Upgrader */
    $upgrader = new Envato_Theme_Upgrader( new Theme_Upgrader_Skin( compact( 'title', 'nonce', 'url', 'theme' ) ) );
    
    /* upgrade the theme */
    $upgrader->upgrade( $theme, $this->api->wp_download( $item_id ) );
  }
}

/* End of file upgrade.php */
/* Location: ./includes/upgrade.php */