<?php
/**
 * Plugin Name: Envato WordPress Updater
 * Plugin URI: https://github.com/envato/envato-wordpress-updater
 * Description: WordPress install/upgrade utility for Envato Marketplace hosted files.
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
  public function __construct() {
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
  protected function _constants() {
    /**
     * Plugin Version
     */
    define( 'EWPU_PLUGIN_VER', '1.0' );
    
    /**
     * Plugin Name
     */
    define( 'EWPU_PLUGIN_NAME', __( 'Envato WordPress Updater', 'envato' ) );
    
    /**
     * Plugin Slug
     */
    define( 'EWPU_PLUGIN_SLUG', 'envato-wordpress-updater' );
    
    /**
     * Plugin Directory Path
     */
    define( 'EWPU_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . dirname( plugin_basename( __FILE__ ) ) );
    
    /**
     * Plugin Directory URL
     */
    define( 'EWPU_PLUGIN_URL', WP_PLUGIN_URL . '/' . dirname( plugin_basename( __FILE__ ) ) );
  }
  
  /**
   * Include required files
   *
   * @since     1.0
   * @access    private
   */
  protected function _includes() {
    /* load required files */
    foreach ( array( 'class-wp-upgrader', 'api', 'install', 'upgrade' ) as $file )
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
  protected function _hooks() {
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
     * change link URL & text after install & upgrade 
     */
    add_filter( 'install_theme_complete_actions', array( &$this, '_complete_actions' ), 10, 1 );
    add_filter( 'update_theme_complete_actions', array( &$this, '_complete_actions' ), 10, 1 );
  }
  
  /**
   * Adds the Envato menu item
   *
   * @access   private
   * @since    1.0
   */
  public function _envato_menu() {
    $menu_page = add_menu_page( EWPU_PLUGIN_NAME, 'Envato Updater', 'manage_options', EWPU_PLUGIN_SLUG, array( &$this, '_envato_menu_page' ), EWPU_PLUGIN_URL . '/assets/images/envato.png', 59 );
    
    add_action('admin_print_scripts-' . $menu_page, array( &$this, '_envato_load_scripts' ) );
    add_action('admin_print_styles-' . $menu_page, array( &$this, '_envato_load_styles' ) );
  }
  
  /**
   * Loads the scripts for the plugin
   *
   * @access   private
   * @since    1.0
   */
  public function _envato_load_scripts() {
    wp_enqueue_script( 'theme-preview' );
  }
  
  /**
   * Loads the styles for the plugin
   *
   * @access   private
   * @since    1.0
   */
  public function _envato_load_styles() {
    wp_enqueue_style( 'envato-wp-updater', EWPU_PLUGIN_URL . '/assets/css/style.css', false, EWPU_PLUGIN_VER, 'all' );
  }
  
  /**
   * Envato Updater HTML
   *
   * Creates the page used to verify themes for auto install/update
   *
   * @access   private
   * @since    1.0
   *
   * @return   string   Returns the verification form & themes list
   */
  public function _envato_menu_page() {
    if ( ! current_user_can( 'manage_options' ) )
      wp_die( __( 'You do not have sufficient permissions to access this page.', 'envato' ) );
    
    /* read in existing API value from database */
    $options = get_site_option( EWPU_PLUGIN_SLUG );

    $user_name = ( isset( $options['user_name'] ) ) ? $options['user_name'] : '';
    $api_key = ( isset( $options['api_key'] ) ) ? $options['api_key'] : '';
    
    $api =& new Envato_Protected_API( $user_name, $api_key );
    
    /* get purchased marketplace themes */
    $themes = $api->wp_list_themes();

    /* display API errors */
    if ( $errors = $api->api_errors() ) {
      foreach( $errors as $k => $v ) {
        if ( $k !== 'http_code' && ( $user_name || $api_key ) )
          echo '<div class="error"><p>' . $v . '</p></div>';
      }
    }
    
    /* display update messages */
    if ( empty( $errors ) ) {
      echo ( isset( $_GET[ 'settings-updated' ] ) ) ? '<div class="updated below-h2"><p><strong>' . __( 'User Settings Updated.', 'envato' ) . '</strong></p></div>' : '';
      echo ( isset( $_GET[ 'activated' ] ) ) ? '<div class="updated below-h2"><p><strong>' . __( 'Theme Activated.', 'envato' ) . '</strong></p></div>' : '';
      echo ( isset( $_GET[ 'deleted' ] ) ) ? '<div class="updated below-h2"><p><strong>' . __( 'Theme Deleted.', 'envato' ) . '</strong></p></div>' : '';
    }

    /* run actions not loaded in admin init */
    if ( isset( $_GET['action'] ) ) {
      if ( 'install-theme' == $_GET['action'] ) {
        if ( isset( $_GET['theme'] ) && $api ) {
          $envato_install =& new Envato_Install( $api );
          $envato_install->install_theme( $_GET['theme'], $themes );
        }
      } else if ( 'upgrade-theme' == $_GET['action'] ) {
        if ( isset( $_GET['theme'] ) && isset( $_GET['item_id'] ) && $api ) {
          $envato_upgrade =& new Envato_Upgrade( $api );
          $envato_upgrade->upgrade_theme( $_GET['theme'], $_GET['item_id'] );
        }
      }
    /* display normal views */
    } else {
      echo '<div class="wrap">';
        echo '<div id="icon-themes" class="icon32"></div><h2>' . EWPU_PLUGIN_NAME . '</h2>';
        include( EWPU_PLUGIN_DIR . '/views/account.php' );
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
      if ( $page == EWPU_PLUGIN_SLUG ) {
      
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
          
          if ( 'activate' == $action ) {
            $this->_activate_theme( $template, $stylesheet );
          } else if ( 'delete' == $action ) {
            $this->_delete_theme( $template );
          }
        }
      }
    } 
  }
  
  /**
   * Activate an Envato theme
   *
   * @access    private
   * @since     1.0
   *
   * @param     string    Template name
   * @param     array     Stylesheet name
   * @return    void
   */
  protected function _activate_theme( $template, $stylesheet ) {
    check_admin_referer( 'switch-theme_' . $template );
    
    if ( ! current_user_can( 'switch_themes' ) && ! current_user_can( 'edit_theme_options' ) )
      wp_die( __( 'You do not have sufficient permissions to update themes for this site.', 'envato' ) );
    
    if ( ! function_exists( 'delete_theme' ) )
      include_once( ABSPATH . 'wp-admin/includes/theme.php' );
      
    switch_theme( $template, $stylesheet );
    wp_redirect( admin_url( 'admin.php?page=' . EWPU_PLUGIN_SLUG . '&activated=true' ) );
    exit;
  }
  
  /**
   * Delete an Envato theme
   *
   * @access    private
   * @since     1.0
   *
   * @param     string    Template name
   * @return    void
   */
  protected function _delete_theme( $template ) {
    check_admin_referer( 'delete-theme_' . $template );

    if ( ! current_user_can( 'switch_themes' ) && ! current_user_can( 'edit_theme_options' ) )
      wp_die( __( 'You do not have sufficient permissions to update themes for this site.', 'envato' ) );
    
    if ( ! current_user_can( 'delete_themes' ) )
      wp_die( __( 'You do not have sufficient permissions to delete themes for this site.', 'envato' ) );
    
    if ( ! function_exists( 'switch_theme' ) )
      include_once( ABSPATH . 'wp-admin/includes/theme.php' );
    
    delete_theme( $template, wp_nonce_url( 'admin.php?page=' . EWPU_PLUGIN_SLUG . '&action=delete&template=' . $template, 'delete-theme_' . $template ) );
    wp_redirect( admin_url( 'admin.php?page=' . EWPU_PLUGIN_SLUG . '&deleted=true' ) );
    exit;
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
    register_setting( EWPU_PLUGIN_SLUG, EWPU_PLUGIN_SLUG );
    add_settings_section( 'user_account_info', 'User Account Information', array( &$this, '_user_account_info' ), EWPU_PLUGIN_SLUG );
    add_settings_field( 'user_name', 'Marketplace Username', array( &$this, '_section_user_name' ), EWPU_PLUGIN_SLUG, 'user_account_info' );
    add_settings_field( 'api_key', 'Secret API Key', array( &$this, '_section_api_key' ), EWPU_PLUGIN_SLUG, 'user_account_info' );
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
    _e( 'To obtain your API Key, visit your "My Settings" page on any of the Envato Marketplaces.', 'envato' );
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
    $options = get_option( EWPU_PLUGIN_SLUG );
    echo '<input type="text" class="regular-text" name="' . EWPU_PLUGIN_SLUG . '[user_name]" value="' . esc_attr( $options['user_name'] ) . '" />';
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
    $options = get_option( EWPU_PLUGIN_SLUG );
    echo '<input type="password" class="regular-text" name="' . EWPU_PLUGIN_SLUG . '[api_key]" value="' . esc_attr( $options['api_key'] ) . '" />';
  }
  
  /**
   * Change the text on the install or upgrade screen
   *
   * @access   private
   * @since    1.0
   *
   * @return   array
   */
  public function _complete_actions( $actions ) {
    if ( isset( $_GET['page'] ) && isset( $_GET['action'] ) ) {
      $page   = $_GET['page'];
      $action = $_GET['action'];
      if ( $page == EWPU_PLUGIN_SLUG ) {
        if ( 'install-theme' == $action || 'upgrade-theme' == $action ) {
          $actions['themes_page'] = '<a href="' . self_admin_url( 'admin.php?page=' . EWPU_PLUGIN_SLUG ) . '" title="' . esc_attr__( sprintf( __( 'Return to %s', 'envato' ), EWPU_PLUGIN_NAME ) ) . '" target="_parent">' . sprintf( __( 'Return to %s', 'envato' ), EWPU_PLUGIN_NAME ) . '</a>';
        }
      }
    }
    return $actions;
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