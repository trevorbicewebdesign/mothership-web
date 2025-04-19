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
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.keepalive');

Text::script('COM_RSFIREWALL_ERROR_CHECK');
Text::script('COM_RSFIREWALL_ERROR_CHECK_RETRYING');
Text::script('COM_RSFIREWALL_ERROR_FIX');
Text::script('COM_RSFIREWALL_CONFIGURATION_LINE');
Text::script('COM_RSFIREWALL_MORE_FOLDERS');
Text::script('COM_RSFIREWALL_MORE_FILES');
Text::script('COM_RSFIREWALL_RENAME_ADMIN');

Text::script('COM_RSFIREWALL_BUTTON_FAILED');
Text::script('COM_RSFIREWALL_BUTTON_PROCESSING');
Text::script('COM_RSFIREWALL_BUTTON_SUCCESS');

Text::script('COM_RSFIREWALL_CONFIRM_OVERWRITE_LOCAL_FILE');
Text::script('COM_RSFIREWALL_DOWNLOAD_ORIGINAL');
Text::script('COM_RSFIREWALL_HASHES_CORRECT');
Text::script('COM_RSFIREWALL_HASHES_INCORRECT');
Text::script('COM_RSFIREWALL_VIEW_DIFF');
Text::script('COM_RSFIREWALL_FILE_HAS_BEEN_MODIFIED');
Text::script('COM_RSFIREWALL_FILE_HAS_BEEN_MODIFIED_AGO');
Text::script('COM_RSFIREWALL_FILE_IS_MISSING');
Text::script('COM_RSFIREWALL_FILE_HAS_BEEN_IGNORED');
Text::script('COM_RSFIREWALL_UNIGNORE_BUTTON');
Text::script('COM_RSFIREWALL_CONFIRM_UNIGNORE');
Text::script('COM_RSFIREWALL_FILES_READDED_TO_CHECK');

Text::script('COM_RSFIREWALL_FOLDER_PERMISSIONS_INCORRECT');
Text::script('COM_RSFIREWALL_FOLDER_PERMISSIONS_CORRECT');
Text::script('COM_RSFIREWALL_PLEASE_WAIT_WHILE_BUILDING_DIRECTORY_STRUCTURE');
Text::script('COM_RSFIREWALL_FIX_FOLDER_PERMISSIONS_DONE');

Text::script('COM_RSFIREWALL_FILE_PERMISSIONS_INCORRECT');
Text::script('COM_RSFIREWALL_FILE_PERMISSIONS_CORRECT');
Text::script('COM_RSFIREWALL_PLEASE_WAIT_WHILE_BUILDING_FILE_STRUCTURE');
Text::script('COM_RSFIREWALL_FIX_FILE_PERMISSIONS_DONE');

Text::script('COM_RSFIREWALL_ITEMS_LEFT');

Text::script('COM_RSFIREWALL_MALWARE_PLEASE_REVIEW_FILES');
Text::script('COM_RSFIREWALL_VIEW_FILE');
Text::script('COM_RSFIREWALL_NO_MALWARE_FOUND');

Text::script('COM_RSFIREWALL_GRADE_NOT_FINISHED');
Text::script('COM_RSFIREWALL_GRADE_NOT_FINISHED_DESC');
Text::script('COM_RSFIREWALL_SCANNING_IN_PROGRESS_LEAVE');

$this->document->addScriptDeclaration(
	'RSFirewall.requestTimeOut.Seconds = \'' . (float) $this->config->get('request_timeout') . '\';' . "\n" .
	'RSFirewall.MaxRetries = ' . (int) $this->config->get('max_retries') . ';' . "\n" .
	'RSFirewall.RetryTimeout = ' . (int) $this->config->get('retries_timeout') . ';' . "\n" .
	'RSFirewall.System.Check.steps = ' . json_encode($this->getSystemCheckSteps()) . ';' . "\n" .
	'RSFirewall.System.Check.limit = ' . $this->offset . ';'
);

if ($this->isWindows)
{
    $this->document->addScriptDeclaration('RSFirewall.System.Check.isWindows = true;');
}

HTMLHelper::_('script', 'com_rsfirewall/jquery.knob.js', array('relative' => true, 'version' => 'auto'));
HTMLHelper::_('script', 'com_rsfirewall/check.js', array('relative' => true, 'version' => 'auto'));
HTMLHelper::_('script', 'com_rsfirewall/diff.js', array('relative' => true, 'version' => 'auto'));
?>
<form action="<?php echo Route::_('index.php?option=com_rsfirewall&view=check');?>" method="post" name="adminForm" id="adminForm">

<?php echo RSFirewallAdapterGrid::sidebar(); ?>
	<p class="alert alert-info" id="com-rsfirewall-last-run"><?php echo Text::sprintf('COM_RSFIREWALL_SYSTME_CHECK_LAST_RUN', $this->lastRun); ?></p>
	<div id="com-rsfirewall-main-content">
		<div id="com-rsfirewall-grade">
			<h2><?php echo Text::_('COM_RSFIREWALL_GRADE_FINISHED'); ?></h2>
			<p><?php echo Text::_('COM_RSFIREWALL_GRADE_FINISHED_DESC'); ?></p>
			<input type="text" value="100" readonly="readonly" disabled="disabled" />
		</div>
		<?php if ($this->hasXdebug) { ?>
			<div class="alert alert-danger" id="com-rsfirewall-xdebug-warning">
			<p><strong><?php echo Text::_('COM_RSFIREWALL_SYSTEM_CHECK_HAS_DEBUG'); ?></strong></p>
			<p><?php echo Text::_('COM_RSFIREWALL_SYSTEM_CHECK_HAS_DEBUG_DESC'); ?></p>
			<p><button class="btn btn-danger btn-lg btn-large" type="button"><?php echo Text::_('COM_RSFIREWALL_XDEBUG_I_UNDERSTAND_THE_RISKS_AND_WANT_TO_CONTINUE'); ?></button></p>
			</div>
		<?php } ?>
		<div id="com-rsfirewall-scan-in-progress" class="com-rsfirewall-hidden">
			<div class="lds-ripple"><div></div><div></div></div>
			<p><?php echo Text::_('COM_RSFIREWALL_SCAN_IS_IN_PROGRESS'); ?></p>
		</div>
		<p><button type="button" class="btn btn-primary" id="com-rsfirewall-start-button" <?php if ($this->hasXdebug) { ?>disabled="disabled"<?php } ?>><?php echo Text::_('COM_RSFIREWALL_CHECK_SYSTEM'); ?></button></p>
		<div class="com-rsfirewall-content-box">
			<div class="com-rsfirewall-content-box-header">
				<h3><span class="icon-joomla"></span> <?php echo Text::_('COM_RSFIREWALL_JOOMLA_CONFIGURATION'); ?></h3>
			</div>
			<div id="com-rsfirewall-joomla-configuration" class="com-rsfirewall-content-box-content com-rsfirewall-hidden">
				<div class="com-rsfirewall-progress" id="com-rsfirewall-joomla-configuration-progress"><div class="com-rsfirewall-bar">0%</div></div>
				<table id="com-rsfirewall-joomla-configuration-table">
					<thead>
						<tr>
						   <th><?php echo Text::_('COM_RSFIREWALL_ACTION'); ?></th>
						   <th><?php echo Text::_('COM_RSFIREWALL_RESULT'); ?></th>
						   <th>&nbsp;</th>
						</tr>
					</thead>
					<tbody>
						<tr class="com-rsfirewall-table-row com-rsfirewall-hidden">
							<td><span><?php echo Text::_('COM_RSFIREWALL_JOOMLA_VERSION_CHECK'); ?></span></td>
							<td class="com-rsfirewall-count"><span></span></td>
							<td nowrap="nowrap" width="1%"><button class="com-rsfirewall-button com-rsfirewall-details-button com-rsfirewall-hidden" type="button"><span class="icon-arrow-down"></span></button></td>
						</tr>
						<tr class="com-rsfirewall-table-row com-rsfirewall-hidden">
							<td colspan="3"></td>
						</tr>
						<tr class="com-rsfirewall-table-row alt-row com-rsfirewall-hidden">
							<td><span><?php echo Text::_('COM_RSFIREWALL_FIREWALL_VERSION_CHECK'); ?></span></td>
							<td class="com-rsfirewall-count"><span></span></td>
							<td nowrap="nowrap" width="1%"><button class="com-rsfirewall-button com-rsfirewall-details-button com-rsfirewall-hidden" type="button"><span class="icon-arrow-down"></span></button></td>
						</tr>
						<tr class="com-rsfirewall-table-row alt-row com-rsfirewall-hidden">
							<td colspan="3"></td>
						</tr>
						<tr class="com-rsfirewall-table-row com-rsfirewall-hidden">
							<td><span><?php echo Text::_('COM_RSFIREWALL_CHECKING_SQL_PASSWORD'); ?></span></td>
							<td class="com-rsfirewall-count"><span></span></td>
							<td nowrap="nowrap" width="1%"><button class="com-rsfirewall-button com-rsfirewall-details-button com-rsfirewall-hidden" type="button"><span class="icon-arrow-down"></span></button></td>
						</tr>
						<tr class="com-rsfirewall-table-row com-rsfirewall-hidden">
							<td colspan="3"></td>
						</tr>
						<tr class="com-rsfirewall-table-row alt-row com-rsfirewall-hidden">
							<td><span><?php echo Text::_('COM_RSFIREWALL_CHECKING_ADMIN_USER'); ?></span></td>
							<td class="com-rsfirewall-count"><span></span></td>
							<td nowrap="nowrap" width="1%"><button class="com-rsfirewall-button com-rsfirewall-details-button com-rsfirewall-hidden" type="button"><span class="icon-arrow-down"></span></button></td>
						</tr>
						<tr class="com-rsfirewall-table-row alt-row com-rsfirewall-hidden">
							<td colspan="3"><p><label for="com-rsfirewall-new-username"><?php echo Text::_('COM_RSFIREWALL_NEW_USERNAME'); ?></label> <input id="com-rsfirewall-new-username" type="text" name="" value="admin" /> <button type="button" data-fix="fixAdminUser" id="com-rsfirewall-rename-admin-button" class="com-rsfirewall-button com-rsfirewall-fix-button"><?php echo Text::sprintf('COM_RSFIREWALL_RENAME_ADMIN', 'admin'); ?></button></p></td>
						</tr>
						<tr class="com-rsfirewall-table-row com-rsfirewall-hidden">
							<td><span><?php echo Text::_('COM_RSFIREWALL_CHECKING_FTP_PASSWORD'); ?></span></td>
							<td class="com-rsfirewall-count"><span></span></td>
							<td nowrap="nowrap" width="1%"><button class="com-rsfirewall-button com-rsfirewall-details-button com-rsfirewall-hidden" type="button"><span class="icon-arrow-down"></span></button></td>
						</tr>
						<tr class="com-rsfirewall-table-row com-rsfirewall-hidden">
							<td colspan="3"><p><button type="button" data-fix="fixFTPPassword" class="com-rsfirewall-button com-rsfirewall-fix-button"><?php echo Text::_('COM_RSFIREWALL_REMOVE_FTP_PASSWORD'); ?></button></p></td>
						</tr>
						<tr class="com-rsfirewall-table-row alt-row com-rsfirewall-hidden">
							<td><span><?php echo Text::_('COM_RSFIREWALL_CHECKING_SEF'); ?></span></td>
							<td class="com-rsfirewall-count"><span></span></td>
							<td nowrap="nowrap" width="1%"><button class="com-rsfirewall-button com-rsfirewall-details-button com-rsfirewall-hidden" type="button"><span class="icon-arrow-down"></span></button></td>
						</tr>
						<tr class="com-rsfirewall-table-row alt-row com-rsfirewall-hidden">
							<td colspan="3"><p><button type="button" data-fix="fixSEF" class="com-rsfirewall-button com-rsfirewall-fix-button"><?php echo Text::_('COM_RSFIREWALL_ENABLE_SEF'); ?></button></p></td>
						</tr>
						<tr class="com-rsfirewall-table-row com-rsfirewall-hidden">
							<td><span><?php echo Text::_('COM_RSFIREWALL_CHECKING_CONFIGURATION_INTEGRITY'); ?></span></td>
							<td class="com-rsfirewall-count"><span></span></td>
							<td nowrap="nowrap" width="1%"><button class="com-rsfirewall-button com-rsfirewall-details-button com-rsfirewall-hidden" type="button"><span class="icon-arrow-down"></span></button></td>
						</tr>
						<tr class="com-rsfirewall-table-row com-rsfirewall-hidden">
							<td colspan="3"><p><button type="button" data-fix="fixConfiguration" class="com-rsfirewall-button com-rsfirewall-fix-button"><?php echo Text::_('COM_RSFIREWALL_REBUILD_CONFIGURATION'); ?></button></p><p><?php echo Text::_('COM_RSFIREWALL_CONFIGURATION_DETAILS'); ?></p></td>
						</tr>
						<tr class="com-rsfirewall-table-row alt-row com-rsfirewall-hidden">
							<td><span><?php echo Text::_('COM_RSFIREWALL_CHECKING_SESSION_LIFETIME'); ?></span></td>
							<td class="com-rsfirewall-count"><span></span></td>
							<td nowrap="nowrap" width="1%"><button class="com-rsfirewall-button com-rsfirewall-details-button com-rsfirewall-hidden" type="button"><span class="icon-arrow-down"></span></button></td>
						</tr>
						<tr class="com-rsfirewall-table-row alt-row com-rsfirewall-hidden">
							<td colspan="3"><p><button type="button" data-fix="fixSession" class="com-rsfirewall-button com-rsfirewall-fix-button"><?php echo Text::_('COM_RSFIREWALL_DECREASE_SESSION'); ?></button></p></td>
						</tr>
						<tr class="com-rsfirewall-table-row com-rsfirewall-hidden">
							<td><span><?php echo Text::_('COM_RSFIREWALL_CHECKING_TEMPORARY_FILES'); ?></span></td>
							<td class="com-rsfirewall-count"><span></span></td>
							<td nowrap="nowrap" width="1%"><button class="com-rsfirewall-button com-rsfirewall-details-button com-rsfirewall-hidden" type="button"><span class="icon-arrow-down"></span></button></td>
						</tr>
						<tr class="com-rsfirewall-table-row com-rsfirewall-hidden">
							<td colspan="3"><p><button type="button" data-fix="fixTemporaryFiles" class="com-rsfirewall-button com-rsfirewall-fix-button"><?php echo Text::_('COM_RSFIREWALL_EMPTY_TEMPORARY_FOLDER'); ?></button></p></td>
						</tr>
						<tr class="com-rsfirewall-table-row alt-row com-rsfirewall-hidden">
							<td><span><?php echo Text::sprintf('COM_RSFIREWALL_CHECKING_HTACCESS', $this->accessFile); ?></span></td>
							<td class="com-rsfirewall-count"><span></span></td>
							<td nowrap="nowrap" width="1%"><button class="com-rsfirewall-button com-rsfirewall-details-button com-rsfirewall-hidden" type="button"><span class="icon-arrow-down"></span></button></td>
						</tr>
						<tr class="com-rsfirewall-table-row alt-row com-rsfirewall-hidden">
							<td colspan="3"><p><button type="button" data-fix="fixHtaccess" class="com-rsfirewall-button com-rsfirewall-fix-button"><?php echo Text::sprintf('COM_RSFIREWALL_RENAME_HTACCESS', $this->defaultAccessFile, $this->accessFile); ?></button></p></td>
						</tr>
						<tr class="com-rsfirewall-table-row com-rsfirewall-hidden">
							<td><span><?php echo Text::_('COM_RSFIREWALL_ADDITIONAL_BACKEND_PASSWORD_ENABLED'); ?></span></td>
							<td class="com-rsfirewall-count"><span></span></td>
							<td nowrap="nowrap" width="1%"><button class="com-rsfirewall-button com-rsfirewall-details-button com-rsfirewall-hidden" type="button"><span class="icon-arrow-down"></span></button></td>
						</tr>
						<tr class="com-rsfirewall-table-row com-rsfirewall-hidden">
							<td colspan="3"></td>
						</tr>
						<?php if (in_array('safebrowsing', $this->config->get('google_apis'))) { ?>
							<tr class="com-rsfirewall-table-row alt-row com-rsfirewall-hidden">
								<td><span><?php echo Text::_('COM_RSFIREWALL_CHECKING_GOOGLE_SAFE_BROWSER'); ?></span></td>
								<td class="com-rsfirewall-count"><span></span></td>
								<td nowrap="nowrap" width="1%"><button class="com-rsfirewall-button com-rsfirewall-details-button com-rsfirewall-hidden" type="button"><span class="icon-arrow-down"></span></button></td>
							</tr>
							<tr class="com-rsfirewall-table-row alt-row com-rsfirewall-hidden">
								<td colspan="3"></td>
							</tr>
						<?php } ?>
						<?php if (in_array('webrisk', $this->config->get('google_apis'))) { ?>
							<tr class="com-rsfirewall-table-row <?php if (count($this->config->get('google_apis')) != 2) { echo 'alt-row'; } ?> com-rsfirewall-hidden">
								<td><span><?php echo Text::_('COM_RSFIREWALL_CHECKING_GOOGLE_WEB_RISK'); ?></span></td>
								<td class="com-rsfirewall-count"><span></span></td>
								<td nowrap="nowrap" width="1%"><button class="com-rsfirewall-button com-rsfirewall-details-button com-rsfirewall-hidden" type="button"><span class="icon-arrow-down"></span></button></td>
							</tr>
							<tr class="com-rsfirewall-table-row <?php if (count($this->config->get('google_apis')) != 2) { echo 'alt-row'; } ?> com-rsfirewall-hidden">
								<td colspan="3"></td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>
		</div><!-- Joomla! config -->
		<div class="com-rsfirewall-content-box">
			<div class="com-rsfirewall-content-box-header">
				<h3><span class="icon-database"></span> <?php echo Text::_('COM_RSFIREWALL_SERVER_CONFIGURATION'); ?></h3>
			</div>
			<div id="com-rsfirewall-server-configuration" class="com-rsfirewall-content-box-content com-rsfirewall-hidden">
				<div class="com-rsfirewall-progress" id="com-rsfirewall-server-configuration-progress"><div class="com-rsfirewall-bar">0%</div></div>
				<table id="com-rsfirewall-server-configuration-table">
					<thead>
						<tr>
						   <th><?php echo Text::_('COM_RSFIREWALL_PHP_DIRECTIVE'); ?></th>
						   <th><?php echo Text::_('COM_RSFIREWALL_RESULT'); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr class="com-rsfirewall-table-row com-rsfirewall-hidden">
							<td width="15%" nowrap="nowrap"><span><?php echo Text::_('COM_RSFIREWALL_PHP_VERSION'); ?></span></td>
							<td class="com-rsfirewall-count"><span></span></td>
						</tr>
						<tr class="com-rsfirewall-table-row com-rsfirewall-hidden">
							<td colspan="2"></td>
						</tr>
						<tr class="com-rsfirewall-table-row alt-row com-rsfirewall-hidden">
							<td width="15%" nowrap="nowrap"><span>allow_url_include</span></td>
							<td class="com-rsfirewall-count"><span></span></td>
						</tr>
						<tr class="com-rsfirewall-table-row alt-row com-rsfirewall-hidden">
							<td colspan="2"></td>
						</tr>
						<tr class="com-rsfirewall-table-row com-rsfirewall-hidden">
							<td width="15%" nowrap="nowrap"><span>disable_functions</span></td>
							<td class="com-rsfirewall-count"><span></span></td>
						</tr>
						<tr class="com-rsfirewall-table-row com-rsfirewall-hidden">
							<td colspan="2"></td>
						</tr>
						<tr class="com-rsfirewall-table-row alt-row com-rsfirewall-hidden">
							<td width="15%" nowrap="nowrap"><span>expose_php</span></td>
							<td class="com-rsfirewall-count"><span></span></td>
						</tr>
						<tr class="com-rsfirewall-table-row alt-row com-rsfirewall-hidden">
							<td colspan="2"></td>
						</tr>
						<tr class="com-rsfirewall-table-row com-rsfirewall-hidden" id="com-rsfirewall-server-configuration-fix">
							<td colspan="2">
								<p class="alert alert-info"><?php echo Text::_('COM_RSFIREWALL_PHP_RECOMMENDED_INI_SETTINGS'); ?></p>
								<div id="com-rsfirewall-php-ini-wrapper">
									<pre id="com-rsfirewall-php-ini"><?php echo $this->escape($this->PHPini); ?></pre>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div><!-- Server config -->
		<div class="com-rsfirewall-content-box">
			<div class="com-rsfirewall-content-box-header">
				<h3><span class="icon-file"></span> <?php echo Text::_('COM_RSFIREWALL_SCAN_RESULT'); ?></h3>
			</div>
			<div id="com-rsfirewall-file-scan" class="com-rsfirewall-content-box-content com-rsfirewall-hidden">
				<table id="com-rsfirewall-file-scan-table">
					<thead>
						<tr>
						   <th><?php echo Text::_('COM_RSFIREWALL_ACTION'); ?></th>
						   <th><?php echo Text::_('COM_RSFIREWALL_RESULT'); ?></th>
						   <th>&nbsp;</th>
						</tr>
					</thead>
					<tbody>
						<tr class="com-rsfirewall-table-row alt-row com-rsfirewall-hidden">
							<td width="15%" nowrap="nowrap"><span><?php echo Text::_('COM_RSFIREWALL_SCANNING_JOOMLA_HASHES'); ?></span></td>
							<td class="com-rsfirewall-count"><span></span> <button type="button" class="com-rsfirewall-button com-rsfirewall-hidden" id="com-rsfirewall-ignore-files-button"><?php echo Text::_('COM_RSFIREWALL_VIEW_IGNORED'); ?></button></td>
							<td nowrap="nowrap" width="1%"><button class="com-rsfirewall-button com-rsfirewall-details-button com-rsfirewall-hidden" type="button"><span class="icon-arrow-down"></span></button></td>
						</tr>
						<tr class="com-rsfirewall-table-row alt-row com-rsfirewall-hidden">
							<td colspan="3">
								<p><button type="button" data-fix="fixHashes" class="com-rsfirewall-button com-rsfirewall-fix-button"><?php echo Text::_('COM_RSFIREWALL_ACCEPT_CHANGES'); ?></button></p>
								<p class="alert"><small><?php echo Text::_('COM_RSFIREWALL_ACCEPT_CHANGES_WARNING'); ?></small></p>
							</td>
						</tr>
						<?php if (!$this->isWindows) { ?>
						<tr class="com-rsfirewall-table-row com-rsfirewall-hidden">
							<td width="15%" nowrap="nowrap"><span><?php echo Text::_('COM_RSFIREWALL_SCANNING_FOLDERS'); ?></span></td>
							<td class="com-rsfirewall-count"><span></span></td>
							<td nowrap="nowrap" width="1%"><button class="com-rsfirewall-button com-rsfirewall-details-button com-rsfirewall-hidden" type="button"><span class="icon-arrow-down"></span></button></td>
						</tr>
						<tr class="com-rsfirewall-table-row com-rsfirewall-hidden">
							<td colspan="3">
								<p><button type="button" data-fix="fixFolderPermissions" class="com-rsfirewall-button com-rsfirewall-fix-button"><?php echo Text::sprintf('COM_RSFIREWALL_ATTEMPT_TO_FIX_FOLDER_PERMISSIONS', $this->config->get('folder_permissions')); ?></button></p>
								<p class="alert"><small><?php echo Text::sprintf('COM_RSFIREWALL_FIX_FOLDER_PERMISSIONS_WARNING', $this->config->get('folder_permissions')); ?></small></p>
							</td>
						</tr>
						<tr class="com-rsfirewall-table-row alt-row com-rsfirewall-hidden">
							<td width="15%" nowrap="nowrap"><span><?php echo Text::_('COM_RSFIREWALL_SCANNING_FILES'); ?></span></td>
							<td class="com-rsfirewall-count"><span></span></td>
							<td nowrap="nowrap" width="1%"><button class="com-rsfirewall-button com-rsfirewall-details-button com-rsfirewall-hidden" type="button"><span class="icon-arrow-down"></span></button></td>
						</tr>
						<tr class="com-rsfirewall-table-row alt-row com-rsfirewall-hidden">
							<td colspan="3">
								<p><button type="button" data-fix="fixFilePermissions" class="com-rsfirewall-button com-rsfirewall-fix-button"><?php echo Text::sprintf('COM_RSFIREWALL_ATTEMPT_TO_FIX_FILE_PERMISSIONS', $this->config->get('file_permissions')); ?></button></p>
								<p class="alert"><small><?php echo Text::sprintf('COM_RSFIREWALL_FIX_FILE_PERMISSIONS_WARNING', $this->config->get('file_permissions')); ?></small></p>
							</td>
						</tr>
						<?php } ?>
						<tr class="com-rsfirewall-table-row com-rsfirewall-hidden">
							<td width="15%" nowrap="nowrap"><span><?php echo Text::_('COM_RSFIREWALL_SCANNING_FILES_FOR_MALWARE'); ?></span></td>
							<td class="com-rsfirewall-count"><span></span></td>
							<td nowrap="nowrap" width="1%"><button class="com-rsfirewall-button com-rsfirewall-details-button com-rsfirewall-hidden" type="button"><span class="icon-arrow-down"></span></button></td>
						</tr>
						<tr class="com-rsfirewall-table-row alt-row com-rsfirewall-hidden">
							<td colspan="3">
								<p><button type="button" data-fix="ignoreFiles" class="com-rsfirewall-button com-rsfirewall-fix-button"><?php echo Text::_('COM_RSFIREWALL_IGNORE_FILES'); ?></button></p>
								<p class="alert"><small><?php echo Text::_('COM_RSFIREWALL_IGNORE_FILES_WARNING'); ?></small></p>
							</td>
						</tr>
						<tr class="com-rsfirewall-table-row com-rsfirewall-hidden">
							<td colspan="3"></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div><!-- Scan result -->
	</div>
</div>
</form>