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

?>
<form action="<?php echo Route::_('index.php?option=com_rsfirewall');?>" method="post" name="adminForm" id="adminForm">
	<?php echo RSFirewallAdapterGrid::sidebar(); ?>
		<div class="<?php echo RSFirewallAdapterGrid::row(); ?>">
		<div class="<?php echo RSFirewallAdapterGrid::column(8); ?>">
			<?php
			if ($this->files)
			{
				echo '<h2>' . Text::_('COM_RSFIREWALL_FILES_MODIFIED') . '</h2>';
				echo $this->loadTemplate('files');
			}

			if ($this->canViewLogs)
			{
				echo $this->loadTemplate('charts');

				if ($this->renderMap)
				{
					echo $this->loadTemplate('vectormap');
				}
				echo '<h2>' . Text::sprintf('COM_RSFIREWALL_LAST_MESSAGES_FROM_SYSTEM_LOG', $this->logNum) . '</h2>';
				echo $this->loadTemplate('logs');
			}
			?>
		</div>
		<div class="visible-desktop <?php echo RSFirewallAdapterGrid::column(4); ?>">
			<?php echo $this->loadTemplate('version'); ?>
		</div>
		</div>
	</div>

		<div>
			<input type="hidden" name="boxchecked" value="0" />
			<input type="hidden" name="task" value="" />
			<?php echo HTMLHelper::_( 'form.token' ); ?>
		</div>
	</div>
</form>