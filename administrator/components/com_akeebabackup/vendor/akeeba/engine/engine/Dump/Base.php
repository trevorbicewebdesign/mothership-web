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

namespace Akeeba\Engine\Dump;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Base\Part;
use Akeeba\Engine\Core\Domain\Pack;
use Akeeba\Engine\Driver\Base as DriverBase;
use Akeeba\Engine\Dump\Dependencies\Entity;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Akeeba\Engine\Util\FileCloseAware;
use Akeeba\Engine\Util\HashTrait;
use Exception;
use RuntimeException;

#[\AllowDynamicProperties]
abstract class Base extends Part
{
	use HashTrait;
	use FileCloseAware;

	// **********************************************************************
	// Configuration parameters
	// **********************************************************************

	/** @var int Current dump file part number */
	public $partNumber = 0;

	/** @var string Prefix to this database */
	protected $prefix = '';

	/** @var string MySQL database server host name or IP address */
	protected $host = '';

	/** @var string MySQL database server port (optional) */
	protected $port = '';

	/** @var string MySQL socket or named pipe (optional) */
	protected $socket = '';

	/** @var string MySQL user name, for authentication */
	protected $username = '';

	/** @var string MySQL password, for authentication */
	protected $password = '';

	/** @var string MySQL database */
	protected $database = '';

	/** @var string The database driver to use */
	protected $driver = '';

	/** @var array|null The MySQL SSL options */
	protected $ssl;

	// **********************************************************************
	// File handling fields
	// **********************************************************************
	/** @var boolean Should I post process quoted values */
	protected $postProcessValues = false;

	/** @var string Absolute path to dump file; must be writable (optional; if left blank it is automatically calculated) */
	protected $dumpFile = '';

	/** @var string Data cache, used to cache data before being written to disk */
	protected $data_cache = '';

	/** @var int */
	protected $largest_query = 0;

	/** @var int Size of the data cache, default 128Kb */
	protected $cache_size = 131072;

	/** @var bool Should I process empty prefixes when creating abstracted names? */
	protected $processEmptyPrefix = true;

	/** @var string Absolute path to the temp file */
	protected $tempFile = '';

	/** @var string Relative path of how the file should be saved in the archive */
	protected $saveAsName = '';

	/** @var array Contains the sorted (by dependencies) list of tables/views to backup */
	protected $tables = [];

	// **********************************************************************
	// Protected fields (data handling)
	// **********************************************************************
	/** @var array Contains the configuration data of the tables */
	protected $tables_data = [];

	/** @var array Maps database table names to their abstracted format */
	protected $table_name_map = [];

	/** @var array Contains the dependencies of tables and views (temporary) */
	protected $dependencies = [];

	/** @var mixed The next table or DB entity to back up */
	protected $nextTable;

	/** @var integer The next row of the table to start backing up from */
	protected $nextRange;

	/** @var integer Current table's row count */
	protected $maxRange;

	/** @var bool Use extended INSERTs */
	protected $extendedInserts = false;

	/** @var integer Maximum packet size for extended INSERTs, in bytes */
	protected $packetSize = 0;

	/** @var string Extended INSERT query, while it's being constructed */
	protected $query = '';

	/** @var int Dump part's maximum size */
	protected $partSize = 0;

	/** @var resource Filepointer to the current dump part */
	private $fp = null;

	/**
	 * Should I be using an abstract prefix (#__) for table names?
	 *
	 * @var   bool
	 * @since 9.1.0
	 */
	private $useAbstractPrefix;

	/**
	 * Public constructor.
	 *
	 * @return  void
	 * @since   9.1.0
	 */
	public function __construct()
	{
		$this->useAbstractPrefix =
			Factory::getEngineParamsProvider()->getScriptingParameter('db.saveasname', 'normal') !== 'output';

		parent::__construct();
	}


	/**
	 * This method is called when the factory is being serialized and is used to perform necessary cleanup steps.
	 *
	 * @return  void
	 */
	public function _onSerialize()
	{
		$this->closeFile();
	}

	/**
	 * This method is called when the object is destroyed and is used to perform necessary cleanup steps.
	 */
	public function __destruct()
	{
		$this->closeFile();
	}

	/**
	 * Close any open SQL dump (output) file.
	 */
	public function closeFile()
	{
		if (!is_resource($this->fp))
		{
			return;
		}

		Factory::getLog()->debug("Closing SQL dump file.");

		$this->conditionalFileClose($this->fp);
		$this->fp = null;
	}

	/**
	 * Call a specific stage of the dump engine
	 *
	 * @param   string  $stage
	 *
	 * @throws Exception
	 */
	public function callStage($stage)
	{
		switch ($stage)
		{
			case '_prepare':
				$this->_prepare();
				break;

			case '_run':
				$this->_run();
				break;

			case '_finalize':
				$this->_finalize();
				break;
		}
	}

	public function getPrefix(): string
	{
		return $this->prefix ?: '';
	}

	/**
	 * Find where to store the backup files
	 *
	 * @param $partNumber int The SQL part number, default is 0 (.sql)
	 */
	protected function getBackupFilePaths($partNumber = 0)
	{
		Factory::getLog()->debug(__CLASS__ . " :: Getting temporary file");
		$basename       = substr(self::md5(microtime() . random_bytes(8)), random_int(0, 16), 16);
		$this->tempFile = Factory::getTempFiles()->registerTempFile($basename . '.sql');
		Factory::getLog()->debug(__CLASS__ . " :: Temporary file is {$this->tempFile}");

		// Get the base name of the dump file
		$partNumber = intval($partNumber);
		$baseName   = $this->dumpFile;

		if ($partNumber > 0)
		{
			// The file names are in the format dbname.sql, dbname.s01, dbname.s02, etc
			if (strtolower(substr($baseName, -4)) == '.sql')
			{
				$baseName = substr($baseName, 0, -4) . '.s' . sprintf('%02u', $partNumber);
			}
			else
			{
				$baseName = $baseName . '.s' . sprintf('%02u', $partNumber);
			}
		}

		if (empty($this->installerSettings))
		{
			// Fetch the installer settings
			$this->installerSettings = (object) [
				'installerroot'  => 'installation',
				'sqlroot'        => 'installation/sql',
				'databasesini'   => 1,
				'readme'         => 1,
				'extrainfo'      => 1,
				'password'       => 0,
				'typedtablelist' => 0,
			];
			$config                  = Factory::getConfiguration();
			$installerKey            = $config->get('akeeba.advanced.embedded_installer');
			$installerDescriptors    = Factory::getEngineParamsProvider()->getInstallerList();

			if (array_key_exists($installerKey, $installerDescriptors))
			{
				// The selected installer exists, use it
				$this->installerSettings = (object) $installerDescriptors[$installerKey];
			}
			elseif (array_key_exists('brs', $installerDescriptors))
			{
				// The selected installer doesn't exist, but ANGIE exists; use that instead
				$this->installerSettings = (object) $installerDescriptors['brs'];
			}
		}

		switch (Factory::getEngineParamsProvider()->getScriptingParameter('db.saveasname', 'normal'))
		{
			case 'output':
				// The SQL file will be stored uncompressed in the output directory
				$statistics       = Factory::getStatistics();
				$statRecord       = $statistics->getRecord();
				$this->saveAsName = $statRecord['absolute_path'];
				break;

			case 'normal':
				// The SQL file will be stored in the SQL root of the archive, as
				// specified by the particular embedded installer's settings
				$this->saveAsName = $this->installerSettings->sqlroot . '/' . $baseName;
				break;

			case 'short':
				// The SQL file will be stored on archive's root
				$this->saveAsName = $baseName;
				break;
		}

		if ($partNumber > 0)
		{
			Factory::getLog()->debug("AkeebaDomainDBBackup :: Creating new SQL dump part #$partNumber");
		}

		Factory::getLog()->debug("AkeebaDomainDBBackup :: SQL temp file is " . $this->tempFile);
		Factory::getLog()->debug("AkeebaDomainDBBackup :: SQL file location in archive is " . $this->saveAsName);
	}

	/**
	 * Deletes any leftover files from previous backup attempts
	 *
	 */
	protected function removeOldFiles()
	{
		Factory::getLog()->debug("AkeebaDomainDBBackup :: Deleting leftover files, if any");

		if (file_exists($this->tempFile))
		{
			@unlink($this->tempFile);
		}
	}

	/**
	 * Populates the table arrays with the information for the db entities to back up
	 *
	 * @return  void
	 */
	abstract protected function getTablesToBackup(): void;

	/**
	 * Runs a step of the database dump
	 *
	 * @return  void
	 */
	abstract protected function stepDatabaseDump(): void;

	/**
	 * Return the current database name by querying the database connection object (e.g. SELECT DATABASE() in MySQL)
	 *
	 * @return  string
	 */
	abstract protected function getDatabaseNameFromConnection(): string;

	abstract protected function getAllTables(): array;

	/**
	 * Implements the _prepare abstract method
	 *
	 * @throws Exception
	 */
	protected function _prepare()
	{
		$this->setStep('Initialization');
		$this->setSubstep('');

		// Process parameters, passed to us using the setup() public method
		Factory::getLog()->debug(__CLASS__ . " :: Processing parameters");

		if (is_array($this->_parametersArray))
		{
			$this->parametersArrayToProperties();
		}

		// Try to detect and rectify the wrong database table naming prefix
		$this->workaroundWrongPrefix();

		// Make sure we have self-assigned the first part
		$this->partNumber = 0;

		// Get DB backup only mode
		$configuration = Factory::getConfiguration();

		// Find tables to be included and put them in the $_tables variable
		$this->getTablesToBackup();

		// Find where to store the database backup files
		$this->getBackupFilePaths($this->partNumber);

		// Remove any leftovers
		$this->removeOldFiles();

		// Initialize the extended INSERTs feature
		$this->extendedInserts = ($configuration->get('engine.dump.common.extended_inserts', 0) != 0);
		$this->packetSize      = (int) $configuration->get('engine.dump.common.packet_size', 0);

		if ($this->packetSize == 0)
		{
			$this->extendedInserts = false;
		}

		// Initialize the split dump feature
		$this->partSize = $configuration->get('engine.dump.common.splitsize', 1048576);
		if (Factory::getEngineParamsProvider()->getScriptingParameter('db.saveasname', 'normal') == 'output')
		{
			$this->partSize = 0;
		}
		if (($this->partSize != 0) && ($this->packetSize != 0) && ($this->packetSize > $this->partSize))
		{
			$this->packetSize = floor($this->partSize / 2);
		}

		// Initialize the algorithm
		Factory::getLog()->debug(__CLASS__ . " :: Initializing algorithm for first run");
		$this->goToNextTable();

		// If there is no table to back up we are done with the database backup
		if ($this->nextTable === null)
		{
			$this->setState(self::STATE_POSTRUN);

			return;
		}

		$this->nextRange = 0;
		$this->query     = '';

		// FIX 2.2: First table of extra databases was not being written to disk.
		// This deserved a place in the Bug Fix Hall Of Fame. In subsequent calls to _init, the $fp in
		// _writeline() was not nullified. Therefore, the first dump chunk (that is, the first table's
		// definition and first chunk of its data) were not written to disk. This call causes $fp to be
		// nullified, causing it to be recreated, pointing to the correct file.
		$null = null;
		$this->writeline($null);

		// Finally, mark ourselves "prepared".
		$this->setState(self::STATE_PREPARED);
	}

	/**
	 * Get the next table to back up.
	 *
	 * Engines can override it to queue up more complex objects. Set $this->nextTable to NULL to indicate there is
	 * nothing else to back up.
	 *
	 * @return  void
	 * @since   9.10.0
	 */
	protected function goToNextTable(): void
	{
		$this->nextTable = null;

		if (empty($this->tables))
		{
			return;
		}

		$this->nextTable = array_shift($this->tables);
	}

	/**
	 * Implements the _run() abstract method
	 *
	 * @throws Exception
	 */
	protected function _run()
	{
		// Check if we are already done
		if ($this->getState() == self::STATE_POSTRUN)
		{
			Factory::getLog()->debug(__CLASS__ . " :: Already finished");
			$this->setStep("");
			$this->setSubstep("");

			return;
		}

		// Mark ourselves as still running (we will test if we actually do towards the end ;) )
		$this->setState(self::STATE_RUNNING);

		/**
		 * Resume packing / post-processing part files if necessary.
		 *
		 * @see \Akeeba\Engine\Archiver\BaseArchiver::putDataFromFileIntoArchive
		 *
		 * Sometimes the SQL part file may be bigger than the big file threshold (engine.archiver.common.
		 * big_file_threshold). In this case when we try to add it to the backup archive the archiver engine figures
		 * out it has to be added uncompressed, one chunk (engine.archiver.common.chunk_size) bytes at a time. This
		 * happens in a loop. We read a chunk, push it to the archive, rinse and repeat.
		 *
		 * There are two cases when we might break the loop:
		 *
		 * 1. Not enough free space in the backup archive part and engine.postproc.common.after_part (immediate post-
		 *    processing) is enabled. We break the step to let the backup part be post-processed.
		 *
		 * 2. We ran out of time copying data.
		 *
		 * The following if-blocks deal with these two cases.
		 */
		if (Factory::getEngineParamsProvider()->getScriptingParameter('db.saveasname', 'normal') != 'output')
		{
			$archiver      = Factory::getArchiverEngine();
			$configuration = Factory::getConfiguration();

			// Check whether we need to immediately post-processing a done part
			if (Pack::postProcessDonePartFile($archiver, $configuration))
			{
				return;
			}

			// We had already started putting the DB dump file into the archive but it needs more time
			if ($configuration->get('volatile.engine.archiver.processingfile', false))
			{
				/**
				 * We MUST NOT try to continue adding the file to the backup archive manually. Instead, we have to go
				 * through getNextDumpPart. This method will continue adding the part to the backup archive and when
				 * this is done it will remove the file and create a new one.
				 *
				 * If that method returns false it means that we either hit an error or the archiver didn't have enough
				 * time to add the part to the backup archive. In either case we have to return and let the Engine step.
				 */
				if ($this->getNextDumpPart() === false)
				{
					return;
				}
			}
		}

		$this->stepDatabaseDump();

		$null = null;
		$this->writeline($null);
	}

	/**
	 * Implements the _finalize() abstract method
	 *
	 * @throws Exception
	 */
	protected function _finalize()
	{
		Factory::getLog()->debug("Adding any extra SQL statements imposed by the filters");

		foreach (Factory::getFilters()->getExtraSQL($this->databaseRoot) as $sqlStatement)
		{
			$sqlStatement = trim($sqlStatement) . "\n";

			$this->writeDump($sqlStatement, true);
		}

		// We need this to write out the cached extra SQL statements before closing the file.
		$this->writeDump(null, true);

		// Close the file pointer (otherwise the SQL file is left behind)
		$this->closeFile();

		// If we are not just doing a main db only backup, add the SQL file to the archive
		$finished = true;

		if (Factory::getEngineParamsProvider()->getScriptingParameter('db.saveasname', 'normal') != 'output')
		{
			$archiver      = Factory::getArchiverEngine();
			$configuration = Factory::getConfiguration();

			if ($configuration->get('volatile.engine.archiver.processingfile', false))
			{
				// We had already started archiving the db file, but it needs more time
				Factory::getLog()->debug("Continuing adding the SQL dump to the archive");
				$archiver->addFile(null, null, null);

				$finished = !$configuration->get('volatile.engine.archiver.processingfile', false);
			}
			else
			{
				// We have to add the dump file to the archive
				Factory::getLog()->debug("Adding the final SQL dump to the archive");
				$archiver->addFileRenamed($this->tempFile, $this->saveAsName);

				$finished = !$configuration->get('volatile.engine.archiver.processingfile', false);
			}
		}
		else
		{
			// We just have to move the dump file to its final destination
			Factory::getLog()->debug("Moving the SQL dump to its final location");
			$result = Platform::getInstance()->move($this->tempFile, $this->saveAsName);

			if (!$result)
			{
				Factory::getLog()->debug("Removing temporary file of final SQL dump");
				Factory::getTempFiles()->unregisterAndDeleteTempFile($this->tempFile, true);

				throw new RuntimeException('Could not move the SQL dump to its final location');
			}
		}

		// Make sure that if the archiver needs more time to process the file we can supply it
		if ($finished)
		{
			Factory::getLog()->debug("Removing temporary file of final SQL dump");
			Factory::getTempFiles()->unregisterAndDeleteTempFile($this->tempFile, true);

			$this->setState(self::STATE_FINISHED);
		}
	}

	/**
	 * Creates a new dump part
	 *
	 * @return bool
	 * @throws Exception
	 */
	protected function getNextDumpPart()
	{
		// On database dump only mode we mustn't create part files!
		if (Factory::getEngineParamsProvider()->getScriptingParameter('db.saveasname', 'normal') == 'output')
		{
			return false;
		}

		// Is the archiver still processing?
		$configuration   = Factory::getConfiguration();
		$archiver        = Factory::getArchiverEngine();
		$stillProcessing = $configuration->get('volatile.engine.archiver.processingfile', false);

		if ($stillProcessing)
		{
			/**
			 * The archiver is still adding the previous dump part. This means that we are called from the top few lines
			 * of the _run method. We must continue adding the previous dump part.
			 */
			Factory::getLog()->debug("Continuing adding the SQL dump part to the archive");
			$archiver->addFile('', '', '');
		}
		else
		{
			/**
			 * There is no other dump part being processed. Therefore the current SQL dump part is still open. We must
			 * close it and ask the archiver to add it to the backup archive.
			 */
			$this->closeFile();
			Factory::getLog()->debug("Adding the SQL dump part to the archive");
			$archiver->addFileRenamed($this->tempFile, $this->saveAsName);
		}

		// Return false if the file didn't finish getting added to the archive
		if ($configuration->get('volatile.engine.archiver.processingfile', false))
		{
			Factory::getLog()->debug("The SQL dump file has not been processed thoroughly by the archiver. Resuming in the next step.");

			return false;
		}

		/**
		 * If you are here the SQL dump part file is completely added to the backup archive. All we have to do now is
		 * remove it and create a new dump part file.
		 */
		// Remove the old file
		Factory::getLog()->debug("Removing dump part's temporary file");
		Factory::getTempFiles()->unregisterAndDeleteTempFile($this->tempFile, true);

		// Create the new dump part
		$this->partNumber++;
		$this->getBackupFilePaths($this->partNumber);
		$null = null;
		$this->writeline($null);

		return true;
	}

	/**
	 * Creates a new dump part, but only if required to do so
	 *
	 * @return bool
	 * @throws Exception
	 */
	protected function createNewPartIfRequired()
	{
		if ($this->partSize == 0)
		{
			return true;
		}

		$filesize = 0;

		if (@file_exists($this->tempFile))
		{
			$filesize = @filesize($this->tempFile);
		}

		$projectedSize = $filesize + strlen($this->query);

		if ($this->extendedInserts)
		{
			$projectedSize = $filesize + $this->packetSize;
		}

		if ($projectedSize > $this->partSize)
		{
			return $this->getNextDumpPart();
		}

		return true;
	}

	/**
	 * Returns a table's abstract name (replacing the prefix with the magic #__ string)
	 *
	 * @param   string  $tableName  The canonical name, e.g. 'jos_content'
	 *
	 * @return string The abstract name, e.g. '#__content'
	 */
	protected function getAbstract($tableName)
	{
		// Don't return abstract names for non-CMS tables
		if (is_null($this->prefix))
		{
			return $tableName;
		}

		switch ($this->prefix)
		{
			case '':
				if ($this->processEmptyPrefix)
				{
					// This is more of a hack; it assumes all tables are core CMS tables if the prefix is empty.
					return '#__' . $tableName;
				}

				// If $this->processEmptyPrefix (the process_empty_prefix config flag) is false, we don't
				// assume anything.
				return $tableName;

				break;

			default:
				// Normal behaviour for 99% of sites. Start by assuming the table has no prefix, therefore is non-core.
				$tableAbstract = $tableName;

				// If there's a prefix use the abstract name
				if (!empty($this->prefix) && (substr($tableName, 0, strlen($this->prefix)) == $this->prefix))
				{
					$tableAbstract = '#__' . substr($tableName, strlen($this->prefix));
				}

				return $tableAbstract;

				break;
		}
	}

	/**
	 * Writes the SQL dump into the output files. If it fails, it sets the error
	 *
	 * @param   string  $data       Data to write to the dump file. Pass NULL to force flushing to file.
	 * @param   bool    $addMarker  Should I prefix the data with a marker?
	 *
	 * @return  boolean  TRUE on successful write, FALSE otherwise
	 * @throws  Exception
	 */
	protected function writeDump($data, $addMarker = false)
	{
		if (!empty($data))
		{
			if ($addMarker && $this->useAbstractPrefix)
			{
				$this->data_cache .= '/**ABDB**/';
			}
			elseif (!$this->useAbstractPrefix)
			{
				// Replace #__ with the prefix when writing plain .sql files
				$db   = $this->getDB();
				$data = $db->replacePrefix($data) . "\n";
			}

			$this->data_cache .= $data;

			if (strlen($data) > $this->largest_query)
			{
				$this->largest_query = strlen($data);
				Factory::getConfiguration()->set('volatile.database.largest_query', $this->largest_query);
			}
		}

		if ((strlen($this->data_cache) >= $this->cache_size) || (is_null($data) && (!empty($this->data_cache))))
		{
			$this->data_cache = rtrim($this->data_cache, "\n");

			if ($this->useAbstractPrefix && $addMarker && substr($this->data_cache, -10) !== '/**ABDB**/')
			{
				$this->data_cache .= '/**ABDB**/';
			}

			$this->data_cache .= "\n";

			Factory::getLog()->debug("Writing " . strlen($this->data_cache) . " bytes to the dump file");
			$result = $this->writeline($this->data_cache);

			if (!$result)
			{
				$errorMessage = sprintf('Couldn\'t write to the SQL dump file %s; check the temporary directory permissions and make sure you have enough disk space available.', $this->tempFile);
				throw new RuntimeException($errorMessage);
			}

			$this->data_cache = '';
		}

		return true;
	}

	/**
	 * Saves the string in $fileData to the file $backupfile. Returns TRUE. If saving
	 * failed, return value is FALSE.
	 *
	 * @param   string  $fileData  Data to write. Set to null to close the file handle.
	 *
	 * @return boolean TRUE is saving to the file succeeded
	 * @throws Exception
	 */
	protected function writeline(&$fileData)
	{
		if (!is_resource($this->fp))
		{
			$this->fp = @fopen($this->tempFile, 'a');

			if ($this->fp === false)
			{
				throw new RuntimeException('Could not open ' . $this->tempFile . ' for append, in DB dump.');
			}
		}

		if (is_null($fileData))
		{
			$this->conditionalFileClose($this->fp);

			$this->fp = null;

			return true;
		}
		else
		{
			if ($this->fp)
			{
				$ret = fwrite($this->fp, $fileData);
				@clearstatcache();

				// Make sure that all data was written to disk
				return ($ret == strlen($fileData));
			}

			return false;
		}
	}

	/**
	 * Return an instance of DriverBase
	 *
	 * @return DriverBase|bool
	 *
	 * @throws Exception
	 */
	protected function &getDB()
	{
		$ssl     = $this->ssl ?? [];
		$ssl     = is_array($ssl) ? $ssl : [];
		$options = [
			'driver'   => $this->driver,
			'host'     => $this->host,
			'port'     => $this->port,
			'socket'   => $this->socket,
			'user'     => $this->username,
			'password' => $this->password,
			'database' => $this->database,
			'prefix'   => is_null($this->prefix) ? '' : $this->prefix,
			'ssl'      => $ssl,
		];

		$db = Factory::getDatabase($options);

		if ($db->getErrorNum() > 0)
		{
			$error = $db->getErrorMsg();

			throw new RuntimeException(__CLASS__ . ' :: Database Error: ' . $error);
		}

		return $db;
	}

	/**
	 * Returns the database name. If the name was not declared when the object was created we will go through the
	 * getDatabaseNameFromConnection method to populate it.
	 *
	 * @return  string
	 */
	protected function getDatabaseName()
	{
		if (empty($this->database) && $this->database !== '0')
		{
			$this->database = $this->getDatabaseNameFromConnection();
		}

		return $this->database;
	}

	/**
	 * Post process a quoted value before it's written to the database dump.
	 * So far it's only required for SQL Server which has a problem escaping
	 * newline characters...
	 *
	 * @param   string  $value  The quoted value to post-process
	 *
	 * @return  string
	 */
	protected function postProcessQuotedValue($value)
	{
		return $value;
	}

	/**
	 * Returns a preamble for the data dump portion of the SQL backup. This is
	 * used to output commands before the first INSERT INTO statement for a
	 * table when outputting a plain SQL file.
	 *
	 * Practical use: the SET IDENTITY_INSERT sometable ON required for SQL Server
	 *
	 * @param   string   $tableAbstract  Abstract name of the table, e.g. #__foobar
	 * @param   string   $tableName      Real name of the table, e.g. abc_foobar
	 * @param   integer  $maxRange       Row count on this table
	 *
	 * @return  string   The SQL commands you want to be written in the dump file
	 */
	protected function getDataDumpPreamble($tableAbstract, $tableName, $maxRange)
	{
		return '';
	}

	/**
	 * Returns an epilogue for the data dump portion of the SQL backup. This is
	 * used to output commands after the last INSERT INTO statement for a
	 * table when outputting a plain SQL file.
	 *
	 * Practical use: the SET IDENTITY_INSERT sometable OFF required for SQL Server
	 *
	 * @param   string   $tableAbstract  Abstract name of the table, e.g. #__foobar
	 * @param   string   $tableName      Real name of the table, e.g. abc_foobar
	 * @param   integer  $maxRange       Row count on this table
	 *
	 * @return  string   The SQL commands you want to be written in the dump file
	 */
	protected function getDataDumpEpilogue($tableAbstract, $tableName, $maxRange)
	{
		return '';
	}

	/**
	 * Return a list of field names for the INSERT INTO statements. This is only
	 * required for Microsoft SQL Server because without it the SET IDENTITY_INSERT
	 * has no effect.
	 *
	 * @param   array|string  $fieldNames  A list of field names in array format or '*' if it's all fields
	 *
	 * @return  string
	 * @throws Exception
	 */
	protected function getFieldListSQL($fieldNames)
	{
		// If we get a literal '*' we dumped all columns so we don't need to add column names in the INSERT.
		if ($fieldNames === '*')
		{
			return '';
		}

		return '(' . implode(', ', array_map([$this->getDB(), 'qn'], $fieldNames)) . ')';
	}

	/**
	 * Return a list of columns to use in the SELECT query for dumping table data.
	 *
	 * This is used to filter out all generated rows.
	 *
	 * @param   string  $tableAbstract
	 *
	 * @return  string|array  An array of table columns or the string literal '*' to quickly select all columns.
	 *
	 * @see  https://dev.mysql.com/doc/refman/5.7/en/create-table-generated-columns.html
	 */
	protected function getSelectColumns($tableAbstract)
	{
		return '*';
	}

	/**
	 * Converts a human formatted size to integer representation of bytes,
	 * e.g. 1M to 1024768
	 *
	 * @param   string  $setting  The value in human readable format, e.g. "1M"
	 *
	 * @return  integer  The value in bytes
	 */
	protected function humanToIntegerBytes($setting)
	{
		$val  = trim($setting);
		$last = strtolower($val[strlen($val) - 1]);

		if (is_numeric($last))
		{
			return $setting;
		}

		switch ($last)
		{
			case 't':
				$val *= 1024;
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}

		return (int) $val;
	}

	/**
	 * Get the PHP memory limit in bytes
	 *
	 * @return int|null  Memory limit in bytes or null if we can't figure it out.
	 */
	protected function getMemoryLimit()
	{
		if (!function_exists('ini_get'))
		{
			return null;
		}

		$memLimit = ini_get("memory_limit");

		if ((is_numeric($memLimit) && ($memLimit < 0)) || !is_numeric($memLimit))
		{
			$memLimit = 0; // 1.2a3 -- Rare case with memory_limit < 0, e.g. -1Mb!
		}

		$memLimit = $this->humanToIntegerBytes($memLimit);

		return $memLimit;
	}

	/**
	 * @return void
	 */
	private function parametersArrayToProperties(): void
	{
		$this->driver             = $this->_parametersArray['driver'] ?? $this->driver;
		$this->host               = $this->_parametersArray['host'] ?? $this->host;
		$this->port               = $this->_parametersArray['port'] ?? $this->port;
		$this->socket             = $this->_parametersArray['socket'] ?? $this->socket;
		$this->username           = $this->_parametersArray['username'] ?? $this->username;
		$this->username           = $this->_parametersArray['user'] ?? $this->username;
		$this->password           = $this->_parametersArray['password'] ?? $this->password;
		$this->database           = $this->_parametersArray['database'] ?? $this->database;
		$this->prefix             = $this->_parametersArray['prefix'] ?? $this->prefix;
		$this->dumpFile           = $this->_parametersArray['dumpFile'] ?? $this->dumpFile;
		$this->processEmptyPrefix = $this->_parametersArray['process_empty_prefix'] ?? $this->processEmptyPrefix;
		$this->ssl                = $this->_parametersArray['ssl'] ?? $this->ssl;
		$this->ssl                = is_array($this->ssl) ? $this->ssl : [];

		$this->ssl['enable']             = (bool) (($this->ssl['enable'] ?? $this->_parametersArray['dbencryption'] ?? false) ?: false);
		$this->ssl['cipher']             = ($this->ssl['cipher'] ?? $this->_parametersArray['dbsslcipher'] ?? null) ?: null;
		$this->ssl['ca']                 = ($this->ssl['ca'] ?? $this->_parametersArray['dbsslca'] ?? null) ?: null;
		$this->ssl['capath']             = ($this->ssl['capath'] ?? $this->_parametersArray['dbsslcapath'] ?? null) ?: null;
		$this->ssl['key']                = ($this->ssl['key'] ?? $this->_parametersArray['dbsslkey'] ?? null) ?: null;
		$this->ssl['cert']               = ($this->ssl['cert'] ?? $this->_parametersArray['dbsslcert'] ?? null) ?: null;
		$this->ssl['verify_server_cert'] = (bool) (($this->ssl['verify_server_cert'] ?? $this->_parametersArray['dbsslverifyservercert'] ?? false) ?: false);
	}

	private function workaroundWrongPrefix(): void
	{
		// Let's see what kind of prefix I have
		$allLowerCasePrefix = strtolower($this->prefix);
		$allUpperCasePrefix = strtoupper($this->prefix);
		$isUpperCasePrefix  = $this->prefix === $allUpperCasePrefix;
		$isLowerCasePrefix  = $this->prefix === $allLowerCasePrefix;
		$isMixedCasePrefix  = !$isUpperCasePrefix && !$isLowerCasePrefix;

		// Log a message
		if ($isUpperCasePrefix)
		{
			Factory::getLog()->info(
				sprintf(
					'You have an all uppercase database prefix (%s). This might cause backup and restoration issues. We are applying automatic mitigations.',
					$this->prefix
				)
			);
		}
		elseif (!$isLowerCasePrefix)
		{
			Factory::getLog()->info(
				sprintf(
					'You have a mixed-case database prefix (%s). This might cause backup and restoration issues. We are applying automatic mitigations.',
					$this->prefix
				)
			);
		}

		// Check if I have any tables with any form of the prefix
		$allTables = $this->getAllTables();

		$hasOriginalTables = array_reduce(
			$allTables,
			function (bool $carry, string $table): bool {
				return $carry || substr($table, 0, strlen($this->prefix)) === $this->prefix;
			},
			false
		);

		$hasLowercaseTables = array_reduce(
			$allTables,
			function (bool $carry, string $table) use ($allLowerCasePrefix): bool {
				return $carry || substr($table, 0, strlen($allLowerCasePrefix)) === $allLowerCasePrefix;
			},
			false
		);

		$hasUppercaseTables = array_reduce(
			$allTables,
			function (bool $carry, string $table) use ($allUpperCasePrefix): bool {
				return $carry || substr($table, 0, strlen($allUpperCasePrefix)) === $allUpperCasePrefix;
			},
			false
		);

		$hasWrongMixedCaseTables = array_reduce(
			$allTables,
			function (bool $carry, string $table) use ($allUpperCasePrefix, $allLowerCasePrefix): bool {
				if ($carry)
				{
					return $carry;
				}

				$prefix = substr($table, 0, strlen($allUpperCasePrefix));

				if ($prefix === $allUpperCasePrefix || $prefix === $allLowerCasePrefix || $prefix === $this->prefix)
				{
					return false;
				}

				return strtolower($prefix) === strtolower($this->prefix);
			},
			false
		);

		// Set up the warning message
		$warningMessage = sprintf(
			'You have database tables whose name starts with a form of the database prefix (%s) which has a different letter case. This WILL cause problems if you restore your site on Windows or macOS. We strongly recommend excluding all tables which do not start with the configured prefix, exactly as shown above (case-sensitive).',
			$this->prefix
		);

		/**
		 * We have tables returned with the original prefix (e.g. fOo_).
		 *
		 * We won't change the prefix, but we have to warn the user if there is a mix of prefixes in the installed
		 * tables.
		 *
		 * We warn when:
		 * - Any kind of prefix, there are tables with a mixed case prefix which doesn't match the configured one.
		 * - Lowercase prefix, there are uppercase tables
		 * - Uppercase prefix, there are lowercase tables
		 * - Mixed case prefix, there are upper- or lowercase tables
		 */
		if ($hasOriginalTables)
		{
			if (
				$hasWrongMixedCaseTables
				|| ($isLowerCasePrefix && $hasUppercaseTables)
				|| ($isUpperCasePrefix && $hasLowercaseTables)
				|| ($isMixedCasePrefix && ($hasLowercaseTables || $hasUppercaseTables))
			)
			{
				Factory::getLog()->warning($warningMessage);
			}

			return;
		}

		/**
		 * No tables with this prefix. Nothing for me to do.
		 *
		 * At this point we have checked if there are any tables with the mixed case prefix, the all lowercase prefix,
		 * and the uppercase prefix. None was found in any of these cases.
		 *
		 * I will not change the prefix to back up. However, if there are tables with the wrong mixed case format of the
		 * prefix I will have to issue a warning.
		 */
		if (!$hasLowercaseTables && !$hasUppercaseTables)
		{
			if ($hasWrongMixedCaseTables)
			{
				Factory::getLog()->warning($warningMessage);
			}

			return;
		}

		if (
			($isLowerCasePrefix && !$hasLowercaseTables && $hasUppercaseTables)
			|| ($isMixedCasePrefix && $hasUppercaseTables)
		)
		{
			Factory::getLog()->info(
				sprintf(
					'Auto-fixing the database prefix: you have configured the database table name prefix %s but we could not find any tables with this prefix. Instead, we found tables with the %s prefix; using that instead.',
					$this->prefix, $allUpperCasePrefix
				)
			);

			$this->_parametersArray['prefix'] = $allUpperCasePrefix;
			$this->prefix                     = $allUpperCasePrefix;
		}
		elseif (
			($isUpperCasePrefix && !$hasUppercaseTables && $hasLowercaseTables)
			|| ($isMixedCasePrefix && $hasLowercaseTables)
		)
		{
			Factory::getLog()->info(
				sprintf(
					'Auto-fixing the database prefix: you have configured the database table name prefix %s but we could not find any tables with this prefix. Instead, we found tables with the %s prefix; using that instead.',
					$this->prefix, $allLowerCasePrefix
				)
			);

			$this->_parametersArray['prefix'] = $allLowerCasePrefix;
			$this->prefix                     = $allLowerCasePrefix;
		}
		else
		{
			Factory::getLog()->warning(
				sprintf(
					'WRONG DATABASE PREFIX. You have configured the database table name prefix %s but we could not find any tables with this prefix, its lowercase (%s) or uppercase (%s) form. THIS WILL CAUSE RESTORATION PROBLEMS. Please rename your tables starting with different forms of the %1$s prefix so that they all start with its lowercase form (%2$s), then retake the backup.',
					$this->prefix, $allLowerCasePrefix, $allUpperCasePrefix
				)
			);

			return;
		}

		if ($hasLowercaseTables && $hasUppercaseTables)
		{
			Factory::getLog()->warning($warningMessage);
		}
	}
}
