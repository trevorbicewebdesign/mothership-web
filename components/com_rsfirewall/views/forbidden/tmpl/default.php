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
<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title><?php echo Text::_('COM_RSFIREWALL_403_FORBIDDEN'); ?></title>
	<style>
		*{margin:0;box-sizing:border-box}body{padding-top:40px;padding-bottom:40px;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans",sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji"}.text-center{text-align:center}.container{max-width:1140px;width:100%;margin:15px auto}.alert{position:relative;padding:.75rem 1.25rem;margin-bottom:1rem;border:1px solid transparent;border-radius:.25rem}.alert-danger{color:#721c24;background-color:#f8d7da;border-color:#f5c6cb}h1{margin-bottom:.5rem;font-family:inherit;font-weight:500;font-size:1.5rem;line-height:1.2;color:inherit}
	</style>
</head>
<body>
	<div class="container">
		<div class="alert alert-danger text-center" role="alert">
			<h1><?php echo Text::_('COM_RSFIREWALL_403_FORBIDDEN'); ?></h1>
			<?php echo $this->reason; ?>
		</div>
	</div>
</body>
</html>