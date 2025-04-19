<?php
/**
******************************************************************************************

**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Table\Asset;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Access\Rules;
use \Joomla\CMS\Form\Form;
use \Joomla\CMS\Table\Asset;
use \Joomla\CMS\Table\Table;

/**
* Trait for Tables with no asset
*
* @since  4.0.0
*/
trait NoAssetTableTrait
{
  /**
	 * Define a namespaced asset name for inclusion in the #__assets table
	 *
	 * @return string The asset name
	 *
   * @since 4.0.0
	 * @see Joomla\CMS\Table\Table::_getAssetName
	 */
	protected function _getAssetName($itemtype = null)
  {
    return parent::_getAssetName($itemtype);
  }

  /**
	 * Returns the parent asset's id. If you have a tree structure, retrieve the parent's id using the external key field
	 *
	 * @param   Table   $table  Table name
	 * @param   integer  $id     Id
	 *
	 * @see Table::_getAssetParentId
	 *
	 * @return mixed The id on success, false on failure.
	 */
	protected function _getAssetParentId($table = null, $id = null)
	{
		// We will retrieve the parent-asset from the Asset-table
		$assetTable = new Asset($this->getDbo());

    // Load the JoomGallery global asset
    $assetTable->loadByName(_JOOM_OPTION);

		// Return the found asset-parent-id
		if($assetTable->id)
		{
			$assetParentId = $assetTable->id;
		}
		else
		{
			// If no asset-parent can be found we take the global asset
			$assetParentId = $assetTable->getRootId();
		}

		return $assetParentId;
	}

  /**
	 * Method to return the title to use for the asset table.
	 *
	 * @return  string
	 *
   * @since 4.0.0
	 * @see Joomla\CMS\Table\Table::_getAssetTitle
	 */
	protected function _getAssetTitle($itemtype = null)
	{
    if(\property_exists($this, 'title'))
    {
      return $this->title;
    }
    else
    {
      return $this->_getAssetName();
    }    
	}

	/**
   * Method to set empty rules for the record based on a form.
   * 
   * @param   Form  $form  The form object where the rules gets extracted
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function setEmptyRules(Form $form)
  {
		// Add the rules for ACL
		$rules = new Rules('{}');
		$this->setRules($rules);
  }
}
