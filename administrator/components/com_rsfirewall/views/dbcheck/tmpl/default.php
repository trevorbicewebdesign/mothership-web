<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

if ($this->supported)
{
	$this->document->addScriptDeclaration("RSFirewall.requestTimeOut.Seconds = '" . (float) $this->request_timeout . "';");
    $script = '';

	foreach ($this->tables as $table)
	{
		$script .= 'RSFirewall.Database.Check.tables.unshift(\'' . addslashes($table->Name) . '\');';
	}
	$this->document->addScriptDeclaration($script);

	HTMLHelper::_('script', 'com_rsfirewall/dbcheck.js', array('relative' => true, 'version' => 'auto'));
}
?>
<form action="<?php echo Route::_('index.php?option=com_rsfirewall'); ?>" method="post" name="adminForm" id="adminForm">

<?php echo RSFirewallAdapterGrid::sidebar(); ?>
	<div id="com-rsfirewall-main-content">
	<?php if ($this->supported) { ?>
	<div id="com-rsfirewall-dbcheck-messages">
		<div class="alert alert-info"><p><?php echo Text::_('COM_RSFIREWALL_ONLY_TABLES_WITH_MYISAM_STORAGE_ENGINE_DESC');?></p></div>
	</div>
	<div id="com-rsfirewall-scan-in-progress" class="com-rsfirewall-hidden">
		<div class="lds-ripple"><div></div><div></div></div>
		<p><?php echo Text::_('COM_RSFIREWALL_SCAN_IS_IN_PROGRESS'); ?></p>
	</div>
	<p><button type="button" class="btn btn-primary" id="com-rsfirewall-start-button"><?php echo Text::_('COM_RSFIREWALL_CHECK_DB'); ?></button></p>

	<div class="com-rsfirewall-content-box">
		<div class="com-rsfirewall-content-box-header">
			<h3><span class="icon-database"></span> <?php echo Text::_('COM_RSFIREWALL_SERVER_DATABASE'); ?></h3>
		</div>
		<div id="com-rsfirewall-database" class="com-rsfirewall-content-box-content com-rsfirewall-hidden">
			<div class="com-rsfirewall-progress" id="com-rsfirewall-database-progress"><div class="com-rsfirewall-bar">0%</div></div>
			<table id="com-rsfirewall-database-table">
				<thead>
					<tr>
						<th width="20%" nowrap="nowrap"><?php echo Text::_('COM_RSFIREWALL_TABLE_NAME'); ?></th>
						<th width="1%" nowrap="nowrap"><?php echo Text::_('COM_RSFIREWALL_TABLE_ENGINE'); ?></th>
						<th width="1%" nowrap="nowrap"><?php echo Text::_('COM_RSFIREWALL_TABLE_COLLATION'); ?></th>
						<th width="1%" nowrap="nowrap"><?php echo Text::_('COM_RSFIREWALL_TABLE_ROWS'); ?></th>
						<th width="1%" nowrap="nowrap"><?php echo Text::_('COM_RSFIREWALL_TABLE_DATA'); ?></th>
						<th width="1%" nowrap="nowrap"><?php echo Text::_('COM_RSFIREWALL_TABLE_INDEX'); ?></th>
						<th width="1%" nowrap="nowrap"><?php echo Text::_('COM_RSFIREWALL_TABLE_OVERHEAD'); ?></th>
						<th><?php echo Text::_('COM_RSFIREWALL_RESULT'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($this->tables as $i => $table) { ?>
					<tr class="com-rsfirewall-table-row <?php if ($i % 2) { ?>alt-row<?php } ?> com-rsfirewall-hidden">
						<td width="20%" nowrap="nowrap"><?php echo $this->escape($table->Name); ?></td>
						<td width="1%" nowrap="nowrap"><?php echo $this->escape($table->Engine); ?></td>
						<td width="1%" nowrap="nowrap"><?php echo $this->escape($table->Collation); ?></td>
						<td width="1%" nowrap="nowrap"><?php echo (int) $table->Rows; ?></td>
						<td width="1%" nowrap="nowrap"><?php echo $this->_convert($table->Data_length); ?></td>
						<td width="1%" nowrap="nowrap"><?php echo $this->_convert($table->Index_length); ?></td>
						<td width="1%" nowrap="nowrap">
							<?php if ($table->Data_free > 0) { ?>
								<?php if (strtolower($table->Engine) == 'myisam') { ?>
								<b class="com-rsfirewall-level-high"><?php echo $this->_convert($table->Data_free); ?></b>
								<?php } else { ?>
								<em><?php echo Text::_('COM_RSFIREWALL_NOT_SUPPORTED'); ?></em>
								<?php } ?>
							<?php } else { ?>
								<?php echo $this->_convert($table->Data_free); ?>
							<?php } ?>
						</td>
						<td id="result<?php echo $i; ?>"></td>
					</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
	</div>
	<?php } else { ?>
	<div class="alert alert-info"><?php echo Text::_('COM_RSFIREWALL_DB_CHECK_UNSUPPORTED'); ?></div>
	<?php } ?>
	</div>
</div>
</form>