<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

class RSFirewallAdapterTabs
{
	protected $id;
	protected $titles   = array();
	protected $contents = array();
	
	public function __construct($id)
	{
		$this->id = preg_replace('/[^A-Z0-9_\. -]/i', '', $id);
	}
	
	public function addTitle($label, $id)
	{
		$this->titles[] = (object) array('label' => $label, 'id' => $id);
	}
	
	public function addContent($content)
	{
		$this->contents[] = $content;
	}
	
	public function render()
	{
		$active = reset($this->titles);

		JHtml::_('behavior.tabstate');
		echo JHtml::_('bootstrap.startTabSet', $this->id, array('active' => $active->id));

		foreach ($this->titles as $i => $title)
		{
			echo JHtml::_('bootstrap.addTab', $this->id, $title->id, JText::_($title->label));
			echo $this->contents[$i];
			echo JHtml::_('bootstrap.endTab');
		}

		echo JHtml::_('bootstrap.endTabSet');
	}
}