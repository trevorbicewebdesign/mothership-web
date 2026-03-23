<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
?>
<h1><?php echo $this->escape($this->filename); ?></h1>
<?php if ($this->status) { ?>
	<h2><?php echo $this->escape($this->status['reason']); ?></h2>
<?php } ?>
<?php if ($this->time) { ?>
<p><?php echo Text::sprintf('COM_RSFIREWALL_FILE_HAS_BEEN_MODIFIED_AGO', $this->time); ?></p>
<?php } ?>

<pre class="com-rsfirewall-code"><?php
	if ($this->status) {
		$contents = str_replace($this->escape($this->status['match']), '<strong class="com-rsfirewall-level-high">'.$this->escape($this->status['match']).'</strong>', $this->escape($this->contents));
	} else {
		$contents = $this->escape($this->contents);
	}
	echo $contents;
?></pre>