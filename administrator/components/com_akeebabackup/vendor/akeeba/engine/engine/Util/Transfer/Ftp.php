<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License version 3, or later
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program. If not, see
 * <https://www.gnu.org/licenses/>.
 */

namespace Akeeba\Engine\Util\Transfer;

defined('AKEEBAENGINE') || die();

use Exception;
use RuntimeException;

/**
 * FTP transfer object, using PHP as the transport backend
 */
class Ftp implements TransferInterface, RemoteResourceInterface
{
	/**
	 * FTP server's hostname or IP address
	 *
	 * @var  string
	 */
	protected $host = 'localhost';

	/**
	 * FTP server's port, default: 21
	 *
	 * @var  integer
	 */
	protected $port = 21;

	/**
	 * Username used to authenticate to the FTP server
	 *
	 * @var  string
	 */
	protected $username = '';

	/**
	 * Password used to authenticate to the FTP server
	 *
	 * @var  string
	 */
	protected $password = '';

	/**
	 * FTP initial directory
	 *
	 * @var  string
	 */
	protected $directory = '/';

	/**
	 * Should I use SSL to connect to the server (FTP over explicit SSL, a.k.a. FTPS)?
	 *
	 * @var  boolean
	 */
	protected $ssl = false;

	/**
	 * Should I use FTP passive mode?
	 *
	 * @var bool
	 */
	protected $passive = true;

	/**
	 * Timeout for connecting to the FTP server, default: 10
	 *
	 * @var  integer
	 */
	protected $timeout = 10;

	/**
	 * The FTP connection handle
	 *
	 * @var  resource|null
	 */
	private $connection = null;

	/**
	 * Public constructor
	 *
	 * @param   array  $options  Configuration options
	 *
	 * @return  void
	 *
	 * @throws  RuntimeException
	 */
	public function __construct(array $options)
	{
		if (isset($options['host']))
		{
			$this->host = $options['host'];
		}

		if (isset($options['port']))
		{
			$this->port = (int) $options['port'];
		}

		if (isset($options['username']))
		{
			$this->username = $options['username'];
		}

		if (isset($options['password']))
		{
			$this->password = $options['password'];
		}

		if (isset($options['directory']))
		{
			$this->directory = '/' . ltrim(trim($options['directory']), '/');
		}

		if (isset($options['ssl']))
		{
			$this->ssl = $options['ssl'];
		}

		if (isset($options['passive']))
		{
			$this->passive = $options['passive'];
		}

		if (isset($options['timeout']))
		{
			$this->timeout = max(1, (int) $options['timeout']);
		}

		$this->connect();
	}

	/**
	 * Is this transfer method blocked by a server firewall?
	 *
	 * @param   array  $params  Any additional parameters you might need to pass
	 *
	 * @return  boolean  True if the firewall blocks connections to a known host
	 */
	public static function isFirewalled(array $params = [])
	{
		try
		{
			$connector = new static([
				'host'      => 'test.rebex.net',
				'port'      => 21,
				'username'  => 'demo',
				'password'  => 'password',
				'directory' => '',
				'ssl'       => $params['ssl'] ?? false,
				'passive'   => true,
				'timeout'   => 5,
			]);

			$data = $connector->read('readme.txt');

			if (empty($data))
			{
				return true;
			}
		}
		catch (Exception $e)
		{
			return true;
		}

		return false;
	}

	/**
	 * Save all parameters on serialization except the connection resource
	 *
	 * @return  array
	 */
	public function __sleep()
	{
		return ['host', 'port', 'username', 'password', 'directory', 'ssl', 'passive', 'timeout'];
	}

	/**
	 * Reconnect to the server on unserialize
	 *
	 * @return  void
	 */
	public function __wakeup()
	{
		$this->connect();
	}

	/**
	 * Connect to the FTP server
	 *
	 * @throws  RuntimeException
	 */
	public function connect()
	{
		// Try to connect to the server
		if ($this->ssl)
		{
			if (function_exists('ftp_ssl_connect'))
			{
				$this->connection = @ftp_ssl_connect($this->host, $this->port);
			}
			else
			{
				$this->connection = false;

				throw new RuntimeException('ftp_ssl_connect not available on this server', 500);
			}
		}
		else
		{
			$this->connection = @ftp_connect($this->host, $this->port, $this->timeout);
		}

		if ($this->connection === false)
		{
			throw new RuntimeException(sprintf('Cannot connect to FTP server [host:port] = %s:%s', $this->host, $this->port), 500);
		}

		// Attempt to authenticate
		if (!@ftp_login($this->connection, $this->username, $this->password))
		{
			@ftp_close($this->connection);
			$this->connection = null;

			throw new RuntimeException(sprintf('Cannot log in to FTP server [username:password] = %s:%s', $this->username, $this->password), 500);
		}

		// Attempt to change to the initial directory
		$defaultDir  = @ftp_pwd($this->connection) ?: '/';
		$directories = [
			$this->directory,
			rtrim($this->directory, '/'),
			trim($this->directory, '/'),
			$defaultDir,
		];

		foreach ($directories as $dir)
		{
			$changedDir = @ftp_chdir($this->connection, $dir);

			if ($changedDir)
			{
				$this->directory = $dir;

				break;
			}
		}

		if (!$changedDir)
		{
			@ftp_close($this->connection);
			$this->connection = null;

			throw new RuntimeException(sprintf('Cannot change to initial FTP directory "%s" – make sure the folder exists and that you have adequate permissions to it. Pro tip: the default directory of your FTP connection is reported to be %s', $this->directory, $defaultDir), 500);
		}

		// Apply the passive mode preference
		@ftp_pasv($this->connection, $this->passive);
	}

	/**
	 * Public destructor, closes any open FTP connections
	 */
	public function __destruct()
	{
		if (!is_null($this->connection))
		{
			@ftp_close($this->connection);
		}
	}

	/**
	 * Write the contents into the file
	 *
	 * @param   string  $fileName  The full path to the file
	 * @param   string  $contents  The contents to write to the file
	 *
	 * @return  boolean  True on success
	 */
	public function write($fileName, $contents)
	{
		// Make sure the buffer:// wrapper is loaded
		class_exists('\\Akeeba\\Engine\\Util\\Buffer', true);

		$handle = fopen('buffer://akeeba_engine_transfer_ftp', 'r+');
		fwrite($handle, $contents);
		rewind($handle);

		$cwd            = $this->cwd();
		$remoteFilename = '/' . ltrim($fileName, '/');
		$remotePath     = dirname($remoteFilename);
		$remoteName     = basename($remoteFilename);

		if (!$this->isDir($remotePath))
		{
			$this->mkdir($remotePath);
		}

		$changedDir = @ftp_chdir($this->connection, $remotePath);

		$ret = $changedDir && @ftp_fput($this->connection, $remoteName, $handle, FTP_BINARY);

		if ($changedDir)
		{
			@ftp_chdir($this->connection, $cwd);
		}

		fclose($handle);

		return $ret;
	}

	/**
	 * Uploads a local file to the remote storage
	 *
	 * @param   string  $localFilename   The full path to the local file
	 * @param   string  $remoteFilename  The full path to the remote file
	 * @param   bool    $useExceptions   Throw an exception instead of returning "false" on connection error.
	 *
	 * @return  boolean  True on success
	 */
	public function upload($localFilename, $remoteFilename, $useExceptions = true)
	{
		$handle = @fopen($localFilename, 'r');

		if ($handle === false)
		{
			if ($useExceptions)
			{
				throw new RuntimeException("Unreadable local file $localFilename");
			}

			return false;
		}

		$cwd            = $this->cwd();
		$remoteFilename = '/' . ltrim($remoteFilename, '/');
		$remotePath     = dirname($remoteFilename);
		$remoteName     = basename($remoteFilename);

		if (!$this->isDir($remotePath))
		{
			$this->mkdir($remotePath);
		}

		$changedDir = @ftp_chdir($this->connection, $remotePath);
		$ret        = $changedDir && @ftp_fput($this->connection, $remoteName, $handle, FTP_BINARY);

		if ($changedDir)
		{
			@ftp_chdir($this->connection, $cwd);
		}

		@fclose($handle);

		return $ret;
	}

	/**
	 * Read the contents of a remote file into a string
	 *
	 * @param   string  $fileName  The full path to the remote file
	 *
	 * @return  string  The contents of the remote file
	 */
	public function read($fileName)
	{
		// Make sure the buffer:// wrapper is loaded
		class_exists('\\Akeeba\\Engine\\Util\\Buffer', true);

		$handle = fopen('buffer://akeeba_engine_transfer_ftp', 'r+');

		$cwd            = $this->cwd();
		$remoteFilename = '/' . ltrim($fileName, '/');
		$remotePath     = dirname($remoteFilename);
		$remoteName     = basename($remoteFilename);
		$changedDir     = @ftp_chdir($this->connection, $remotePath);
		$result         = $changedDir && @ftp_fget($this->connection, $handle, $remoteName, FTP_BINARY);

		if ($changedDir)
		{
			@ftp_chdir($this->connection, $cwd);
		}

		if ($result === false)
		{
			fclose($handle);
			throw new RuntimeException("Can not download remote file $fileName");
		}

		rewind($handle);

		$ret = '';

		while (!feof($handle))
		{
			$ret .= fread($handle, 131072);
		}

		fclose($handle);

		return $ret;
	}

	/**
	 * Download a remote file into a local file
	 *
	 * @param   string  $remoteFilename  The remote file path to download from
	 * @param   string  $localFilename   The local file path to download to
	 * @param   bool    $useExceptions   Throw an exception instead of returning "false" on connection error.
	 *
	 * @return  boolean  True on success
	 */
	public function download($remoteFilename, $localFilename, $useExceptions = true)
	{
		$cwd            = $this->cwd();
		$remoteFilename = '/' . ltrim($remoteFilename, '/');
		$remotePath     = dirname($remoteFilename);
		$remoteName     = basename($remoteFilename);
		$changedDir     = @ftp_chdir($this->connection, $remotePath);

		$ret = $changedDir && @ftp_get($this->connection, $localFilename, $remoteName, FTP_BINARY);

		if ($changedDir)
		{
			@ftp_chdir($this->connection, $cwd);
		}

		if (!$ret && $useExceptions)
		{
			throw new RuntimeException("Cannot download remote file $remoteFilename through FTP.");
		}

		return $ret;
	}

	/**
	 * Delete a file (remove it from the disk)
	 *
	 * @param   string  $fileName  The full path to the file
	 *
	 * @return  boolean  True on success
	 */
	public function delete($fileName)
	{
		$cwd            = $this->cwd();
		$remoteFilename = '/' . ltrim($fileName, '/');
		$remotePath     = dirname($remoteFilename);
		$remoteName     = basename($remoteFilename);

		if (!$this->isDir($remotePath))
		{
			$this->mkdir($remotePath);
		}

		$changedDir = @ftp_chdir($this->connection, $remotePath);
		$ret        = $changedDir && @ftp_delete($this->connection, $remoteName);;

		if ($changedDir)
		{
			ftp_chdir($this->connection, $cwd);
		}

		return $ret;
	}

	/**
	 * Create a copy of the file. Actually, we have to read it in memory and upload it again.
	 *
	 * @param   string  $from  The full path of the file to copy from
	 * @param   string  $to    The full path of the file that will hold the copy
	 *
	 * @return  boolean  True on success
	 */
	public function copy($from, $to)
	{
		// Make sure the buffer:// wrapper is loaded
		class_exists('\\Akeeba\\Engine\\Util\\Buffer', true);

		$handle = fopen('buffer://akeeba_engine_transfer_ftp', 'r+');

		$cwd            = $this->cwd();
		$remoteFilename = '/' . ltrim($from, '/');
		$remotePath     = dirname($remoteFilename);
		$remoteName     = basename($remoteFilename);
		$changedDir     = @ftp_chdir($this->connection, $remotePath);

		$ret = $changedDir && @ftp_fget($this->connection, $handle, $remoteName, FTP_BINARY);

		if ($ret !== false)
		{
			rewind($handle);

			$remoteFilename = '/' . ltrim($to, '/');
			$remotePath     = dirname($remoteFilename);
			$remoteName     = basename($remoteFilename);

			if (!$this->isDir($remotePath))
			{
				$this->mkdir($remotePath);
			}

			$changedDir = @ftp_chdir($this->connection, $remotePath);
			$ret        = $changedDir && @ftp_fput($this->connection, $remoteName, $handle, FTP_BINARY);
		}

		if ($changedDir)
		{
			@ftp_chdir($this->connection, $cwd);
		}

		fclose($handle);

		return $ret;
	}

	/**
	 * Move or rename a file
	 *
	 * @param   string  $from  The full path of the file to move
	 * @param   string  $to    The full path of the target file
	 *
	 * @return  boolean  True on success
	 */
	public function move($from, $to)
	{
		return @ftp_rename($this->connection, $from, $to);
	}

	/**
	 * Change the permissions of a file
	 *
	 * @param   string   $fileName     The full path of the file whose permissions will change
	 * @param   integer  $permissions  The new permissions, e.g. 0644 (remember the leading zero in octal numbers!)
	 *
	 * @return  boolean  True on success
	 */
	public function chmod($fileName, $permissions)
	{
		if (@ftp_chmod($this->connection, $permissions, $fileName) !== false)
		{
			return true;
		}

		$permissionsOctal = decoct((int) $permissions);

		if (@ftp_site($this->connection, "CHMOD $permissionsOctal $fileName") !== false)
		{
			return true;
		}

		return false;
	}

	/**
	 * Create a directory if it doesn't exist. The operation is implicitly recursive, i.e. it will create all
	 * intermediate directories if they do not already exist.
	 *
	 * @param   string   $dirName      The full path of the directory to create
	 * @param   integer  $permissions  The permissions of the created directory
	 *
	 * @return  boolean  True on success
	 */
	public function mkdir($dirName, $permissions = 0755)
	{
		$targetDir = rtrim($dirName, '/');

		$directories = explode('/', $targetDir);

		$remoteDir = '';

		foreach ($directories as $dir)
		{
			if (!$dir)
			{
				continue;
			}

			$remoteDir .= '/' . $dir;

			// Continue if the folder already exists. Otherwise I'll get a an error even if everything is fine
			if ($this->isDir($remoteDir))
			{
				continue;
			}

			$ret = @ftp_mkdir($this->connection, $remoteDir);

			if ($ret === false)
			{
				return $ret;
			}
		}

		$this->chmod($dirName, $permissions);

		return true;
	}

	/**
	 * Checks if the given directory exists
	 *
	 * @param   string  $path  The full path of the remote directory to check
	 *
	 * @return  boolean  True if the directory exists
	 */
	public function isDir($path)
	{
		$cur_dir = ftp_pwd($this->connection);

		if (@ftp_chdir($this->connection, $path))
		{
			// If it is a directory, then change the directory back to the original directory
			ftp_chdir($this->connection, $cur_dir);

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Get the current working directory
	 *
	 * @return  string
	 */
	public function cwd()
	{
		return ftp_pwd($this->connection);
	}

	/**
	 * Returns the absolute remote path from a path relative to the initial directory configured when creating the
	 * transfer object.
	 *
	 * @param   string  $fileName  The relative path of a file or directory
	 *
	 * @return  string  The absolute path for use by the transfer object
	 */
	public function getPath($fileName)
	{
		$fileName = str_replace('\\', '/', $fileName);

		if (strpos($fileName, $this->directory) === 0)
		{
			return $fileName;
		}

		$fileName = trim($fileName, '/');
		$fileName = rtrim($this->directory, '/') . '/' . $fileName;

		return $fileName;
	}

	/**
	 * Lists the subdirectories inside an FTP directory
	 *
	 * @param   null|string  $dir  The directory to scan. Skip to use the current directory.
	 *
	 * @return  array|bool  A list of folders, or false if we could not get a listing
	 *
	 * @throws  RuntimeException  When the server is incompatible with our FTP folder scanner
	 */
	public function listFolders($dir = null)
	{
		if (!@ftp_chdir($this->connection, $dir))
		{
			throw new RuntimeException(sprintf('Cannot change to FTP directory "%s" – make sure the folder exists and that you have adequate permissions to it', $dir), 500);
		}

		$list = @ftp_rawlist($this->connection, '.');

		if ($list === false)
		{
			throw new RuntimeException("Sorry, your FTP server doesn't support our FTP directory browser.");
		}

		$folders = [];

		foreach ($list as $v)
		{
			$vInfo = preg_split("/[\s]+/", $v, 9);

			if ($vInfo[0] !== "total")
			{
				$perms = $vInfo[0];

				if (substr($perms, 0, 1) == 'd')
				{
					$folders[] = $vInfo[8];
				}
			}
		}

		asort($folders);

		return $folders;
	}

	/**
	 * Return a string with the appropriate stream wrapper protocol for $path. You can use the result with all PHP
	 * functions / classes which accept file paths such as DirectoryIterator, file_get_contents, file_put_contents,
	 * fopen etc.
	 *
	 * @param   string  $path
	 *
	 * @return  string
	 */
	public function getWrapperStringFor($path)
	{
		$usernameEncoded = urlencode($this->username);
		$passwordEncoded = urlencode($this->password);
		$hostname        = $this->host . ($this->port ? ":{$this->port}" : '');
		$protocol        = $this->ssl ? "ftps" : "ftp";

		return "{$protocol}://{$usernameEncoded}:{$passwordEncoded}@{$hostname}{$path}";
	}

	/**
	 * Return the raw server listing for the requested folder.
	 *
	 * @param   string  $folder  The path name to list
	 *
	 * @return  string
	 */
	public function getRawList($folder)
	{
		return ftp_rawlist($this->connection, $folder);
	}
}
