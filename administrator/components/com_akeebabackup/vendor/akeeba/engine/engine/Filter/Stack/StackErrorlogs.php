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

namespace Akeeba\Engine\Filter\Stack;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Filter\Base;

/**
 * Files exclusion filter based on regular expressions
 */
class StackErrorlogs extends Base
{
	function __construct()
	{
		$this->object  = 'file';
		$this->subtype = 'all';
		$this->method  = 'api';

		if (empty($this->filter_name))
		{
			$this->filter_name = strtolower(basename(__FILE__, '.php'));
		}

		parent::__construct();
	}

	protected function is_excluded_by_api($test, $root)
	{
		// Is it an error log? Exclude the file.
		if (in_array(basename($test), [
			'php_error',
			'php_errorlog',
			'error_log',
			'error.log',
		]))
		{
			return true;
		}

		// No match? Just include the file!
		return false;
	}

}
