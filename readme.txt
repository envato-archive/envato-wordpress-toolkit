=== Envato WordPress Toolkit ===
Contributors: envato, valendesigns
Tags: install, update, api, envato, theme, upgrade
Requires at least: 3.7
Tested up to: 4.3-beta1
Stable tag: 1.7.3

WordPress toolkit for Envato Marketplace hosted items. Currently supports the following theme functionality: install, upgrade, & backups during upgrade.

== Description ==

This toolkit plugin establishes an Envato Marketplace API connection to take advantage of the new `wp-list-themes` & `wp-download` methods created specifically for this plugin. These API methods grants access to information about your purchased themes and create temporary download URL's for installing and upgrading those themes. Basically, users that have purchased themes from ThemeForest.net can now install and upgrade any theme that takes advantage of these new methods. 

For end users, all that's required to get started is an Envato Marketplace username & API key, and to have purchased one of the many WordPress themes found on ThemeForest.net. 

For theme authors, navigate to your theme's admin page on ThemeForest.net and click edit; you'll need to upload the `Optional WordPress Theme` ZIP which contains your installable WordPress Theme. Once you've got an installable ZIP uploaded and approved, users can install & update directly from within WordPress. Also, to take advantage of the update functionality you'll need to increment your themes version in the style.css every time a new version is available for download and repeat the process above of uploading an installable ZIP.

Below is a description of the new api-key protected Envato Marketplace API methods or sets. For full documentation on how to use the API go to http://marketplace.envato.com/api/documentation and have a look at the examples.

`wp-list-themes`
* Details of all WordPress themes you have purchased. Contains the item ID, item name, theme name, author name & version.

`wp-download`
* Temporary download URL to a WordPress item you have purchased. Requires the item ID, e.g. wp-download:1234.

== Installation ==

1. Upload the `envato-wordpress-toolkit` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the `Plugins` menu in WordPress.
3. To establish an Envato Marketplace API connection navigate to the `Envato Toolkit` page and insert your Marketplace username and secret API key in the designated input fields. To obtain your API Key, visit your "My Settings" page on any of the Envato Marketplaces.
4. Once the API connection has been established you will see a list of themes that can be auto installed. If you don't see any themes and are certain you've done everything correct, there is a good chance the theme author has not updated their theme to be available for auto install and update. If that's the case, please contact the theme author and ask them to update their theme's information.

== Changelog ==

= 1.7.3 =
* Added a custom user agent to all API requests so a connection is once again possible.
* Added the option to turn sslverify on and off.
* Envato API requests are now using HTTPS endpoints.
* Replace `wp_remote_request` with `wp_safe_remote_request`, which will validated the API url.
* Lowered the API request timeout to something more reasonable.

= 1.7.2 =
* Added checks to stop PHP from throwing redeclare class errors.
* Fix an issue where the `ZipArchive` class was called before the `class_exists` check.
* Fixed the Github Updater class so it now shows updates on `update-core.php`.
* Changed the Github Updater class to pull in the contents of `readme.txt` to build the config array.
* Changed the `raw_url` in `_admin_update_check` to use `raw.githubusercontent`, because `raw.github` causes a second `http` request.

= 1.7.1 =
* Fixed: Stop Mojo Marketplace from tracking your movements and causing long or hanging page loads.
* Fixed: Fix an issue that caused the timeout to be set high globally.
* Added: Ability to deactivate the Github Updater.
* Fixed: Stopped `wp_list_themes` from making an API request before credentials have been entered into WordPress.
* Fixed: Changed the menu position and load priority to stop Mojo Marketplace from hiding the Envato Toolkit menu item.
* Fixed: Switched from `.png` to a font icon, which makes it Admin Color Scheme compatible.
* Added: New i18n file and changed the domain from `envato` to `envato-wordpress-toolkit` to avoid potential conflicts.
* Fixed: Changed the UI so it now has tabs for better content separation.
* Fixed: Now uses the Customizer to preview installed themes.

= 1.7.0 =
* Fixed: Converted transient names into hashes to comply with character limits.
* Fixed: Invalid argument supplied foreach warning.
* Fixed: Call to undefined function wp_create_nonce.
* Fixed: Changed the WP_GitHub_Updater class so it will properly name the directory.
* Fixed: Decompression error caused by gzinflate().

= 1.6.3 =
* Fixed: Conflict with the WP-Compatibility Installer plugin.

= 1.6.2 =
* Fixed: Conflict with other plugins using the GitHub updater.

= 1.6.1 =
* set_time_limit errors are now hidden in favor of a more user-friendly message.
* Make error notices dismissible.

= 1.6 =
* Fixed: Bug that prevented updating if backups were enabled.
* Added auto-updating.
* Various bug fixes.

= 1.5 =
* Changed use of cURL to the WordPress HTTP API
* Removed ini_set usage

= 1.4 =
* Added support for theme backups during upgrade.
* Allow backups to be turned off from within the UI.

= 1.3 =
* Added caching to the Envato Marketplace API requests.
* Added support for Multisite.

= 1.2 =
* Updated register_setting group ID bug.

= 1.1 =
* Gutted the unnecessary files and classes.
* Renamed the plugin and moved functions into a single class.
* Updated strings for future Internationalization.

= 1.0 =
* Initial release with auto theme install and update.
* Force an increase on 'max_execution_time' to ensure large themes finish downloading.