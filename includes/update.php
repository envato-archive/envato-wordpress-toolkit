<?php if ( ! defined( 'EWPU_PLUGIN_VER') ) exit( 'No direct script access allowed' );
/**
 * Envato Update
 *
 * This class is used to update themes via the Envato Marketplace API.
 *
 * @package     Envato WordPress Updater
 * @author      Derek Herman <derek@valendesigns.com>
 * @since       1.0
 */
class Envato_Update {
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
    /* Uncomment code below for testing */
    /* set_site_transient( 'update_themes', null ); */
    
    /* We need to edit the version details URL due to a weird bug in WordPress MU. */
    if ( is_multisite() ) {
      $this->_filter_update_messages();
    }

    /**
     * Hooks into the update_themes filter before it's executed and 
     * runs a check on the marketplace API for new theme versions.
     */
    add_filter( 'pre_set_site_transient_update_themes', array( &$this, '_update_themes' ) );
  }

  /**
   * Return an array of purchased marketplace themes.
   *
   * @uses     get_themes()  Returns an array of purchased themes.
   *
   * @return   bool
   *
   * @access   private
   * @since    1.0
   */
  function _get_themes() {
    $themes = get_themes();
    $theme_names = array_keys($themes);
    
    if ( $themes )
      foreach ( (array) $theme_names as $theme_name )
        if ( $themes[$theme_name]['Author Name'] !== 'Derek Herman' )
          unset($themes[$theme_name]);
    
    return $themes;  
  }
  
  /**
   * Check if a theme has been verified
   *
   * It only checks to see if the form vales exist, it does not test if the 
   * item has actually been purchased. That is done on api.valendesigns.com
   *
   * @param    string   The theme slug
   * @return   bool
   *
   * @access   private
   * @since    1.0
   */
  function _is_verified( $theme ) {
    $themes = get_site_option( 'motif_themes' );
  
    if ( empty( $themes ) )
      return false;
    
    if ( $theme != 'motif' && $themes[$theme]['verify'] && $themes[$theme]['item_id'] )
      return true;
      
    if ( $theme == 'motif' )
      foreach( $themes as $k => $v )
        if ( $themes[$k]['verify'] && $themes[$k]['item_id'] )
          return true;

    return false;     
  }
  
  
  /**
   * Adds filters to modify upgrade messages
   *
   * @return   void
   *
   * @access   private
   * @since    1.0
   */
  function _filter_update_messages() {
    $themes = $this->_get_themes();
    $theme_names = array_keys($themes);
    
    if ( $themes )
      foreach ( (array) $theme_names as $theme_name )
        add_filter( "in_theme_update_message-".$themes[$theme_name]['Stylesheet'], array( &$this, '_filter_update_message' ), 10, 2 );
  }
  
  /**
   * Prints JavaScript to filter upgrade message URL
   *
   * Hopefully in the future WordPress will patch this issue
   * and I can remove this function entirely.
   *
   * @param    array    An array of themes being updated
   * @param    array    The current response from 'update_themes'
   * @return   string
   *
   * @access   private
   * @since    1.0
   */
  function _filter_update_message( $theme, $r ) {
    $theme_name   = is_object($theme) ? $theme->name : (is_array($theme) ? $theme['Name'] : '');
    $details_url  = add_query_arg(array('TB_iframe' => 'true', 'width' => 1024, 'height' => 800), $r['url']);
    $text         = sprintf( __( 'View version %1$s details' ), $r['new_version'] );
    echo '
    <script type="text/javascript">
    jQuery(function($){
      $("a[title=\''.$theme_name.'\'].thickbox").each(function(){
        if ( $(this).text() == "'.$text.'" )
          $(this).attr("href", "'.$details_url.'");
      });
    });
    </script>
    ';
  }

  /**
   * Check theme versions against the latest versions hosted on api.valendesigns.com
   *
   * Build a list of all themes installed & authored by Derek Herman. Then checks against
   * the private server at api.valendesigns.com for an update.
   *
   * @param    array    The array that is used to update themes
   * @return   mixed    Returns the $checked_data array with/without new themes for updating
   *
   * @access   private
   * @since    1.0
   */
  function _update_themes( $checked_data ) {
    global $wp_version;
    
    if ( empty( $checked_data->checked) || !$my_themes = $this->_get_themes() )
      return $checked_data;

    $themes = array();
    $exclude_fields = array( 'Title', 'Description', 'Template Files', 'Stylesheet Files', 'Template Dir', 'Stylesheet Dir', 'Status', 'Screenshot', 'Tags', 'Theme Root', 'Theme Root URI' );
    $motif_themes = get_site_option( 'motif_themes' );
    
    foreach ( (array) $my_themes as $theme_title => $theme ) {
      if ( $this->_is_verified( $theme['Stylesheet'] ) ) {
        $themes[$theme['Stylesheet']] = array();
        
        if ( $theme['Stylesheet'] == 'motif' ) {
          foreach( $motif_themes as $k => $v ) {
            if ( $motif_themes[$k]['item_id'] && $motif_themes[$k]['verify'] ) {
              $item_id = $motif_themes[$k]['item_id'];
              $verify = $motif_themes[$k]['verify'];
            }   
          }
        }
        
        $themes[$theme['Stylesheet']]['ItemID']   = ( $theme['Stylesheet'] == 'motif' ) ? $item_id : $motif_themes[$theme['Stylesheet']]['item_id'];
        $themes[$theme['Stylesheet']]['Verify']   = ( $theme['Stylesheet'] == 'motif' ) ? $verify : $motif_themes[$theme['Stylesheet']]['verify'];
        
        foreach ( (array) $theme as $key => $value )
          if ( !in_array($key, $exclude_fields) )
            $themes[$theme['Stylesheet']][$key] = $value;
      }
    }

    $options = array(
      'body' => array( 'themes' => serialize( $themes ) ),
      'user-agent' => 'WordPress/' . $wp_version . ' ' . home_url()
    );
  
    $raw_response = wp_remote_post( 'http://api.valendesigns.com/themes/update-check/1.0/', $options );
    
    if ( is_wp_error( $raw_response ) )
      return false;
    
    if ( 200 != $raw_response['response']['code'] )
      return false;
    
    $response = unserialize( $raw_response['body'] );
    
    if ( $response )
      foreach( $response as $array )
        foreach( $array as $theme_title => $theme )
          $checked_data->response[$theme_title] = $theme;
    
    return $checked_data;
  }

}

/**
 * Holds the Envato Update object
 *
 * @since   1.0
 * @global  object
 */
$envato_update =& new Envato_Update();

/* End of file update.php */
/* Location: ./includes/update.php */