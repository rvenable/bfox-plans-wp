<?php
/*************************************************************************
Plugin Name: Biblefox Daily
Plugin URI: https://github.com/rvenable/bfox-plans-wp
Description: Adds a custom post type for creating your own Bible Reading Plans
Version: 1.0 beta
Author: Biblefox.com, rvenable
Author URI: http://biblefox.com
License: General Public License version 2
Requires at least: WP 3.0, BuddyPress 1.2
Tested up to: WP 3.0, BuddyPress 1.2.4.1
Text Domain: bfox
*************************************************************************/

/*************************************************************************

	Copyright 2010 Biblefox.com

	This file is part of Biblefox for WordPress.

	Biblefox for WordPress is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	Biblefox for WordPress is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with Biblefox for WordPress.  If not, see <http://www.gnu.org/licenses/>.

*************************************************************************/

function bfox_plan_load($core) {
	require_once dirname(__FILE__) . '/bfox_plans_controller.php';
	$core->plans = new BfoxPlansController($core, 'bfox-plans-wp', 'bfox_plans', '1.0', 1);
}
add_action('bfox_load', 'bfox_plan_load', 10, 1);

?>