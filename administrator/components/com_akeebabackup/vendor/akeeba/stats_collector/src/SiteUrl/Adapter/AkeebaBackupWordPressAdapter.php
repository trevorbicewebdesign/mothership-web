<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\SiteUrl\Adapter;

use Awf\Uri\Uri;
use Solo\Container;
use Throwable;

/**
 * Site URL adapter for Akeeba Backup for WordPress
 *
 * @since  1.0.0
 */
final class AkeebaBackupWordPressAdapter implements AdapterInterface
{

	/**
	 * @inheritDoc
	 */
	public function getUrl(): string
	{
		return $this->getUrlReal() ?? '';
	}

	/**
	 * @inheritDoc
	 */
	public function isAvailable(): bool
	{
		global $akeebaBackupWordPressContainer;

		return defined('WPINC')
		       && isset($akeebaBackupWordPressContainer)
		       && class_exists(Uri::class)
		       && class_exists(Container::class)
		       && !empty($this->getUrlReal());
	}

	/**
	 * Get the URL in the adapter-specific way
	 *
	 * @return  string|null  NULL if we cannot determine it
	 * @since   1.0.0
	 */
	private function getUrlReal(): ?string
	{
		/** @var Container $akeebaBackupWordPressContainer */
		global $akeebaBackupWordPressContainer;

		if (PHP_SAPI !== 'cli')
		{
			return Uri::base();
		}

		try
		{
			return $akeebaBackupWordPressContainer->application->getContainer()->appConfig->get('options.siteurl');
		}
		catch (Throwable $e)
		{
			return null;
		}
	}
}