<?php if ( ! defined( 'EWPU_PLUGIN_VER') ) exit( 'No direct script access allowed' );
/**
 * List all the themes purchased along with any relevant links and info.
 *
 * @package     Envato WordPress Updater
 * @author      Derek Herman <derek@valendesigns.com>
 * @since       1.0
 */
   
/* themes are available */
if ( count( $themes ) > 0 ) {

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
    $item_details = $api->item_details( $item_id );
    
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
    $activate_url = wp_nonce_url( 'admin.php?page=' . EWPU_PLUGIN_SLUG . '&action=activate&amp;template=' . urlencode( $template ) . '&amp;stylesheet=' . urlencode( $stylesheet ), 'switch-theme_' . $template );
    $preview_url = htmlspecialchars( add_query_arg( array( 'preview' => 1, 'template' => $template, 'stylesheet' => $stylesheet, 'preview_iframe' => 1, 'TB_iframe' => 'true' ), trailingslashit( esc_url( get_option( 'home' ) ) ) ) );
    $delete_url = wp_nonce_url( 'admin.php?page=' . EWPU_PLUGIN_SLUG . '&action=delete&template=' . $stylesheet, 'delete-theme_' . $stylesheet );
    $delete_onclick = 'onclick="if ( confirm(\'' . esc_js( sprintf( __("You're about to delete the '%s' theme. 'Cancel' to stop, 'OK' to update."), $title ) ) . '\') ) {return true;}return false;"';
    $install_url = wp_nonce_url( self_admin_url( 'admin.php?page=' . EWPU_PLUGIN_SLUG . '&action=install-theme&theme=' . $item_id ), 'install-theme_' . $item_id );
    $update_url = wp_nonce_url( 'admin.php?page=' . EWPU_PLUGIN_SLUG . '&action=upgrade-theme&amp;theme=' . $stylesheet . '&amp;item_id=' . $item_id, 'upgrade-theme_' . $stylesheet );
    $update_onclick = 'onclick="if ( confirm(\'' . esc_js( __("Updating this theme will lose any customizations you have made. 'Cancel' to stop, 'OK' to update.") ) . '\') ) {return true;}return false;"';
    
    /* Theme Title message */
    $content.= '<h3>' . $title . ' ' . $version . ' by ' . $author . '</h3>';
      
    /* Theme Description */
    if ( $description ) {
      $content.= '<p class="description">' . $description . '</p>';
    }
    
    /* Links list */
    if ( $stylesheet && $template && $current_stylesheet !== $stylesheet ) {
      $links[] = '<a href="' . $activate_url .  '" class="activatelink" title="' . esc_attr( sprintf( __( 'Activate &#8220;%s&#8221;' ), $title ) ) . '">' . __( 'Activate' ) . '</a>';
      $links[] = '<a href="' . $preview_url . '" class="thickbox thickbox-preview" title="' . esc_attr( sprintf( __( 'Preview &#8220;%s&#8221;' ), $title ) ) . '">' . __( 'Preview' ) . '</a>';
      $links[] = '<a href="' . $delete_url . '" class="submitdelete deletion" title="' . esc_attr( sprintf( __( 'Delete &#8220;%s&#8221;' ), $title ) ) . '" ' . $delete_onclick . '>' . __( 'Delete' ) . '</a>';
      $links[] = '<a href="' . $details_url . '" class="thickbox thickbox-preview" title="' . esc_attr( sprintf( __( 'View version %1$s details' ), $latest_version ) ) . '">' . esc_attr( sprintf( __( 'View version %1$s details' ), $latest_version ) ) . '</a>';
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
        $content.= '<div class="update-info"><span>' . __( 'Options:' ) . '</span> ' . implode( ' | ', $options ) . '</div>';
    }
    
    /* Theme path information */
    if ( current_user_can( 'edit_themes' ) && $installed ) {
      if ( $parent_theme ) {
         $content.= '<p>' . sprintf( __( 'The template files are located in <code>%2$s</code>. The stylesheet files are located in <code>%3$s</code>. <strong>%4$s</strong> uses templates from <strong>%5$s</strong>. Changes made to the templates will affect both themes.' ), $title, str_replace( WP_CONTENT_DIR, '', $template_dir ), str_replace( WP_CONTENT_DIR, '', $stylesheet_dir ), $title, $parent_theme ) . '</p>';
      } else {
         $content.= '<p>' . sprintf( __( 'All of this theme&#8217;s files are located in <code>%2$s</code>.' ), $title, str_replace( WP_CONTENT_DIR, '', $template_dir ), str_replace( WP_CONTENT_DIR, '', $stylesheet_dir ) ) . '</p>';
      }
    }
    
    /* Tags list */
    if ( $tags ) {
      $content.= '<p>' . __( 'Tags: ' ). join( ', ', $tags ) . '</p>';
    }
    
    /* Upgrade/Install message */
    if ( $has_update ) {
      if ( ! current_user_can( 'update_themes' ) ) {
        $content.= sprintf( '<div class="updated below-h2"><p><strong>' . __('There is a new version of %1$s available. <a href="%2$s" class="thickbox thickbox-preview" title="%1$s">View version %3$s details</a>.') . '</strong></p></div>', $title, $details_url, $latest_version );
      } else {
        $content.= sprintf( '<div class="updated below-h2"><p><strong>' . __('There is a new version of %1$s available. <a href="%2$s" class="thickbox thickbox-preview" title="%1$s">View version %3$s details</a> or <a href="%4$s" %5$s>update automatically</a>.') . '</strong></p></div>', $title, $details_url, $latest_version, $update_url, $update_onclick );
      }
    } else if ( ! $installed ) {
      if ( ! current_user_can( 'update_themes' ) ) {
        $content.= sprintf( '<div class="updated below-h2"><p><strong>' . __('%1$s has not been installed. <a href="%2$s" class="thickbox thickbox-preview" title="%1$s">View version %3$s details</a>.') . '</strong></p></div>', $title, $details_url, $latest_version );
      } else {
        $content.= sprintf( '<div class="updated below-h2"><p><strong>' . __('%1$s has not been installed. <a href="%2$s" class="thickbox thickbox-preview" title="%1$s">View version %3$s details</a> or <a href="%4$s">install automatically</a>.') . '</strong></p></div>', $title, $details_url, $latest_version, $install_url );
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
      echo '<ul class="item-list">';
        echo $current_theme['list_item'];
      echo '</ul>';
    }
    
    /* list premium themes */
    _e( '<h3>Available Themes</h3>' );
    echo '<ul class="item-list">';
    foreach ( $premium_themes as $k => $v )
      echo $premium_themes[$k]['list_item'];
    echo '</ul>';
  }
}

/* End of file themes.php */
/* Location: ./views/themes.php */