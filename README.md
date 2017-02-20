# fdv-settings
WordPress plugin that helps other plugins to offer their own settings page easily

INSTRUCTIONS
1. Download this plugin and extract it into .../wp-content/plugins/fdv-settings
2. Activate the plugin in the admin pages. You'll see and extra admin page, named 'Example using Fdv Settings'
3. Copy the sample.php lines into your own plugin and adapt it. Please change the function names, and the options keys, so they don't clash with other plugins.
4. Check the admin pages, your own new settings page should appear.
5. Remove the sample.php or comment out the last lines of fdv-settings.php so it won't load. The extra admin page will dissappear.
6. Use the settings in your plugin with get_option(setting key)

Good luck!
