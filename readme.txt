=== Gravity Forms Constant Contact ===
Tags: gravity forms, forms, gravity, form, crm, gravity form, mail, email, newsletter, Constant Contact, plugin, sidebar, widget, mailing list, API, email marketing, newsletters
Requires at least: 3.3
Tested up to: 4.3
Stable tag: trunk
Contributors: katzwebdesign, katzwebservices
Donate link: https://gravityview.co/?utm_source=plugin&utm_medium=readme&utm_content=donatelink&utm_campaign=gravity-forms-constant-contact

Add contacts to your Constant Contact mailing list when they submit a Gravity Forms form.

== Description ==

> This plugin requires a <a href="http://wordpress.constantcontact.com/index.jsp" rel="nofollow">Constant Contact</a> account.

###Integrate Constant Contact with Gravity Forms
If you use <strong>Constant Contact</strong> email service and the Gravity Forms plugin, you're going to want this plugin!

Integrate your Gravity Forms forms so that when users submit a form entry, the entries get added to Constant Contact. Link any field type with Constant Contact, including custom fields!

### You may also be interested in:

* __[GravityView](https://gravityview.co/?utm_source=plugin&utm_medium=readme&utm_content=alsointerestedlink&utm_campaign=gravity-forms-constant-contact)__ - Display your Gravity Forms entries; easily turn Gravity Forms into a directory plugin.

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


== Screenshots ==

1. Users can choose which Constant Contact lists they are added to.

== Upgrade Notice ==

= 2.2.2 (August 11, 2015) =
* Fixed: Invalid login issue (thanks [@robertark](https://github.com/robertark)!)
* Confirmed plugin compatibility with WordPress 4.3

= 2.2 & 2.2.1 (January 5, 2015) = 
* Fixed: Fatal error on activation for plugins located outside of `/plugins/` directory
* Modified: Converted API to use WordPress remote request functionality - now works with or without `curl` enabled.
* Fixed: PHP notices
* Tweak: Replace some images with Dashicons
* Tweak: Update Constant Contact logo for Retina displays
* Removed PressTrends reporting
* Added: Intro to GravityView plugin
* Tweak: Only fetch the API if a feed is going to be exported to CTCT


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

= 2.2.2 (August 11, 2015) =
* Fixed: Invalid login issue (thanks [@robertark](https://github.com/robertark)!)
* Confirmed plugin compatibility with WordPress 4.3

= 2.2 & 2.2.1 (January 5, 2015) =
* Fixed: Fatal error on activation for plugins located outside of `/plugins/` directory
* Modified: Converted API to use WordPress remote request functionality - now works with or without `curl` enabled.
* Fixed: PHP notices
* Tweak: Replace some images with Dashicons
* Tweak: Update Constant Contact logo for Retina displays
* Removed PressTrends reporting
* Added: Intro to GravityView plugin
* Tweak: Only fetch the API if a feed is going to be exported to CTCT

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