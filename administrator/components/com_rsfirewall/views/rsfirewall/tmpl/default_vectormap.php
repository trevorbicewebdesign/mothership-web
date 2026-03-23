<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

HTMLHelper::_('script', 'com_rsfirewall/jquery.vmap.min.js', array('relative' => true, 'version' => 'auto'));
HTMLHelper::_('script', 'com_rsfirewall/jquery.vmap.world.js', array('relative' => true, 'version' => 'auto'));

HTMLHelper::_('stylesheet', 'com_rsfirewall/jqvmap.css', array('relative' => true, 'version' => 'auto'));
HTMLHelper::_('script', 'com_rsfirewall/vmap.js', array('relative' => true, 'version' => 'auto'));
?>
<h2><?php echo Text::_('COM_RSFIREWALL_ATTACKS_BLOCKED_REGION_BASED'); ?></h2>
<div id="com-rsfirewall-virtual-map"></div>