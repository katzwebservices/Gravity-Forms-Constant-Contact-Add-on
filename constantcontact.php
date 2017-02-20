<?php
/*
Plugin Name: Gravity Forms Constant Contact Add-On
Plugin URI: https://katz.co/plugins/gravity-forms-constant-contact/
Description: Integrates Gravity Forms with Constant Contact allowing form submissions to be automatically sent to your Constant Contact account.
Version: 3.0
Text Domain: gravity-forms-constant-contact
Author: Katz Web Services, Inc.
Author URI: https://katz.co
Domain Path: /languages

------------------------------------------------------------------------
Copyright 2017 Katz Web Services, Inc.

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

define( 'GF_CONSTANT_CONTACT_VERSION', '3.0' );

add_action( 'gform_loaded', array( 'GF_Constant_Contact_Bootstrap', 'load' ), 5 );

class GF_Constant_Contact_Bootstrap {

	public static function load(){

        require_once( plugin_dir_path( __FILE__ ) . 'class-gf-constant-contact.php' );

	    GFAddOn::register( 'GF_Constant_Contact' );
	}

}

function gf_constant_contact() {
	return GF_Constant_Contact::get_instance();
}
