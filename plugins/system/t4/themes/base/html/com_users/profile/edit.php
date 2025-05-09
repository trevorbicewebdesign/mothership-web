<?php
/**
T4 Overide
 */


defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');

// Load user_profile plugin language
$lang = Factory::getLanguage();
$lang->load('plg_user_profile', JPATH_ADMINISTRATOR);
if(version_compare(JVERSION, '4', 'ge')){
	/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
	$wa = $this->document->getWebAssetManager();
	$wa->useScript('keepalive')
	->useScript('form.validate');
	if(version_compare(JVERSION, '4.2', 'lt')){
		HTMLHelper::_('script', 'com_users/two-factor-switcher.min.js', array('version' => 'auto', 'relative' => true));
	}
}else {
	HTMLHelper::_('behavior.keepalive');
	HTMLHelper::_('behavior.formvalidator');
	HTMLHelper::_('formbehavior.chosen', 'select');
}
?>
<div class="com-users-profile__edit profile-edit">
	<?php if ($this->params->get('show_page_heading')) : ?>
		<div class="page-header">
			<h1>
				<?php echo $this->escape($this->params->get('page_heading')); ?>
			</h1>
		</div>
	<?php endif; ?>
	<?php if (version_compare(JVERSION, '4.0', 'lt')): ?>
			<script>
		Joomla.twoFactorMethodChange = function(e)
		{
			var selectedPane = 'com_users_twofactor_' + jQuery('#jform_twofactor_method').val();

			jQuery.each(jQuery('#com_users_twofactor_forms_container>div'), function(i, el)
			{
				if (el.id != selectedPane)
				{
					jQuery('#' + el.id).hide(0);
					jQuery('#' + el.id).addClass('hidden');
				}
				else
				{
					jQuery('#' + el.id).show(0);
					jQuery('#' + el.id).removeClass('hidden');
				}
			});
		}
	</script>
<?php endif ?>
	<form id="member-profile" action="<?php echo Route::_('index.php?option=com_users&task=profile.save'); ?>" method="post" class="com-users-profile__edit-form form-validate form-horizontal" enctype="multipart/form-data">
		<?php // Iterate through the form fieldsets and display each one. ?>
		<?php foreach ($this->form->getFieldsets() as $group => $fieldset) : ?>
			<?php $fields = $this->form->getFieldset($group); ?>
			<?php if (count($fields)) : ?>
				<fieldset>
					<?php // If the fieldset has a label set, display it as the legend. ?>
					<?php if (isset($fieldset->label)) : ?>
						<legend>
							<?php echo Text::_($fieldset->label); ?>
						</legend>
					<?php endif; ?>
					<?php if (isset($fieldset->description) && trim($fieldset->description)) : ?>
						<p>
							<?php echo $this->escape(Text::_($fieldset->description)); ?>
						</p>
					<?php endif; ?>
					<?php // Iterate through the fields in the set and display them. ?>
					<?php foreach ($fields as $field) : ?>
					<?php // If the field is hidden, just display the input. ?>
						<?php if ($field->hidden) : ?>
							<?php echo $field->input; ?>
						<?php else : ?>
							<div class="control-group row">
								<div class="control-label col-sm-3">
									<?php echo $field->label; ?>
									<?php if (!$field->required && $field->type !== 'Spacer') : ?>
										<?php if(version_compare(JVERSION, '4', 'lt')) : ?>
											<span class="optional">
												<?php echo Text::_('COM_USERS_OPTIONAL'); ?>
											</span>
										<?php endif ?>
									<?php endif; ?>
								</div>
								<div class="col-sm-9">
									<?php echo $field->input; ?>
								</div>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				</fieldset>
			<?php endif; ?>
		<?php endforeach; ?>
		<?php if(version_compare(JVERSION,'4.2','ge')):?>
			<?php if ($this->mfaConfigurationUI) : ?>
					<fieldset class="com-users-profile__multifactor">
							<legend><?php echo Text::_('COM_USERS_PROFILE_MULTIFACTOR_AUTH'); ?></legend>
							<?php echo $this->mfaConfigurationUI ?>
					</fieldset>
			<?php endif; ?>
		<?php else:?>
			<?php if (is_array($this->twofactormethods) && count($this->twofactormethods) > 1) : ?>
				<fieldset class="com-users-profile__twofactor">
					<legend><?php echo Text::_('COM_USERS_PROFILE_TWO_FACTOR_AUTH'); ?></legend>

					<div class="com-users-profile__twofactor-method control-group">
						<div class="control-label col-sm-3">
							<label id="jform_twofactor_method-lbl" for="jform_twofactor_method" class="hasTooltip"
									title="<?php echo '<strong>' . Text::_('COM_USERS_PROFILE_TWOFACTOR_LABEL') . '</strong><br>' . Text::_('COM_USERS_PROFILE_TWOFACTOR_DESC'); ?>">
								<?php echo Text::_('COM_USERS_PROFILE_TWOFACTOR_LABEL'); ?>
							</label>
						</div>
						<div class="col-sm-9">
							<?php echo HTMLHelper::_('select.genericlist', $this->twofactormethods, 'jform[twofactor][method]', array('class' => 'custom-select valid form-control-success', 'onchange' => 'Joomla.twoFactorMethodChange()'), 'value', 'text', $this->otpConfig->method, 'jform_twofactor_method', false); ?>
						</div>
					</div>
					<div id="com_users_twofactor_forms_container" class="com-users-profile__twofactor-form">
						<?php foreach ($this->twofactorform as $form) : ?>
							<?php $style = $form['method'] == $this->otpConfig->method ? '' : 'hidden'; ?>
							<div id="com_users_twofactor_<?php echo $form['method']; ?>" class="<?php echo $style; ?>">
								<?php echo $form['form']; ?>
							</div>
						<?php endforeach; ?>
					</div>
				</fieldset>

				<fieldset class="com-users-profile__oteps">
					<legend>
						<?php echo Text::_('COM_USERS_PROFILE_OTEPS'); ?>
					</legend>
					<div class="alert alert-info">
						<span class="fa fa-info-circle" aria-hidden="true"></span><span class="sr-only"><?php echo Text::_('INFO'); ?></span>
						<?php echo Text::_('COM_USERS_PROFILE_OTEPS_DESC'); ?>
					</div>
					<?php if (empty($this->otpConfig->otep)) : ?>
						<div class="alert alert-warning">
							<span class="fa fa-exclamation-circle" aria-hidden="true"></span><span class="sr-only"><?php echo Text::_('WARNING'); ?></span>
							<?php echo Text::_('COM_USERS_PROFILE_OTEPS_WAIT_DESC'); ?>
						</div>
					<?php else : ?>
						<?php foreach ($this->otpConfig->otep as $otep) : ?>
							<span class="col-md-3">
								<?php echo substr($otep, 0, 4); ?>-<?php echo substr($otep, 4, 4); ?>-<?php echo substr($otep, 8, 4); ?>-<?php echo substr($otep, 12, 4); ?>
							</span>
						<?php endforeach; ?>
						<div class="clearfix"></div>
					<?php endif; ?>
				</fieldset>
			<?php endif; ?>
		<?php endif; ?>

		<div class="com-users-profile__edit-submit control-group row">
			<div class="offset-sm-3 col-sm-9">
				<button type="submit" class="btn btn-primary validate">
					<span>
						<?php echo Text::_('JSAVE'); ?>
					</span>
				</button>
				<a class="btn btn-danger" href="<?php echo Route::_('index.php?option=com_users&view=profile'); ?>" title="<?php echo Text::_('JCANCEL'); ?>"><?php echo Text::_('JCANCEL'); ?></a>
				<input type="hidden" name="option" value="com_users">
				<input type="hidden" name="task" value="profile.save">
			</div>
		</div>
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>
