<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
?>
<table class="table table-striped">
	<thead>
	<tr>
		<th width="1%"><?php echo Text::_('COM_RSFIREWALL_ALERT_LEVEL'); ?></th>
		<th width="1%"><?php echo Text::_('COM_RSFIREWALL_LOG_DATE_EVENT'); ?></th>
		<th width="1%"><?php echo Text::_('COM_RSFIREWALL_LOG_IP_ADDRESS'); ?></th>
		<th width="1%"><?php echo Text::_('COM_RSFIREWALL_LOG_USER_ID'); ?></th>
		<th width="1%"><?php echo Text::_('COM_RSFIREWALL_LOG_USERNAME'); ?></th>
		<th><?php echo Text::_('COM_RSFIREWALL_LOG_PAGE'); ?></th>
		<th><?php echo Text::_('COM_RSFIREWALL_LOG_DESCRIPTION'); ?></th>
	</tr>
	</thead>
	<?php foreach ($this->lastLogs as $item) { ?>
		<tr>
			<td width="1%" class="com-rsfirewall-level-<?php echo $item->level; ?>"><?php echo Text::_('COM_RSFIREWALL_LEVEL_'.$item->level); ?></td>
			<td width="1%"><?php echo HTMLHelper::_('date', $item->date, 'Y-m-d H:i:s'); ?></td>
			<td width="1%"><?php echo HTMLHelper::_('image', 'com_rsfirewall/flags/' . $this->geoip->getCountryFlag($item->ip), $this->geoip->getCountryCode($item->ip), array(), true); ?> <?php echo $this->geoip->show($item->ip); ?></td>
			<td width="1%"><?php echo (int) $item->user_id; ?></td>
			<td width="1%"><?php echo $this->escape($item->username); ?></td>
			<td class="com-rsfirewall-break-word"><?php echo $this->escape($item->page); ?></td>
			<td class="com-rsfirewall-break-word"><?php echo Text::_('COM_RSFIREWALL_EVENT_'.$item->code); ?></td>
		</tr>
	<?php } ?>
</table>