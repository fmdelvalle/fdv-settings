<?php
/**************************************************************************
* SAMPLE (copy the code below to your own plugin and adjust everything).
* You could also delete this file with confidence. Or you could just
* comment out the add_action('init', 'exampleSettings') line at the end.
**************************************************************************/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
* Fernando del Valle: uses FdvSettings to provide a settings page.
* This must be called on init, as this plugin could be loaded before the FdvSettings plugin and therefore
* it class_exists('FdvSettings') would fail
*/
function exampleSettings() {
    if( class_exists('FdvSettings')) {
        FdvSettings::addPage('Example using Fdv Settings', 'examplesettings',
            array(
                'section_1' => array(
                    'label' => 'First section',
                    'description' => 'This section is the first one',
                    'fields' => array(
                        'option_key_1' => array(
                            'label' => 'Enable something',
                            'type' => 'yesno',
                            'description' => 'This activates something'
                        ),
                        'option_key_2' => array(
                            'label' => 'Choose something',
                            'type' => 'select',
                            'options' => array(
                                'blue' => 'Blue sea',
                                'red' => 'Red heart',
                                'yellow' => 'Yellow flower'
                            )
                        ),
                        'option_key_3' => array(
                            'label' => 'Pick a number',
                            'type' => 'number'
                        ),
                        'option_key_4' => array(
                            'label' => 'Enter some text'
                        )
                    ),
                ),
                'section_2' => array(
                    'label' => 'Second section',
                    'fields' => array(
                        'option_key_2_1' => array(
                            'label' => 'Enable another thing',
                            'type' => 'yesno'
                        ),
                    )
                )
            )

        );
    }
}

add_action('init', 'exampleSettings');

?>