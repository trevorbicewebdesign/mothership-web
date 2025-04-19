<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

JHtml::_('behavior.formvalidator');
// JHtml::_('formbehavior.chosen', 'select');

/** @var \TrevorBice\Component\Mothership\Administrator\View\Log\HtmlView $this */

$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect');

$user = $this->getCurrentUser();
$userId = $user->id;
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>

<form action="<?php echo Route::_('index.php?option=com_mothership&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="log-form" aria-label="<?php echo Text::_('COM_MOTHERSHIP_LOG_' . ((int) $this->item->id === 0 ? 'NEW' : 'EDIT'), true); ?>" class="form-validate">
    <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>
        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'details', Text::_('COM_MOTHERSHIP_FORM_LOG_DETAILS_TAB')); ?>
        <div class="row">
            <div class="col-lg-8">
            <div>
                <fieldset class="adminform">
                <?php echo $this->form->renderField('details'); ?>
                <?php echo $this->form->renderField('description'); ?>
                <?php echo $this->form->renderField('old_value'); ?>
                <?php echo $this->form->renderField('new_value'); ?>
                
                </fieldset>
            </div>
            </div>
            <div class="col-lg-4">
                <div class="card card-body">
                    <?php echo $this->form->renderField('client_id'); ?>
                    <?php echo $this->form->renderField('account_id'); ?>
                    <?php echo $this->form->renderField('object_type'); ?>
                    <?php echo $this->form->renderField('object_id'); ?>
                    <?php echo $this->form->renderField('action'); ?>
                    <?php echo $this->form->renderField('field_changed'); ?>
                    <?php echo $this->form->renderField('user_id'); ?>
                    <?php echo $this->form->renderField('created'); ?>
                    <?php echo $this->form->renderField('created_by'); ?>
                </div>
        </div>

        <?php echo HTMLHelper::_('uitab.endTab'); ?>
        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    </div>

    <input type="hidden" name="jform[id]" value="<?php echo (isset($this->item->id) && $this->item->id > 0) ? (int) $this->item->id : ""; ?>" />
    <input type="hidden" name="jform[return]" value="<?php echo $_REQUEST['return']; ?>" />
    <input type="hidden" name="task" value="" />
    <?php echo JHtml::_('form.token'); ?>
</form>