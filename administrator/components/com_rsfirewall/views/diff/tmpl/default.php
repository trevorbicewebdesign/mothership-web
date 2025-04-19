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

Text::script('COM_RSFIREWALL_HASHES_CORRECT');
Text::script('COM_RSFIREWALL_CONFIRM_OVERWRITE_LOCAL_FILE');
Text::script('COM_RSFIREWALL_BUTTON_FAILED');
Text::script('COM_RSFIREWALL_BUTTON_PROCESSING');
Text::script('COM_RSFIREWALL_BUTTON_SUCCESS');

HTMLHelper::_('script', 'com_rsfirewall/diff.js', array('relative' => true, 'version' => 'auto'));
?>

<div class="rsfirewall-replace-original text-center">
	<button type="button" id="replace-original" class="btn btn-primary" data-filename="<?php echo $this->escape($this->filename); ?>" data-hash="<?php echo $this->escape($this->hashId); ?>"><?php echo Text::_('COM_RSFIREWALL_DOWNLOAD_ORIGINAL') ?></button>
</div>

<?php
// Output table
echo Diff::toTable(Diff::compare($this->remote, $this->local), '', '', array(
	Text::sprintf('COM_RSFIREWALL_REMOTE_FILE', $this->remoteFilename),
	Text::sprintf('COM_RSFIREWALL_LOCAL_FILE', realpath($this->localFilename), $this->localTime)
));