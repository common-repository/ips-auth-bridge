=== IPS Authentication Bridge ===
Contributors: dancorbe
Donate link: https://www.patreon.com/bePatron?c=1913543
Tags: ips, invision, power, board, powerboard, community, single, sign-on, sso, bridge
Requires at least: 4.9
Tested up to: 4.9.7
Stable tag: 0.9.0
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The following software bridges Wordpress to your IPS installation.  It's designed to offer your wordpress users and your IPS users a seamless login experience.

=== About This File ===

The following software bridges Wordpress to your IPS installation.  It's designed to offer your wordpress users and your IPS users a seamless experience and as of 0.9.0 has the following features:

. Single Sign-On, with IPS as the authoritative entity

. Login status sync.   If you're signed into one system, you're also signed into the other.

. 100% Compatible with third party authentication plugins, provided they make use of the new 4.3.x OAuth framework.  Other authentication mechanisms may also work but remain untested.     

. Uses IPS' login, logout, password reset and profile forms.

. Synchronizes avatars and other pertinent profile information.

. Automatically sync IPS primary group membership to a Wordpress role. 
 

== Additional Recommendations ==

For the best experience possible, we also recommend the following plugins:

. IPS Referer Redirect:  This plugin will allow IPS to redirect the user back to your Wordpress site after login provided the login was initiated from a Wordpress page.

. Capability Manager Enhanced: This plugin allows for more fine-grained permission management within Wordpress.   
 

== Software Freedom ==

We believe in software freedom.   This software is offered to the community completely free of charge; however, we cannot continue to bring you quality plugins like this without your help.   If you find this software useful, please consider supporting us on Patreon.

Support me on Patreon!

Please visit my Patreon page to donate.   Every little bit helps.

https://www.patreon.com/bePatron?c=1913543

== A list of supported authentication plugins ==

This is a list of authentication plugins that we were actually able to test:

. All built-in login mechanisms.   Plus:

. Twitch Integration by @LZDigital

. Instagram Login Handler by @Firdavs Khaydarov

. Discord Login Handler by @Fosters

. Steam Profile Integration by @Aiwa
