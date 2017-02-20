<?php
/*
Plugin Name: Fdv Settings
Plugin URI: https://github.com/fmdelvalle/fdv-settings
Description: Enables other plugins to easily provide settings pages. It allows several plugins to use it
at the same time without conflicts.
Version: 1.00
Author: Fernando del Valle <fmdelvalle@gmail.com>
Author URI: http://fmdelvalle.es
Text Domain: fdv-settings
*/


/*
    Copyright 2017  Fernando del Valle (email : fmdelvalle@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class FdvSettings {
    protected static $configuration = array();
    protected static $hooks_registered = false;

    /**
     * Other plugins should only call this static method.
     * $domain will be used for several things, like providing the translation domain, naming the admin page, etc.
     * @param $menu_title string The title of the settings page
     * @param $domain string Unique name for the plugin page, the translation domain, etc.
     * @param $cfg array an array with the following structure:
     * [
     *      section_key => [
     *          label => 'Section visible name'
     *          description => 'Optional subheader',
     *          fields => [
     *              options_unique_key => [
     *                  label => 'Field visible name'
     *                  description => 'Optional helper text'
     *                  type => 'yesno|select|number|text'
     *                  options => [ key => label, ... ]   (for selects only)
     *              ]
     *              ...
     *          ]
     *      ]
     *      ...
     * ]
     */
    public static function addPage( $menu_title, $domain, $cfg ) {
        static::$configuration[$domain] = array(
            'title' => $menu_title,
            'domain' => $domain,
            'sections' => $cfg
        );
        static::registerHooks();
    }

    public static function registerHooks() {
        if( !static::$hooks_registered ) {
            // Prepare the menu entries
            add_action( 'admin_menu', array('FdvSettings', 'onAdminMenu') );
            // Prepare the Settings and Options to be used
            add_action( 'admin_init', array('FdvSettings', 'onAdminInit') );
            static::$hooks_registered = true;
        }
    }

    public static function onAdminMenu() {
        // Add a new admin menu entry
        foreach( static::$configuration as $domain => $domainattrs ) {
            {
                $domain_copy = $domain;
                add_menu_page(
                    $domainattrs['title'],
                    __($domainattrs['title'], $domain),
                    'manage_options',
                    $domain.'-page',
                    array('FdvSettings', 'genSettingsPage')
                );
            }
        }
    }

    public static function genSettingsPage($args) {
        if( !current_user_can('manage_options'))
            return;
        $page = $_GET['page'];
        $domain = str_replace('-page', '', $page);
        $domain_attrs = static::$configuration[$domain];
        // add error/update messages

        // check if the user have submitted the settings
        // wordpress will add the "settings-updated" $_GET parameter to the url
        if ( isset( $_GET['settings-updated'] ) ) {
            // add settings saved message with the class of "updated"
            add_settings_error(
                $domain.'-messages',
                $domain.'-message',
                __( 'Settings Saved', $domain ),
                'updated' );
        }

        // show error/update messages
        settings_errors( $domain.'-messages' );
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
                <?php
                // output security fields for the registered setting "wporg"
                settings_fields( $domain );
                // output setting sections and their fields
                // (sections are registered for "wporg", each field is registered to a specific section)
                do_settings_sections( $domain );
                // output save settings button
                submit_button( __('Save Settings', $domain) );
                ?>
            </form>
        </div>
    <?php
    }

    public static function onAdminInit() {
        // Register Options and Settings to be used
        foreach( static::$configuration as $domain => $domainattrs ) {
            $settings_page = $domain;
            foreach( $domainattrs['sections'] as $section_name => $groupattrs) {
                add_settings_section($section_name,
                    __($groupattrs['label'], $domain),
                    array('FdvSettings', 'onSectionRender'),
                    $settings_page
                );
                foreach( $groupattrs['fields'] as $setting_name => $setting_attrs) {
                    //error_log("Registrando option $setting_name en ".$domain);
                    $wptype = 'string';
                    if( array_key_exists('type', $setting_attrs ) ) {
                        if( $setting_attrs['type'] == 'number')
                            $wptype = 'intval';
                    }
                    register_setting($domain, $setting_name, $wptype);
                    add_settings_field(
                        $setting_name, // as of WP 4.6 this value is used only internally
                        // use $args' label_for to populate the id inside the callback
                        __( $setting_attrs['label'], $domain ),
                        array('FdvSettings', 'onFieldRender'),
                        $settings_page,
                        $section_name,
                        [
                            'settings_section' => $section_name,
                            'setting_name' => $setting_name,
                            'label_for' => $setting_name,
                            'class' => $setting_name.'_row',
                            $setting_name.'_custom_data' => 'custom',
                        ]
                    );
                }
            }
        }
    }

    /**
     * Callback after rendering the section title. It lets us add some description below.
     *
     * @param $args - it receives a section title, a section id and some other data
     */
    public static function onSectionRender( $args ) {
        $page = $_GET['page'];
        $domain = str_replace('-page', '', $page);
        $groupattrs = static::$configuration[$domain]['sections'][$args['id']];
        if( array_key_exists('description', $groupattrs)) {
            ?>
            <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( $groupattrs['description'], $domain ); ?></p>
        <?php
        }
    }

    // field callbacks can accept an $args parameter, which is an array.
    // $args is defined at the add_settings_field() function.
    // wordpress has magic interaction with the following keys: label_for, class.
    // the "label_for" key value is used for the "for" attribute of the <label>.
    // the "class" key value is used for the "class" attribute of the <tr> containing the field.
    // you can add custom key value pairs to be used inside your callbacks.
    public static function onFieldRender( $args ) {
        $page = $_GET['page'];
        $domain = str_replace('-page', '', $page);
        // get the value of the setting we've registered with register_setting()
        $groupname = $args['settings_section'];
        $fieldname = $args['setting_name'];
        $options = get_option( $fieldname );
        $fieldvalue = $options === null ? null : $options;
        $cfg = static::$configuration[$domain]['sections'][$groupname]['fields'][$fieldname];
        $type = array_key_exists( 'type', $cfg ) ? $cfg['type'] : 'text';
        // We could support other types. Right now we only allow for Yes/No SELECT boxes
        if( $type == 'yesno' ) {
            static::onSelectRender( $domain, $groupname, $fieldname, $fieldvalue ? $fieldvalue : 'false',
                array(
                    'true' => __('Yes', $domain),
                    'false' => __('No', $domain)
                ));
        } else if( $type == 'select' ) {
            static::onSelectRender( $domain, $groupname, $fieldname, $fieldvalue ? $fieldvalue : null,
                $cfg['options'] );
        } else if( $type == 'number' ) {
            static::onTextInputRender( $domain, $groupname, $fieldname, $fieldvalue ? intval($fieldvalue) : null );
        } else if( $type == 'text' ) {
            static::onTextInputRender( $domain, $groupname, $fieldname, $fieldvalue ? $fieldvalue : null );
        } else if( $type == 'textarea' ) {
            static::onTextAreaRender( $domain, $groupname, $fieldname, $fieldvalue ? $fieldvalue : null );
        } else {
            throw new Exception("Unsupported type: {$type}");
        }
        if(array_key_exists('description', $cfg)) { ?>
            <p class="description">
                <?php esc_html_e( $cfg['description'], $domain ); ?>
            </p>
        <?php }
    }

    protected static function onSelectRender( $domain, $groupname, $fieldname, $default_value, $options ) {
        // output the field
        ?>
        <select id="<?php echo esc_attr( $fieldname ); ?>"
                data-custom="<?php echo esc_attr( 'wporg_custom_data' ); ?>"
                name="<?php echo esc_attr( $fieldname ); ?>"
            >
            <?php foreach( $options as $key => $label ) { ?>
                <option value="<?php echo esc_attr($key)?>"
                    <?php echo $default_value !== null ? ( selected( $default_value, $key, false ) ) : ( '' ); ?>>
                    <?php esc_html_e( $label, $domain ); ?>
                </option>
            <?php } ?>
        </select>

    <?php
    }

    protected static function onTextInputRender( $domain, $groupname, $fieldname, $default_value ) {
        // output the field
        ?>
        <input type="text" id="<?php echo esc_attr( $fieldname ); ?>"
                data-custom="<?php echo esc_attr( 'wporg_custom_data' ); ?>"
                name="<?php echo esc_attr( $fieldname ); ?>"
                value="<?php echo esc_attr( $default_value ); ?>"
            />
    <?php
    }

    protected static function onTextAreaRender( $domain, $groupname, $fieldname, $default_value ) {
        // output the field
        ?>
        <textarea id="<?php echo esc_attr( $fieldname ); ?>"
               data-custom="<?php echo esc_attr( 'wporg_custom_data' ); ?>"
               name="<?php echo esc_attr( $fieldname ); ?>"
               ><?php echo esc_attr( $default_value ); ?></textarea>
    <?php
    }
}

FdvSettings::registerHooks();

/**
 * You can remove this if you want to remove the 'Example using Fdv Sections' admin page. Or you
 * could remove the sample.php file completely.
 */
if(file_exists(dirname(__FILE__)."/sample.php")) {
    require_once(dirname(__FILE__)."/sample.php");
}
