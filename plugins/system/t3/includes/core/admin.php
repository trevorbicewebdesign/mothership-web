<?php

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;

/** 
 *------------------------------------------------------------------------------
 * @package       T3 Framework for Joomla!
 *------------------------------------------------------------------------------
 * @copyright     Copyright (C) 2004-2013 JoomlArt.com. All Rights Reserved.
 * @license       GNU General Public License version 2 or later; see LICENSE.txt
 * @authors       JoomlArt, JoomlaBamboo, (contribute to this project at github 
 *                & Google group to become co-author)
 * @Google group: https://groups.google.com/forum/#!forum/t3fw
 * @Link:         http://t3-framework.org 
 *------------------------------------------------------------------------------
 */

// Define constant
class T3Admin {

	protected $langs = array();
	protected $html = array();

	/**
	 * init admin backend to edit template style
	 */
	public function init() {
		$app = Factory::getApplication();
		$input = $app->input;
		if ($input->getCmd('view') == 'style') {
			$app->set('themes.base', T3_ADMIN_PATH);
			$app->set('theme', 'admin');
		}
		if(version_compare(JVERSION, '4', 'ge')){
			$wa = Factory::getDocument()->getWebAssetManager();
			//var_dump($wa->getAssets('script'));die;
			$wa->registerAsset('script', 'bootstrap.js.bundle', T3_ADMIN_REL . '/admin/bootstrap/js/bootstrap.js', ['dependencies' => 'jquery']);
			$wa->registerAsset('script', 'jquery', T3_ADMIN_REL . '/admin/js/jquery-1.x.min.js');
			$wa->registerAsset('script', 'jquery-noconflict', T3_ADMIN_REL . '/admin/js/jquery.noconflict.js');
			//$wa->disableAsset('script', 'bootstrap.init.legacy');
			//$wa->useAsset('script', 'bootstrap.js.bundle');
		}
	}


	public function updateHead() {
	}

	/**
	 * function render
	 * render T3 administrator configuration form
	 *
	 * @return render success or not
	 */
	public function render(){
		$app = Factory::getApplication();
		$input  = $app->input;
		if ('style' != $input->getCmd('view')) return;

		$body   = $app->getBody();
		$layout = T3_ADMIN_PATH . '/admin/tpls/default.php';
		$layout = false;
		if(file_exists($layout)){
			// ob_start();
			// $this->renderAdmin();
			// $buffer = ob_get_clean();

			//this cause backtrack_limit in some server
			//$body = preg_replace('@<form\s[^>]*name="adminForm"[^>]*>(.*)</form>@msU', $buffer, $body);
			$opentags = explode('<form', $body);
			$endtags = explode('</form>', $body);
			$open = array_shift($opentags);
			$close = array_pop($endtags);

			//should not happend
			if(count($opentags) > 1) {
	
				$iopen = 0;
				$iclose = count($opentags);

				foreach ($opentags as $index => $value) {
					if($iopen !== -1 && strpos($value, 'name="adminForm"') === false){
						$iopen++;
						$open = $open . '<form' . $value;
					} else {
						$iopen = -1;
					}

					if($iclose !== -1 && strpos($endtags[--$iclose], 'name="adminForm"') === false){
						$close = $endtags[$iclose] . '</form>' . $close;
					} else {
						$iclose = -1;
					}
				}
			}

			//$body = $open . $this->html['admin'] . $close;
			//$body = $this->html['admin'];
		}

		if(!$input->getCmd('file')){
			$body = $this->replaceToolbar($body);
		}

		$body = $this->replaceDoctype($body);

		$app->setBody($body);
	}

	public function addAssets() {
		$japp   = Factory::getApplication();
		$jdoc   = Factory::getDocument();
		$db     = Factory::getDbo();
		$params = T3::getTplParams();
		$input  = $japp->input;

		if ('style' != $input->getCmd('view')) return;

		// load template language
		Factory::getLanguage()->load ('tpl_'.T3_TEMPLATE.'.sys', JPATH_ROOT, null, true);

		$langs = array(
			'unknownError' => JText::_('T3_MSG_UNKNOWN_ERROR'),

			'logoPresent' => JText::_('T3_LAYOUT_LOGO_TEXT'),
			'emptyLayoutPosition' => JText::_('T3_LAYOUT_EMPTY_POSITION'),
			'defaultLayoutPosition' => JText::_('T3_LAYOUT_DEFAULT_POSITION'),
			
			'layoutConfig' => JText::_('T3_LAYOUT_CONFIG_TITLE'),
			'layoutConfigDesc' => JText::_('T3_LAYOUT_CONFIG_DESC'),
			'layoutUnknownWidth' => JText::_('T3_LAYOUT_UNKN_WIDTH'),
			'layoutPosWidth' => JText::_('T3_LAYOUT_POS_WIDTH'),
			'layoutPosName' => JText::_('T3_LAYOUT_POS_NAME'),

			'layoutCanNotLoad' => JText::_('T3_LAYOUT_LOAD_ERROR'),

			'askCloneLayout' => JText::_('T3_LAYOUT_ASK_ADD_LAYOUT'),
			'correctLayoutName' => JText::_('T3_LAYOUT_ASK_CORRECT_NAME'),
			'askDeleteLayout' => JText::_('T3_LAYOUT_ASK_DEL_LAYOUT'),
			'askDeleteLayoutDesc' => JText::_('T3_LAYOUT_ASK_DEL_LAYOUT_DESC'),
			'askPurgeLayout' => JText::_('T3_LAYOUT_ASK_DEL_LAYOUT'),
			'askPurgeLayoutDesc' => JText::_('T3_LAYOUT_ASK_PURGE_LAYOUT_DESC'),

			'lblDeleteIt' => JText::_('T3_LAYOUT_LABEL_DELETEIT'),
			'lblCloneIt' => JText::_('T3_LAYOUT_LABEL_CLONEIT'),

			'layoutEditPosition' => JText::_('T3_LAYOUT_EDIT_POSITION'),
			'layoutShowPosition' => JText::_('T3_LAYOUT_SHOW_POSITION'),
			'layoutHidePosition' => JText::_('T3_LAYOUT_HIDE_POSITION'),
			'layoutChangeNumpos' => JText::_('T3_LAYOUT_CHANGE_NUMPOS'),
			'layoutDragResize' => JText::_('T3_LAYOUT_DRAG_RESIZE'),
			'layoutHiddenposDesc' => JText::_('T3_LAYOUT_HIDDEN_POS_DESC'),
			
			'updateFailedGetList' => JText::_('T3_OVERVIEW_FAILED_GETLIST'),
			'updateDownLatest' => JText::_('T3_OVERVIEW_GO_DOWNLOAD'),
			'updateCheckUpdate' => JText::_('T3_OVERVIEW_CHECK_UPDATE'),
			'updateChkComplete' => JText::_('T3_OVERVIEW_CHK_UPDATE_OK'),
			'updateHasNew' => JText::_('T3_OVERVIEW_TPL_NEW'),
			'updateCompare' => JText::_('T3_OVERVIEW_TPL_COMPARE'),
			'switchResponsiveMode' => JText::_('T3_MSG_SWITCH_RESPONSIVE_MODE')
		);

		//just in case
		if(!($params instanceof Registry)){
			$params = new Registry;
		}

		//get extension id of framework and template
		$query  = $db->getQuery(true);
		$query
			->select('extension_id')
			->from('#__extensions')
			->where('(element='. $db->quote(T3_TEMPLATE) . ' AND type=' . $db->quote('template') . ') 
					OR (element=' . $db->quote(T3_ADMIN) . ' AND type=' . $db->quote('plugin'). ')');

		$db->setQuery($query);
		$results = $db->loadRowList();
		$eids = array();
		foreach ($results as $eid) {
			$eids[] = $eid[0];
		}

		//check for version compatible
		if(version_compare(JVERSION, '3.0', 'ge')){
			//JHtml::_('jquery.framework');
			JHtml::_('bootstrap.framework');
		} else {
			$jdoc->addStyleSheet(T3_ADMIN_URL . '/admin/bootstrap/css/bootstrap.css');

			$jdoc->addScript(T3_ADMIN_URL . '/admin/js/jquery-1.x.min.js');
			$jdoc->addScript(T3_ADMIN_URL . '/admin/bootstrap/js/bootstrap.js');
			$jdoc->addScript(T3_ADMIN_URL . '/admin/js/jquery.noconflict.js');
		}

		if(!$this->checkAssetsLoaded('chosen.css', '_styleSheets')){
			$jdoc->addStyleSheet(T3_ADMIN_URL . '/admin/plugins/chosen/chosen.css');
		}

		$jdoc->addStyleSheet(T3_ADMIN_URL . '/includes/depend/css/depend.css');
		$jdoc->addStyleSheet(T3_URL . '/css/layout-preview.css');
		$jdoc->addStyleSheet(T3_ADMIN_URL . '/admin/layout/css/layout.css');
		if(file_exists(T3_TEMPLATE_PATH . '/admin/layout-custom.css')) {
			$jdoc->addStyleSheet(T3_TEMPLATE_URL . '/admin/layout-custom.css');
		}
		$jdoc->addStyleSheet(T3_ADMIN_URL . '/admin/css/admin.css');

		if(version_compare(JVERSION, '3.0', 'ge')){
			$jdoc->addStyleSheet(T3_ADMIN_URL . '/admin/css/admin-j30.css');

			if($input->get('file') && version_compare(JVERSION, '3.2', 'ge')){
				$jdoc->addStyleSheet(T3_ADMIN_URL . '/admin/css/file-manager.css');
			}
		} else {
			$jdoc->addStyleSheet(T3_ADMIN_URL . '/admin/css/admin-j25.css');
		}

		if(!$this->checkAssetsLoaded('chosen.jquery.min.js', '_scripts')){
			$jdoc->addScript(T3_ADMIN_URL . '/admin/plugins/chosen/chosen.jquery.min.js');	
		}

		$jdoc->addScript(T3_ADMIN_URL . '/includes/depend/js/depend.js');
		$jdoc->addScript(T3_ADMIN_URL . '/admin/js/json2.js');
		$jdoc->addScript(T3_ADMIN_URL . '/admin/js/jimgload.js');
		$jdoc->addScript(T3_ADMIN_URL . '/admin/layout/js/layout.js');
		if(version_compare(JVERSION, '4','lt')){
			$jdoc->addScript(T3_ADMIN_URL . '/admin/js/admin.js');
		}else{
			$jdoc->addScript(T3_ADMIN_URL . '/admin/js/admin_j4.js');
		}


		$jdoc->addScriptDeclaration ( '
			T3Admin = window.T3Admin || {};
			T3Admin.adminurl = \'' . JUri::getInstance()->toString() . '\';
			T3Admin.t3adminurl = \'' . T3_ADMIN_URL . '\';
			T3Admin.baseurl = \'' . JURI::base(true) . '\';
			T3Admin.rooturl = \'' . JURI::root() . '\';
			T3Admin.template = \'' . T3_TEMPLATE . '\';
			T3Admin.templateid = \'' . Factory::getApplication()->input->get('id') . '\';
			T3Admin.langs = ' . json_encode($langs) . ';
			T3Admin.devmode = ' . $params->get('devmode', 0) . ';
			T3Admin.themermode = ' . $params->get('themermode', 1) . ';
			T3Admin.eids = [' . implode(',', $eids) .'];
			T3Admin.telement = \'' . T3_TEMPLATE . '\';
			T3Admin.felement = \'' . T3_ADMIN . '\';
			T3Admin.jversion = \'' . jversion::MAJOR_VERSION . '\';
			T3Admin.themerUrl = \'' . JUri::getInstance()->toString() . '&t3action=theme&t3task=thememagic' . '\';
			T3Admin.megamenuUrl = \'' . JUri::getInstance()->toString() . '&t3action=megamenu&t3task=megamenu' . '\';
			T3Admin.t3updateurl = \'' . JURI::base() . 'index.php?option=com_installer&view=update&task=update.ajax' . '\';
			T3Admin.t3layouturl = \'' . JURI::base() . 'index.php?t3action=layout' . '\';
			T3Admin.jupdateUrl = \'' . JURI::base() . 'index.php?option=com_installer&view=update' . '\';'
		);

		// render admin
		// $this->_renderAdmin();
		$this->_renderToolbar();

	}

	public function addJSLang($key = '', $value = '', $overwrite = true){
		if($key && $value && ($overwrite || !array_key_exists($key, $this->langs))){
			$this->langs[$key] = $value ? $value : JText::_($key);
		}
	}
	
	/**
	 * function loadParam
	 * load and re-render parameters
	 *
	 * @return render success or not
	 */
	function _renderAdmin(){
		return;
		$frwXml = T3_ADMIN_PATH . '/'. T3_ADMIN . '.xml';
		$tplXml = T3_TEMPLATE_PATH . '/templateDetails.xml';
		$cusXml = T3Path::getPath('etc/assets.xml');
		$jtpl = T3_ADMIN_PATH . '/admin/tpls/default.php';
		
		if(file_exists($tplXml) && file_exists($jtpl)){
			
			T3::import('depend/t3form');

			//get the current joomla default instance
			$form = JForm::getInstance('com_templates.style', 'style', array('control' => 'jform', 'load_data' => true));

			//wrap
			$form = new T3Form($form);
			
			//remove all fields from group 'params' and reload them again in right other base on template.xml
			$form->removeGroup('params');
			//load the template
			$form->loadFile(T3_PATH . '/params/template.xml');
			//overwrite / extend with params of template
			$form->loadFile($tplXml, true, '//config');
			//overwrite / extend with custom config in custom/etc/assets.xml
			if ($cusXml && file_exists($cusXml))
				$form->loadFile($cusXml, true, '//config');
			// extend parameters
			T3Bot::prepareForm($form);

			$xml = simplexml_load_file($tplXml);
			$fxml = simplexml_load_file($frwXml);

			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query
				->select('id, title')
				->from('#__template_styles')
				->where('template='. $db->quote(T3_TEMPLATE));
			
			$db->setQuery($query);
			$styles = $db->loadObjectList();
			foreach ($styles as $key => &$style) {
				$style->title = ucwords(str_replace('_', ' ', $style->title));
			}
			
			$session = Factory::getSession();
			$t3lock = $session->get('T3.t3lock', 'overview_params');
			$session->set('T3.t3lock', null);
			$input = Factory::getApplication()->input;

			ob_start();
			include $jtpl;
			$this->html['admin'] = ob_get_clean();
			/*
			//search for global parameters
			$japp = Factory::getApplication();
			$pglobals = array();
			foreach($form->getGroup('params') as $param){
				if($form->getFieldAttribute($param->fieldname, 'global', 0, 'params')){
					$pglobals[] = array('name' => $param->fieldname, 'value' => $form->getValue($param->fieldname, 'params')); 
				}
			}
			$japp->setUserState('oparams', $pglobals);
			*/

			return true;
		}
		
		return false;
	}

	function _renderToolbar() {
		$t3toolbar = T3_ADMIN_PATH . '/admin/tpls/toolbar.php';
		$input = Factory::getApplication()->input;

		if(file_exists($t3toolbar) && class_exists('JToolBar')){
			//get the existing toolbar html
			jimport('joomla.language.help');
			$params  = T3::getTplParams();
			$this->html['toolbar'] = JToolBar::getInstance('toolbar')->render();
			$helpurl = JHelp::createURL($input->getCmd('view') == 'template' ? 'JHELP_EXTENSIONS_TEMPLATE_MANAGER_TEMPLATES_EDIT' : 'JHELP_EXTENSIONS_TEMPLATE_MANAGER_STYLES_EDIT');
			$helpurl = htmlspecialchars($helpurl, ENT_QUOTES);

			//render our toolbar
			ob_start();
			include $t3toolbar;
			$this->html['t3toolbar'] = ob_get_clean();
		}
	}

	function replaceToolbar($body){
		/*
		$t3toolbar = T3_ADMIN_PATH . '/admin/tpls/toolbar.php';
		$input = Factory::getApplication()->input;

		if(file_exists($t3toolbar) && class_exists('JToolBar')){
			//get the existing toolbar html
			jimport('joomla.language.help');
			$params  = T3::getTplParams();
			$toolbar = JToolBar::getInstance('toolbar')->render();
			$helpurl = JHelp::createURL($input->getCmd('view') == 'template' ? 'JHELP_EXTENSIONS_TEMPLATE_MANAGER_TEMPLATES_EDIT' : 'JHELP_EXTENSIONS_TEMPLATE_MANAGER_STYLES_EDIT');
			$helpurl = htmlspecialchars($helpurl, ENT_QUOTES);

			//render our toolbar
			ob_start();
			include $t3toolbar;
			$t3toolbar = ob_get_clean();

			//replace it
			$body = str_replace($toolbar, $t3toolbar, $body);
		}
		*/

		//$body = str_replace($this->html['toolbar'], $this->html['t3toolbar'], $body);
		$body = str_replace('[[TOOLBAR]]', $this->html['t3toolbar'], $body);
		return $body;
	}

	function replaceDoctype($body){
		return preg_replace('@<!DOCTYPE\s(.*?)>@', '<!DOCTYPE html>', $body);
	}

	function checkAssetsLoaded($pattern, $hash){
		$doc = Factory::getDocument();
		$hash = $doc->$hash;

		foreach ($hash as $path => $object) {
			if(strpos($path, $pattern) !== false){
				return true;
			}
		}

		return false;
	}
}

?>