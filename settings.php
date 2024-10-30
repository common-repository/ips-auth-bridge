<?php
// http://qnimate.com/wordpress-settings-api-a-comprehensive-developers-guide/

/*
 * Our hooks
 */
// register our page in the admin_menu action hook
add_action('admin_menu', 'ipb_options');

// this action is executed after loads its core, after registering all
// actions, finds out what page to execute and before producing the
// actual output(before calling any action callback)
add_action("admin_init", "ipb_display_options");

function ipb_options()
{
    // add top level menu page
    add_menu_page(
        'Invision Power Suite',
        'IPS Options',
        'manage_options',
        'ipb',
        'ipb_options_page'
    );
}

/**
 * top level menu:
 * callback functions
 */
function ipb_options_page()
{
    // Check user capabilities
    if (!(current_user_can('manage_options')))
    {
        wp_die('Unauthorized User');
        return;
    }
    ?>
    <div class="wrap">
    <div id="icon-options-general" class="icon32"></div>

    <!-- run the settings_errors() function here. -->
    <?php settings_errors(); ?>
    <h1>Invision Power Suite</h1>
    <form method="post" action="options.php">
    <?php
    {
                      
        //add_settings_section callback is displayed here.
        //For every new section we need to call settings_fields.
        settings_fields("header_section");
        
        // all the add_settings_field callbacks are displayed here
        do_settings_sections("ipb");
                
        // Add the submit button to serialize the options
        submit_button();                     
    }
    ?>
    </form>
    </div>
    <?php
}

function ipb_display_options()
{
    //section name, display name, callback to print description
    // of section, page to which section is attached.
    add_settings_section("header_section",
                         "Suite Options",
                         "ipb_display_header_options_content",
                         "ipb");

    //setting name, display name, callback to print form element,
    // page in which field is displayed, section to which it belongs.
    // last field section is optional.
    add_settings_field("ipb_install_location",
                       "Install Location",
                       "ipb_display_install_location",
                       "ipb",
                       "header_section");

    //section name, form element name, callback for sanitization
    register_setting("header_section", "ipb_install_location");

    // Ad nauseum...
    add_settings_field("ipb_default_group_membership",
                       "Default Role Mapping",
                       "ipb_display_default_group_membership",
                       "ipb",
                       "header_section");
    register_setting("header_section", "ipb_default_group_membership");

    // Rounded profile pictures
    add_settings_field("ipb_rounded_profile_pics",
                       "Use rounded profile pictures",
                       "ipb_display_rounded_profile_pics",
                       "ipb",
                       "header_section");
    register_setting("header_section", "ipb_rounded_profile_pics");

    // Group mappings
    // We need to catch any errors here in case of misconfiguration
    if (class_exists('\IPS\Member\Group'))
    {
        foreach (\IPS\Member\Group::groups() as $group)
        {
            $groupname = ipb_getGroupName($group);
            $setting = 'ipb_groupmap_' . $group->g_id;
            $value = get_option($setting, $default = FALSE);
            add_settings_field($setting,
                               $groupname,
                               "ipb_display_groupmap",
                               "ipb",
                               "header_section",
                               array('g_id' => $group->g_id));
            register_setting("header_section", $setting);
        }
    }
}

function ipb_display_groupmap($args)
{
    $group = \IPS\Member\Group::load($args['g_id']);
    $groupname = ipb_getGroupName($group);
    $setting = 'ipb_groupmap_' . $group->g_id;
    $value = get_option($setting, $default = FALSE);
    
    echo "<select id=\"" . $setting . "\" name=\"" . $setting . "\">";
    wp_dropdown_roles(get_option($setting));
    echo "</select>";
}

function ipb_display_rounded_profile_pics()
{
    $checked = get_option('ipb_rounded_profile_pics');
    echo "<input type='checkbox' "
        . "name='ipb_rounded_profile_pics' id='ipb_rounded_profile_pics' "
        . "value='1' "
        . checked($checked, 1, false)
        . " />";
}
    
function ipb_display_header_options_content()
{
    echo "IPS Configuration";
}

function ipb_display_install_location()
{
    //id and name of form element should be same as the setting name.
    ?>
    <input type="text"
        name="ipb_install_location"
        id="ipb_install_location"
        value="<?php echo get_option('ipb_install_location'); ?>"
        size=64 />
    <?php
}

function ipb_display_default_group_membership()
{
    echo "<select name='ipb_default_group_membership'>";
    wp_dropdown_roles(
        get_option('ipb_default_group_membership', $default = FALSE));
    echo "</select>";    
}

?>
