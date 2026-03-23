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
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

?>
<div class="dashboard-info">
	<div class="dashboard-icon">
		<?php echo HTMLHelper::_('image', 'com_rsfirewall/icon-48-rsfirewall.png', 'RSFirewall!', array(), true); ?>
        RSFirewall!
    </div>
	<ul>
		<li><strong><?php echo Text::_('COM_RSFIREWALL_PRODUCT_VERSION') ?>:</strong> <?php echo $this->version; ?></li>
		<li><strong><?php echo Text::_('COM_RSFIREWALL_COPYRIGHT_NAME') ?>:</strong> &copy; 2009 &mdash; <?php echo Factory::getDate()->format('Y', true);?> <a href="https://www.rsjoomla.com" target="_blank">RSJoomla!</a></li>
		<li><strong><?php echo Text::_('COM_RSFIREWALL_LICENSE_NAME') ?>:</strong> <a href="http://www.gnu.org/licenses/gpl.html" target="_blank">GNU/GPL</a></li>
		<li><strong><?php echo Text::_('COM_RSFIREWALL_UPDATE_CODE') ?>:</strong><br />
			<?php if (strlen($this->code) == 20) { ?>
				<span class="correct-code"><?php echo $this->escape($this->code); ?></span>
			<?php } elseif ($this->code) { ?>
				<span class="incorrect-code"><?php echo $this->escape($this->code); ?></span>
			<?php } else { ?>
				<span class="missing-code"><a href="<?php echo Route::_('index.php?option=com_rsfirewall&view=configuration'); ?>"><?php echo Text::_('COM_RSFIREWALL_PLEASE_ENTER_YOUR_CODE_IN_THE_CONFIGURATION'); ?></a></span>
			<?php } ?></li>
	</ul>
</div>