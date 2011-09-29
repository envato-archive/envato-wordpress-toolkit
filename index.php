<?php
/**
 * Plugin Name: Envato WordPress Updater
 * Plugin URI:
 * Description:
 * Version: 1.0
 * Author: Derek Herman
 * Author URI: http://valendesigns.com
 */
class Envato_WordPress_Updater {
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
  function __construct() {
    $this->_constants();
    $this->_includes();
    $this->_hooks();
  }
  
  /**
   * Defines the constants for use within the plugin.
   *
   * @since     1.0
   * @access    private
   */
  function _constants() {
    /**
    * Series Plugin Version
    */
    define( 'EWPU_PLUGIN_VER', '1.0' );
    
    /**
    * Series Plugin Directory Path
    */
    define( 'EWPU_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . dirname( plugin_basename( __FILE__ ) ) );
    
    /**
    * Series Plugin Directory URL
    */
    define( 'EWPU_PLUGIN_URL', WP_PLUGIN_URL . '/' . dirname( plugin_basename( __FILE__ ) ) );
  }
  
  /**
   * Include required files
   *
   * @since     1.0
   * @access    private
   */
  function _includes() {
    /* load required files */
    foreach ( array( 'api', 'update', 'install' ) as $file )
      require_once( EWPU_PLUGIN_DIR . '/includes/' . $file . '.php' );
  }
  
  /**
   * Setup the default filters and actions
   *
   * @since     1.0
   * @access    private
   *
   * @uses      add_action()  To add various actions
   */
  function _hooks() {
    /**
     * Add envato menu item, change menu filter for multisite
     */
    if ( is_multisite() ) {
      add_action( 'network_admin_menu', array( &$this, '_envato_menu' ) );
    } else {
      add_action( 'admin_menu', array( &$this, '_envato_menu' ) );
    }
    
    add_action( 'admin_init', array( &$this, '_admin_init' ) );
    
    add_filter( 'install_theme_complete_actions', array( &$this, '_install_theme_complete_actions' ), 10, 1 );
    
    add_thickbox();
		wp_enqueue_script( 'theme-preview' );
  }
  
  function _install_theme_complete_actions( $install_actions ) {
    if ( isset( $_GET['page'] ) && isset( $_GET['action'] ) ) {
      $page = $_GET['page'];
      $action = $_GET['action'];
      if ( $page == 'envato-wordpress-updater' ) {
        if ( $action == 'install-theme' ) {
          $install_actions['themes_page'] = '<a href="' . self_admin_url('admin.php?page=envato-wordpress-updater') . '" title="' . esc_attr__( 'Return to Theme Installer' ) . '" target="_parent">' . __( 'Return to Theme Installer' ) . '</a>';
        }
      }
    }
    return $install_actions;
  }
  
  /**
   * Adds the Envato menu item
   *
   * @access   private
   * @since    1.0
   *
   * @return   void
   */
  function _envato_menu() {
    add_menu_page( 'Envato WordPress Updater', 'Envato Updater', 'manage_options', 'envato-wordpress-updater', array( &$this, '_envato_menu_page' ), EWPU_PLUGIN_URL . '/assets/images/envato.png', 59 );
  }
  
  /**
   * Envato Updater menu item page
   *
   * Creates the page used to verify themes for auto install/update
   *
   * @return   string   Returns the verification form
   *
   * @access   private
   * @since    1.0
   */
  function _envato_menu_page() {   
    if ( ! current_user_can( 'manage_options' ) )
      wp_die( __( 'You do not have sufficient permissions to access this page.', 'envato' ) );

    echo ( isset( $_GET[ 'settings-updated' ] ) ) ? '<div class="updated"><p><strong>' . __( 'Settings Updated.', 'envato' ) . '</strong></p></div>' : '' ?>
    <div class="wrap">
      <?php _e( '<div id="icon-themes" class="icon32"><br/></div><h2>Envato WordPress Updater</h2>', 'envato' ); ?>
      <form name="verification_form" method="post" action="options.php">
        <?php wp_nonce_field( 'update-options' ); ?>
        <?php settings_fields( 'envato-wordpress-updater' ); ?>
        <?php do_settings_sections( 'envato-wordpress-updater' ); ?>
        <p class="submit">
          <input type="submit" name="Submit" class="button-primary" value="<?php _e( 'Save Changes', 'envato' ); ?>" />
        </p>
      </form>
      <hr />
      <?php
      /* read in existing option value from database */
      $options = get_site_option( 'envato-wordpress-updater' );
      
      $user_name = ( isset( $options['user_name'] ) ) ? $options['user_name'] : '';
      $api_key = ( isset( $options['api_key'] ) ) ? $options['api_key'] : '';
      
      if ( $user_name && $api_key )
        $api =& new Envato_Protected_API( $user_name, $api_key );
          
      /* run action */
      if ( isset( $_GET['action'] ) ) {
        $theme = isset( $_REQUEST['theme'] ) ? urldecode( $_REQUEST['theme'] ) : '';
        $action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';
      	
        if ( 'install-theme' == $action ) {
          if ( class_exists( 'Envato_Protected_API' ) && $theme !== '' ) {
            $envato_install =& new Envato_Install( $api );
            $envato_install->install_theme( $theme );
          }
        }
      } else {
        if ( $user_name || $api_key ) {
          $api =& new Envato_Protected_API( $user_name, $api_key );
          $ewpu_themes = $api->wp_list_themes();
          
          if ( $errors = $api->api_errors() ) {
            print_r( $errors );
          } else if ( ! empty( $ewpu_themes ) ) {
            
            //$api->pretty_print( $ewpu_themes );
            
            //$themes = get_themes();
            //$theme_names = array_keys($themes);
            //$api->pretty_print( $themes );
            
            echo '<ul class="item-list">';
            foreach( $ewpu_themes as $theme ) {
              
              $item_details = $api->item_details( $theme->item_id );
              //$api->pretty_print($item_details);
              
              //$download = $api->wp_download( $theme->item_id );
              //$api->pretty_print( $download );
              
              echo '<li>';
                echo '<div class="thumbnail"><img src="' . $item_details->thumbnail  . '" alt="' . $theme->item_name . '" height="80" width="80" /></div>';
                echo '
                <div class="item-details">
                  <h4>' . $theme->item_name . ' by ' . $theme->author_name . '</h4>
                  <p>lorem ipsum</p>
                  ' . ( $item_details->tags ? '<p>Tags: ' . $item_details->tags . '</p>' : '' ) . '
                </div>';
                echo '<div class="update-info"><a class="button-primary" id="install" href="' . wp_nonce_url( self_admin_url( 'admin.php?page=envato-wordpress-updater&action=install-theme&theme=' . $theme->item_id ), 'install-theme_' . $theme->item_id ) . '">' . __( 'Install Now' ) . '</a></div>';
              echo '</li>';
            }
            echo '</ul>';
          }
        }      
      }
      ?>
    </div>
    <?php
  }
  
  /**
   * Registers the settings for the updater
   *
   * @access   private
   * @since    1.0
   *
   * @return   void
   */
  public function _admin_init() {
    register_setting( 'envato-wordpress-updater', 'envato-wordpress-updater' );
    add_settings_section( 'user_account_info', 'User Account Information', array( &$this, '_user_account_info' ), 'envato-wordpress-updater' );
    add_settings_field( 'user_name', 'Username', array( &$this, '_section_user_name' ), 'envato-wordpress-updater', 'user_account_info' );
    add_settings_field( 'api_key', 'Secret API Key', array( &$this, '_section_api_key' ), 'envato-wordpress-updater', 'user_account_info' );
  }
  
  /**
   * User account description
   *
   * @access   private
   * @since    1.0
   *
   * @return   string
   */
  public function _user_account_info() {
    _e( 'The general section description goes here.', 'envato' );
  }
  
  /**
   * Username text field
   *
   * @access   private
   * @since    1.0
   *
   * @return   string
   */
  public function _section_user_name() {
    $options = get_option( 'envato-wordpress-updater' );
    echo '<input type="text" class="regular-text" name="envato-wordpress-updater[user_name]" value="' . esc_attr( $options['user_name'] ) . '" />';
  }
  
  /**
   * API Key text field
   *
   * @access   private
   * @since    1.0
   *
   * @return   string
   */
  public function _section_api_key() {
    $options = get_option( 'envato-wordpress-updater' );
    echo '<input type="text" class="regular-text" name="envato-wordpress-updater[api_key]" value="' . esc_attr( $options['api_key'] ) . '" />';
  }
}

/**
 * Holds the Envato WordPress Updater object
 *
 * @since     1.0
 * @global    object
 */
$envato_wordpress_updater =& new Envato_WordPress_Updater();
  
/* End of file index.php */
/* Location: ./index.php */