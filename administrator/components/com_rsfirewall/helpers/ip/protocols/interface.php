<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

interface RSFirewallIPInterface
{	
	// Test returns true if IP matches current protocol.
	// @return boolean
	public static function test($ip);
	
	// Provides an unpacking method for IP. Used by toBinary().
	// @return string
	public function toUnpacked();
	
	// Provides a variable that can be used with comparison operators.
	// @return mixed
	public function toComparable();
	
	// Makes sure mask is clean. Returns cleaned mask as a result.
	// @return int
	public function cleanMask($mask);
}