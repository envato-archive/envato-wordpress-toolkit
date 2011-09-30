<?php if ( ! defined( 'EWPU_PLUGIN_VER') ) exit( 'No direct script access allowed' );
/**
 * User API info
 *
 * @package     Envato WordPress Updater
 * @author      Derek Herman <derek@valendesigns.com>
 * @since       1.0
 */

echo '
<form name="verification_form" method="post" action="options.php" id="api-verification">';
  wp_nonce_field( 'update-options' );
  settings_fields( 'envato-wordpress-updater' );
  do_settings_sections( 'envato-wordpress-updater' );
  echo '
  <p class="submit">
    <input type="submit" name="Submit" class="button-primary" value="' . __( 'Save Changes', 'envato' ) . '" />
  </p>
</form>';