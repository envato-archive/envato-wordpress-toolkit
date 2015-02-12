<?php if ( ! defined( 'EWPT_PLUGIN_VER') ) exit( 'No direct script access allowed' );
/**
 * Envato file backup class
 *
 * Original class created by Human Made Limited http://hmn.md/. This version has 
 * removed database backups and altered namespacing for inclusion in the 
 * Envato WordPress Toolkit plugin to prevent conflicts.
 *
 * @package     WordPress
 * @subpackage  Envato WordPress Toolkit
 * @author      Derek Herman <derek@envato.com>, Human Made Limited
 * @since       1.4
 */
if ( ! class_exists( 'Envato_Backup' ) ) {

  class Envato_Backup {
    /**
     * The path where the backup file should be saved
     *
     * @access    public
     * @since     1.4
     *
     * @var       string
     */
    public $path;
    
    /**
     * The filename of the backup file
     *
     * @access    public
     * @since     1.4
     *
     * @var       string
     */
    public $archive_filename;
    
    /**
     * The path to the zip command
     *
     * @access    public
     * @since     1.4
     *
     * @var       string
     */
    public $zip_command_path;
    
    /**
     * An array of exclude rules
     *
     * @access    public
     * @since     1.4
     *
     * @var       array
     */
    public $excludes;
    
    /**
     * The path that should be backed up
     *
     * @access    public
     * @since     1.4
     *
     * @var       string
     */
    public $root;
    
    /**
     * Store the current backup instance
     *
     * @access    public
     * @since     1.4
     *
     * @var       object
     * @static
     */
    private static $instance;
    
    /**
     * An array of all the files in root
     * excluding excludes
     *
     * @access    private
     * @since     1.4
     *
     * @var       array
     */
    private $files;
    
    /**
     * Contains an array of error
     *
     * @access    private
     * @since     1.4
     *
     * @var       mixed
     */
    private $errors;
    
    /**
     * Contains an array of warnings
     *
     * @access    private
     * @since     1.4
     *
     * @var       mixed
     */
    private $warnings;
    
    /**
     * The archive method used
     *
     * @access    private
     * @since     1.4
     *
     * @var       string
     */
    private $archive_method;
  
    /**
     * PHP5 constructor method.
     *
     * Sets up the default properties
     *
     * @access    public
     * @since     1.4
     *
     * @return    null
     */
    public function __construct() {
    
      /* Raise the memory limit and max_execution_time time */
      @ini_set( 'memory_limit', apply_filters( 'admin_memory_limit', WP_MAX_MEMORY_LIMIT ) );
      @set_time_limit( 0 );
      
      $this->errors = array();
      
      set_error_handler( array( $this, 'error_handler' ) );
      
      /* Defaults */
      $this->root = $this->conform_dir( ABSPATH );
      
      $this->path = $this->conform_dir( WP_CONTENT_DIR . '/envato-backups' );
      
      $this->archive_filename = strtolower( sanitize_file_name( get_bloginfo( 'name' ) . '.backup.' . date( 'Y-m-d-H-i-s', time() + ( current_time( 'timestamp' ) - time() ) ) . '.zip' ) );
      
      $this->zip_command_path = $this->guess_zip_command_path();
      
    }
    
    /**
     * Return the current instance
     *
     * @access    public
     * @since     1.4
     *
     * @static
     * @return    object
     */
    public static function get_instance() {
    
      if ( empty( self::$instance ) )
        self::$instance = new Envato_Backup();
    
      return self::$instance;
    
    }
    
    /**
     * The full filepath to the archive file.
     *
     * @access    public
     * @since     1.4
     *
     * @return    string
     */
    public function archive_filepath() {
      return trailingslashit( $this->path() ) . $this->archive_filename();
    }
  
    /**
     * Helper function to sanitize archive filename.
     *
     * @access    public
     * @since     1.4
     *
     * @return    string
     */
    public function archive_filename() {
      return strtolower( sanitize_file_name( remove_accents( $this->archive_filename ) ) );
    }
    
    /**
     * Helper function to sanitize the root directory path.
     *
     * @access    public
     * @since     1.4
     *
     * @return    string
     */
    public function root() {
      return $this->conform_dir( $this->root );
    }
    
    /**
     * Helper function to sanitize the archive directory path.
     *
     * @access    public
     * @since     1.4
     *
     * @return    string
     */
    public function path() {
      return $this->conform_dir( $this->path );
    }
    
    /**
     * Helper function to return the archive method.
     *
     * @access    public
     * @since     1.4
     *
     * @return    string
     */
    public function archive_method() {
      return $this->archive_method;
    }
    
    /**
     * Kick off a backup
     *
     * @access    public
     * @since     1.4
     *
     * @return    bool
     */
    public function backup() {
    
      do_action( 'envato_backup_started', $this );
    
      /* Zip everything up */
      $this->archive();
    
      do_action( 'envato_backup_complete', $this );
    
    }
  
    /**
     * Zip up all the files.
     *
     * Attempts to use the shell zip command, if
     * thats not available then it fallsback to
     * PHP ZipArchive and finally PclZip.
     *
     * @access    public
     * @since     1.4
     *
     * @return    null
     */
    public function archive() {
    
      do_action( 'envato_archive_started' );
      
      /* Do we have the path to the zip command */
      if ( $this->zip_command_path )
        $this->zip();
      
      /* If not or if the shell zip failed then use ZipArchive */
      if ( empty( $this->archive_verified ) && class_exists( 'ZipArchive' ) && empty( $this->skip_zip_archive ) )
        $this->zip_archive();
      
      /* If ZipArchive is unavailable or one of the above failed */
      if ( empty( $this->archive_verified ) )
        $this->pcl_zip();
      
      do_action( 'envato_archive_finished' );
    
    }
  
    /**
     * Zip using the native zip command
     *
     * @access    public
     * @since     1.4
     *
     * @return    null
     */
    public function zip() {
    
      $this->archive_method = 'zip';
    
      $this->warning( $this->archive_method, shell_exec( 'cd ' . escapeshellarg( $this->root() ) . ' && ' . escapeshellarg( $this->zip_command_path ) . ' -rq ' . escapeshellarg( $this->archive_filepath() ) . ' ./' . ' 2>&1' ) );
    
      $this->check_archive();
    
    }
  
    /**
     * Fallback for creating zip archives if zip command is unnavailable.
     *
     * @access    public
     * @since     1.4
     *
     * @param     string    $path
     * @return    null
     */
    public function zip_archive() {
    
      $this->errors_to_warnings( $this->archive_method );
      $this->archive_method = 'ziparchive';
      
      if ( ! class_exists( 'ZipArchive' ) )
        return;

      $zip = new ZipArchive();
      
      if ( ! $zip->open( $this->archive_filepath(), ZIPARCHIVE::CREATE ) )
        return;
      
      $files_added = 0;
      
      foreach ( $this->files() as $file ) {
      
        if ( is_dir( trailingslashit( $this->root() ) . $file ) )
          $zip->addEmptyDir( trailingslashit( $file ) );
      
        elseif ( is_file( trailingslashit( $this->root() ) . $file ) )
          $zip->addFile( trailingslashit( $this->root() ) . $file, $file );
      
        if ( ++$files_added % 500 === 0 )
          if ( ! $zip->close() || ! $zip->open( $this->archive_filepath(), ZIPARCHIVE::CREATE ) )
            return;
      
      }
      
      if ( $zip->status )
        $this->warning( $this->archive_method, $zip->status );
      
      if ( $zip->statusSys )
        $this->warning( $this->archive_method, $zip->statusSys );
      
      $zip->close();
      
      $this->check_archive();
    
    }
  
    /**
     * Fallback for creating zip archives if both zip command 
     * and ZipArchive are unnavailable.
     *
     * Uses the PclZip library that ships with WordPress
     *
     * @access    public
     * @since     1.4
     *
     * @param     string    $path
     * @return    null
     */
    public function pcl_zip() {
    
      $this->errors_to_warnings( $this->archive_method );
      $this->archive_method = 'pclzip';
      
      global $_envato_backup_exclude_string;
      
      $_envato_backup_exclude_string = $this->exclude_string( 'regex' );
      
      $this->load_pclzip();
      
      $archive = new PclZip( $this->archive_filepath() );
      
      /* Zip up everything */
      if ( ! $archive->add( $this->root(), PCLZIP_OPT_REMOVE_PATH, $this->root(), PCLZIP_CB_PRE_ADD, array( $this, 'pcl_zip_callback' ) ) )
        $this->warning( $this->archive_method, $archive->errorInfo( true ) );
      
      unset( $GLOBALS['_envato_backup_exclude_string'] );
      
      $this->check_archive();
    
    }

    /**
     * Add file callback for PclZip, excludes files
     * and sets the database dump to be stored in the root
     * of the zip
     *
     * @access    private
     * @since     1.4
     * @since     1.7.2 (moved into class)
     *
     * @param     string    $event
     * @param     array     &$file
     * @return    bool
     */
    function pcl_zip_callback( $event, &$file ) {

      global $_envato_backup_exclude_string;

      /* Don't try to add unreadable files. */
      if ( ! is_readable( $file['filename'] ) || ! file_exists( $file['filename'] ) )
      return false;

      /* Match everything else past the exclude list */
      elseif ( $_envato_backup_exclude_string && preg_match( '(' . $_envato_backup_exclude_string . ')', $file['stored_filename'] ) )
      return false;

      return true;

    }
  
    /**
     * Verify that the archive is valid and contains all the files it should contain.
     *
     * @access    public
     * @since     1.4
     *
     * @return    bool
     */
    public function check_archive() {
    
      /* If we've already passed then no need to check again */
      if ( ! empty( $this->archive_verified ) )
        return true;
      
      if ( ! file_exists( $this->archive_filepath() ) )
        $this->error( $this->archive_method(), __( 'The backup file was not created', 'envato-wordpress-toolkit' ) );
      
      /* Verify using the zip command if possible */
      if ( $this->zip_command_path ) {
      
        $verify = shell_exec( escapeshellarg( $this->zip_command_path ) . ' -T ' . escapeshellarg( $this->archive_filepath() ) . ' 2> /dev/null' );
      
        if ( strpos( $verify, 'OK' ) === false )
          $this->error( $this->archive_method(), $verify );
      
      }
      
      /* If there are errors delete the backup file. */
      if ( $this->errors( $this->archive_method() ) && file_exists( $this->archive_filepath() ) )
        unlink( $this->archive_filepath() );
      
      if ( $this->errors( $this->archive_method() ) )
        return false;
      
      return $this->archive_verified = true;
    
    }
  
    /**
     * Generate the array of files to be backed up by looping through
     * root, ignored unreadable files and excludes
     *
     * @access    public
     * @since     1.4
     *
     * @return    array
     */
    public function files() {
    
      if ( ! empty( $this->files ) )
        return $this->files;
      
      $this->files = array();
      
      if ( defined( 'RecursiveDirectoryIterator::FOLLOW_SYMLINKS' ) ) {
      
        $filesystem = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $this->root(), RecursiveDirectoryIterator::FOLLOW_SYMLINKS ), RecursiveIteratorIterator::SELF_FIRST, RecursiveIteratorIterator::CATCH_GET_CHILD );
      
        $excludes = $this->exclude_string( 'regex' );
      
        foreach ( $filesystem as $file ) {
      
          if ( ! $file->isReadable() ) {
            $this->unreadable_files[] = $file->getPathName();
            continue;
          }
      
          $pathname = str_ireplace( trailingslashit( $this->root() ), '', $this->conform_dir( $file->getPathname() ) );
      
          /* Excludes */
          if ( $excludes && preg_match( '(' . $excludes . ')', $pathname ) )
            continue;
      
          $this->files[] = $pathname;
      
        }
      
      } else {
      
        $this->files = $this->files_fallback( $this->root() );
      
      }
      
      if ( ! empty( $this->unreadable_files ) )
        $this->warning( $this->archive_method(), __( 'The following files are unreadable and could not be backed up: ', 'envato-wordpress-toolkit' ) . implode( ', ', $this->unreadable_files ) );
      
      return $this->files;
    
    }
  
    /**
     * Fallback function for generating a filesystem array
     *
     * Used if RecursiveDirectoryIterator::FOLLOW_SYMLINKS isn't available
     *
     * @access    private
     * @since     1.4
     *
     * @param     string    $dir
     * @param     array     $files. (default: array())
     * @return    array
     */
    private function files_fallback( $dir, $files = array() ) {
      
      $handle = opendir( $dir );
      
      $excludes = $this->exclude_string( 'regex' );
      
      while ( $file = readdir( $handle ) ) :
      
        /* Ignore current dir and containing dir and any unreadable files or directories */
        if ( $file == '.' || $file == '..' )
          continue;
      
        $filepath = $this->conform_dir( trailingslashit( $dir ) . $file );
        $file = str_ireplace( trailingslashit( $this->root() ), '', $filepath );
      
        if ( ! is_readable( $filepath ) ) {
          $this->unreadable_files[] = $filepath;
          continue;
        }
      
        /* Skip the backups dir and any excluded paths */
        if ( ( $excludes && preg_match( '(' . $excludes . ')', $file ) ) )
          continue;
      
        $files[] = $file;
      
        if ( is_dir( $filepath ) )
          $files = $this->files_fallback( $filepath, $files );
      
      endwhile;
      
      return $files;
    
    }
    
    /**
     * Helper function to load the PclZip library.
     *
     * @access    private
     * @since     1.4
     *
     * @return    null
     */
    private function load_pclzip() {
    
      /* Load PclZip */
      if ( ! defined( 'PCLZIP_TEMPORARY_DIR' ) )
        define( 'PCLZIP_TEMPORARY_DIR', trailingslashit( $this->path() ) );
      
      require_once( ABSPATH . 'wp-admin/includes/class-pclzip.php' );
    
    }
  
    /**
     * Attempt to work out the path to the zip command
     *
     * @access    public
     * @since     1.4
     *
     * @return    string
     */
    public function guess_zip_command_path() {
    
      /* Check shell_exec is available and hasn't been explicitly bypassed */
      if ( ! $this->shell_exec_available() )
        return '';
      
      /* List of possible zip locations */
      $zip_locations = array(
        '/usr/bin/zip'
      );
      
      if ( is_null( shell_exec( 'hash zip 2>&1' ) ) )
        return 'zip';
      
      /* Find the one which works */
      foreach ( $zip_locations as $location )
        if ( @file_exists( $this->conform_dir( $location ) ) )
          return $location;
      
      return '';
    
    }
  
    /**
     * Generate the exclude param string for the zip backup
     *
     * Takes the exclude rules and formats them for use with either
     * the shell zip command or pclzip
     *
     * @access    public
     * @since     1.4
     *
     * @param     string    $context. (default: 'zip')
     * @return    string
     */
    public function exclude_string( $context = 'zip' ) {
    
      /* Return a comma separated list by default */
      $separator = ', ';
      $wildcard = '';
      
      /* The zip command */
      if ( $context == 'zip' ) {
        
        $wildcard = '*';
        $separator = ' -x ';
      
      /* The PclZip fallback library */
      } else if ( $context == 'regex' ) {
        
        $wildcard = '([\s\S]*?)';
        $separator = '|';
      
      }
      
      /* Sanitize the excludes */
      $excludes = array_filter( array_unique( array_map( 'trim', (array) $this->excludes ) ) );
      
      /* If path() is inside root(), exclude it */
      if ( strpos( $this->path(), $this->root() ) !== false )
        $excludes[] = trailingslashit( $this->path() );
      
      foreach( $excludes as $key => &$rule ) {
      
        $file = $absolute = $fragment = false;
        
        /* Files don't end with /  */
        if ( ! in_array( substr( $rule, -1 ), array( '\\', '/' ) ) ) {       
          $file = true;
        
        /* If rule starts with a / then treat as absolute path */
        } else if ( in_array( substr( $rule, 0, 1 ), array( '\\', '/' ) ) ) {        
          $absolute = true;
        
        /* Otherwise treat as dir fragment */
        } else {        
          $fragment = true;
          
        }
        
        /* Strip $this->root and conform */
        $rule = str_ireplace( $this->root(), '', untrailingslashit( $this->conform_dir( $rule ) ) );
        
        /* Strip the preceeding slash */
        if ( in_array( substr( $rule, 0, 1 ), array( '\\', '/' ) ) )
          $rule = substr( $rule, 1 );
        
        /* Escape string for regex */
        if ( $context == 'regex' )
          $rule = str_replace( '.', '\.', $rule );
        
        /* Convert any existing wildcards */
        if ( $wildcard != '*' && strpos( $rule, '*' ) !== false )
          $rule = str_replace( '*', $wildcard, $rule );
        
        /* Wrap directory fragments and files in wildcards for zip */
        if ( $context == 'zip' && ( $fragment || $file ) )
          $rule = $wildcard . $rule . $wildcard;
        
        /* Add a wildcard to the end of absolute url for zips */
        if ( $context == 'zip' && $absolute )
          $rule .= $wildcard;
        
        /* Add and end carrot to files for pclzip but only if it doesn't end in a wildcard */
        if ( $file && $context == 'regex' )
          $rule .= '$';
        
        /* Add a start carrot to absolute urls for pclzip */
        if ( $absolute && $context == 'regex' )
          $rule = '^' . $rule;
      
      }
      
      /* Escape shell args for zip command */
      if ( $context == 'zip' )
        $excludes = array_map( 'escapeshellarg', array_unique( $excludes ) );
      
      return implode( $separator, $excludes );
    
    }
  
    /**
     * Check whether safe mode is active or not
     *
     * @access    public
     * @since     1.4
     *
     * @return    bool
     */
    public function is_safe_mode_active() {
      
      if ( $safe_mode = ini_get( 'safe_mode' ) && strtolower( $safe_mode ) != 'off' )
        return true;
      
      return false;
    
    }
  
    /**
     * Check whether shell_exec has been disabled.
     *
     * @access    private
     * @since     1.4
     *
     * @return    bool
     */
    private function shell_exec_available() {
    
      /* Are we in Safe Mode */
      if ( $this->is_safe_mode_active() )
        return false;
      
      /* Is shell_exec disabled? */
      if ( in_array( 'shell_exec', array_map( 'trim', explode( ',', ini_get( 'disable_functions' ) ) ) ) )
        return false;
      
      /* Can we issue a simple echo command? */
      if ( ! @shell_exec( 'echo envatobackup' ) )
        return false;
      
      return true;
    
    }
  
    /**
     * Sanitize a directory path
     *
     * @access    public
     * @since     1.4
     *
     * @param     string    $dir
     * @param     bool      $recursive. (default: false)
     * @return    string    $dir
     */
    public function conform_dir( $dir, $recursive = false ) {
    
      /* Assume empty dir is root */
      if ( ! $dir )
        $dir = '/';
      
      /* Replace single forward slash (looks like double slash because we have to escape it) */
      $dir = str_replace( '\\', '/', $dir );
      $dir = str_replace( '//', '/', $dir );
      
      /* Remove the trailing slash */
      if ( $dir !== '/' )
        $dir = untrailingslashit( $dir );
      
      /* Carry on until completely normalized */
      if ( ! $recursive && $this->conform_dir( $dir, true ) != $dir )
        return $this->conform_dir( $dir );
      
      return (string) $dir;
    
    }
  
    /**
     * Get the errors
     *
     * @access    public
     * @since     1.4
     *
     * @param     string    $context
     * @return    mixed
     */
    public function errors( $context = null ) {
    
      if ( ! empty( $context ) )
        return isset( $this->errors[$context] ) ? $this->errors[$context] : array();
      
      return $this->errors;
    
    }
  
    /**
     * Add an error to the errors stack
     *
     * @access    private
     * @since     1.4
     *
     * @param     string    $context
     * @param     mixed     $error
     * @return    null
     */
    private function error( $context, $error ) {
    
      if ( empty( $context ) || empty( $error ) )
        return;
    
      $this->errors[$context][$_key = md5( implode( ':' , (array) $error ) )] = $error;
    
    }
  
    /**
     * Migrate errors to warnings
     *
     * @access    private
     * @since     1.4
     * 
     * @param     string    $context. (default: null)
     * @return    null
     */
    private function errors_to_warnings( $context = null ) {
    
      $errors = empty( $context ) ? $this->errors() : array( $context => $this->errors( $context ) );
      
      if ( empty( $errors ) )
        return;
      
      foreach ( $errors as $error_context => $errors )
        foreach( $errors as $error )
          $this->warning( $error_context, $error );
      
      if ( $context )
        unset( $this->errors[$context] );
      
      else
        $this->errors = array();
    
    }
  
    /**
     * Get the warnings
     *
     * @access    public
     * @since     1.4
     *
     * @return    null
     */
    public function warnings( $context = null ) {
    
      if ( ! empty( $context ) )
        return isset( $this->warnings[$context] ) ? $this->warnings[$context] : array();
    
      return $this->warnings;
    
    }
  
    /**
     * Add an warning to the warnings stack
     *
     * @access    private
     * @since     1.4
     *
     * @param     string    $context
     * @param     mixed     $warning
     * @return    null
     */
    private function warning( $context, $warning ) {
    
      if ( empty( $context ) || empty( $warning ) )
        return;
    
      $this->warnings[$context][$_key = md5( implode( ':' , (array) $warning ) )] = $warning;
    
    }
  
    /**
     * Custom error handler for catching errors
     *
     * @access    private
     * @since     1.4
     *
     * @param     string    $type
     * @param     string    $message
     * @param     string    $file
     * @param     string    $line
     * @return    null
     */
    public function error_handler( $type ) {
    
      if ( ( defined( 'E_DEPRECATED' ) && $type == E_DEPRECATED ) || ( defined( 'E_STRICT' ) && $type == E_STRICT ) || error_reporting() === 0 )
        return false;
      
      $args = func_get_args();
      
      $this->warning( 'php', array_splice( $args, 0, 4 ) );
      
      return false;
    
    }
  
  }

}

/* End of file class-envato-backup.php */
/* Location: ./includes/class-envato-backup.php */