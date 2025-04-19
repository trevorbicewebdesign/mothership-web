<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

Text::script('COM_RSFIREWALL_DOWNLOAD_GEOIP_SERVER_ERROR');
Text::script('COM_RSFIREWALL_GEOIP_DB_CANNOT_DOWNLOAD');
Text::script('COM_RSFIREWALL_GEOIP_DB_CANNOT_DOWNLOAD_CONTINUED');
Text::script('COM_RSFIREWALL_GEOIP_DB_TRY_TO_DOWNLOAD_MANUALLY');

$blocked_countries = $this->config->get('blocked_countries');
$class = in_array('US', $blocked_countries) ? '' : 'com-rsfirewall-hidden';

// set description if required
if (isset($this->fieldset->description) && !empty($this->fieldset->description)) { ?>
	<div class="alert alert-info com-rsfirewall-tooltip"><i class="icon-lightbulb icon-lamp"></i> <?php echo Text::_($this->fieldset->description); ?><br />
	<a href="https://www.rsjoomla.com/support/documentation/rsfirewall-user-guide/frequently-asked-questions/how-do-i-use-country-blocking-and-where-do-i-get-geoipdat-.html" target="_blank"><?php echo Text::_('COM_RSFIREWALL_GEOIP_DOCUMENTATION_LINK'); ?></a></div>

	<?php if ($this->geoip->works) { ?>
		<div class="alert alert-success rsfirewall-geoip-works">
			<?php echo Text::_('COM_RSFIREWALL_GEOIP_SETUP_CORRECTLY'); ?>
		</div>
	<?php } ?>

	<?php if (!$this->geoip->mmdb) { ?>
		<div class="alert alert-info">
			<h4><?php echo Text::_('COM_RSFIREWALL_GEOIP_LITE_DB'); ?></h4>
			<p><?php echo Text::_('COM_RSFIREWALL_GEOIP_DB_LITE_DOWNLOAD_INSTRUCTIONS'); ?></p>
			<div><button type="button" class="btn btn-primary" id="com-rsfirewall-geoip-download-button"><i class="icon-refresh"></i> <?php echo Text::_('COM_RSFIREWALL_DOWNLOAD_GEOIP_DB_LITE'); ?></button></div>
		</div>
	<?php } elseif (!empty($this->geoip->mmdb_old)) { ?>
		<div class="alert alert-info">
			<h4><?php echo Text::_('COM_RSFIREWALL_GEOIP_LITE_DB'); ?></h4>
			<p><?php echo Text::sprintf('COM_RSFIREWALL_GEOIP_DB_UPDATE_INSTRUCTIONS', $this->geoip->mmdb_modified); ?></p>
			<div><button type="button" class="btn btn-primary" id="com-rsfirewall-geoip-download-button"><i class="icon-refresh"></i> <?php echo Text::_('COM_RSFIREWALL_UPDATE_GEOIP_DB_LITE'); ?></button></div>
		</div>
	<?php } ?>
<?php } ?>
	<div class="alert alert-danger <?php echo $class ?>" id="us-country-blocked">
		<?php echo Text::_('COM_RSFIREWALL_YOU_BANNED_US'); ?>
	</div>
<?php
foreach ($this->fields as $field)
{
	if ($field->fieldname == 'geoip_upload')
	{
		continue;
	}

	echo $this->form->renderField($field->fieldname);
}
?>
<p><small><?php echo Text::_('COM_RSFIREWALL_MAXMIND_ATTRIBUTION_MESSAGE'); ?></small></p>
