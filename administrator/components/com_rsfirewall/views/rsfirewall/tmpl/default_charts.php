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

Text::script('COM_RSFIREWALL_LEVEL_LOW');
Text::script('COM_RSFIREWALL_LEVEL_MEDIUM');
Text::script('COM_RSFIREWALL_LEVEL_HIGH');
Text::script('COM_RSFIREWALL_LEVEL_CRITICAL');

HTMLHelper::_('script', 'com_rsfirewall/charts.js', array('relative' => true, 'version' => 'auto'));
HTMLHelper::_('script', 'com_rsfirewall/chart.min.js', array('relative' => true, 'version' => 'auto'));

$this->document->addScriptDeclaration('var RSFirewallChartLabels = ' .  json_encode(array_keys($this->lastMonthLogs)) . ';');
$this->document->addScriptDeclaration('var RSFirewallChartDatasets = ' .  json_encode(array_values($this->lastMonthLogs)) . ';');
?>
<h2><?php echo Text::_('COM_RSFIREWALL_ATTACKS_BLOCKED_PAST_MONTH'); ?></h2>
<div>
	<canvas id="com-rsfirewall-logs-chart"></canvas>
</div>