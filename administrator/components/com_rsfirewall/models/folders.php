<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\Filesystem\Path;

class RsfirewallModelFolders extends BaseDatabaseModel
{
	protected $path;
	protected $input;

	public function __construct($config = array()) {
		$this->path  = JPATH_SITE;
		$this->input = Factory::getApplication()->input;
		if (is_dir($this->input->get('folder', '', 'raw'))) {
			$this->path = $this->input->get('folder', '', 'raw');
		}
		
		parent::__construct($config);
	}
	
	public function getPath() {
		return $this->path;
	}
	
	public function getPrevious() {
		$path = $this->getPath();
		$path = explode(DIRECTORY_SEPARATOR, $path);
		array_pop($path);
		
		return implode(DIRECTORY_SEPARATOR, $path);
	}
	
	public function getName() {
		return $this->input->get('name', '', 'raw');
	}
	
	public function getFolders() {
		$checkModel = $this->getInstance('Check', 'RsfirewallModel');
		$path		= $this->getPath();

		// workaround to grab the correct root
		if ($path == '') {
			$path = '/';
		}

		$folders = $checkModel->getFolders($path, false, true, false);
		return $this->getFoldersData($folders);
	}
	
	protected function getFoldersData($folders) {
		$dataFolders = array();
		foreach ($folders as $folder) {
			$newPath = realpath($this->getPath().'/'.$folder);
			$perms   = fileperms($newPath);
			
			$dataFolders[$folder]['octal'] = substr(sprintf('%o', $perms), -4);
			$dataFolders[$folder]['full']  = Path::getPermissions($newPath);
		}
		return $dataFolders;
	
	}
	
	public function getFiles() {
		$checkModel = $this->getInstance('Check', 'RsfirewallModel');
		$path		= $this->getPath();

		// workaround to grab the correct root
		if ($path == '') {
			$path = '/';
		}

		$files = $checkModel->getFiles($path, false, true, false);
		return $this->getFilesData($files);
	}
	
	protected function getFilesData($files) {
		$dataFiles = array();
		foreach ($files as $file) {
			$newPath = realpath($this->getPath().'/'.$file);
			$perms   = fileperms($newPath);

			$dataFiles[$file]['octal']		= substr(sprintf('%o', $perms), -4);
			$dataFiles[$file]['full']  		= Path::getPermissions($newPath);
			$dataFiles[$file]['filesize']   = $this->niceFilesize(filesize($newPath), 2);
		}
		return $dataFiles;
	}
	
	protected function niceFilesize($bytes, $decimals = 2) {
		$scale = array('bytes', 'KB', 'MB', 'GB', 'TB', 'PB');
		$factor = floor((strlen($bytes) - 1) / 3);
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) .' '.$scale[$factor];
	}
	
	public function getElements() {
		$path 		= $this->getPath();
		$elements 	= explode(DIRECTORY_SEPARATOR, $path);
		$navigation_path = '';
		foreach ($elements as $i => $element) {
			$navigation_path .= $element;
			$newelement = new stdClass();
			$newelement->name = $element;
			$newelement->fullpath = $navigation_path;
			$elements[$i] = (object) array(
				'name' => $element,
				'fullpath' => $navigation_path
			);
			$navigation_path .= DIRECTORY_SEPARATOR;
		}
		
		return $elements;
	}
	
	public function getAllowFolders() {
		return $this->input->get('allowfolders', 0, 'int');
	}
	
	public function getAllowFiles() {
		return $this->input->get('allowfiles', 0, 'int');
	}
}