<?php
/*
Plugin Name: Gravity Forms Constant Contact Add-On
Plugin URI: https://katz.co/plugins/gravity-forms-constant-contact/
Description: Integrates Gravity Forms with Constant Contact allowing form submissions to be automatically sent to your Constant Contact account.
Version: 2.2.2
Author: Katz Web Services, Inc.
Author URI: http://www.katzwebservices.com

------------------------------------------------------------------------
Copyright 2015 Katz Web Services, Inc.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/

add_action('init',  array('GFConstantContact', 'init'));
register_activation_hook( __FILE__, array("GFConstantContact", "add_permissions"));

class GFConstantContact {

	private static $name = "Gravity Forms Constant Contact Add-On";
    private static $path = "gravity-forms-constant-contact/constantcontact.php";
    private static $url = "http://www.gravityforms.com";
    private static $slug = "gravity-forms-constant-contact";
    private static $version = "2.2.2";
    private static $min_gravityforms_version = "1.3.9";
    public  static $plugin_dir_path;

    //Plugin starting point. Will load appropriate files
    public static function init(){
	    global $pagenow;

        self::$plugin_dir_path = plugin_dir_path(__FILE__);

		if($pagenow === 'plugins.php') {
			add_action("admin_notices", array('GFConstantContact', 'is_gravity_forms_installed'), 10);
		}

		if(self::is_gravity_forms_installed(false, false) === 0){
			add_action('after_plugin_row_' . self::$path, array('GFConstantContact', 'plugin_row') );
           return;
        }

        if(defined('RG_CURRENT_PAGE') && RG_CURRENT_PAGE == "plugins.php"){
            //loading translations
            load_plugin_textdomain('gravity-forms-constant-contact', FALSE, '/gravity-forms-constant-contact/languages' );

           //force new remote request for version info on the plugin page
            self::flush_version_info();
        }

        if(!self::is_gravityforms_supported()){
           return;
        }

        //loading data class
        require_once(self::get_base_path() . "/data.php");

        if(is_admin()){
            //loading translations
            load_plugin_textdomain('gravity-forms-constant-contact', FALSE, '/gravity-forms-constant-contact/languages' );

            add_filter("transient_update_plugins", array('GFConstantContact', 'check_update'));
            add_filter("site_transient_update_plugins", array('GFConstantContact', 'check_update'));

            add_action('install_plugins_pre_plugin-information', array('GFConstantContact', 'display_changelog'));

            //creates a new Settings page on Gravity Forms' settings screen
            if(self::has_access("gravityforms_constantcontact")){
                RGForms::add_settings_page("Constant Contact", array("GFConstantContact", "settings_page"), self::get_base_url() . "/images/ctct_logo_600x90.png");
            }
        }

        //integrating with Members plugin
        if(function_exists('members_get_capabilities'))
            add_filter('members_get_capabilities', array("GFConstantContact", "members_get_capabilities"));

        //creates the subnav left menu
        add_filter("gform_addon_navigation", array('GFConstantContact', 'create_menu'));

        if(self::is_constantcontact_page()){

			//loading Gravity Forms tooltips
	        require_once(GFCommon::get_base_path() . "/tooltips.php");

	        //enqueueing sack for AJAX requests
			wp_enqueue_script("sack");

	        add_filter('gform_tooltips', array('GFConstantContact', 'tooltips'));

			//loading data lib
            require_once(self::get_base_path() . "/data.php");

            //loading upgrade lib
            if(!class_exists("RGConstantContactUpgrade"))
                require_once("plugin-upgrade.php");

            //runs the setup when version changes
            self::setup();

         }
         else if(in_array(RG_CURRENT_PAGE, array("admin-ajax.php"))){

            //loading data class
            require_once(self::get_base_path() . "/data.php");

            add_action('wp_ajax_rg_update_feed_active', array('GFConstantContact', 'update_feed_active'));
            add_action('wp_ajax_gf_select_constantcontact_form', array('GFConstantContact', 'select_constantcontact_form'));

        }
        else{
        	 //handling post submission.
            add_action("gform_post_submission", array('GFConstantContact', 'export'), 10, 2);
        }
    }

    public static function is_gravity_forms_installed($asd = '', $echo = true) {
		global $pagenow, $page; $message = '';

		$installed = 0;
		$name = self::$name;
		if(!class_exists('RGForms')) {
			if(file_exists(WP_PLUGIN_DIR.'/gravityforms/gravityforms.php')) {
				$installed = 1;
				$message .= __(sprintf('%sGravity Forms is installed but not active. %sActivate Gravity Forms%s to use the %s plugin.%s', '<p>', '<strong><a href="'.wp_nonce_url(admin_url('plugins.php?action=activate&plugin=gravityforms/gravityforms.php'), 'activate-plugin_gravityforms/gravityforms.php').'">', '</a></strong>', $name,'</p>'), 'gravity-forms-salesforce');
			} else {
				$message .= <<<EOD
<p><a href="http://katz.si/gravityforms?con=banner" title="Gravity Forms Contact Form Plugin for WordPress"><img src="http://gravityforms.s3.amazonaws.com/banners/728x90.gif" alt="Gravity Forms Plugin for WordPress" width="728" height="90" style="border:none;" /></a></p>
		<h3><a href="http://katz.si/gravityforms" target="_blank">Gravity Forms</a> is required for the $name</h3>
		<p>You do not have the Gravity Forms plugin installed. <a href="http://katz.si/gravityforms">Get Gravity Forms</a> today.</p>
EOD;
			}

			if(!empty($message) && $echo) {
				echo '<div id="message" class="updated">'.$message.'</div>';
			}
		} else {
			return true;
		}
		return $installed;
	}

	public static function plugin_row(){
        if(!self::is_gravityforms_supported()){
            $message = sprintf(__("%sGravity Forms%s is required. %sPurchase it today!%s"), "<a href='http://katz.si/gravityforms'>", "</a>", "<a href='http://katz.si/gravityforms'>", "</a>");
            self::display_plugin_message($message, true);
        }
    }

    public static function display_plugin_message($message, $is_error = false){
    	$style = '';
        if($is_error)
            $style = 'style="background-color: #ffebe8;"';

        echo '</tr><tr class="plugin-update-tr"><td colspan="5" class="plugin-update"><div class="update-message" ' . $style . '>' . $message . '</div></td>';
    }

    public static function update_feed_active(){
        check_ajax_referer('rg_update_feed_active','rg_update_feed_active');
        $id = $_POST["feed_id"];
        $feed = GFConstantContactData::get_feed($id);
        GFConstantContactData::update_feed($id, $feed["form_id"], $_POST["is_active"], $feed["meta"]);
    }

    //--------------   Automatic upgrade ---------------------------------------------------

    public static function flush_version_info(){
        if(!class_exists("RGConstantContactUpgrade"))
            require_once( self::get_base_path() . "plugin-upgrade.php");

        RGConstantContactUpgrade::set_version_info(false);
    }

    //Displays current version details on Plugin's page
    public static function display_changelog(){
        if($_REQUEST["plugin"] != self::$slug)
            return;

        //loading upgrade lib
        if(!class_exists("RGConstantContactUpgrade"))
            require_once(self::get_base_path()."plugin-upgrade.php");

        RGConstantContactUpgrade::display_changelog(self::$slug, self::get_key(), self::$version);
    }

    public static function check_update($update_plugins_option){
        if(!class_exists("RGConstantContactUpgrade"))
            require_once(self::get_base_path()."plugin-upgrade.php");

        return RGConstantContactUpgrade::check_update(self::$path, self::$slug, self::$url, self::$slug, self::get_key(), self::$version, $update_plugins_option);
    }

    private static function get_key(){
        if(self::is_gravityforms_supported())
            return GFCommon::get_key();
        else
            return "";
    }
    //---------------------------------------------------------------------------------------

    //Returns true if the current page is an Feed pages. Returns false if not
    private static function is_constantcontact_page(){
    	global $plugin_page,$pagenow;

    	return ($pagenow === 'admin.php' && isset($_GET['page']) && $_GET['page'] === 'gf_constantcontact');
	}

    //Creates or updates database tables. Will only run when version changes
    private static function setup(){
		if(get_option("gf_constantcontact_version") != self::$version)
            GFConstantContactData::update_table();

        update_option("gf_constantcontact_version", self::$version);
    }

    //Adds feed tooltips to the list of tooltips
    public static function tooltips($tooltips){
        $constantcontact_tooltips = array(
            "constantcontact_contact_list" => "<h6>" . __("Constant Contact List", "gravity-forms-constant-contact") . "</h6>" . __("Select the Constant Contact list you would like to add your contacts to.", "gravity-forms-constant-contact"),
            "constantcontact_gravity_form" => "<h6>" . __("Gravity Form", "gravity-forms-constant-contact") . "</h6>" . __("Select the Gravity Form you would like to integrate with Constant Contact. Contacts generated by this form will be automatically added to your Constant Contact account.", "gravity-forms-constant-contact"),
            "constantcontact_map_fields" => "<h6>" . __("Map Fields", "gravity-forms-constant-contact") . "</h6>" . __("Associate your Constant Contact merge variables to the appropriate Gravity Form fields by selecting.", "gravity-forms-constant-contact"),
            "constantcontact_optin_condition" => "<h6>" . __("Opt-In Condition", "gravity-forms-constant-contact") . "</h6>" . __("When the opt-in condition is enabled, form submissions will only be exported to Constant Contact when the condition is met. When disabled all form submissions will be exported.", "gravity-forms-constant-contact"),

        );
        return array_merge($tooltips, $constantcontact_tooltips);
    }

    //Creates Constant Contact left nav menu under Forms
    public static function create_menu($menus){

        // Adding submenu if user has access
        $permission = self::has_access("gravityforms_constantcontact");
        if(!empty($permission))
            $menus[] = array("name" => "gf_constantcontact", "label" => __("Constant Contact", "gravity-forms-constant-contact"), "callback" =>  array("GFConstantContact", "constantcontact_page"), "permission" => $permission);

        return $menus;
    }

    public static function settings_page(){

        if(!class_exists("RGConstantContactUpgrade"))
            require_once(self::get_base_path()."plugin-upgrade.php");

        if(!empty($_POST["uninstall"])){
            check_admin_referer("uninstall", "gf_constantcontact_uninstall");
            self::uninstall();

            ?>
            <div class="updated fade" style="padding:20px;"><?php _e(sprintf("Gravity Forms Constant Contact Add-On has been successfully uninstalled. It can be re-activated from the %splugins page%s.", "<a href='plugins.php'>","</a>"), "gravity-forms-constant-contact")?></div>
            <?php
            return;
        }
        else if(!empty($_POST["gf_constantcontact_submit"])){
            check_admin_referer("update", "gf_constantcontact_update");
            $settings = array("username" => stripslashes($_POST["gf_constantcontact_username"]), "password" => stripslashes($_POST["gf_constantcontact_password"]));
            update_option("gf_constantcontact_settings", $settings);
        }
        else{
            $settings = get_option("gf_constantcontact_settings");
        }

		$feedback_image = "";
        //feedback for username/password
        if(!empty($settings["username"]) || !empty($settings["password"])){

        	if(isset($_POST["gf_constantcontact_submit"])) {
	            $is_valid = self::is_valid_login($settings["username"], $settings["password"]);
                $text = $is_valid ? 'Settings Saved: Success' : 'Settings Saved: Error';
	        } else {
		        $is_valid = get_option('gravity_forms_cc_valid_api');
	        }

            if($is_valid){
                $message = sprintf(__("Valid username and password. Now go %sconfigure form integration with Constant Contact%s!", "gravity-forms-constant-contact"), '<a href="'.admin_url('admin.php?page=gf_constantcontact').'">', '</a>');
                $class = 'updated notice';
                $icon = 'yes';
                $style = "green";
            }
            else{
                $message = __("Invalid username / password combo. Please try another combination. Please note: spaces in your username are not allowed. You can change your username in the My Account link when you are logged into your account, and this may remedy the problem.", "gravity-forms-constant-contact");
                $class = 'error notice';
                $icon =  'no';
                $style = "red";
            }
            $feedback_image = "<i class='dashicons dashicons-{$icon}' style='color:{$style};' title='{$icon}'></i>";
        }

		if( !empty($message) ) {
			$message = str_replace('Api', 'API', $message);
	        ?>
	        <div id="message" class="<?php echo $class ?>"><?php echo wpautop($message); ?></div>
	        <?php
        }

        ?>
        <form method="post" action="">
            <?php wp_nonce_field("update", "gf_constantcontact_update") ?>
            <?php self::ctct_logo(); ?>
            <h2><?php _e("Constant Contact Account Information", "gravity-forms-constant-contact") ?></h2>
            <h3><?php printf(__("If you don't have a Constant Contact account, you can %ssign up for one here%s.", 'gravity-forms-constant-contact'), "<a href='http://katz.si/6p' target='_blank'>" , "</a>"); ?></h3>
            <p style="text-align: left; font-size:1.2em; line-height:1.4">
                <?php _e("Constant Contact makes it easy to send email newsletters to your customers, manage your subscriber lists, and track campaign performance. Use Gravity Forms to collect customer information and automatically add them to your Constant Contact subscriber list.", "gravity-forms-constant-contact"); ?>
            </p>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="gf_constantcontact_username"><?php _e("Constant Contact Username", "gravity-forms-constant-contact"); ?></label> </th>
                    <td>
                        <input type="text" id="gf_constantcontact_username" name="gf_constantcontact_username" value="<?php echo esc_attr($settings["username"]) ?>" size="50"/>
                        <?php echo $feedback_image?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="gf_constantcontact_password"><?php _e("Constant Contact Password", "gravity-forms-constant-contact"); ?></label> </th>
                    <td>
                        <input type="password" id="gf_constantcontact_password" name="gf_constantcontact_password" value="<?php echo esc_attr($settings["password"]) ?>" size="50"/>
                        <?php echo $feedback_image?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" ><input type="submit" name="gf_constantcontact_submit" class="button-primary" value="<?php _e("Save Settings", "gravity-forms-constant-contact") ?>" /></td>
                </tr>
            </table>
        </form>

        <?php
            include_once self::get_base_path().'gravityview-info.php';
        ?>

        <form action="" method="post">
            <?php wp_nonce_field("uninstall", "gf_constantcontact_uninstall") ?>
            <?php if(GFCommon::current_user_can_any("gravityforms_constantcontact_uninstall")){ ?>
                <div class="hr-divider"></div>

                <h3><?php _e("Uninstall Constant Contact Add-On", "gravity-forms-constant-contact") ?></h3>
                <div class="delete-alert"><?php _e("Warning! This operation deletes ALL Constant Contact Feeds.", "gravity-forms-constant-contact") ?>
                    <?php
                    $uninstall_button = '<input type="submit" name="uninstall" value="' . __("Uninstall Constant Contact Add-On", "gravity-forms-constant-contact") . '" class="button" onclick="return confirm(\'' . __("Warning! ALL Constant Contact Feeds will be deleted. This cannot be undone. \'OK\' to delete, \'Cancel\' to stop", "gravity-forms-constant-contact") . '\');"/>';
                    echo apply_filters("gform_constantcontact_uninstall_button", $uninstall_button);
                    ?>
                </div>
            <?php } ?>
        </form>
        <?php
    }

    public static function constantcontact_page(){
    	$view = isset( $_GET['view'] ) ? $_GET['view'] : '';
        if( $view == 'edit' )
            self::edit_page( $_GET['id'] );
        else
            self::list_page();
    }

    private static function ctct_logo() { ?>
        <div><a href='http://katz.si/6p' target='_blank' style="background: transparent url(<?php echo self::get_base_url() ?>/images/ctct_logo_600x90.png) left top no-repeat; background-size: contain; width:300px; height:45px; display:block; text-indent: -9999px; overflow:hidden; direction: ltr; margin:15px 0 10px;">CTCT</a></div>
        <?php
    }

    //Displays the constantcontact feeds list page
    private static function list_page(){
        if(!self::is_gravityforms_supported()){
            die(__(sprintf("Constant Contact Add-On requires Gravity Forms %s. Upgrade automatically on the %sPlugin page%s.", self::$min_gravityforms_version, "<a href='plugins.php'>", "</a>"), "gravity-forms-constant-contact"));
        }

        if(!empty($_POST["action"]) && $_POST["action"] === "delete"){
            check_admin_referer("list_action", "gf_constantcontact_list");

            $id = absint($_POST["action_argument"]);
            GFConstantContactData::delete_feed($id);
            ?>
            <div class="updated fade" style="padding:6px"><?php _e("Feed deleted.", "gravity-forms-constant-contact") ?></div>
            <?php
        }
        else if (!empty($_POST["bulk_action"])){
            check_admin_referer("list_action", "gf_constantcontact_list");
            $selected_feeds = $_POST["feed"];
            if(is_array($selected_feeds)){
                foreach($selected_feeds as $feed_id)
                    GFConstantContactData::delete_feed($feed_id);
            }
            ?>
            <div class="updated fade" style="padding:6px"><?php _e("Feeds deleted.", "gravity-forms-constant-contact") ?></div>
            <?php
        }

        ?>
        <div class="wrap">

            <?php self::ctct_logo(); ?>

            <h2><?php _e("Constant Contact Feeds", "gravity-forms-constant-contact") ?> <a class="button add-new-h2" href="admin.php?page=gf_constantcontact&amp;view=edit&amp;id=0"><?php _e("Add New", "gravity-forms-constant-contact") ?></a></h2>

			<ul class="subsubsub">
	            <li><a href="<?php echo admin_url('admin.php?page=gf_settings&amp;addon=Constant+Contact'); ?>"><?php _e('Constant Contact Settings', 'gravity-forms-constant-contact'); ?></a> |</li>
	            <li><a href="<?php echo admin_url('admin.php?page=gf_constantcontact'); ?>" class="current"><?php _e('Constant Contact Feeds', 'gravity-forms-constant-contact'); ?></a></li>
	        </ul>

<?php
        //ensures valid credentials were entered in the settings page
        if(!get_option('gravity_forms_cc_valid_api')) {
            echo '<div class="wrap clear" style="padding-top: 1em;"><h3>';
            echo sprintf( __( "To get started, please configure your %sConstant Contact Settings%s.", "gravity-forms-constant-contact"), '<a href="admin.php?page=gf_settings&addon=Constant+Contact">', "</a>");
            echo '</h3></div>';
            echo '</div>'; // close .wrap
	        return;
        }
?>

            <form id="feed_form" method="post">
                <?php wp_nonce_field('list_action', 'gf_constantcontact_list') ?>
                <input type="hidden" id="action" name="action"/>
                <input type="hidden" id="action_argument" name="action_argument"/>

                <div class="tablenav">
                    <div class="alignleft actions" style="padding:8px 0 7px; 0">
                        <label class="hidden" for="bulk_action"><?php _e("Bulk action", "gravity-forms-constant-contact") ?></label>
                        <select name="bulk_action" id="bulk_action">
                            <option value=''> <?php _e("Bulk action", "gravity-forms-constant-contact") ?> </option>
                            <option value='delete'><?php _e("Delete", "gravity-forms-constant-contact") ?></option>
                        </select>
                        <?php
                        echo '<input type="submit" class="button" value="' . __("Apply", "gravity-forms-constant-contact") . '" onclick="if( jQuery(\'#bulk_action\').val() == \'delete\' && !confirm(\'' . __("Delete selected feeds? ", "gravity-forms-constant-contact") . __("\'Cancel\' to stop, \'OK\' to delete.", "gravity-forms-constant-contact") .'\')) { return false; } return true;"/>';
                        ?>
                    </div>
                </div>
                <table class="widefat fixed" cellspacing="0">
                    <thead>
                        <tr>
                            <th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>
                            <th scope="col" id="active" class="manage-column check-column"></th>
                            <th scope="col" class="manage-column"><?php _e("Form", "gravity-forms-constant-contact") ?></th>
                            <th scope="col" class="manage-column"><?php _e("Constant Contact List", "gravity-forms-constant-contact") ?></th>
                        </tr>
                    </thead>

                    <tfoot>
                        <tr>
                            <th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>
                            <th scope="col" id="active" class="manage-column check-column"></th>
                            <th scope="col" class="manage-column"><?php _e("Form", "gravity-forms-constant-contact") ?></th>
                            <th scope="col" class="manage-column"><?php _e("Constant Contact List", "gravity-forms-constant-contact") ?></th>
                        </tr>
                    </tfoot>

                    <tbody class="list:user user-list">
                        <?php

                        $settings = GFConstantContactData::get_feeds();
                        if(is_array($settings) && sizeof($settings) > 0){
                            foreach($settings as $setting){
                                ?>
                                <tr class='author-self status-inherit' valign="top">
                                    <th scope="row" class="check-column"><input type="checkbox" name="feed[]" value="<?php echo $setting["id"] ?>"/></th>
                                    <td><img src="<?php echo self::get_base_url() ?>/images/active<?php echo intval($setting["is_active"]) ?>.png" alt="<?php echo $setting["is_active"] ? __("Active", "gravity-forms-constant-contact") : __("Inactive", "gravity-forms-constant-contact");?>" title="<?php echo $setting["is_active"] ? __("Active", "gravity-forms-constant-contact") : __("Inactive", "gravity-forms-constant-contact");?>" onclick="ToggleActive(this, <?php echo $setting['id'] ?>); " /></td>
                                    <td class="column-title">
                                        <a href="admin.php?page=gf_constantcontact&view=edit&id=<?php echo $setting["id"] ?>" title="<?php _e("Edit", "gravity-forms-constant-contact") ?>"><?php echo $setting["form_title"] ?></a>
                                        <div class="row-actions">
                                            <span class="edit">
                                            <a title="Edit this setting" href="admin.php?page=gf_constantcontact&view=edit&id=<?php echo $setting["id"] ?>" title="<?php _e("Edit", "gravity-forms-constant-contact") ?>"><?php _e("Edit", "gravity-forms-constant-contact") ?></a>
                                            |
                                            </span>

                                            <span class="edit">
                                            <a title="<?php _e("Delete", "gravity-forms-constant-contact") ?>" href="javascript: if(confirm('<?php _e("Delete this feed? ", "gravity-forms-constant-contact") ?> <?php _e("\'Cancel\' to stop, \'OK\' to delete.", "gravity-forms-constant-contact") ?>')){ DeleteSetting(<?php echo $setting["id"] ?>);}"><?php _e("Delete", "gravity-forms-constant-contact")?></a>

                                            </span>
                                        </div>
                                    </td>
                                    <td class="column-date"><?php echo $setting["meta"]["contact_list_name"] ?></td>
                                </tr>
                                <?php
                            }
                        }
                        else if(get_option('gravity_forms_cc_valid_api')) {
                            ?>
                            <tr>
                                <td colspan="4" style="padding:20px;">
                                    <?php _e(sprintf("You don't have any Constant Contact feeds configured. Let's go %screate one%s!", '<a href="admin.php?page=gf_constantcontact&view=edit&id=0">', "</a>"), "gravity-forms-constant-contact"); ?>
                                </td>
                            </tr>
                            <?php
                        }
                        else{
                            ?>
                            <tr>
                                <td colspan="4" style="padding:20px;">
                                    <?php _e(sprintf("To get started, please configure your %sConstant Contact Settings%s.", '<a href="admin.php?page=gf_settings&addon=Constant+Contact">', "</a>"), "gravity-forms-constant-contact"); ?>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </form>
        </div>
        <script type="text/javascript">
            function DeleteSetting(id){
                jQuery("#action_argument").val(id);
                jQuery("#action").val("delete");
                jQuery("#feed_form")[0].submit();
            }
            function ToggleActive(img, feed_id){
                var is_active = img.src.indexOf("active1.png") >=0
                if(is_active){
                    img.src = img.src.replace("active1.png", "active0.png");
                    jQuery(img).attr('title','<?php _e("Inactive", "gravity-forms-constant-contact") ?>').attr('alt', '<?php _e("Inactive", "gravity-forms-constant-contact") ?>');
                }
                else{
                    img.src = img.src.replace("active0.png", "active1.png");
                    jQuery(img).attr('title','<?php _e("Active", "gravity-forms-constant-contact") ?>').attr('alt', '<?php _e("Active", "gravity-forms-constant-contact") ?>');
                }

                var mysack = new sack("<?php echo admin_url("admin-ajax.php")?>" );
                mysack.execute = 1;
                mysack.method = 'POST';
                mysack.setVar( "action", "rg_update_feed_active" );
                mysack.setVar( "rg_update_feed_active", "<?php echo wp_create_nonce("rg_update_feed_active") ?>" );
                mysack.setVar( "feed_id", feed_id );
                mysack.setVar( "is_active", is_active ? 0 : 1 );
                mysack.encVar( "cookie", document.cookie, false );
                mysack.onError = function() { alert('<?php _e("Ajax error while updating feed", "gravity-forms-constant-contact" ) ?>' )};
                mysack.runAJAX();

                return true;
            }
        </script>
        <?php
    }

    private static function is_valid_login($user = null, $password=null){
        $api = new CC_List();
        $api->login = trim($user);
        $api->password = trim($password);
		$api->apiPath = str_replace('USERNAME', trim($user), $api->apiPath);
		$api->actionBy = 'ACTION_BY_CONTACT';
		$api->requestLogin = $api->apikey.'%'.$user.':'.$password;

		$lists = @$api->getAccountLists();

		update_option('gravity_forms_cc_valid_api', !empty($lists));

		return empty($lists) ? false : true;
    }


	private static function get_api(){

        if(!class_exists("CC_Utility")){
            require_once( self::get_base_path() ."api/cc_class.php");
        }

        $api = new CC_GF_SuperClass();
        $api->updateSettings();

        if(!$api || !empty($api->errorCode)) {
           return null;
        }

        return $api;
	}

    private static function edit_page(){
    ?>
        <style>
            .constantcontact_col_heading{padding-bottom:2px; border-bottom: 1px solid #ccc; font-weight:bold;}
            .constantcontact_field_cell {padding: 6px 17px 0 0; margin-right:15px;}
            .gfield_required{color:red;}

            .feeds_validation_error{ background-color:#FFDFDF;}
            .feeds_validation_error td{ margin-top:4px; margin-bottom:6px; padding-top:6px; padding-bottom:6px; border-top:1px dotted #C89797; border-bottom:1px dotted #C89797}

            .left_header{float:left; width:200px;}
            .margin_vertical_10{margin: 10px 0;}
        </style>
        <script type="text/javascript">
            var form = Array();
        </script>
        <div class="wrap">
            <?php self::ctct_logo(); ?>
            <h2><?php _e("Constant Contact Feed", "gravity-forms-constant-contact") ?></h2>
		<div class="clear"></div>
        <?php

	    //ensures valid credentials were entered in the settings page
        if(!get_option('gravity_forms_cc_valid_api')) {
            ?>
            <div class="error" id="message"><p><?php echo sprintf(__("We are unable to login to Constant Contact with the provided credentials. Please make sure they are valid in the %sSettings Page%s.", "gravity-forms-constant-contact"), "<a href='?page=gf_settings&addon=Constant+Contact'>", "</a>"); ?></p></div>
            <?php
            return;
        }

        $api = self::get_api();

        //getting setting id (0 when creating a new one)
        $id = !empty( $_POST["constantcontact_setting_id"] ) ? $_POST["constantcontact_setting_id"] : absint( $_GET["id"] );
        $config = empty( $id ) ? array( 'is_active' => true ) : GFConstantContactData::get_feed( $id );

        //getting merge vars from selected list (if one was selected)
        $merge_vars = empty( $config['meta']['contact_list_id'] ) ? array() : $api->listMergeVars( $config['meta']['contact_list_id'] );

        //updating meta information
        if( isset( $_POST['gf_constantcontact_submit'] ) ) {

            list($list_id, $list_name) = self::get_constantcontact_list_details( $_POST["gf_constantcontact_list"] );

            $config["meta"]["contact_list_id"] = $list_id;
            $config["meta"]["contact_list_name"] = $list_name;
            $config["form_id"] = absint($_POST["gf_constantcontact_form"]);

            $is_valid = true;
            $merge_vars = $api->listMergeVars($config["meta"]["contact_list_id"]);
            $field_map = array();
            foreach($merge_vars as $var){
                $field_name = "constantcontact_map_field_" . $var["tag"];
                $mapped_field = stripslashes($_POST[$field_name]);
                if(!empty($mapped_field)){
                    $field_map[$var["tag"]] = $mapped_field;
                }
                else{
                    unset($field_map[$var["tag"]]);
                    if($var["req"] == "Y")
                    $is_valid = false;
                }
            }

            $config["meta"]["field_map"] = $field_map;
            $config["meta"]["optin_enabled"] = !empty( $_POST["constantcontact_optin_enable"] ) ? true : false;
            $config["meta"]["optin_field_id"] = !empty( $config["meta"]["optin_enabled"] ) ? $_POST["constantcontact_optin_field_id"] : "";
            $config["meta"]["optin_operator"] = !empty( $config["meta"]["optin_enabled"] ) ? $_POST["constantcontact_optin_operator"] : "";
            $config["meta"]["optin_value"] = $config["meta"]["optin_enabled"] ? $_POST["constantcontact_optin_value"] : "";

            if($is_valid){
                $id = GFConstantContactData::update_feed($id, $config["form_id"], $config["is_active"], $config["meta"]);
                ?>
                <div class="updated fade" style="padding:6px"><?php echo sprintf(__("Feed Updated. %sback to list%s", "gravity-forms-constant-contact"), "<a href='?page=gf_constantcontact'>", "</a>") ?></div>
                <input type="hidden" name="constantcontact_setting_id" value="<?php echo $id ?>"/>
                <?php
            }
            else{
                ?>
                <div class="error" style="padding:6px"><?php echo __("Feed could not be updated. Please enter all required information below.", "gravity-forms-constant-contact") ?></div>
                <?php
            }
        }

        ?>
        <form method="POST" action="<?php echo remove_query_arg('refresh'); ?>">
            <input type="hidden" name="constantcontact_setting_id" value="<?php echo $id ?>"/>
            <div class="margin_vertical_10">
                <label for="gf_constantcontact_list" class="left_header"><?php _e("Constant Contact list", "gravity-forms-constant-contact"); ?> <?php gform_tooltip("constantcontact_contact_list") ?></label>
                <?php


                //getting all contact lists
                $lists = $api->CC_List()->getLists();

                if (empty($lists)){
                    echo __("Could not load Constant Contact contact lists. <br/>Error: ", "gravity-forms-constant-contact") . $api->errorMessage;
                }
                else{
                    ?>
                    <select id="gf_constantcontact_list" name="gf_constantcontact_list" onchange="SelectList(jQuery(this).val());">
                        <option value="" ><?php _e("Select a Constant Contact List", "gravity-forms-constant-contact"); ?></option>
                    <?php
                    $curr_contact_list_id = isset( $config["meta"]["contact_list_id"] ) ? $config["meta"]["contact_list_id"] : '';
                    foreach( $lists as $list ) {
						echo '<option value="'. esc_attr( self::get_cc_list_short_id( $list['id'] ) ) .'" '. selected( $list['id'] , $curr_contact_list_id, false ) .'>'. esc_html( $list['title'] ) .'</option>';
                    }
                    ?>
                  </select>
                <?php
                }
                ?>
            </div>
            <div id="constantcontact_form_container" valign="top" class="margin_vertical_10" <?php echo empty($config["meta"]["contact_list_id"]) ? "style='display:none;'" : "" ?>>
                <label for="gf_constantcontact_form" class="left_header"><?php _e("Gravity Form", "gravity-forms-constant-contact"); ?> <?php gform_tooltip("constantcontact_gravity_form") ?></label>

                <select id="gf_constantcontact_form" name="gf_constantcontact_form" onchange="SelectForm(jQuery('#gf_constantcontact_list').val(), jQuery(this).val());">
                <option value=""><?php _e("Select a form", "gravity-forms-constant-contact"); ?> </option>
                <?php
                $curr_form_id = isset( $config['form_id'] ) ? $config['form_id'] : '';
                $forms = RGFormsModel::get_forms();
                foreach( $forms as $form ){
					echo '<option value="'. absint( $form->id ) .'" '. selected( absint( $form->id ), $curr_form_id, false ) .'>'. esc_html( $form->title ) .'</option>';
                }
                ?>
                </select>
                &nbsp;&nbsp;
                <span class="spinner" id="constantcontact_wait" style="display:none; float:none; position:absolute; margin-top: .33em;"></span>
            </div>
            <div id="constantcontact_field_group" valign="top" <?php echo empty($config["meta"]["contact_list_id"]) || empty($config["form_id"]) ? "style='display:none;'" : "" ?>>
                <div id="constantcontact_field_container" valign="top" class="margin_vertical_10" >
                    <label for="constantcontact_fields" class="left_header"><?php _e("Map Fields", "gravity-forms-constant-contact"); ?> <?php gform_tooltip("constantcontact_map_fields") ?></label>

                    <div id="constantcontact_field_list">
                    <?php
                    $selection_fields = '';
                    if(!empty($config["form_id"])){

                        //getting list of all ConstantContact merge variables for the selected contact list
                        if(empty($merge_vars)) {

	                        $merge_vars = $api->listMergeVars( $list_id );
                        }


                        //getting field map UI
                        echo self::get_field_mapping($config, $config["form_id"], $merge_vars);

                        //getting list of selection fields to be used by the optin
                        $form_meta = RGFormsModel::get_form_meta($config["form_id"]);
                        $selection_fields = GFCommon::get_selection_fields($form_meta, @$config["meta"]["optin_field_id"]);
                    }
                    ?>
                    </div>
                </div>

                <div id="constantcontact_optin_container" valign="top" class="margin_vertical_10">
                    <label for="constantcontact_optin" class="left_header"><?php _e("Opt-In Condition", "gravity-forms-constant-contact"); ?> <?php gform_tooltip("constantcontact_optin_condition") ?></label>
                    <div id="constantcontact_optin">
                        <table>
                            <tr>
                                <td>
                                    <input type="checkbox" id="constantcontact_optin_enable" name="constantcontact_optin_enable" value="1" onclick="if(this.checked){jQuery('#constantcontact_optin_condition_field_container').show('slow');} else{jQuery('#constantcontact_optin_condition_field_container').hide('slow');}" <?php echo @$config["meta"]["optin_enabled"] ? "checked='checked'" : ""?>/>
                                    <label for="constantcontact_optin_enable"><?php _e("Enable", "gravity-forms-constant-contact"); ?></label>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div id="constantcontact_optin_condition_field_container" <?php echo empty($config["meta"]["optin_enabled"]) ? "style='display:none'" : "" ?>>
                                        <div id="constantcontact_optin_condition_fields" <?php echo empty($selection_fields) ? "style='display:none'" : "" ?>>
                                            <?php _e("Export to Constant Contact if ", "gravity-forms-constant-contact") ?>

                                            <select id="constantcontact_optin_field_id" name="constantcontact_optin_field_id" class='optin_select' onchange='jQuery("#constantcontact_optin_value").html(GetFieldValues(jQuery(this).val(), "", 20));'><?php echo $selection_fields ?></select>
                                            <select id="constantcontact_optin_operator" name="constantcontact_optin_operator" >
                                                <option value="is" <?php echo @$config["meta"]["optin_operator"] == "is" ? "selected='selected'" : "" ?>><?php _e("is", "gravity-forms-constant-contact") ?></option>
                                                <option value="isnot" <?php echo @$config["meta"]["optin_operator"] == "isnot" ? "selected='selected'" : "" ?>><?php _e("is not", "gravity-forms-constant-contact") ?></option>
                                            </select>
                                            <select id="constantcontact_optin_value" name="constantcontact_optin_value" class='optin_select'>
                                            </select>

                                        </div>
                                        <div id="constantcontact_optin_condition_message" <?php echo !empty($selection_fields) ? "style='display:none'" : ""?>>
                                            <?php _e("To create an Opt-In condition, your form must have a drop down, checkbox or multiple choice field.", "gravityform") ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <script type="text/javascript">
                        <?php
                        if(!empty($config["form_id"])){
                            ?>
                            //creating Javascript form object
                            form = <?php echo GFCommon::json_encode($form_meta)?> ;

                            //initializing drop downs
                            jQuery(document).ready(function(){
                                var selectedField = "<?php echo str_replace('"', '\"', @$config["meta"]["optin_field_id"])?>";
                                var selectedValue = "<?php echo str_replace('"', '\"', @$config["meta"]["optin_value"])?>";
                                SetOptin(selectedField, selectedValue);
                            });
                        <?php
                        }
                        ?>
                    </script>
                </div>


                <div id="constantcontact_submit_container" class="margin_vertical_10">
                    <input type="submit" name="gf_constantcontact_submit" value="<?php echo empty($id) ? __("Save Feed", "gravity-forms-constant-contact") : __("Update Feed", "gravity-forms-constant-contact"); ?>" class="button-primary"/>
                </div>
            </div>
        </form>
        </div>
        <script type="text/javascript">

            function SelectList(listId){
                if(listId){
                    jQuery("#constantcontact_form_container").slideDown();
                    jQuery("#gf_constantcontact_form").val("");
                }
                else{
                    jQuery("#constantcontact_form_container").slideUp();
                    EndSelectForm("");
                }
            }

            function SelectForm(listId, formId){
                if(!formId){
                    jQuery("#constantcontact_field_group").slideUp();
                    return;
                }

                jQuery("#constantcontact_wait").css('display', 'inline-block');
                jQuery("#constantcontact_field_group").slideUp();

                var mysack = new sack("<?php bloginfo( 'wpurl' ); ?>/wp-admin/admin-ajax.php" );
                mysack.execute = 1;
                mysack.method = 'POST';
                mysack.setVar( "action", "gf_select_constantcontact_form" );
                mysack.setVar( "gf_select_constantcontact_form", "<?php echo wp_create_nonce("gf_select_constantcontact_form") ?>" );
                mysack.setVar( "list_id", listId);
                mysack.setVar( "form_id", formId);
                mysack.encVar( "cookie", document.cookie, false );
                mysack.onError = function() {jQuery("#constantcontact_wait").hide(); alert('<?php _e("Ajax error while selecting a form", "gravity-forms-constant-contact") ?>' )};
                mysack.runAJAX();

                return true;
            }

            function SetOptin(selectedField, selectedValue){

                //load form fields
                jQuery("#constantcontact_optin_field_id").html(GetSelectableFields(selectedField, 20));
                var optinConditionField = jQuery("#constantcontact_optin_field_id").val();

                if(optinConditionField){
                    jQuery("#constantcontact_optin_condition_message").hide();
                    jQuery("#constantcontact_optin_condition_fields").show();
                    jQuery("#constantcontact_optin_value").html(GetFieldValues(optinConditionField, selectedValue, 20));
                }
                else{
                    jQuery("#constantcontact_optin_condition_message").show();
                    jQuery("#constantcontact_optin_condition_fields").hide();
                }
            }

            function EndSelectForm(fieldList, form_meta){
                //setting global form object
                form = form_meta;

                if(fieldList){

                    SetOptin("","");

                    jQuery("#constantcontact_field_list").html(fieldList);
                    jQuery("#constantcontact_field_group").slideDown();

                }
                else{
                    jQuery("#constantcontact_field_group").slideUp();
                    jQuery("#constantcontact_field_list").html("");
                }
                jQuery("#constantcontact_wait").hide();
            }

            function GetFieldValues(fieldId, selectedValue, labelMaxCharacters){
                if(!fieldId)
                    return "";

                var str = "";
                var field = GetFieldById(fieldId);
                if(!field || !field.choices)
                    return "";

                var isAnySelected = false;

                for(var i=0; i<field.choices.length; i++){
                    var fieldValue = field.choices[i].value ? field.choices[i].value : field.choices[i].text;
                    var isSelected = fieldValue == selectedValue;
                    var selected = isSelected ? "selected='selected'" : "";
                    if(isSelected)
                        isAnySelected = true;

                    str += "<option value='" + fieldValue.replace("'", "&#039;") + "' " + selected + ">" + TruncateMiddle(field.choices[i].text, labelMaxCharacters) + "</option>";
                }

                if(!isAnySelected && selectedValue){
                    str += "<option value='" + selectedValue.replace("'", "&#039;") + "' selected='selected'>" + TruncateMiddle(selectedValue, labelMaxCharacters) + "</option>";
                }

                return str;
            }

            function GetFieldById(fieldId){
                for(var i=0; i<form.fields.length; i++){
                    if(form.fields[i].id == fieldId)
                        return form.fields[i];
                }
                return null;
            }

            function TruncateMiddle(text, maxCharacters){
                if(text.length <= maxCharacters)
                    return text;
                var middle = parseInt(maxCharacters / 2);
                return text.substr(0, middle) + "..." + text.substr(text.length - middle, middle);
            }

            function GetSelectableFields(selectedFieldId, labelMaxCharacters){
                var str = "";
                var inputType;
                for(var i=0; i<form.fields.length; i++){
                    fieldLabel = form.fields[i].adminLabel ? form.fields[i].adminLabel : form.fields[i].label;
                    inputType = form.fields[i].inputType ? form.fields[i].inputType : form.fields[i].type;
                    if(inputType == "checkbox" || inputType == "radio" || inputType == "select"){
                        var selected = form.fields[i].id == selectedFieldId ? "selected='selected'" : "";
                        str += "<option value='" + form.fields[i].id + "' " + selected + ">" + TruncateMiddle(fieldLabel, labelMaxCharacters) + "</option>";
                    }
                }
                return str;
            }

        </script>

        <?php

    }

    public static function add_permissions(){
        global $wp_roles;
        $wp_roles->add_cap("administrator", "gravityforms_constantcontact");
        $wp_roles->add_cap("administrator", "gravityforms_constantcontact_uninstall");
    }

    //Target of Member plugin filter. Provides the plugin with Gravity Forms lists of capabilities
    public static function members_get_capabilities( $caps ) {
        return array_merge($caps, array("gravityforms_constantcontact", "gravityforms_constantcontact_uninstall"));
    }

    public static function disable_constantcontact(){
        delete_option("gf_constantcontact_settings");
    }

    public static function select_constantcontact_form(){

        check_ajax_referer("gf_select_constantcontact_form", "gf_select_constantcontact_form");
        $form_id =  intval(@$_POST["form_id"]);
        $list_id = !empty( $_POST["list_id"] ) ? self::get_constantcontact_list_endpoint( $_POST["list_id"] ) : '';
        $setting_id =  intval(@$_POST["setting_id"]);

        $api = self::get_api();
        if(!$api)
            die("EndSelectForm();");

        //getting list of all Constant Contact merge variables for the selected contact list
        $merge_vars = $api->listMergeVars($list_id);

        //getting configuration
        $config = GFConstantContactData::get_feed($setting_id);

        //getting field map UI
        $str = self::get_field_mapping($config, $form_id, $merge_vars);

        //fields meta
        $form = RGFormsModel::get_form_meta($form_id);
        //$fields = $form["fields"];
        die("EndSelectForm('" . str_replace("'", "\'", $str) . "', " . GFCommon::json_encode($form) . ");");
    }


    /**
     * Convert CC endpoint id into a number id to be used on forms (avoid issues with more strict servers)
     *
     * @access public
     * @static
     * @param mixed $endpoint
     * @return string $id
     */
    public static function get_cc_list_short_id( $endpoint ) {

		if( empty( $endpoint ) ) {
			return '';
		}

		if( false !== ( $pos = strrpos( rtrim( $endpoint, '/' ), '/' ) ) ) {
			return trim( substr( $endpoint, $pos + 1 ) );
		}

		return '';
    }


    /**
     * Given a list short id (just the numeric part) return the list endpoint
     *
     * @access public
     * @static
     * @param integer $list_id
     * @return string $endpoint
     */
    public static function get_constantcontact_list_endpoint( $list_id ) {

		if( empty( $list_id ) ) {
			return '';
		}

		$api = self::get_api();

		if( !$api ) {
			return '';
		}

		$lists = $api->CC_List()->getLists();

		foreach( $lists as $list ) {
			if( self::get_cc_list_short_id( $list['id'] ) == $list_id ) {
				return $list['id'];
			}

		}

		return '';
    }


    /**
     * Given a list short id (just the numeric part) return the list details (endpoint and title)
     *
     * @access public
     * @static
     * @param mixed $list_id
     * @return array (id, title)
     */
    public static function get_constantcontact_list_details( $list_id ) {

		if( empty( $list_id ) ) {
			return '';
		}

		$api = self::get_api();

		if( !$api ) {
			return '';
		}

		$lists = $api->CC_List()->getLists();

		foreach( $lists as $list ) {
			if( self::get_cc_list_short_id( $list['id'] ) == $list_id ) {
				return array( $list['id'], $list['title'] );
			}

		}

		return '';
    }





    private static function get_field_mapping($config, $form_id, $merge_vars){

        //getting list of all fields for the selected form
        $form_fields = self::get_form_fields($form_id);

        $str = "<table cellpadding='0' cellspacing='0'><tr><td class='constantcontact_col_heading'>" . __("List Fields", "gravity-forms-constant-contact") . "</td><td class='constantcontact_col_heading'>" . __("Form Fields", "gravity-forms-constant-contact") . "</td></tr>";
        foreach($merge_vars as $var){
            $selected_field = @$config["meta"]["field_map"][$var["tag"]];
            $required = $var["req"] == "Y" ? "<span class='gfield_required'>*</span>" : "";
            $error_class = $var["req"] == "Y" && empty($selected_field) && !empty($_POST["gf_constantcontact_submit"]) ? " feeds_validation_error" : "";
            $str .= "<tr class='$error_class'><td class='constantcontact_field_cell'>" . $var["name"]  . " $required</td><td class='constantcontact_field_cell'>" . self::get_mapped_field_list($var["tag"], $selected_field, $form_fields) . "</td></tr>";
        }
        $str .= "</table>";

        return $str;
    }

    public static function get_form_fields($form_id){
        $form = RGFormsModel::get_form_meta($form_id);
        $fields = array();

        //Adding default fields
        array_push($form["fields"],array("id" => "date_created" , "label" => __("Entry Date", "gravity-forms-constant-contact")));
        array_push($form["fields"],array("id" => "ip" , "label" => __("User IP", "gravity-forms-constant-contact")));
        array_push($form["fields"],array("id" => "source_url" , "label" => __("Source Url", "gravity-forms-constant-contact")));

        if(is_array($form["fields"])){
            foreach($form["fields"] as $field){
                if(isset($field["inputs"]) && is_array($field["inputs"])){

                    //If this is an address field, add full name to the list
                    if(RGFormsModel::get_input_type($field) == "address")
                        $fields[] =  array($field["id"], GFCommon::get_label($field) . " (" . __("Full" , "gravity-forms-constant-contact") . ")");

                    foreach($field["inputs"] as $input)
                        $fields[] =  array($input["id"], GFCommon::get_label($field, $input["id"]));
                }
                else if(empty($field["displayOnly"])){
                    $fields[] =  array($field["id"], GFCommon::get_label($field));
                }
            }
        }
        return $fields;
    }

    private static function get_address($entry, $field_id){
        $street_value = str_replace("  ", " ", trim($entry[$field_id . ".1"]));
        $street2_value = str_replace("  ", " ", trim($entry[$field_id . ".2"]));
        $city_value = str_replace("  ", " ", trim($entry[$field_id . ".3"]));
        $state_value = str_replace("  ", " ", trim($entry[$field_id . ".4"]));
        $zip_value = trim($entry[$field_id . ".5"]);
        $country_value = GFCommon::get_country_code(trim($entry[$field_id . ".6"]));

        $address = $street_value;
        $address .= !empty($address) && !empty($street2_value) ? "  $street2_value" : $street2_value;
        $address .= !empty($address) && (!empty($city_value) || !empty($state_value)) ? "  $city_value" : $city_value;
        $address .= !empty($address) && !empty($city_value) && !empty($state_value) ? "  $state_value" : $state_value;
        $address .= !empty($address) && !empty($zip_value) ? "  $zip_value" : $zip_value;
        $address .= !empty($address) && !empty($country_value) ? "  $country_value" : $country_value;

        return $address;
    }

    public static function get_mapped_field_list($variable_name, $selected_field, $fields){
        $field_name = "constantcontact_map_field_" . $variable_name;
        $str = "<select name='$field_name' id='$field_name'><option value=''>" . __("", "gravity-forms-constant-contact") . "</option>";
        foreach($fields as $field){
            $field_id = $field[0];
            $field_label = $field[1];

            $selected = $field_id == $selected_field ? "selected='selected'" : "";
            $str .= "<option value='" . $field_id . "' ". $selected . ">" . $field_label . "</option>";
        }
        $str .= "</select>";
        return $str;
    }

    public static function export($entry, $form){

        //loading data class
        require_once( self::get_base_path() . "/data.php");

        //getting all active feeds
        $feeds = GFConstantContactData::get_feed_by_form($form["id"], true);

        foreach($feeds as $feed){

            //only export if user has opted in
            if(self::is_optin($form, $feed)) {

                if( !isset( $api ) ) {

                    //Login to Constant Contact
                    $api = self::get_api();

                    // If no API, don't process
                    if(!$api) {
                        self::add_note($entry["id"], __('Not added/updated in Constant Contact; there was an error fetching the API.', 'gravity-forms-constant-contact'));
                        continue;
                    }
                }

            	self::export_feed($entry, $form, $feed, $api);
            }
        }
    }

    public static function export_feed($entry, $form, $feed, $api){

        $email_field_id = $feed["meta"]["field_map"]["email_address"];
        $email = $entry[$email_field_id];
        $merge_vars = array('');
        foreach($feed["meta"]["field_map"] as $var_tag => $field_id){

            $field = RGFormsModel::get_field($form, $field_id);
			$field_type = RGFormsModel::get_input_type($field);

            if($field_id == intval($field_id) && $field_type == "address") {
            	//handling full address
                $merge_vars[$var_tag] = self::get_address($entry, $field_id);
			} elseif( $field_type == 'date' ) {
				$merge_vars[$var_tag] = apply_filters( 'gravityforms_constant_contact_change_date_format', $entry[$field_id] );
            } else {
                $merge_vars[$var_tag] = $entry[$field_id];
			}
        }

        $retval = $api->listSubscribe($feed["meta"]["contact_list_id"], $merge_vars, "html");

       if( !is_wp_error( $retval ) && !empty($retval)) {
           self::add_note($entry["id"], __('Successfully added/updated in Constant Contact.', 'gravity-forms-constant-contact'));
        } else {

            $error = '';
            if( is_wp_error( $retval ) ) {
                $error = ': '.$retval->get_error_message();
            }

            self::add_note($entry["id"], __('Errors when adding/updating in Constant Contact', 'gravity-forms-constant-contact') . $error );
        }
    }

    /**
     * Add note to GF Entry
     * @param int $id   Entry ID
     * @param string $note Note text
     */
    static private function add_note($id, $note) {

        if(!apply_filters('gravityforms_constant_contact_add_notes_to_entries', true) || !class_exists('RGFormsModel')) { return; }

        RGFormsModel::add_note($id, 0, __('Gravity Forms Constant Contact Add-on'), $note);
    }

    public static function uninstall(){

        //loading data lib
        require_once(self::get_base_path() . "/data.php");

        if(!GFConstantContact::has_access("gravityforms_constantcontact_uninstall"))
            die(__("You don't have adequate permission to uninstall Constant Contact Add-On.", "gravity-forms-constant-contact"));

        //droping all tables
        GFConstantContactData::drop_tables();

        //removing options
        delete_option("gf_constantcontact_settings");
        delete_option("gf_constantcontact_version");

        //Deactivating plugin
        $plugin = "gravity-forms-constant-contact/constantcontact.php";
        deactivate_plugins($plugin);
        update_option('recently_activated', array($plugin => time()) + (array)get_option('recently_activated'));
    }

    public static function is_optin($form, $settings){
        $config = $settings["meta"];
        $operator = $config["optin_operator"];

        $field = RGFormsModel::get_field($form, $config["optin_field_id"]);
        $field_value = RGFormsModel::get_field_value($field, array());
        $is_value_match = is_array($field_value) ? in_array($config["optin_value"], $field_value) : $field_value == $config["optin_value"];

        return  !$config["optin_enabled"] || empty($field) || ($operator == "is" && $is_value_match) || ($operator == "isnot" && !$is_value_match);
    }


    private static function is_gravityforms_installed(){
        return class_exists("RGForms");
    }

    private static function is_gravityforms_supported(){
        if(class_exists("GFCommon")){
            $is_correct_version = version_compare(GFCommon::$version, self::$min_gravityforms_version, ">=");
            return $is_correct_version;
        }
        else{
            return false;
        }
    }

    protected static function has_access($required_permission){
        $has_members_plugin = function_exists('members_get_capabilities');
        $has_access = $has_members_plugin ? current_user_can($required_permission) : current_user_can("level_7");
        if($has_access)
            return $has_members_plugin ? $required_permission : "level_7";
        else
            return false;
    }

    /**
     * Returns the url of the plugin's root folder
     * @return string
     */
    static protected function get_base_url(){
        return plugins_url(null, __FILE__);
    }

    static public function get_file() {
        return __FILE__;
    }

    //Returns the physical path of the plugin's root folder
    static public function get_base_path(){
        return plugin_dir_path(__FILE__) . '/';
    }

}

if(!class_exists("CC_Utility")) {
    require_once(GFConstantContact::get_base_path()."/api/cc_class.php");
}

class CC_GF_SuperClass extends CC_Utility {

	function CC_GF_SuperClass($user = null, $password=null) {
		self::updateSettings($this);
	}

	public function updateSettings($object = false) {
		if(!is_object($object)) {
            $object = new CC_GF_SuperClass;
        }
        $settings = get_option("gf_constantcontact_settings");

		$object->login = trim($settings['username']);
        $object->password = trim($settings['password']);
        if(isset($object->apiPath)) {
		  $object->apiPath = str_replace('USERNAME', '', (string)$object->apiPath).trim($settings['username']);
        }

		$actionBy = apply_filters('gravity_forms_constant_contact_action_by', 'ACTION_BY_CONTACT');
		if($actionBy === 'ACTION_BY_CONTACT' || $actionBy === 'ACTION_BY_CUSTOMER') {
	    	$this->actionBy = $actionBy;
	    } else {
		    $this->actionBy = 'ACTION_BY_CUSTOMER';
	    }

        if(isset($object->apikey)) {
		  $object->requestLogin = $object->apikey.'%'.$object->login.':'.$object->password;
        }
		$object->curl_debug = isset($_GET['debug']) && ( function_exists('current_user_can') && current_user_can( 'manage_options' ) );
	}

	public function listSubscribe($id, $merge_vars, $email_type='html') {
        $params = $merge_vars;

        foreach($params as $key => $p) {
        	$p = trim($p);
        	if(empty($p) && $p != '0') {
        		unset($params[$key]);
        	}
        }

        $params["lists"] = array($id); //array(preg_replace('/(?:.*?)\/lists\/(\d+)/ism','$1',$id));
        $params['mail_type'] = strtolower(@$params['mail_type']);
        if($params['mail_type'] != 'html' && $params['mail_type'] != 'text') {
        	$params['mail_type'] = 'html';
        }

        // Check if email already exists; update if it does
        if($existingID = self::CC_Contact()->subscriberExists($params['email_address'])) {

            // Get the current subscriber data
            $contactInfo = self::CC_Contact()->getSubscriberDetails($params['email_address']);

            // Merge the lists together
            $params['lists'] = array_merge( $params['lists'], $contactInfo['lists']);

            $contactXML = self::CC_Contact()->createContactXML((string)$existingID,$params);

        	$return = self::CC_Contact()->editSubscriber((string)$existingID, (string)$contactXML);
        } else {
        	$contactXML = self::CC_Contact()->createContactXML(null,$params);
        	$contactXML = (string)$contactXML;
        	$return = self::CC_Contact()->addSubscriber($contactXML);
        }

        return $return;

	}

	public function CC_List() {
		$ccListOBJ = new CC_List();
		self::updateSettings($ccListOBJ);
		return $ccListOBJ;
	}

	public function CC_Campaign() {
		$CC_Campaign = new CC_Campaign();
		self::updateSettings($CC_Campaign);
		return $CC_Campaign;
	}

	public function CC_Utility() {
		$CC_Utility = new CC_Utility();
		self::updateSettings($CC_Utility);
		return $CC_Utility;
	}

	public function CC_Contact() {
		$CC_Contact = new CC_Contact();
		self::updateSettings($CC_Contact);
		return $CC_Contact;
	}

	public function listMergeVars() {
		return array(
			array('tag'=>'email_address', 'req' => true, 'name' => "Email Address"),
			array('tag'=>'first_name', 	  'req' => false, 'name' => "First Name"),
			array('tag'=>'middle_name',   'req' => false, 'name' => "Middle Name"),
			array('tag'=>'last_name',	  'req' => false, 'name' => "Last Name"),
			array('tag'=>'job_title', 	  'req' => false, 'name' => "Job Title"),
			array('tag'=>'company_name',  'req' => false, 'name' => "Company Name"),
			array('tag'=>'home_number',   'req' => false, 'name' => "Home Phone"),
			array('tag'=>'work_number',	  'req' => false, 'name' => "Work Phone"),
			array('tag'=>'address_line_1','req' => false, 'name' => "Address 1"),
			array('tag'=>'address_line_2','req' => false, 'name' => "Address 2"),
			array('tag'=>'address_line_3','req' => false, 'name' => "Address 3"),
			array('tag'=>'city_name',	  'req' => false, 'name' => "City"),
			array('tag'=>'state_code',	  'req' => false, 'name' => "State Code"),
			array('tag'=>'state_name',	  'req' => false, 'name' => "State Name"),
			array('tag'=>'country_code',  'req' => false, 'name' => "Country Code"),
			array('tag'=>'country_name',  'req' => false, 'name' => "Country Name"),
			array('tag'=>'zip_code',	  'req' => false, 'name' => "Postal Code"),
			array('tag'=>'sub_zip_code',  'req' => false, 'name' => "Sub Postal Code"),
			array('tag'=>'notes',		  'req' => false, 'name' => "Note"),
			array('tag'=>'mail_type', 	  'req' => false, 'name' => "Email Type (Text or HTML)"),
			array('tag'=>'custom_field_1','req' => false, 'name' => "Custom Field 1 (Up to 50 characters)"),
			array('tag'=>'custom_field_2', 'req' => false, 'name' => "Custom Field 2 (Up to 50 characters)"),
			array('tag'=>'custom_field_3', 'req' => false, 'name' => "Custom Field 3 (Up to 50 characters)"),
			array('tag'=>'custom_field_4', 'req' => false, 'name' => "Custom Field 4 (Up to 50 characters)"),
			array('tag'=>'custom_field_5', 'req' => false, 'name' => "Custom Field 5 (Up to 50 characters)"),
			array('tag'=>'custom_field_6', 'req' => false, 'name' => "Custom Field 6 (Up to 50 characters)"),
			array('tag'=>'custom_field_7', 'req' => false, 'name' => "Custom Field 7 (Up to 50 characters)"),
			array('tag'=>'custom_field_8', 'req' => false, 'name' => "Custom Field 8 (Up to 50 characters)"),
			array('tag'=>'custom_field_9', 'req' => false, 'name' => "Custom Field 9 (Up to 50 characters)"),
			array('tag'=>'custom_field_10','req' => false, 'name' => "Custom Field 10 (Up to 50 characters)"),
			array('tag'=>'custom_field_11','req' => false, 'name' => "Custom Field 11 (Up to 50 characters)"),
			array('tag'=>'custom_field_12','req' => false, 'name' => "Custom Field 12 (Up to 50 characters)"),
			array('tag'=>'custom_field_13','req' => false, 'name' => "Custom Field 13 (Up to 50 characters)"),
			array('tag'=>'custom_field_14','req' => false, 'name' => "Custom Field 14 (Up to 50 characters)"),
			array('tag'=>'custom_field_15','req' => false, 'name' => "Custom Field 15 (Up to 50 characters)"),
		);
	}

}

?>