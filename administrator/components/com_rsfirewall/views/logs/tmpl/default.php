<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Text;

Text::script('COM_RSFIREWALL_BLOCK');
Text::script('COM_RSFIREWALL_UNBLOCK');
Text::script('COM_RSFIREWALL_LOG_ERROR');
Text::script('COM_RSFIREWALL_LOG_WARNING');
Text::script('COM_RSFIREWALL_EMPTY_LOG_SURE');
Text::script('WARNING');
Text::script('ERROR');
Text::script('INFO');
Text::script('NOTICE');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));

$this->document->addScriptDeclaration('Joomla.submitbutton = function(task) {
	if (task === \'logs.truncate\') {
		if (!confirm(Joomla.JText._(\'COM_RSFIREWALL_EMPTY_LOG_SURE\'))) {
			return false;
		}
	}
	
	Joomla.submitform(task);
	
	if (task === \'logs.download\') {
		document.adminForm.task.value = \'\';
	}
}');

HTMLHelper::_('script', 'com_rsfirewall/logs.js', array('relative' => true, 'version' => 'auto'));
?>
<form action="<?php echo Route::_('index.php?option=com_rsfirewall&view=logs'); ?>" method="post" name="adminForm" id="adminForm">
	<?php echo RSFirewallAdapterGrid::sidebar(); ?>
	<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
	<?php
	if (empty($this->items)) { ?>
		<div class="alert alert-info">
			<span class="fa fa-info-circle" aria-hidden="true"></span><span class="sr-only"><?php echo Text::_('INFO'); ?></span>
			<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
		</div>
	<?php } else { ?>
	<table class="table table-striped">
		<caption id="captionTable" class="sr-only">
			<?php echo Text::_('COM_RSFIREWALL_LOGS_TABLE_CAPTION'); ?>,
			<span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
			<span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
		</caption>
		<thead>
		<tr>
			<th class="com-rsfirewall-w-1 text-center">
				<?php echo HTMLHelper::_('grid.checkall'); ?>
			</th>
			<th width="1%" nowrap="nowrap">

			</th>
			<th width="1%" nowrap="nowrap" class="hidden-phone">
				<?php echo HTMLHelper::_('searchtools.sort', 'COM_RSFIREWALL_ALERT_LEVEL', 'logs.level', $listDirn, $listOrder); ?>
			</th>
			<th width="1%" nowrap="nowrap" class="hidden-phone">
				<?php echo HTMLHelper::_('searchtools.sort', 'COM_RSFIREWALL_LOG_DATE_EVENT', 'logs.date', $listDirn, $listOrder); ?>
			</th>
			<th scope="col" style="min-width:100px">
				<?php echo HTMLHelper::_('searchtools.sort', 'COM_RSFIREWALL_LOG_IP_ADDRESS', 'logs.ip', $listDirn, $listOrder); ?>
			</th>
			<th width="1%" nowrap="nowrap" class="hidden-phone">
				<?php echo HTMLHelper::_('searchtools.sort', 'COM_RSFIREWALL_LOG_USER_ID', 'logs.user_id', $listDirn, $listOrder); ?>
			</th>
			<th width="1%" nowrap="nowrap" class="hidden-phone">
				<?php echo HTMLHelper::_('searchtools.sort', 'COM_RSFIREWALL_LOG_USERNAME', 'logs.username', $listDirn, $listOrder); ?>
			</th>
			<th>
				<?php echo HTMLHelper::_('searchtools.sort', 'COM_RSFIREWALL_LOG_PAGE', 'logs.page', $listDirn, $listOrder); ?>
			</th>
			<th class="hidden-phone">
				<?php echo HTMLHelper::_('searchtools.sort', 'COM_RSFIREWALL_LOG_REFERER', 'logs.referer', $listDirn, $listOrder); ?>
			</th>
			<th>
				<?php echo Text::_('COM_RSFIREWALL_LOG_DESCRIPTION'); ?>
			</th>
		</tr>
		</thead>
	<?php foreach ($this->items as $i => $item) { ?>
		<tr class="rsf-entry" id="rsf-log-<?php echo $item->id;?>">
			<td class="text-center com-rsfirewall-w-1">
				<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
			</td>
			<td width="1%" nowrap="nowrap" class="rsf-status"><?php 
				if (!is_null($item->type))
				{
                    if ($item->type)
					{
						echo Text::_('COM_RSFIREWALL_WHITELISTED');
					}
                    else
                    {
                        echo '<button type="button" class="btn btn-secondary com-rsfirewall-change-status" data-id="' . $item->id . '" data-listid="' . $item->listId . '">'.Text::_('COM_RSFIREWALL_UNBLOCK').'</button>';
                    }
				}
				else
				{
					echo '<button type="button" class="btn btn-danger com-rsfirewall-change-status" data-id="' . $item->id . '">' . Text::_('COM_RSFIREWALL_BLOCK') . '</button>';
				}
			?></td>
			<td width="1%" nowrap="nowrap" class="hidden-phone com-rsfirewall-level-<?php echo $item->level; ?>"><?php echo Text::_('COM_RSFIREWALL_LEVEL_'.$item->level); ?></td>
			<td width="1%" nowrap="nowrap" class="hidden-phone"><?php echo HTMLHelper::_('date', $item->date, 'Y-m-d H:i:s'); ?></td>
			<td width="1%" nowrap="nowrap"><?php echo HTMLHelper::_('image', 'com_rsfirewall/flags/' . $this->geoip->getCountryFlag($item->ip), $this->geoip->getCountryCode($item->ip), array(), true); ?> <?php echo $this->geoip->show($item->ip); ?></td>
			<td width="1%" nowrap="nowrap" class="hidden-phone"><?php echo (int) $item->user_id; ?></td>
			<td width="1%" nowrap="nowrap" class="hidden-phone"><?php echo $this->escape($item->username); ?></td>
			<td class="com-rsfirewall-break-word"><?php echo $this->escape($item->page); ?></td>
			<td class="hidden-phone com-rsfirewall-break-word"><?php echo $item->referer ? $this->escape($item->referer) : '<em>'.Text::_('COM_RSFIREWALL_NO_REFERER').'</em>'; ?></td>
			<td class="com-rsfirewall-break-word">
				<?php echo Text::_('COM_RSFIREWALL_EVENT_'.$item->code); ?>
				<?php if (!empty($item->debug_variables)) { ?>
					<button type="button" class="btn btn-small btn-info btn-sm com-rsfirewall-show-log"><?php echo Text::_('COM_RSFIREWALL_SHOW'); ?></button>
					<div class="com-rsfirewall-hidden">
						<p><b><?php echo Text::_('COM_RSFIREWALL_LOG_DEBUG_VARIABLES'); ?></b></p>
						<?php echo nl2br($this->escape($item->debug_variables)); ?>
					</div>
				<?php } ?>
			</td>
		</tr>
	<?php } ?>
	</table>
		<?php echo $this->pagination->getListFooter(); ?>
	<?php } ?>
	
	<div>
		<?php echo HTMLHelper::_( 'form.token' ); ?>
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="task" value="" />
	</div>
	</div>
</form>