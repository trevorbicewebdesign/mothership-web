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
use Joomla\CMS\Version;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Table\Table;

require_once JPATH_ADMINISTRATOR . '/components/com_rsfirewall/helpers/diff.php';

class RsfirewallModelDiff extends BaseDatabaseModel
{
	const RAW_URL = 'https://raw.githubusercontent.com/joomla/joomla-cms/%s/%s';

	public function getFile()
	{
		return Factory::getApplication()->input->getPath('file');
	}
	
	public function getHashId()
	{
		return Factory::getApplication()->input->get('hid');
	}

	protected function getJoomlaVersion()
	{
		$jversion = new Version();

		return $jversion->getShortVersion();
	}

	public function getLocalFilename()
	{
		return JPATH_SITE . '/' . $this->getFile();
	}

	public function getLocalFile()
	{
		$path = $this->getLocalFilename();

		if (!file_exists($path))
		{
			throw new Exception(Text::sprintf('COM_RSFIREWALL_FILE_NOT_FOUND', $path));
		}

		if (!is_readable($path))
		{
			throw new Exception(Text::sprintf('COM_RSFIREWALL_FILE_NOT_READABLE', $path));
		}

		if (!is_file($path))
		{
			throw new Exception(Text::sprintf('COM_RSFIREWALL_NOT_A_FILE', $path));
		}

		return file_get_contents($path);
	}
	
	public function getLocalTime()
	{
		$path = $this->getLocalFilename();

		if (!file_exists($path))
		{
			throw new Exception(Text::sprintf('COM_RSFIREWALL_FILE_NOT_FOUND', $path));
		}

		if (!is_readable($path))
		{
			throw new Exception(Text::sprintf('COM_RSFIREWALL_FILE_NOT_READABLE', $path));
		}

		if (!is_file($path))
		{
			throw new Exception(Text::sprintf('COM_RSFIREWALL_NOT_A_FILE', $path));
		}
		
		if ($time = @filemtime($path))
		{
			return HTMLHelper::_('date.relative', gmdate('Y-m-d H:i:s', $time));
		}
		
		return '';
	}

	public function getRemoteFilename()
	{
		return sprintf(self::RAW_URL, $this->getJoomlaVersion(), $this->getFile());
	}

	public function getRemoteFile()
	{
		$url = $this->getRemoteFilename();

		// Try to connect
		$response = $this->connect($url);

		// Error in response code
		if ($response->code != 200)
		{
			throw new Exception(Text::sprintf('COM_RSFIREWALL_HTTP_ERROR_RESPONSE_CODE', $response->code));
		}

		return $response->body;
	}

	public function downloadOriginalFile($localFile, $exit = true)
	{
		$message = array(
			'status' => false,
			'files'  =>  array(
				'localFile' => $localFile
			)
		);

		$message['files']['remoteFile'] = sprintf(self::RAW_URL, $this->getJoomlaVersion(), $message['files']['localFile']);

		try {
			$response = $this->connect($message['files']['remoteFile']);

			// Error in response code
			if ($response->code != 200)
			{
				throw new Exception(Text::sprintf('COM_RSFIREWALL_HTTP_ERROR_RESPONSE_CODE', $response->code));
			}

			// Rewrite the localfile with the remote file
			if (! @ File::write(JPATH_SITE . '/' . $message['files']['localFile'], $response->body)){
				throw new Exception(Text::_('COM_RSFIREWALL_FILESYSTEM_ERROR_COPY_FAILED'));
			}

            $table = Table::getInstance('Hashes', 'RsfirewallTable', array());
			$db = Factory::getDbo();
            $query = $db->getQuery(true);

            $query->select($db->qn('id'))
                ->from($db->qn('#__rsfirewall_hashes'))
                ->where($db->qn('file').'='.$db->q($message['files']['localFile']))
                ->where('('. $db->qn('type') . '=' . $db->q('ignore') . ' OR ' . $db->qn('type') . ' = ' . $db->q($this->getCurrentJoomlaVersion()) . ')');
            $db->setQuery($query);
            if ($id = $db->loadResult())
            {
                $table->load($id);
                $table->bind(array(
                    'hash' => md5_file(JPATH_SITE . '/' . $message['files']['localFile']),
                    'date' => Factory::getDate()->toSql()
                ));
                $table->store();
            }

			$message['status']  = true;
			$message['message'] = Text::_('COM_RSFIREWALL_FILESYSTEM_FILES_COPIED');

		} catch (Exception $e) {
			$message['message'] = $e->getMessage();
		}

		if ($exit)
		{
			echo json_encode($message);
			jexit();
		}
		else 
		{
			// for cli call we must return the message
			return $message;
		}
	}

    public function getCurrentJoomlaVersion()
    {
        static $current = null;

        if (is_null($current))
        {
            $current = $this->getInstance('Check', 'RsfirewallModel')->getCurrentJoomlaVersion();
        }

        return $current;
    }

	protected function connect($url, $caching = true)
	{
		$cache = Factory::getCache('com_rsfirewall');
		$cache->setCaching($caching);
		$cache->setLifetime(300);

		return $cache->get(array('RsfirewallModelDiff', 'connectCache'), array($url));
	}

	public static function connectCache($url)
	{
		$response = HttpFactory::getHttp()->get($url, array(), 30);

		return (object) array(
			'code' => $response->code,
			'body' => $response->body
		);
	}
}