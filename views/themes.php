<?php if ( ! defined( 'EWPU_PLUGIN_VER') ) exit( 'No direct script access allowed' );
/**
 * List all the themes purchased
 *
 * @package     Envato WordPress Updater
 * @author      Derek Herman <derek@valendesigns.com>
 * @since       1.0
 */
   
/* themes are available */
if ( count( $themes ) > 0 ) {

  /* get WP installed themes */
  $get_themes = get_themes();
  
  echo '<h3>Available Themes</h3>';
  echo '<ul class="item-list">';
  
  /* loop through the marketplace themes */
  foreach( $themes as $theme ) {
    
    /* setup the item details */
    $item_details = $api->item_details( $theme->item_id );
    
    $current_stylesheet = get_option( 'stylesheet' );
    $template = '';
    $stylesheet = '';
    
    /* check if installed */
    foreach( $get_themes as $k => $v ) {
      if ( $get_themes[$k]['Title'] == $theme->theme_name && $get_themes[$k]['Author Name'] == $theme->author_name ) {
        $template = $get_themes[$k]['Template'];
        $stylesheet = $get_themes[$k]['Stylesheet'];
        continue;
      }
    }
    
    /* setup the links */
    $install_actions = array();
    
    if ( $stylesheet && $template ) {
      if ( $current_stylesheet == $stylesheet ) {
        
        /* current theme link */
        $current_link = self_admin_url( 'themes.php' );
        $install_actions['current'] = '<a href="' . $current_link .  '" class="disabled" title="' . esc_attr( sprintf( __( '&#8220;%s&#8221; is currently active' ), $theme->theme_name ) ) . '">' . __( 'Currently Active' ) . '</a>';

      } else {
        
        /* activate link */
        $activate_link = wp_nonce_url( 'admin.php?page=envato-wordpress-updater&action=activate&amp;template=' . urlencode( $template ) . '&amp;stylesheet=' . urlencode( $stylesheet ), 'switch-theme_' . $template );
        $install_actions['activate'] = '<a href="' . $activate_link .  '" class="activatelink" title="' . esc_attr( sprintf( __( 'Activate &#8220;%s&#8221;' ), $theme->theme_name ) ) . '">' . __( 'Activate' ) . '</a>';
        
        /* preview link */
        $preview_link = htmlspecialchars( add_query_arg( array( 'preview' => 1, 'template' => $template, 'stylesheet' => $stylesheet, 'preview_iframe' => 1, 'TB_iframe' => 'true' ), trailingslashit( esc_url( get_option( 'home' ) ) ) ) );
        $install_actions['preview'] = '<a href="' . $preview_link . '" class="thickbox thickbox-preview" title="' . esc_attr( sprintf( __( 'Preview &#8220;%s&#8221;' ), $theme->theme_name ) ) . '">' . __( 'Preview' ) . '</a>';
        
        /* delete link */
        $delete_link = wp_nonce_url( 'admin.php?page=envato-wordpress-updater&action=delete&template=' . $template, 'delete-theme_' . $template );
        $install_actions['delete'] = '<a href="' . $delete_link . '" class="submitdelete deletion" title="' . esc_attr( sprintf( __( 'Delete &#8220;%s&#8221;' ), $theme->theme_name ) ) . '" onclick="' . "return confirm( '" . esc_js( sprintf( __( "You are about to delete the '%s' theme.\n\n'Cancel' to stop, 'OK' to delete." ), $theme->theme_name ) ) . "' );" . '">' . __( 'Delete' ) . '</a>';
        
      }
    } else {
      
      /* install link */
      $install_link = wp_nonce_url( self_admin_url( 'admin.php?page=envato-wordpress-updater&action=install-theme&theme=' . $theme->item_id ), 'install-theme_' . $theme->item_id );
      $install_actions['install'] = '<a href="' . $install_link .  '" class="installlink" title="' . esc_attr( sprintf( __( 'Install &#8220;%s&#8221;' ), $theme->theme_name ) ) . '">' . __( 'Install' ) . '</a>';
      
    }
    
    /* themeforest link */
    $theme_link = htmlspecialchars( add_query_arg( array( 'TB_iframe' => 'true', 'width' => 1024, 'height' => 800 ), $item_details->url ) );
    $install_actions['theme_url'] = '<a href="' . $theme_link .  '" class="thickbox thickbox-preview" title="' . esc_attr( sprintf( __( 'View &#8220;%s&#8221; on ThemeForest ' ), $theme->theme_name ) ) . '">' . __( 'View on ThemeForest' ) . '</a>';
      
    $install_links = '<div class="update-info">' . implode( ' | ', $install_actions ) . '</div>';
    
    /* echo the HTML */
    echo '<li>';
      echo '<div class="thumbnail"><img src="' . $item_details->thumbnail  . '" alt="' . $theme->theme_name . '" /></div>';
      echo '
      <div class="item-details">
        <h3>' . $theme->theme_name . ' ' . $theme->version . ' by ' . $theme->author_name . '</h3>
        ' . ( $theme->description ? '<p class="description">' . $theme->description . '</p>' : '' ) . '
        ' . $install_links . '
      </div>';
    echo '</li>';
  }
  echo '</ul>';
}