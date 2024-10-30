<?php
/**
 * @package ipb_auth_bridge
 * @version 0.9.0
 */
/*
   Plugin Name: IPS Auth Bridge
   Plugin URI: http://www.corbe.net/forum/6-wordpress-ips-bridge
   Description: This plugin bridges Wordpress to the Invision Power Suite.
   Author: Daniel Corbe
   Version: 0.9.0
   Author URI: http://www.corbe.net/news
*/

include( plugin_dir_path( __FILE__ ) . 'settings.php');

/*
 * Our hook list
 */
add_action('init', 'ipb_plugin_init');
add_filter('authenticate', 'ipb_auth', 10, 3);
add_action('register_form', 'ipb_register');
add_action('lost_password', 'ipb_lostpassword');
add_action('login_form', 'ipb_login');
add_action('wp_logout', 'ipb_logout');
add_filter('edit_profile_url', 'ipb_profile_url', 10, 3);
add_action('admin_init', 'remove_profile_menu');
add_filter('get_avatar', 'ipb_avatar', 10, 5);

function ipb_plugin_init()
{
    global $wp;
        
    // Load in IPS
    $ipb_path = get_option('ipb_install_location');
    if (ipb_endsWith($ipb_path, '/'))
    {
        $ipb_path = $ipb_path . "init.php";
    }
    else
    {
        $ipb_path = $ipb_path . "/init.php";
    }
    if (file_exists($ipb_path))
        require_once($ipb_path);
    else
        return;
    
    // On every page load, we need to check to see if the user
    // is logged in on IPB.   If they're not, we need to log
    // them out on the Wordpress side as well.
    if (is_user_logged_in() && !ipb_loggedIn())
    {
        wp_logout();
        wp_redirect(home_url($wp->request));
    }

    // If the user is logged in on IPB but NOT logged in on
    // wordpress, do the user a solid and log them in
    if (!is_user_logged_in() && ipb_loggedIn())
    {
        // So why do we call wp_authenticate instead of
        // ipb_login here?  Simple.   wp_authenticate
        // creates the user if it doesn't exist and
        // ipb_login assumes it does.
        wp_authenticate("foo", "bar");
    }
}

// return true if $str ends with $sub
function ipb_endsWith($str, $sub)
{
    return (substr($str, strlen($str) - strlen($sub)) == $sub);
}

// Automatically logs the user in if they exist in the DB
function ipb_login()
{
    global $wp;

    if (ipb_loggedIn() === TRUE)
    {
        ipb_auto_login(ipb_getUserName());
        if (isset($_REQUEST['redirect_to']))
            do_action('login_redirect', $_REQUEST['redirect_to']);
        else
            do_action('login_redirect', esc_url(home_url()));
        exit;
    }
    else
    {
        $rurl = \IPS\Http\Url::internal(
            'app=core&module=system&controller=login&referer='
            .ipb_curl());
        wp_redirect($rurl);
        exit;
    }    
}

function ipb_logout()
{
    $member = \IPS\Member::loggedIn();

    /* Are we logging out back to an admin user? */
    if(isset($_SESSION['logged_in_as_key']))
    {
        $key = $_SESSION['logged_in_as_key'];
        unset(\IPS\Data\Store::i()->$key);
        unset($_SESSION['logged_in_as_key']);
        unset($_SESSION['logged_in_from']);
    }

    /* Do not allow the login_key to be re-used */
    if (isset(\IPS\Request::i()->cookie['device_key']))
    {
        try
        {
            $device = \IPS\Member\Device::loadAndAuthenticate(
                \IPS\Request::i()->cookie['device_key'], $member );
            $device->login_key = NULL;
            $device->save();
        }
        catch (\OutOfRangeException $e) { }
    }

    /* Clear cookies */
    \IPS\Request::i()->clearLoginCookies();

    /* Destroy the session
       (we have to explicitly reset the session cookie,
       see http://php.net/manual/en/function.session-destroy.php)
    */
    $_SESSION = array();
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
               $params["path"], $params["domain"],
               $params["secure"], $params["httponly"]);
    session_destroy();
}

function ipb_auto_login($username = FALSE, $userid = 0)
{
    if (!is_user_logged_in())
    {
        if ($username)
        {
            $user = get_user_by('login', $username);
        }
        elseif ($userid)
        {
            $user = new WP_User($userid);
        }
        else
        {
            throw new Exception(
                "ipb_auto_login(): Must specify either a username or password");
            return;
        }

        if ($user->ID > 0)
        {
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID);
            do_action('wp_login', $user->user_login);
        }
    }
}

function ipb_loggedIn()
{
    $session = \IPS\Session\Front::i();
    $member = $session->member;

    if ($member->member_id > 0)
    {
        return TRUE;
    }
    else
    {
        return FALSE;
    }    
}

function ipb_getUserName()
{
    $session = \IPS\Session\Front::i();
    $member = $session->member;

    if ($member->member_id > 0)
    {
        return $member->name;
    }

    return FALSE;
}

function ipb_getGroupName($group)
{
    $group = $group->name;
    \IPS\Member::loggedIn()->language()->parseOutputForDisplay($group);

    return $group;
}

function ipb_auth($user, $username, $password)
{
    global $wpdb;
    $user = false;

    if (ipb_loggedIn() === TRUE)
    {
        // Successfully logged in on IPB.   Try and load user data
        $session = \IPS\Session\Front::i();
        $member = $session->member;

        $userobj = new WP_User();
        $user = $userobj->get_data_by('email', $member->email);
        $user = new WP_User($user->ID);

        if ($user->ID == 0)
        {
            $setting = 'ipb_groupmap_' . $member->member_group_id;
            $role = get_option($setting, $default = FALSE)
                  ?: get_option('ipb_default_group_membership',
                              $default='subscriber');
            // The user does not exist in the wordpress DB.
            // TODO:  Fix password generation thing below
            $userdata = array('user_email' => $member->email,
                              'user_login' => $member->name,
                              'user_pass' => 'changeme123',
                              'role' => $role);
            $new_user_id = wp_insert_user($userdata);
            if (is_wp_error($new_user_id))
                wp_die($new_user_id->get_error_message());
            else
                $user = get_userdata($new_user_id);
        }
        else
        {
            /* Determine group information and sync up */
            $setting = 'ipb_groupmap_' . $member->member_group_id;
            $role = get_option($setting, $default = FALSE);
            if ($role)
            {
                $userdata = array(
                    'ID' => $user->ID,
                    'user_email' => $member->email,
                    'role' => $role
                );
                wp_update_user($userdata);

                // We use wpdb->update here instead of wp_update_user
                // because wp_update_user doesn't allow you to change
                // the username.   So we *have* to force the issue
                // by manually updating the database.  
                $userdata = array(
                    'user_login' => $member->name
                );
                $userid = array(
                    'ID' => $user->ID,
                );
                $wpdb->update($wpdb->users, $userdata, $userid);
            }
        }
    }
    else
    {
        ipb_login();
        exit;
    }

    remove_action('authenticate', 'wp_authenticate_username_password', 20);
    ipb_auto_login($username = $user->user_login);
    return($user);
}

function ipb_profile_url($url, $user_id, $scheme)
{
    if (class_exists('\IPS\Session\Front'))
    {
        $session = \IPS\Session\Front::i();
        $member = $session->member;

        $rurl = \IPS\Http\Url::internal(
            'app=core&module=members&controller=profile&id='
            .$member->member_id
        );
        $url = $rurl;
    }
    return($url);
}

// We must also remove the profile link from the admin page
function remove_profile_menu()
{
    remove_submenu_page('users.php', 'profile.php');
    remove_menu_page('profile.php');

    /* Just because jetpack annoys me */
    if(class_exists('Jetpack') && !current_user_can('manage_options'))
    {
		remove_menu_page('jetpack');
	}
}

function ipb_avatar($avatar, $id_or_email, $size, $default, $alt)
{
    if (class_exists('\IPS\Session\Front'))
    {
        $session = \IPS\Session\Front::i();
        $member = $session->member;
        $option = get_option('ipb_rounded_profile_pics');

        $avatar = '<img src="' . $member->photo . '" ' .
                'height="{$size}" width="{$size}" ' .
                'class="avatar avatar-{$size} photo" ';
        if ($option)
        {
            $avatar .= 'style="border-radius:50% 50% 50% 50%" ';
        }
        $avatar .= '>';

    }
    return($avatar);
}

function ipb_register()
{
    $session = \IPS\Session\Front::i();
    $member = $session->member;

    $rurl = \IPS\Http\Url::internal(
        'app=core&module=system&controller=register'
    );
    $url = $rurl;
       
    wp_redirect($rurl);
    exit;
}

function ipb_lostpassword()
{
    $session = \IPS\Session\Front::i();
    $member = $session->member;

    $rurl = \IPS\Http\Url::internal(
        'app=core&module=system&controller=lostpass'
    );
    $url = $rurl;
       
    wp_redirect($rurl);
    exit;
}

function ipb_curl()
{
    return (isset($_SERVER['HTTPS']) &&
            $_SERVER['HTTPS'] === 'on' ? "https" : "http") .
        "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
}

?>
