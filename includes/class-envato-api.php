<?php if ( ! defined( 'EWPT_PLUGIN_VER') ) exit( 'No direct script access allowed' );
/**
 * Envato Protected API
 *
 * Wrapper class for the Envato marketplace protected API methods specific
 * to the Envato WordPress Toolkit plugin.
 *
 * @package     WordPress
 * @subpackage  Envato WordPress Toolkit
 * @author      Derek Herman <derek@envato.com>
 * @since       1.0
 */ 
class Envato_Protected_API {
  /**
   * The buyer's Username
   *
   * @since   1.0
   * @access  public
   *
   * @var     string
   */
  public $user_name;
  
  /**
   * The buyer's API Key
   *
   * @since   1.0
   * @access  public
   *
   * @var     string
   */
  public $api_key;
  
  /**
   * The default API URL
   *
   * @since   1.0
   * @access  private
   *
   * @var     string
   */
  protected $public_url = 'http://marketplace.envato.com/api/edge/set.json';
  
  /**
   * Error messages
   *
   * @since   1.0
   * @access  public
   *
   * @var     array
   */
  public $errors = array( 'errors' => '' );
  
  /**
   * Class contructor method
   *
   * @param   string        The buyer's Username
   * @param   string        The buyer's API Key can be accessed on the marketplaces via My Account -> My Settings -> API Key
   * @return  array|void    Returns error messages if any, or void.
   */
  public function __construct( $user_name = '', $api_key = '' ) {
    if ( $user_name == '' )
      $this->set_error( 'user_name', __( 'Please enter your Envato Marketplace Username.', 'envato' ) );
      
    if ( $api_key == '' )
      $this->set_error( 'api_key', __( 'Please enter your Envato Marketplace API Key.', 'envato' ) );
      
    $this->user_name  = $user_name;
    $this->api_key    = $api_key;
  }
  
  /**
   * Get private user data
   *
   * @since   1.0
   * @access  public
   *
   * @param   string        Available sets: 'vitals', 'earnings-and-sales-by-month', 'statement', 'recent-sales', 'account', 'verify-purchase', 'download-purchase', 'wp-list-themes', 'wp-download'
   * @param   string        The buyer/author username to test against.
   * @param   string        Additional set data such as purchase code or item id.
   * @return  array         An array of values from the requested set, or an error message.
   */ 
  public function private_user_data( $set = '', $user_name = '', $set_data = '' ) { 
    if ( $set == '' )
      $this->set_error( 'set', __( 'The API "set" is a required parameter.', 'envato' ) );
      
    if ( $user_name == '' )
      $user_name = $this->user_name;
      
    if ( $set_data !== '' ) 
      $set_data = ":$set_data";
      
    $url = "http://marketplace.envato.com/api/edge/$user_name/$this->api_key/$set$set_data.json";

    $result = $this->curl( $url );
    
    if ( isset( $result->error ) ) 
      $this->set_error( 'result', $result->error );
    
    if ( $errors = $this->api_errors() )
      return $errors;
    else
      return $result->$set;
  }
  
  /**
   * Used to list purchased themes.
   *
   * @since   1.0
   * @access  public
   *
   * @return  object        If purchased themes, returns an object containing those details.
   */ 
  public function wp_list_themes() {
    return $this->private_user_data( 'wp-list-themes', $this->user_name );
  }
  
  /**
   * Used to download a purchased item.
   *
   * @since   1.0
   * @access  public
   *
   * @param   string        The purchased items id
   * @return  string|bool   If item purchased, returns the download URL.
   */ 
  public function wp_download( $item_id ) {
    if ( ! isset( $item_id ) )
      $this->set_error( 'item_id', __( 'The Envato Marketplace "item ID" is a required parameter.', 'envato' ) );
      
    $download = $this->private_user_data( 'wp-download', $this->user_name, $item_id );
    
    if ( $errors = $this->api_errors() )
      return $errors;
    else
      return isset( $download->url ) ? $download->url : false;
  }
  
  /**
   * Retrieve the details for a specific marketplace item.
   *
   * @since   1.0
   * @access  public
   *
   * @param string $item_id The id of the item you need information for. 
   * @return object Details for the given item.
   */
  public function item_details( $item_id ) {
    $url = preg_replace( '/set/i', 'item:' . $item_id, $this->public_url );
    return $this->curl( $url )->item;
  }
   
  /**
   * Helper function to set error messages.
   *
   * @since   1.0
   * @access  private
   *
   * @param   string        The error array id.
   * @param   string        The error message.
   * @return  void
   */
  public function set_error( $id, $error ) {
    $this->errors['errors'][$id] = $error;
  }
  
  /**
   * Helper function to return errors.
   *
   * @since   1.0
   * @access  private
   *
   * @return  array         The errors array.
   */
  public function api_errors() {
    if ( ! empty( $this->errors['errors'] ) )
      return $this->errors['errors'];
  }
  
  /**
   * Helper function to query the marketplace API via CURL.
   *
   * @since   1.0
   * @access  private
   *
   * @param   string        The url to access.
   * @return  object        The results of the curl request.
   */
  protected function curl( $url ) {
    if ( empty( $url ) ) 
      return false;

    $ch = curl_init( $url );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

    $data = curl_exec( $ch );
    $info = curl_getinfo( $ch );
    curl_close( $ch );
    
    $data = json_decode( $data );
    
    if ( $info['http_code'] == 200 ) 
      return $data;
    else
      $this->set_error( 'http_code', $info['http_code'] );
      
    if ( isset( $data->error ) )
      $this->set_error( 'api_error', $data->error ); 
  }
  
  /**
   * Helper function to print arrays to the screen
   *
   * @since   1.0
   * @access  public
   *
   * @param   array         The array to print out
   * @return  string
   */
  public function pretty_print( $array ) {
    echo '<pre>';
    print_r( $array );
    echo '</pre>';
  }
}

/* End of file class-envato-api.php */
/* Location: ./includes/class-envato-api.php */