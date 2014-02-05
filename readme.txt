=== Gravity Forms Constant Contact ===
Tags: gravity forms, forms, gravity, form, crm, gravity form, mail, email, newsletter, Constant Contact, plugin, sidebar, widget, mailing list, API, email marketing, newsletters
Requires at least: 2.8
Tested up to: 3.8.1
Stable tag: trunk
Contributors: katzwebdesign, katzwebservices
Donate link:https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=zackkatz%40gmail%2ecom&item_name=Gravity%20Forms+Constant%20Contact&no_shipping=0&no_note=1&tax=0&currency_code=USD&lc=US&bn=PP%2dDonationsBF&charset=UTF%2d8

Add contacts to your Constant Contact mailing list when they submit a Gravity Forms form.

== Description ==

> This plugin requires a <a href="http://wordpress.constantcontact.com/index.jsp" rel="nofollow">Constant Contact</a> account.

###Integrate Constant Contact with Gravity Forms
If you use <strong>Constant Contact</strong> email service and the Gravity Forms plugin, you're going to want this plugin!

Integrate your Gravity Forms forms so that when users submit a form entry, the entries get added to Constant Contact. Link any field type with Constant Contact, including custom fields!

### You may also be interested in:

* <a href="http://wordpress.org/extend/plugins/gravity-forms-addons/">Gravity Forms Directory & Addons Plugin</a> - Turn Gravity Forms into a directory plugin, and extend the functionality

== Installation ==
1. Upload plugin files to your plugins folder, or install using WordPress' built-in Add New Plugin installer.
1. Activate the plugin
1. Go to the plugin settings page (under Forms > Settings > Constant Contact)
1. Enter the information requested by the plugin.
1. Click Save Settings.
1. If the settings are correct, it will say so.
1. Follow on-screen instructions for integrating with Constant Contact.

== Frequently Asked Questions ==

= Does this plugin require Constant Contact? =
Yes, it does. If you don't have an Constant Contact account, <a href="http://wordpress.constantcontact.com/index.jsp" rel="nofollow">sign up for an account here</a>.

= What's the license for this plugin? =
This plugin is released under a GPL license.

= How do I prevent opt-in confirmation emails? =

To disable "Confirmed Opt-in", add this code to your theme's `functions.php` file:

`
add_filter('gravity_forms_constant_contact_action_by', 'return_action_by_customer');
function return_action_by_customer() { return 'ACTION_BY_CUSTOMER'; }
`

= How do I prevent the Entry notes confirming that the entry was added to Constant Contact? =

To disable this feature, add this code to your theme's `functions.php` file:

`
add_filter('gravityforms_constant_contact_add_notes_to_entries', '__return_false');
`

= This plugin uses PressTrends =
By installing this plugin, you agree to allow gathering anonymous usage stats through PressTrends. The data gathered is the active Theme name, WordPress version, plugins installed, and other metrics. This allows the developer of this plugin to know what compatibility issues to test for.

To remove PressTrends integration, add the code to your theme's functions.php file:

`
remove_action('plugins_loaded', 'add_presstrends_GravityFormsConstantContact');
`

== Screenshots ==

1. Users can choose which Constant Contact lists they are added to.

== Upgrade Notice ==

= 2.1.2 (February 5, 2014) =
* Fixed: Some servers are very sensitive to the fact of posting a form where some values are urls (in this case, the Constant Contact list endpoint). This version replaces that way of posting and adds three new methods to convert endpoint's in short id's and backwards.
* Fixed: Minor corrections (html, PHP warnings)

= 2.1.1 =
* Added: `gravityforms_constant_contact_change_date_format` filter to enable changing the format of the date field export to Constant Contact.

= 2.1 =
* Fixed: Many PHP notices. This should fix the "spinning" issue when creating a feed with `WP_DEBUG` turned on.
* Added: Now a note is added to each entry to confirm that the entry was added/updated in Constant Contact.
* Improved: PHP 5.4 support
* Improved: Look of the settings page, new CC logo

= 2.0.3 =
* Made it clearer that you need to configure the settings before creating a feed.
* Plugin now only checks username & password when saved; this prevents accounts being frozen
* Corrected "Opt-in Source" to be `ACTION_BY_CONTACT`, which is correct. It used to be `ACTION_BY_CUSTOMER`.
* Added notice when Gravity Forms isn't installed or active
* Attempted to fix bug where user names with spaces don't connect to the API properly
* Turned off curl debug for echoing errors on submitted forms. Add `?debug=true` to the page URL to turn back on.

= 2.0.2 =
* Fixed bug where Custom Fields don't get sent to Constant Contact.
* Added notice on Custom Fields feed setup to let user know that custom fields are limited to 50 characters.

= 2.0.1 =
* Fixed issue where registration notice shows up on Plugins page, even when Gravity Forms is registered.

= 2.0 =
* Converted to Gravity Forms Add-On Feeds system. If upgrading, <strong>you will need to re-configure your connected forms!</strong>
* Removed dependence on the <a href="http://wordpress.org/extend/plugins/constant-contact-api/">Constant Contact for WordPress</a> plugin

= 1.1 =
* Added list selection capability - allow users to choose which lists they are subscribed to (view the plugin's Installation tab or the Help tab on the Edit Form page to learn more)
* Improved notices if Gravity Forms or Constant Contact API is not installed or activated

= 1.0 =
* No upgrade notice, since this is the first version!

== Changelog ==

= 2.1.2 (February 5, 2014) =
* Fixed: Some servers are very sensitive to the fact of posting a form where some values are urls (in this case, the Constant Contact list endpoint). This version replaces that way of posting and adds three new methods to convert endpoint's in short id's and backwards.
* Fixed: Minor corrections (html, PHP warnings)

= 2.1.1 =
* Added: `gravityforms_constant_contact_change_date_format` filter to enable changing the format of the date field export to Constant Contact.

= 2.1 =
* Fixed: Many PHP notices. This should fix the "spinning" issue when creating a feed with `WP_DEBUG` turned on.
* Added: Now a note is added to each entry to confirm that the entry was added/updated in Constant Contact.
* Improved: PHP 5.4 support
* Improved: Look of the settings page, new CC logo

= 2.0.3 =
* Made it clearer that you need to configure the settings before creating a feed.
* Plugin now only checks username & password when saved; this prevents accounts being frozen
* Corrected "Opt-in Source" to be `ACTION_BY_CONTACT`, which is correct. It used to be `ACTION_BY_CUSTOMER`.
* Added notice when Gravity Forms isn't installed or active
* Attempted to fix bug where user names with spaces don't connect to the API properly
* Turned off curl debug for echoing errors on submitted forms. Add `?debug=true` to the page URL to turn back on.

= 2.0.2 =
* Fixed bug where Custom Fields don't get sent to Constant Contact.
* Added notice on Custom Fields feed setup to let user know that custom fields are limited to 50 characters.

= 2.0.1 =
* Fixed issue where registration notice shows up on Plugins page, even when Gravity Forms is registered.

= 2.0 =
* Converted to Gravity Forms Add-On Feeds system. If upgrading, <strong>you will need to re-configure your connected forms!</strong>
* Removed dependence on the <a href="http://wordpress.org/extend/plugins/constant-contact-api/">Constant Contact for WordPress</a> plugin

= 1.1 =
* Added list selection capability - allow users to choose which lists they are subscribed to (view the plugin's Installation tab or the Help tab on the Edit Form page to learn more)
* Improved notices if Gravity Forms or Constant Contact API is not installed or activated

= 1.0 =
* Launched plugin