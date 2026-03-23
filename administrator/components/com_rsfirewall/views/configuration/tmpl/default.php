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
use Joomla\CMS\Router\Route;

//keep session alive while editing
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('formbehavior.chosen', '.advancedSelect');

HTMLHelper::_('script', 'com_rsfirewall/configuration.js', array('relative' => true, 'version' => 'auto'));

Text::script('COM_RSFIREWALL_BACKEND_PASSWORD_LENGTH_ERROR');
Text::script('COM_RSFIREWALL_BACKEND_PASSWORDS_DO_NOT_MATCH');
?>
<?php echo RSFirewallAdapterGrid::sidebar(); ?>
	<form action="<?php echo Route::_('index.php?option=com_rsfirewall&view=configuration'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate form-horizontal" enctype="multipart/form-data" autocomplete="off">
		<div class="alert alert-info"><i class="icon-lightbulb icon-lamp"></i> <?php echo Text::sprintf('COM_RSFIREWALL_YOUR_IP_ADDRESS_IS', $this->escape($this->ip)); ?></div>
	<?php
	foreach ($this->fieldsets as $name => $fieldset)
	{
		// add the tab title
		$this->tabs->addTitle($fieldset->label, $fieldset->name);
		
		// prepare the content
		$this->fieldset =& $fieldset;
		$this->fields 	= $this->form->getFieldset($fieldset->name);

		$template = 'fieldset';

		if (in_array($fieldset->name, array('active_scanner', 'backend_password', 'country_block')))
		{
			$template = $fieldset->name;
		}

		$content = $this->loadTemplate($template);
		
		// add the tab content
		$this->tabs->addContent($content);
	}
	
	// render tabs
	$this->tabs->render();
	?>
		<div>
		<?php echo HTMLHelper::_('form.token'); ?>
		<input type="hidden" name="option" value="com_rsfirewall" />
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="controller" value="configuration" />
		</div>
	</form>
</div>