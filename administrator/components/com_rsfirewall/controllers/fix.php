<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;

class RsfirewallControllerFix extends BaseController
{
	protected $folder_permissions 	 = 755;
	protected $file_permissions 	 = 644;

	public function __construct($config = array()) {
		parent::__construct($config);
		
		$config 					= RSFirewallConfig::getInstance();
		$this->folder_permissions 	= $config->get('folder_permissions');
		$this->file_permissions 	= $config->get('file_permissions');
	}
	
	protected function showResponse($success, $data=null) {
		$app 		= Factory::getApplication();
		$document 	= $app->getDocument();
		
		// set JSON encoding
		$document->setMimeEncoding('application/json');
		
		// compute the response
		$response = new stdClass();
		$response->success = $success;
		if ($data) {
			$response->data = $data;
		}
		
		// show the response
		echo json_encode($response);
		
		// close
		$app->close();
	}
	
	public function fixAdminUser() {
		$checkModel	= $this->getModel('check');
		$fixModel	= $this->getModel('fix');
		
		$id	= $checkModel->hasAdminUser();
		
		$success 		= true;
		$data			= new stdClass();
		$data->result  	= true;
		$data->message 	= Text::_('COM_RSFIREWALL_RENAME_ADMIN_SUCCESS');
		
		if ($id && !$fixModel->renameAdminUser($id)) {
			$data->result  = false;
			$data->message = $fixModel->getError();
		}
		
		$this->showResponse($success, $data);
	}
	
	public function fixFTPPassword() {
		$checkModel	= $this->getModel('check');
		$fixModel	= $this->getModel('fix');
		
		$configuration = $checkModel->buildConfiguration(array('ftp_pass' => ''));
		
		$success 		= true;
		$data	 		= new stdClass();
		$data->result 	= true;
		$data->message  = Text::_('COM_RSFIREWALL_CONFIGURATION_SAVED_SUCCESS');
		
		// using @ because file_put_contents outputs a warning when unsuccessful
		if (!@$fixModel->saveConfiguration($configuration)) {
			$data->result  = false;
			$data->message = $fixModel->getError();
		}
		
		$this->showResponse($success, $data);
	}
	
	public function fixSEF() {
		$checkModel	= $this->getModel('check');
		$fixModel	= $this->getModel('fix');
		
		$configuration = $checkModel->buildConfiguration(array('sef' => 1));
		
		$success 		= true;
		$data	 		= new stdClass();
		$data->result 	= true;
		$data->message  = Text::_('COM_RSFIREWALL_CONFIGURATION_SAVED_SUCCESS');
		
		// using @ because file_put_contents outputs a warning when unsuccessful
		if (!@$fixModel->saveConfiguration($configuration)) {
			$data->result  = false;
			$data->message = $fixModel->getError();
		}
		
		$this->showResponse($success, $data);
	}
	
	public function fixConfiguration() {
		$checkModel	= $this->getModel('check');
		$fixModel	= $this->getModel('fix');
		
		$configuration = $checkModel->buildConfiguration();
		
		$success 		= true;
		$data	 		= new stdClass();
		$data->result 	= true;
		$data->message  = Text::_('COM_RSFIREWALL_CONFIGURATION_SAVED_SUCCESS');
		
		// using @ because file_put_contents outputs a warning when unsuccessfull
		if (!@$fixModel->saveConfiguration($configuration)) {
			$data->result  = false;
			$data->message = $fixModel->getError();
		}
		
		$this->showResponse($success, $data);
	}
	
	public function fixSession() {
		$checkModel	= $this->getModel('check');
		$fixModel	= $this->getModel('fix');
		
		$configuration = $checkModel->buildConfiguration(array('lifetime' => 15));
		
		$success 		= true;
		$data	 		= new stdClass();
		$data->result 	= true;
		$data->message  = Text::_('COM_RSFIREWALL_CONFIGURATION_SAVED_SUCCESS');
		
		if (!$fixModel->saveConfiguration($configuration)) {
			$data->result  = false;
			$data->message = $fixModel->getError();
		}
		
		$this->showResponse($success, $data);
	}
	
	public function fixHtaccess() {
		$checkModel	= $this->getModel('check');
		$fixModel	= $this->getModel('fix');
		
		$file 		= $checkModel->getAccessFile();
		$default 	= $checkModel->getDefaultAccessFile();
		
		$success 		= true;
		$data	 		= new stdClass();
		$data->result 	= true;
		$data->message  = Text::sprintf('COM_RSFIREWALL_HTACCESS_SAVED_SUCCESS', $file);
		
		$contents = $fixModel->loadFile(JPATH_SITE.'/'.$default); // trying to read data
		if (!$contents) {
			$data->result  = false;
			$data->message = Text::sprintf('COM_RSFIREWALL_HTACCESS_READ_ERROR', $default);
		} else {
			// prepare data
			if ($file == '.htaccess') {
				$path = Uri::root(true);
				if ($path) {
					$contents = str_replace('# RewriteBase /', 'RewriteBase '.$path, $contents);
				}
			}
			
			if (!$fixModel->saveFile(JPATH_SITE.'/'.$file, $contents)) { // trying to save data
				$data->result  = false;
				$data->message = Text::sprintf('COM_RSFIREWALL_HTACCESS_SAVED_ERROR', $file);
			}
		}
		
		$this->showResponse($success, $data);
	}
	
	public function fixTemporaryFiles() {
		$checkModel	= $this->getModel('check');
		
		$success 		= true;
		$data	 		= new stdClass();
		$data->result 	= true;
		
		$tmp = $checkModel->getTemporaryFolder();
		/**
		 * Check if the Joomla! temporary folder is wrongfully set
		 */
		if ($tmp == JPATH_SITE || realpath($tmp) == realpath(JPATH_SITE) || $tmp == '' || $tmp == '/tmp' || realpath($tmp) == '' || strpos(JPATH_SITE, $tmp) === 0) {
			$data->result  = false;
			$data->message = Text::sprintf('COM_RSFIREWALL_TEMPORARY_FOLDER_INCORRECTLY_SET', $tmp);
		} else {
			$files 		= $checkModel->getFiles(realpath($tmp), false, false, true);
			$folders 	= $checkModel->getFolders(realpath($tmp), true, false, true);
			
			if ($files === false || $folders === false) {
				$data->result  = false;
				$data->message = $checkModel->getError();
			} else {
				// don't delete index.html from tmp
				$index = array_search(realpath($tmp).DIRECTORY_SEPARATOR.'index.html', $files);
				if ($index !== false) {
					unset($files[$index]);
				}
				
				if (!File::delete($files)) {
					$data->result  = false;
					$data->message = Text::_('COM_RSFIREWALL_COULD_NOT_DELETE_TEMPORARY_FILES');
				}
				
				foreach ($folders as $folder) {
					if (!Folder::delete($folder)) {
						$data->result = false;
						$data->message = Text::_('COM_RSFIREWALL_COULD_NOT_DELETE_TEMPORARY_FILES');
					}
				}
			}
		}
		
		if ($data->result) {
			$data->message  = Text::_('COM_RSFIREWALL_EMPTIED_TEMPORARY_FOLDER');
		}
		
		$this->showResponse($success, $data);
	}
	
	public function fixHashes() {
		$model = $this->getModel('fix');			
		if (!$model->ignoreFiles()) {
			$success 		= false;
            $data	 		= new stdClass();
			$data->result   = false;
			$data->message  = $model->getError();
		} else {
			$success 		= true;
			$data	 		= new stdClass();
			$data->result 	= true;
			$data->message	= Text::_('COM_RSFIREWALL_THE_CHANGES_HAVE_BEEN_ACCEPTED');
		}
		
		$this->showResponse($success, $data);
	}
	
	public function ignoreFiles() {
		$config = RSFirewallConfig::getInstance();
		$input 	= Factory::getApplication()->input;
		$files  = $input->get('files', array(), 'array');
		
		foreach ($files as $file) {
			$config->append('ignore_files_folders', JPATH_SITE . DIRECTORY_SEPARATOR . $file);
		}
		
		$success 		= true;
		$data	 		= new stdClass();
		$data->result 	= true;
		$data->message  = Text::_('COM_RSFIREWALL_IGNORE_FILES_SUCCESS');
		
		$this->showResponse($success, $data);
	
	}
	
	public function fixFolderPermissions() {
		$model 		= $this->getModel('fix');
		$input 		= Factory::getApplication()->input;
		$folders 	= $input->get('folders', array(), 'array');
		
		$success 		= true;
		$data	 		= new stdClass();
		$data->result	= true;
		$data->results  = $model->setPermissions($folders, $this->folder_permissions);
		$data->message  = Text::_('COM_RSFIREWALL_FIX_FOLDER_PERMISSIONS_DONE');
		
		$this->showResponse($success, $data);
	}
	
	public function fixFilePermissions() {
		$model 		= $this->getModel('fix');
		$input 		= Factory::getApplication()->input;
		$files 		= $input->get('files', array(), 'array');
		
		$success 		= true;
		$data	 		= new stdClass();
		$data->result	= true;
		$data->results  = $model->setPermissions($files, $this->file_permissions);
		$data->message  = Text::_('COM_RSFIREWALL_FIX_FILE_PERMISSIONS_DONE');
		
		$this->showResponse($success, $data);
	}
}