<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

abstract class RSFirewallAdapterGrid
{
	public static function row()
	{
		return 'row';
	}

	public static function column($size)
	{
		return 'col-md-' . (int) $size;
	}

	public static function sidebar()
	{
		return '<div id="j-main-container" class="j-main-container">';
	}
}