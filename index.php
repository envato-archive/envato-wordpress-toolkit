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
    foreach ( array( 'class-wp-upgrader', 'api', 'update', 'install' ) as $file )
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
     * add envato menu item, change menu filter for multisite
     */
    if ( is_multisite() ) {
      add_action( 'network_admin_menu', array( &$this, '_envato_menu' ) );
    } else {
      add_action( 'admin_menu', array( &$this, '_envato_menu' ) );
    }
    
    /**
     * loaded during admin init 
     */
    add_action( 'admin_init', array( &$this, '_admin_init' ) );
    
    /**
     * change link URL & text after activation 
     */
    add_filter( 'install_theme_complete_actions', array( &$this, '_install_theme_complete_actions' ), 10, 1 );
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
    $menu_page = add_menu_page( 'Envato WordPress Updater', 'Envato Updater', 'manage_options', 'envato-wordpress-updater', array( &$this, '_envato_menu_page' ), EWPU_PLUGIN_URL . '/assets/images/envato.png', 59 );
    
    add_action('admin_print_scripts-' . $menu_page, array( &$this, '_envato_load_scripts' ) );
    add_action('admin_print_styles-' . $menu_page, array( &$this, '_envato_load_styles' ) );
  }
  
  /**
   * Loads the scripts for the plugin
   *
   * @access   private
   * @since    1.0
   *
   * @return   void
   */
  function _envato_load_scripts() {
		wp_enqueue_script( 'theme-preview' );
  }
  
  /**
   * Loads the styles for the plugin
   *
   * @access   private
   * @since    1.0
   *
   * @return   void
   */
  function _envato_load_styles() {

  }
  
  /**
   * Envato Updater HTML
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
    
    /* read in existing API value from database */
    $options = get_site_option( 'envato-wordpress-updater' );

    $user_name = ( isset( $options['user_name'] ) ) ? $options['user_name'] : '';
    $api_key = ( isset( $options['api_key'] ) ) ? $options['api_key'] : '';
    
    if ( $user_name || $api_key )
      $api =& new Envato_Protected_API( $user_name, $api_key );

    /* display update messages */
    echo ( isset( $_GET[ 'settings-updated' ] ) ) ? '<div class="updated"><p><strong>' . __( 'Settings Updated.', 'envato' ) . '</strong></p></div>' : '';
    echo ( isset( $_GET[ 'activated' ] ) ) ? '<div class="updated"><p><strong>' . __( 'Theme Activated.', 'envato' ) . '</strong></p></div>' : '';
    echo ( isset( $_GET[ 'deleted' ] ) ) ? '<div class="updated"><p><strong>' . __( 'Theme Deleted.', 'envato' ) . '</strong></p></div>' : '';
    
    /* display API errors */
    if ( $errors = $api->api_errors() ) {
      foreach( $errors['errors'] as $k => $v ) {
        if ( $k !== 'http_code' )
          echo '<div class="error"><p>' . $v . '</p></div>';
      }
    }

    /* run action not loaded in admin init */
    if ( isset( $_GET['action'] ) ) {
      $action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';
      $theme = isset( $_REQUEST['theme'] ) ? urldecode( $_REQUEST['theme'] ) : '';
      if ( 'install-theme' == $action ) {
        if ( class_exists( 'Envato_Protected_API' ) && $theme !== '' ) {
          $envato_install =& new Envato_Install( $api );
          $envato_install->install_theme( $theme );
        }
      }
    /* display views */
    } else {
      echo '<div class="wrap">';
        include( EWPU_PLUGIN_DIR . '/views/account.php' );
        
        /* no API errors */
        if ( empty( $errors ) ) {
          include( EWPU_PLUGIN_DIR . '/views/themes.php' );
        }
      echo '</div>';   
    }
  }
  
  /**
   * Runs code before the headers are sent
   *
   * @access   private
   * @since    1.0
   *
   * @return   void
   */
  protected function _admin_init_before() {
    if ( isset( $_GET['page'] ) ) {
      $page = $_GET['page'];
      
      /* only execute if this is the Envato WordPress Updater */
      if ( $page == 'envato-wordpress-updater' ) {
      
        /* adds thickbox for previews */
        add_thickbox();
        
        /* action must be set and template or stylesheet */
        if ( isset( $_GET['action'] ) && ( isset( $_GET['template'] ) || isset( $_GET['stylesheet'] ) ) ) {
        
          /* get request variables */
          $action = $_GET['action'];
          $template = isset( $_GET['template'] ) ? $_GET['template'] : '';
          $stylesheet = isset( $_GET['stylesheet'] ) ? $_GET['stylesheet'] : '';
        
          /* required to use the switch_theme() & delete_theme() functions */
          include_once( ABSPATH . 'wp-admin/includes/theme.php' );
          
          /* die if user doesn't have capabilities */
          if ( ! current_user_can( 'switch_themes' ) && ! current_user_can( 'edit_theme_options' ) )
            wp_die( __( 'You do not have sufficient permissions to update themes for this site.' ) );
          	
          if ( current_user_can( 'switch_themes' ) ) {
            if ( 'activate' == $action ) {
              check_admin_referer( 'switch-theme_' . $template );
              switch_theme( $template, $stylesheet );
              wp_redirect( admin_url( 'admin.php?page=envato-wordpress-updater&activated=true' ) );
              exit;
            } else if ( 'delete' == $action ) {
              check_admin_referer( 'delete-theme_' . $template );
              if ( ! current_user_can( 'delete_themes' ) )
                wp_die( __( 'You do not have sufficient permissions to delete themes for this site.' ) );
              delete_theme( $template, wp_nonce_url( 'admin.php?page=envato-wordpress-updater&action=delete&template=' . $template, 'delete-theme_' . $template ) );
              wp_redirect( admin_url( 'admin.php?page=envato-wordpress-updater&deleted=true' ) );
              exit;
          	}
          }
        }
      }
    } 
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
    $this->_admin_init_before();
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
  
  /**
   * Change the text on the install screen via 'install_theme_complete_actions' hook
   *
   * @access   private
   * @since    1.0
   *
   * @return   array
   */
  public function _install_theme_complete_actions( $install_actions ) {
    if ( isset( $_GET['page'] ) && isset( $_GET['action'] ) ) {
      $page = $_GET['page'];
      $action = $_GET['action'];
      if ( $page == 'envato-wordpress-updater' ) {
        if ( $action == 'install-theme' ) {
          $install_actions['themes_page'] = '<a href="' . self_admin_url( 'admin.php?page=envato-wordpress-updater' ) . '" title="' . esc_attr__( 'Return to Envato WordPress Updater' ) . '" target="_parent">' . __( 'Return to Envato WordPress Updater' ) . '</a>';
        }
      }
    }
    return $install_actions;
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