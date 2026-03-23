<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));

$this->document->addScriptDeclaration('Joomla.submitbutton = function(task) {
	Joomla.submitform(task);
	
	if (task === \'exceptions.download\') {
		document.adminForm.task.value = \'\';
	}
}');
?>
<form action="<?php echo Route::_('index.php?option=com_rsfirewall&view=exceptions'); ?>" method="post" name="adminForm" id="adminForm">

	<?php echo RSFirewallAdapterGrid::sidebar(); ?>
	<?php
	echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));

	if (empty($this->items)) { ?>
		<div class="alert alert-info">
			<span class="fa fa-info-circle" aria-hidden="true"></span><span class="sr-only"><?php echo Text::_('INFO'); ?></span>
			<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
		</div>
	<?php } else { ?>
		<table class="table table-striped">
			<caption id="captionTable" class="sr-only">
				<?php echo Text::_('COM_RSFIREWALL_EXCEPTIONS_TABLE_CAPTION'); ?>,
				<span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
				<span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
			</caption>
			<thead>
			<tr>
				<th class="com-rsfirewall-w-1 text-center">
					<?php echo HTMLHelper::_('grid.checkall'); ?>
				</th>
				<th nowrap="nowrap"><?php echo HTMLHelper::_('searchtools.sort', 'COM_RSFIREWALL_EXCEPTION_DATE', 'date', $listDirn, $listOrder); ?></th>
				<th><?php echo HTMLHelper::_('searchtools.sort', 'COM_RSFIREWALL_EXCEPTION_MATCH', 'match', $listDirn, $listOrder); ?></th>
				<th><?php echo HTMLHelper::_('searchtools.sort', 'COM_RSFIREWALL_EXCEPTION_REASON', 'reason', $listDirn, $listOrder); ?></th>
				<th nowrap="nowrap"><?php echo HTMLHelper::_('searchtools.sort', 'COM_RSFIREWALL_EXCEPTION_TYPE', 'type', $listDirn, $listOrder); ?></th>
				<th nowrap="nowrap"><?php echo HTMLHelper::_('searchtools.sort', 'JPUBLISHED', 'published', $listDirn, $listOrder); ?></th>
			</tr>
			</thead>
		<?php foreach ($this->items as $i => $item) { ?>
			<tr>
				<td class="com-rsfirewall-w-1 text-center"><?php echo HTMLHelper::_('grid.id', $i, $item->id); ?></td>
				<td nowrap="nowrap"><?php echo HTMLHelper::_('date', $item->date); ?></td>
				<td><a href="<?php echo Route::_('index.php?option=com_rsfirewall&task=exception.edit&id='.(int) $item->id); ?>"><?php echo $this->escape($item->match); ?></a></td>
				<td><?php echo $this->escape($item->reason); ?></td>
				<td nowrap="nowrap" class="com-rsfirewall-exception-type-<?php echo $item->type; ?>"><?php echo Text::_('COM_RSFIREWALL_EXCEPTION_TYPE_'.$item->type); ?></td>
				<td nowrap="nowrap" align="center"><?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'exceptions.'); ?></td>
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