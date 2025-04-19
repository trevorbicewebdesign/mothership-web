<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\Sender\Adapter;

use Awf\Container\Container;

/**
 * Information Sending adapter for Akeeba Backup for WordPress
 *
 * @since  1.0.0
 */
final class AkeebaBackupWordPressAdapter extends AbstractAwfAdapter
{
	/** @inheritDoc */
	protected function getContainer(): ?Container
	{
		global $akeebaBackupWordPressContainer;

		return $akeebaBackupWordPressContainer ?? null;
	}
}