=== Envato WordPress Toolkit ===
Contributors: envato, valendesigns, japh
Tags: install, update, api, envato, theme, upgrade
Requires at least: 3.0
Tested up to: 3.3.2
Stable tag: 1.5

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