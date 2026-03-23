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

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));

$this->document->addScriptDeclaration('Joomla.submitbutton = function(task) {
	Joomla.submitform(task);
	
	if (task === \'lists.download\') {
		document.adminForm.task.value = \'\';
	}
}');
?>
<form action="<?php echo Route::_('index.php?option=com_rsfirewall&view=lists'); ?>" method="post" name="adminForm" id="adminForm">

	<?php echo RSFirewallAdapterGrid::sidebar(); ?>
		<?php
		// Search tools bar
		echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));

		if (empty($this->items)) { ?>
			<div class="alert alert-info">
				<span class="fa fa-info-circle" aria-hidden="true"></span><span class="sr-only"><?php echo Text::_('INFO'); ?></span>
				<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</div>
		<?php } else { ?>
			<table class="table table-striped">
				<caption id="captionTable" class="sr-only">
					<?php echo Text::_('COM_RSFIREWALL_LISTS_TABLE_CAPTION'); ?>,
					<span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
					<span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
				</caption>
				<thead>
				<tr>
					<th style="width:1%" class="text-center">
						<?php echo HTMLHelper::_('grid.checkall'); ?>
					</th>
					<th scope="col">
						<?php echo HTMLHelper::_('searchtools.sort', 'COM_RSFIREWALL_LIST_DATE', 'date', $listDirn, $listOrder); ?>
					</th>
					<th scope="col" style="min-width:100px">
						<?php echo HTMLHelper::_('searchtools.sort', 'COM_RSFIREWALL_IP_ADDRESS', 'ip', $listDirn, $listOrder); ?>
					</th>
					<th scope="col">
						<?php echo HTMLHelper::_('searchtools.sort', 'COM_RSFIREWALL_LIST_REASON', 'reason', $listDirn, $listOrder); ?>
					</th>
					<th scope="col"><?php echo HTMLHelper::_('searchtools.sort', 'COM_RSFIREWALL_LIST_TYPE', 'type', $listDirn, $listOrder); ?></th>
					<th scope="col" style="width:1%" class="text-center"><?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'published', $listDirn, $listOrder); ?></th>
				</tr>
				</thead>
			<?php foreach ($this->items as $i => $item) { ?>
				<tr>
					<td style="width:1%" class="text-center">
						<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
					</td>
					<td>
						<?php echo HTMLHelper::_('date', $item->date); ?>
					</td>
					<td style="min-width:100px">
						<a href="<?php echo Route::_('index.php?option=com_rsfirewall&task=list.edit&id='.(int) $item->id); ?>"><?php echo HTMLHelper::_('image', 'com_rsfirewall/flags/' . $this->geoip->getCountryFlag($item->ip), $this->geoip->getCountryCode($item->ip), array(), true); ?><?php echo $this->geoip->show($item->ip, false); ?></a>
					</td>
					<td>
						<?php echo $this->escape($item->reason); ?>
					</td>
					<td class="com-rsfirewall-list-type-<?php echo $item->type; ?>">
						<?php echo Text::_('COM_RSFIREWALL_LIST_TYPE_'.$item->type); ?>
					</td>
					<td class="text-center">
						<?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'lists.'); ?>
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