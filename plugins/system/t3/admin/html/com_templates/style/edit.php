<?php 
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;

$session = Factory::getSession();
$t3lock = $session->get('T3.t3lock', 'overview_params');
$session->set('T3.t3lock', null);
$input = Factory::getApplication()->input;
$form = $this->getForm();

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

$tplXml = T3_TEMPLATE_PATH . '/templateDetails.xml';
$xml = simplexml_load_file($tplXml);

$frwXml = T3_ADMIN_PATH . '/'. T3_ADMIN . '.xml';
$fxml = simplexml_load_file($frwXml);
if(version_compare(JVERSION,'4',"ge")){
	include T3_ADMIN_PATH . '/admin/tpls/default_j4.php';
}else{
	Factory::getDocument()->addStylesheet(Uri::base(true).'/templates/'.Factory::getApplication()->getTemplate().'/css/template' . ( JFactory::getDocument()->direction === 'rtl' ? '-rtl' : '') . '.css');
	include T3_ADMIN_PATH . '/admin/tpls/default.php';
}
?>