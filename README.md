# fdv-settings
<b>WordPress plugin that helps other plugins to offer their own settings page easily</b><br/>

<h1>INSTRUCTIONS</h1>
<ol>
<li>Download this plugin and extract it into .../wp-content/plugins/fdv-settings</li>
<li>Activate the plugin in the admin pages. You'll see and extra admin page, named 'Example using Fdv Settings'</li>
<li>Copy the sample.php lines into your own plugin and adapt it. Please change the function names, and the options keys, so they don't clash with other plugins.</li>
<li>Check the admin pages, your own new settings page should appear.</li>
<li>Remove the sample.php or comment out the last lines of fdv-settings.php so it won't load. The extra admin page will dissappear.</li>
<li>Use the settings in your plugin with get_option(setting key).</li>
</ol>
Good luck!
