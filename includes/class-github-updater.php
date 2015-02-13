<?php
// Prevent loading this file directly and/or if the class is already defined
if ( ! defined( 'ABSPATH' ) || class_exists( 'WP_GitHub_Updater' ) )
  return;

/**
 * Updates the Envato WordPress Toolkit via Guthub.
 *
 * This is a derivitive work of Joachim Kudish's `WP_GitHub_Updater`. 
 * It has been heavily modified to work with this plugin specifically.
 */
class WP_GitHub_Updater {

  /**
   * GitHub Updater version
   */
  const VERSION = EWPT_PLUGIN_VER;

  /**
   * @var $config the config for the updater
   * @access public
   */
  var $config;

  /**
   * @var $missing_config any config that is missing from the initialization of this instance
   * @access public
   */
  var $missing_config;

  /**
   * @var $github_data temporiraly store the data fetched from GitHub, allows us to only load the data once per class instance
   * @access private
   */
  private $github_data;

  /**
   * Class Constructor
   *
   * @since 1.0
   * @param array $config the configuration required for the updater to work
   * @see has_minimum_config()
   * @return void
   */
  public function __construct( $config = array() ) {

    $defaults = array(
      'slug' => plugin_basename( __FILE__ ),
      'plugin' => plugin_basename( __FILE__ ),
      'proper_folder_name' => dirname( plugin_basename( __FILE__ ) ),
      'sslverify' => true,
      'access_token' => '',
    );

    $this->config = wp_parse_args( $config, $defaults );

    // if the minimum config isn't set, issue a warning and bail
    if ( ! $this->has_minimum_config() ) {
      $message = 'The GitHub Updater was initialized without the minimum required configuration, please check the config in your plugin. The following params are missing: ';
      $message .= implode( ',', $this->missing_config );
      _doing_it_wrong( __CLASS__, $message , self::VERSION );
      return;
    }

    $this->set_defaults();

    add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'api_check' ) );

    // Hook into the plugin details screen
    add_filter( 'plugins_api', array( $this, 'get_plugin_info' ), 10, 3 );
    add_filter( 'upgrader_source_selection', array( $this, 'upgrader_source_selection' ) );

    // set timeout
    add_filter( 'http_request_timeout', array( $this, 'http_request_timeout' ) );

    // set sslverify for zip download
    add_filter( 'http_request_args', array( $this, 'http_request_sslverify' ), 10, 2 );
  }

  public function has_minimum_config() {

    $this->missing_config = array();

    $required_config_params = array(
      'api_url',
      'raw_url',
      'github_url',
      'zip_url'
    );

    foreach ( $required_config_params as $required_param ) {
      if ( empty( $this->config[$required_param] ) )
        $this->missing_config[] = $required_param;
    }

    return ( empty( $this->missing_config ) );
  }


  /**
   * Check wether or not the transients need to be overruled and API needs to be called for every single page load
   *
   * @return bool overrule or not
   */
  public function overrule_transients() {
    return ( defined( 'WP_GITHUB_FORCE_UPDATE' ) && WP_GITHUB_FORCE_UPDATE );
  }


  /**
   * Set defaults
   *
   * @since 1.2
   * @return void
   */
  public function set_defaults() {
    if ( ! empty( $this->config['access_token'] ) ) {

      // See Downloading a zipball (private repo) https://help.github.com/articles/downloading-files-from-the-command-line
      extract( parse_url( $this->config['zip_url'] ) ); // $scheme, $host, $path

      $zip_url = $scheme . '://api.github.com/repos' . $path;
      $zip_url = add_query_arg( array( 'access_token' => $this->config['access_token'] ), $zip_url );

      $this->config['zip_url'] = $zip_url;
    }

    if ( ! isset( $this->config['readme'] ) )
      $this->config['readme'] = 'readme.txt';
      
    // Get the contents of the readme.txt file
    $this->readme = $this->get_readme();

    if ( ! isset( $this->config['new_version'] ) )
      $this->config['new_version'] = $this->get_readme_header_info( 'Stable tag' );

    if ( ! isset( $this->config['tested'] ) )
      $this->config['tested'] = $this->get_readme_header_info( 'Tested up to' );

    if ( ! isset( $this->config['requires'] ) )
      $this->config['requires'] = $this->get_readme_header_info( 'Requires at least' );

    if ( ! isset( $this->config['last_updated'] ) )
      $this->config['last_updated'] = $this->get_date();
    
    $plugin_data = $this->get_plugin_data();
    if ( ! isset( $this->config['description'] ) )
      $this->config['description'] = $plugin_data['Description'];

    if ( ! isset( $this->config['plugin_name'] ) )
      $this->config['plugin_name'] = $plugin_data['Name'];

    if ( ! isset( $this->config['version'] ) )
      $this->config['version'] = $plugin_data['Version'];

    if ( ! isset( $this->config['author'] ) )
      $this->config['author'] = $plugin_data['Author'];

    if ( ! isset( $this->config['homepage'] ) )
      $this->config['homepage'] = $plugin_data['PluginURI'];
  }

  /**
   * Callback fn for the http_request_timeout filter
   *
   * @since 1.0
   * @return int timeout value
   */
  public function http_request_timeout() {
    return 2;
  }

  /**
   * Callback fn for the http_request_args filter
   *
   * @param unknown $args
   * @param unknown $url
   *
   * @return mixed
   */
  public function http_request_sslverify( $args, $url ) {
    if ( $this->config[ 'zip_url' ] == $url )
      $args[ 'sslverify' ] = $this->config[ 'sslverify' ];

    return $args;
  }

  /**
   * Get readme.txt from github
   *
   * @since 1.7.2
   * @return int $readme The readme.txt contents
   */
  public function get_readme() {
    $readme = get_site_transient( 'ewt_readme' );

    if ( $this->overrule_transients() || ( ! isset( $readme ) || ! $readme || '' == $readme ) ) {

      $raw_url = trailingslashit( $this->config['raw_url'] ) . $this->config['readme'];
      $raw_response = $this->remote_get( $raw_url );

      if ( is_wp_error( $raw_response ) )
        return false;

      if ( is_array( $raw_response ) && ! empty( $raw_response['body'] ) ) {
        set_site_transient( 'ewt_readme', $raw_response['body'], 3600 );
      }
  
    }

    return $readme;
  }

  /**
   * Get readme header info
   *
   * @since 1.7.2
   * @return mixed
   */
  public function get_readme_header_info( $search = '' ) {
    if ( '' != $search && '' != $this->readme ) {
      preg_match( '#' . $search . '\:\s*(.*)$#im', $this->readme, $matches );
      if ( ! empty( $matches[1] ) ) {
        return $matches[1];
      }  
    }
    return false;
  }

  /**
   * Interact with GitHub
   *
   * @param string $query
   *
   * @since 1.6
   * @return mixed
   */
  public function remote_get( $query ) {
    if ( ! empty( $this->config['access_token'] ) )
      $query = add_query_arg( array( 'access_token' => $this->config['access_token'] ), $query );

    $raw_response = wp_remote_get( $query, array(
      'sslverify' => $this->config['sslverify']
    ) );

    return $raw_response;
  }

  /**
   * Get GitHub Data from the specified repository
   *
   * @since 1.0
   * @return array $github_data the data
   */
  public function get_github_data() {
    if ( isset( $this->github_data ) && ! empty( $this->github_data ) ) {
      $github_data = $this->github_data;
    } else {
      $github_data = get_site_transient( 'ewt_github_data' );

      if ( $this->overrule_transients() || ( ! isset( $github_data ) || ! $github_data || '' == $github_data ) ) {
        $github_data = $this->remote_get( $this->config['api_url'] );

        if ( is_wp_error( $github_data ) )
          return false;

        $github_data = json_decode( $github_data['body'] );

        // refresh every hour
        set_site_transient( 'ewt_github_data', $github_data, 3600 );
      }

      // Store the data in this class instance for future calls
      $this->github_data = $github_data;
    }

    return $github_data;
  }

  /**
   * Get update date
   *
   * @since 1.0
   * @return string $date the date
   */
  public function get_date() {
    $_date = $this->get_github_data();
    return ( !empty( $_date->updated_at ) ) ? date( 'Y-m-d', strtotime( $_date->updated_at ) ) : false;
  }

  /**
   * Get Plugin data
   *
   * @since 1.0
   * @return object $data the data
   */
  public function get_plugin_data() {
    include_once ABSPATH.'/wp-admin/includes/plugin.php';
    $data = get_plugin_data( WP_PLUGIN_DIR . '/' . $this->config['slug'] );
    return $data;
  }

  /**
   * Hook into the plugin update check and connect to github
   *
   * @since 1.0
   * @param object  $transient the plugin data transient
   * @return object $transient updated plugin data transient
   */
  public function api_check( $transient ) {

    // Check if the transient contains the 'checked' information
    // If not, just return its value without hacking it
    if ( empty( $transient->checked ) )
      return $transient;

    // check the version and decide if it's new
    $update = version_compare( $this->config['new_version'], $this->config['version'] );

    if ( 1 === $update ) {
      $response = new stdClass;
      $response->new_version = $this->config['new_version'];
      $response->slug = $this->config['proper_folder_name'];
      $response->plugin = $this->config['plugin'];
      $response->url = add_query_arg( array( 'access_token' => $this->config['access_token'] ), $this->config['github_url'] );
      $response->package = $this->config['zip_url'];

      // If response is false, don't alter the transient
      if ( false !== $response )
        $transient->response[ $this->config['slug'] ] = $response;
    }

    return $transient;
  }

  /**
   * Get Plugin info
   *
   * @since 1.0
   * @param bool    $false  always false
   * @param string  $action the API function being performed
   * @param object  $args   plugin arguments
   * @return object $response the plugin info
   */
  public function get_plugin_info( $false, $action, $response ) {

    // Include plugin updates for `list_plugin_updates()`
    if ( $action == 'plugin_information' && isset( $response->slug ) && $response->slug == dirname( $this->config['slug'] ) ) {
      $found_it = true;
    }
    
    // Check if this call API is for the right plugin
    if ( ! isset( $found_it ) && ( ! isset( $response->slug ) || $response->slug != $this->config['slug'] ) )
      return $false;

    $response->slug = $this->config['slug'];
    $response->plugin = $this->config['plugin'];
    $response->plugin_name = $this->config['plugin_name'];
    $response->version = $this->config['new_version'];
    $response->author = $this->config['author'];
    $response->homepage = $this->config['homepage'];
    $response->requires = $this->config['requires'];
    $response->tested = $this->config['tested'];
    $response->downloaded = 0;
    $response->last_updated = $this->config['last_updated'];
    $response->sections = array( 'description' => $this->config['description'] );
    $response->download_link = $this->config['zip_url'];

    return $response;
  }

  /**
   * Filter the source file location for the upgrade package.
   *
   * @since 1.0
   * @param string      $source        File source location.
   */
  public function upgrader_source_selection( $source ) {
    
    // Fix the destination path
    if ( strpos( $source, $this->config['proper_folder_name'] . '-master' ) !== false ) {
      global $wp_filesystem;
      $proper_source = str_replace( '-master', '', $source );
      $wp_filesystem->move( $source, $proper_source );
      $source = $proper_source;
    }
    
    return $source;

  }

}

/* End of file class-github-updater.php */
/* Location: ./includes/class-github-updater.php */