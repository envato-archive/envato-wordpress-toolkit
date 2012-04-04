<?php
/**
 * Plugin Name: Envato WordPress Toolkit
 * Plugin URI: https://github.com/envato/envato-wordpress-toolkit
 * Description: WordPress toolkit for Envato Marketplace hosted items. Currently supports theme install & upgrade.
 * Version: 1.2
 * Author: Derek Herman
 * Author URI: http://valendesigns.com
 */
class Envato_WP_Toolkit {
  /**
   * The Envato Protected API object
   *
   * @since   1.1
   * @access  private
   *
   * @var     object
   */
  protected $protected_api;
  
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
    define( 'EWPT_PLUGIN_VER', '1.2' );
    
    /**
     * Plugin Name
     */
    define( 'EWPT_PLUGIN_NAME', __( 'Envato WordPress Toolkit', 'envato' ) );
    
    /**
     * Plugin Slug
     */
    define( 'EWPT_PLUGIN_SLUG', 'envato-wordpress-toolkit' );
    
    /**
     * Maximum request time
     */
    define( 'EWPT_PLUGIN_MAX_EXECUTION_TIME' , 60 * 5);
    
    /**
     * Plugin Directory Path
     */
    define( 'EWPT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
    
    /**
     * Plugin Directory URL
     */
    define( 'EWPT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
  }
  
  /**
   * Include required files
   *
   * @since     1.0
   * @access    private
   */
  protected function _includes() {
    /* load required files */
    foreach ( array( 'class-envato-api', 'class-wp-upgrader' ) as $file )
      require_once( EWPT_PLUGIN_DIR . 'includes/' . $file . '.php' );
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
    add_filter( 'http_request_args', array( &$this , '_http_request_args' ), 10, 1 );
  }
  
  /**
   * Adds the Envato menu item
   *
   * @access   private
   * @since    1.0
   */
  public function _envato_menu() {
    $menu_page = add_menu_page( EWPT_PLUGIN_NAME, __( 'Envato Toolkit', 'envato' ), 'manage_options', EWPT_PLUGIN_SLUG, array( &$this, '_envato_menu_page' ), EWPT_PLUGIN_URL . 'assets/images/envato.png', 59 );
    
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
    wp_enqueue_style( 'envato-wp-updater', EWPT_PLUGIN_URL . 'assets/css/style.css', false, EWPT_PLUGIN_VER, 'all' );
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
    $options = get_option( EWPT_PLUGIN_SLUG );

    $user_name = ( isset( $options['user_name'] ) ) ? $options['user_name'] : '';
    $api_key = ( isset( $options['api_key'] ) ) ? $options['api_key'] : '';
    
    $this->protected_api =& new Envato_Protected_API( $user_name, $api_key );
    
    /* get purchased marketplace themes */
    $themes = $this->protected_api->wp_list_themes();
    
    /* display API errors */
    if ( $errors = $this->protected_api->api_errors() ) {
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

    /* execute theme actions */
    if ( isset( $_GET['action'] ) && isset( $_GET['theme'] ) ) {
      if ( 'install-theme' == $_GET['action'] && is_array( $themes ) ) {
        $this->_install_theme( $_GET['theme'], $themes );
      } else if ( 'upgrade-theme' == $_GET['action'] && isset( $_GET['item_id'] ) ) {
        $this->_upgrade_theme( $_GET['theme'], $_GET['item_id'] );
      }
    /* display normal views */
    } else {
      echo '<div class="wrap">';
        echo '<div id="icon-themes" class="icon32"></div><h2>' . EWPT_PLUGIN_NAME . '</h2>';
        echo '
        <form name="verification_form" method="post" action="options.php" id="api-verification">';
          wp_nonce_field( 'update-options' );
          settings_fields( EWPT_PLUGIN_SLUG );
          do_settings_sections( EWPT_PLUGIN_SLUG );
          echo '
          <p class="submit">
            <input type="submit" name="Submit" class="button-primary right" value="' . __( 'Save User Settings', 'envato' ) . '" />
          </p>
        </form>';
        /* no errors & themes are available */
        if ( empty( $errors ) && count( $themes ) > 0 ) {
        
          /* get WP installed themes */
          $get_themes = get_themes();
        
          /* loop through the marketplace themes */
          $premium_themes = array();
          foreach( $themes as $theme ) {
            
            /* setup the defaults */
            $content = '';
            $installed = false;
            $links = array();
            $current_stylesheet = get_stylesheet();
            $latest_version = $theme->version;
            $item_id = $theme->item_id;
            $template = '';
            $stylesheet = '';
            $title = $theme->theme_name;
            $version = '';
            $description = $theme->description;
            $author = $theme->author_name;
            $parent_theme = '';
            $tags = '';
            
            /* setup the item details */
            $item_details = $this->protected_api->item_details( $item_id );
            
            /* get installed theme information */
            foreach( $get_themes as $k => $v ) {
              if ( $get_themes[$k]['Title'] == $title && $get_themes[$k]['Author Name'] == $author && $template == '' ) {
                $template = $get_themes[$k]['Template'];
              	$stylesheet = $get_themes[$k]['Stylesheet'];
              	$title = $get_themes[$k]['Title'];
              	$version = $get_themes[$k]['Version'];
              	$description = $get_themes[$k]['Description'];
              	$author = $get_themes[$k]['Author'];
              	$screenshot = $get_themes[$k]['Screenshot'];
              	$stylesheet_dir = $get_themes[$k]['Stylesheet Dir'];
              	$template_dir = $get_themes[$k]['Template Dir'];
              	$parent_theme = $get_themes[$k]['Parent Theme'];
              	$theme_root = $get_themes[$k]['Theme Root'];
              	$theme_root_uri = $get_themes[$k]['Theme Root URI'];
              	$tags = $get_themes[$k]['Tags'];
              	$installed = true;
                continue;
              }
            }
            
            $has_update = ( $installed && version_compare( $version, $latest_version, '<' ) ) ? TRUE : FALSE;
            $details_url = htmlspecialchars( add_query_arg( array( 'TB_iframe' => 'true', 'width' => 1024, 'height' => 800 ), $item_details->url ) );
            $activate_url = wp_nonce_url( 'admin.php?page=' . EWPT_PLUGIN_SLUG . '&action=activate&amp;template=' . urlencode( $template ) . '&amp;stylesheet=' . urlencode( $stylesheet ), 'switch-theme_' . $template );
            $preview_url = htmlspecialchars( add_query_arg( array( 'preview' => 1, 'template' => $template, 'stylesheet' => $stylesheet, 'preview_iframe' => 1, 'TB_iframe' => 'true' ), trailingslashit( esc_url( get_option( 'home' ) ) ) ) );
            $delete_url = wp_nonce_url( 'admin.php?page=' . EWPT_PLUGIN_SLUG . '&action=delete&template=' . $stylesheet, 'delete-theme_' . $stylesheet );
            $delete_onclick = 'onclick="if ( confirm(\'' . esc_js( sprintf( __( "You're about to delete the '%s' theme. 'Cancel' to stop, 'OK' to update.", 'envato' ), $title ) ) . '\') ) {return true;}return false;"';
            $install_url = wp_nonce_url( self_admin_url( 'admin.php?page=' . EWPT_PLUGIN_SLUG . '&action=install-theme&theme=' . $item_id ), 'install-theme_' . $item_id );
            $update_url = wp_nonce_url( 'admin.php?page=' . EWPT_PLUGIN_SLUG . '&action=upgrade-theme&amp;theme=' . $stylesheet . '&amp;item_id=' . $item_id, 'upgrade-theme_' . $stylesheet );
            $update_onclick = 'onclick="if ( confirm(\'' . esc_js( __( "Updating this theme will lose any customizations you have made. 'Cancel' to stop, 'OK' to update.", 'envato' ) ) . '\') ) {return true;}return false;"';
            
            /* Theme Title message */
            $content.= '<h3>' . $title . ' ' . $version . ' by ' . $author . '</h3>';
              
            /* Theme Description */
            if ( $description ) {
              $content.= '<p class="description">' . $description . '</p>';
            }
            
            /* Links list */
            if ( $stylesheet && $template && $current_stylesheet !== $stylesheet ) {
              $links[] = '<a href="' . $activate_url .  '" class="activatelink" title="' . esc_attr( sprintf( __( 'Activate &#8220;%s&#8221;', 'envato' ), $title ) ) . '">' . __( 'Activate', 'envato' ) . '</a>';
              $links[] = '<a href="' . $preview_url . '" class="thickbox thickbox-preview" title="' . esc_attr( sprintf( __( 'Preview &#8220;%s&#8221;', 'envato' ), $title ) ) . '">' . __( 'Preview', 'envato' ) . '</a>';
              $links[] = '<a href="' . $delete_url . '" class="submitdelete deletion" title="' . esc_attr( sprintf( __( 'Delete &#8220;%s&#8221;', 'envato' ), $title ) ) . '" ' . $delete_onclick . '>' . __( 'Delete' ) . '</a>';
              $links[] = '<a href="' . $details_url . '" class="thickbox thickbox-preview" title="' . esc_attr( sprintf( __( 'View version %1$s details', 'envato' ), $latest_version ) ) . '">' . esc_attr( sprintf( __( 'View version %1$s details', 'envato' ), $latest_version ) ) . '</a>';
              $content.= '<div class="update-info">' . implode( ' | ', $links ) . '</div>';
            }
            
            /**
             * This ugly code lists the current theme options
             * It was pulled from wp-admin/themes.php with minor tweaks
             */
            if ( $current_stylesheet == $stylesheet ) {
              global $submenu;
              $parent_file = 'themes.php';
              $options = array();
              if ( is_array( $submenu ) && isset( $submenu['themes.php'] ) ) {
                foreach ( (array) $submenu['themes.php'] as $item ) {
                  if ( 'themes.php' == $item[2] || 'theme-editor.php' == $item[2] )
                    continue;
                  if ( ! empty( $submenu[$item[2]] ) ) {
                    $submenu[$item[2]] = array_values( $submenu[$item[2]] );
                    $menu_hook = get_plugin_page_hook($submenu[$item[2]][0][2], $item[2]);
                    if ( file_exists( ABSPATH . PLUGINDIR . "/{$submenu[$item[2]][0][2]}" ) || ! empty( $menu_hook ) )
                      $options[] = "<a href='admin.php?page={$submenu[$item[2]][0][2]}'>{$item[0]}</a>";
                    else
                      $options[] = "<a href='{$submenu[$item[2]][0][2]}'>{$item[0]}</a>";
                  } else if ( current_user_can( $item[1] ) ) {
                    if ( file_exists(ABSPATH . 'wp-admin/' . $item[2]) )
                      $options[] = "<a href='{$item[2]}'>{$item[0]}</a>";
                    else
                      $options[] = "<a href='themes.php?page={$item[2]}'>{$item[0]}</a>";
                  }
                }
              }
              if ( ! empty( $options ) )
                $content.= '<div class="update-info"><span>' . __( 'Options:', 'envato' ) . '</span> ' . implode( ' | ', $options ) . '</div>';
            }
            
            /* Theme path information */
            if ( current_user_can( 'edit_themes' ) && $installed ) {
              if ( $parent_theme ) {
                 $content.= '<p>' . sprintf( __( 'The template files are located in <code>%2$s</code>. The stylesheet files are located in <code>%3$s</code>. <strong>%4$s</strong> uses templates from <strong>%5$s</strong>. Changes made to the templates will affect both themes.', 'envato' ), $title, str_replace( WP_CONTENT_DIR, '', $template_dir ), str_replace( WP_CONTENT_DIR, '', $stylesheet_dir ), $title, $parent_theme ) . '</p>';
              } else {
                 $content.= '<p>' . sprintf( __( 'All of this theme&#8217;s files are located in <code>%2$s</code>.', 'envato' ), $title, str_replace( WP_CONTENT_DIR, '', $template_dir ), str_replace( WP_CONTENT_DIR, '', $stylesheet_dir ) ) . '</p>';
              }
            }
            
            /* Tags list */
            if ( $tags ) {
              $content.= '<p>' . __( 'Tags: ' ). join( ', ', $tags ) . '</p>';
            }
            
            /* Upgrade/Install message */
            if ( $has_update ) {
              if ( ! current_user_can( 'update_themes' ) ) {
                $content.= sprintf( '<div class="updated below-h2"><p><strong>' . __( 'There is a new version of %1$s available. <a href="%2$s" class="thickbox thickbox-preview" title="%1$s">View version %3$s details</a>.', 'envato' ) . '</strong></p></div>', $title, $details_url, $latest_version );
              } else {
                $content.= sprintf( '<div class="updated below-h2"><p><strong>' . __( 'There is a new version of %1$s available. <a href="%2$s" class="thickbox thickbox-preview" title="%1$s">View version %3$s details</a> or <a href="%4$s" %5$s>update automatically</a>.', 'envato' ) . '</strong></p></div>', $title, $details_url, $latest_version, $update_url, $update_onclick );
              }
            } else if ( ! $installed ) {
              if ( ! current_user_can( 'update_themes' ) ) {
                $content.= sprintf( '<div class="updated below-h2"><p><strong>' . __( '%1$s has not been installed. <a href="%2$s" class="thickbox thickbox-preview" title="%1$s">View version %3$s details</a>.', 'envato' ) . '</strong></p></div>', $title, $details_url, $latest_version );
              } else {
                $content.= sprintf( '<div class="updated below-h2"><p><strong>' . __( '%1$s has not been installed. <a href="%2$s" class="thickbox thickbox-preview" title="%1$s">View version %3$s details</a> or <a href="%4$s">install automatically</a>.', 'envato' ) . '</strong></p></div>', $title, $details_url, $latest_version, $install_url );
              }
            }
        		
            /* put the HTML into a variable */
            $list_item = '
            <li>
              <div class="thumbnail">
                <img src="' . $item_details->thumbnail  . '" alt="' . $title . '" />
              </div>
              <div class="item-details">
                ' . $content . '
              </div>
            </li>';
            
            $premium_themes[] = array(
              'current_theme' => ( $current_stylesheet == $stylesheet ? true : false ),
              'list_item' => $list_item
            );
            
          }
          
          /**
           * Loop through all the premium themes.
           * Separate out the current one, display it, & remove from array
           * Display the other premium themes after edits to the array.
           */
          if ( ! empty( $premium_themes ) ) {
            $current_theme = array();
            foreach ( $premium_themes as $k => $v ) {
              if ( $premium_themes[$k]['current_theme'] == true ) {
                $current_theme = $premium_themes[$k];
                unset( $premium_themes[$k] );
              }
            }
            
            /* list current premium theme */
            if ( ! empty( $current_theme ) ) {
              _e( '<h3>Current Theme</h3>' );
              echo '<ul class="ewpu-item-list">';
                echo $current_theme['list_item'];
              echo '</ul>';
            }
            
            /* list premium themes */
            _e( '<h3>Available Themes</h3>' );
            echo '<ul class="ewpu-item-list">';
            foreach ( $premium_themes as $k => $v )
              echo $premium_themes[$k]['list_item'];
            echo '</ul>';
          }
            
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
      
      /* only execute if this is the Envato WordPress Toolkit */
      if ( $page == EWPT_PLUGIN_SLUG ) {
      
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
   * Registers the settings for the updater
   *
   * @access   private
   * @since    1.0
   *
   * @return   void
   */
  public function _admin_init() {
    $this->_admin_init_before();
    register_setting( EWPT_PLUGIN_SLUG, EWPT_PLUGIN_SLUG );
    add_settings_section( 'user_account_info', 'User Account Information', array( &$this, '_user_account_info' ), EWPT_PLUGIN_SLUG );
    add_settings_field( 'user_name', 'Marketplace Username', array( &$this, '_section_user_name' ), EWPT_PLUGIN_SLUG, 'user_account_info' );
    add_settings_field( 'api_key', 'Secret API Key', array( &$this, '_section_api_key' ), EWPT_PLUGIN_SLUG, 'user_account_info' );
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
    $options = get_option( EWPT_PLUGIN_SLUG );
    echo '<input type="text" class="regular-text" name="' . EWPT_PLUGIN_SLUG . '[user_name]" value="' . esc_attr( $options['user_name'] ) . '" />';
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
    $options = get_option( EWPT_PLUGIN_SLUG );
    echo '<input type="password" class="regular-text" name="' . EWPT_PLUGIN_SLUG . '[api_key]" value="' . esc_attr( $options['api_key'] ) . '" />';
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
      if ( $page == EWPT_PLUGIN_SLUG ) {
        if ( 'install-theme' == $action || 'upgrade-theme' == $action ) {
          $actions['themes_page'] = '<a href="' . self_admin_url( 'admin.php?page=' . EWPT_PLUGIN_SLUG ) . '" title="' . esc_attr__( sprintf( __( 'Return to %s', 'envato' ), EWPT_PLUGIN_NAME ) ) . '" target="_parent">' . sprintf( __( 'Return to %s', 'envato' ), EWPT_PLUGIN_NAME ) . '</a>';
        }
      }
    }
    return $actions;
  }
  
  
  /**
   * Manually installs a theme from the Envato API.
   *
   * @access    private
   * @since     1.0
   * @updated   1.1
   *
   * @param     string    Theme item_id from ThemeForests
   * @param     array     List of all purchased themes
   * @return    void
   */
  protected function _install_theme( $theme, $themes ) {
    global $current_screen;
    
    check_admin_referer( 'install-theme_' . $theme );
    
    if ( ! current_user_can( 'install_themes' ) )
      wp_die( __( 'You do not have sufficient permissions to install themes for this site.', 'envato' ) );
    
    /* setup theme info in $api array */
    $api = (object) array();
    foreach( $themes as $t ) {
      if ( $theme == $t->item_id ) {
        $api->name = $t->item_name;
        $api->version = $t->version;
        continue;
      }
    }
    
    $title = sprintf( __( 'Installing Theme: %s', 'envato' ), $api->name . ' ' . $api->version );
    $nonce = 'install-theme_' . $theme;
    $url = 'admin.php?page=' . EWPT_PLUGIN_SLUG . '&action=install-theme&theme=' . $theme;
    $type = 'web';
    
    /* trick WP into thinking it's the themes page for the icon32 */
    $current_screen->parent_base = 'themes';
    
    /* new Envato_Theme_Upgrader */
    $upgrader = new Envato_Theme_Upgrader( new Theme_Installer_Skin( compact( 'title', 'url', 'api', 'nonce' ) ) );
    
    /* install the theme */
    $upgrader->install( $this->protected_api->wp_download( $theme ) );
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
    
    if ( ! function_exists( 'switch_theme' ) )
      include_once( ABSPATH . 'wp-admin/includes/theme.php' );
      
    switch_theme( $template, $stylesheet );
    wp_redirect( admin_url( 'admin.php?page=' . EWPT_PLUGIN_SLUG . '&activated=true' ) );
    exit;
  }
  
  /**
   * Manually upgrades a theme from the Envato API.
   *
   * @access    private
   * @since     1.0
   * @updated   1.1
   *
   * @param     string    Theme slug
   * @param     string    Theme item_id from ThemeForests
   * @return    void
   */
  protected function _upgrade_theme( $theme, $item_id ) {
    global $current_screen;
    
    check_admin_referer( 'upgrade-theme_' . $theme );
    
    if ( ! current_user_can( 'update_themes' ) )
      wp_die( __( 'You do not have sufficient permissions to update themes for this site.', 'envato' ) );
    
    $title = __( 'Update Theme', 'envato' );
    $nonce = 'upgrade-theme_' . $theme;
    $url = 'admin.php?page=' . EWPT_PLUGIN_SLUG . '&action=upgrade-theme&theme=' . $theme . '&item_id=' . $item_id;
    
    /* trick WP into thinking it's the themes page for the icon32 */
    $current_screen->parent_base = 'themes';
    
    /* new Envato_Theme_Upgrader */
    $upgrader = new Envato_Theme_Upgrader( new Theme_Upgrader_Skin( compact( 'title', 'nonce', 'url', 'theme' ) ) );
    
    /* upgrade the theme */
    $upgrader->upgrade( $theme, $this->protected_api->wp_download( $item_id ) );
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
    
    if ( ! function_exists( 'delete_theme' ) )
      include_once( ABSPATH . 'wp-admin/includes/theme.php' );
    
    delete_theme( $template, wp_nonce_url( 'admin.php?page=' . EWPT_PLUGIN_SLUG . '&action=delete&template=' . $template, 'delete-theme_' . $template ) );
    wp_redirect( admin_url( 'admin.php?page=' . EWPT_PLUGIN_SLUG . '&deleted=true' ) );
    exit;
  }
  
  /**
   * Force PHP to extend max_execution_time to ensure larger themes can download
   *
   * @author    Arman Mirkazemi
   *
   * @access    private
   * @since     1.0
   */
  public function _http_request_args( $r ){
    if ( (int) ini_get( 'max_execution_time' ) <  EWPT_PLUGIN_MAX_EXECUTION_TIME ) {
      ini_set( 'max_execution_time', EWPT_PLUGIN_MAX_EXECUTION_TIME );
    }

    $r['timeout'] = EWPT_PLUGIN_MAX_EXECUTION_TIME;
    return $r;
  }
  
}

/**
 * Instantiates the Class
 *
 * @since     1.0
 * @global    object
 */
$envato_wp_toolkit = new Envato_WP_Toolkit();
  
/* End of file index.php */
/* Location: ./index.php */