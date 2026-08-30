<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

Text::script('MOD_RSFIREWALL_YOU_ARE_RUNNING_LATEST_VERSION');
Text::script('MOD_RSFIREWALL_UPDATE_IS_AVAILABLE_RSFIREWALL');
?>
<div id="mod_rsfirewall_container">
    <?php
    if ($showGrade)
	{
        ?>
        <div class="mod_rsfirewall_line">
            <strong><i class="icon-shield"></i> <?php echo Text::_('MOD_RSFIREWALL_GRADE'); ?></strong>
            <span <?php if ($grade > 0) { ?>style="color: <?php echo $color; ?>;"<?php } ?>><?php echo $grade > 0 ? Text::sprintf('MOD_RSFIREWALL_YOUR_GRADE_IS', $grade) : Text::_('MOD_RSFIREWALL_GRADE_NOT_RUN'); ?></span>
        </div>
        <?php
	}
	if ($showVersions)
	{
        ?>
        <div class="mod_rsfirewall_line">
            <strong><i class="icon-shield"></i> RSFirewall!</strong>
            <span id="mod-rsfirewall-firewall-version">
                <span class="com-rsfirewall-icon-16-loading"></span>
            </span>
        </div>
        <div class="mod_rsfirewall_line">
            <strong><i class="icon-joomla"></i> Joomla!</strong>
            <span id="mod-rsfirewall-joomla-version">
                <span class="com-rsfirewall-icon-16-loading"></span>
            </span>
        </div>
        <?php
	}
    if ($showMap)
	{
		?>
        <div class="mod_rsfirewall_line"><?php echo Text::_('MOD_RSFIREWALL_ATTACKS_BLOCKED_REGION_BASED'); ?></div>
        <div id="com-rsfirewall-virtual-map"></div>
	    <?php
	}
    if ($showLogs && $logs)
    {
        ?>
	    <div class="mod_rsfirewall_line"><?php echo Text::sprintf('MOD_RSFIREWALL_LAST_MESSAGES_FROM_SYSTEM_LOG', $logNum, Route::_('index.php?option=com_rsfirewall&view=logs')); ?></div>
        <table class="adminlist table table-striped">
        <thead>
            <tr>
                <th nowrap="nowrap"><?php echo Text::_('MOD_RSFIREWALL_ALERT_LEVEL'); ?></th>
                <th nowrap="nowrap"><?php echo Text::_('MOD_RSFIREWALL_DATE_EVENT'); ?></th>
                <th nowrap="nowrap"><?php echo Text::_('MOD_RSFIREWALL_IP_ADDRESS'); ?></th>
                <th nowrap="nowrap"><?php echo Text::_('MOD_RSFIREWALL_PAGE'); ?></th>
                <th nowrap="nowrap"><?php echo Text::_('MOD_RSFIREWALL_ALERT_DESCRIPTION'); ?></th>
            </tr>
        </thead>
        <?php
        foreach ($logs as $i => $log)
        {
            ?>
            <tr class="row<?php echo $i % 2; ?>">
                <td class="com-rsfirewall-level-<?php echo $log->level; ?>"><?php echo Text::_('MOD_RSFIREWALL_LEVEL_'.$log->level); ?></td>
                <td><?php echo HTMLHelper::_('date', $log->date, 'Y-m-d H:i:s'); ?></td>
                <td><?php echo HTMLHelper::_('image', 'com_rsfirewall/flags/' . $geoip->getCountryFlag($log->ip), $geoip->getCountryCode($log->ip), array(), true); ?> <?php echo $geoip->show($log->ip); ?></td>
                <td class="mod-rsfirewall-break-word"><?php echo htmlentities($log->page, ENT_COMPAT, 'utf-8'); ?></td>
                <td><?php echo Text::_('COM_RSFIREWALL_EVENT_'.$log->code); ?></td>
            </tr>
            <?php
        }
        ?>
        </table>
	    <?php
    }
    ?>
</div>