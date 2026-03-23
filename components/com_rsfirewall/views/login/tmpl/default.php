<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title><?php echo Text::_('COM_RSFIREWALL_PROTECTED_AREA'); ?></title>
	<style>
		*{margin:0;box-sizing:border-box}body{padding-top:40px;padding-bottom:40px;background-color:#224c8f;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans",sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji"}.form-signin{max-width:370px;padding:19px 29px 29px;margin:0 auto 20px;background-color:#fff;border:1px solid #e5e5e5;-webkit-border-radius:5px;-moz-border-radius:5px;border-radius:5px;-webkit-box-shadow:0 1px 2px rgba(0,0,0,.05);-moz-box-shadow:0 1px 2px rgba(0,0,0,.05);box-shadow:0 1px 2px rgba(0,0,0,.05)}.form-signin input[type=password]{font-size:16px;height:auto;margin-bottom:15px;padding:7px 9px;text-align:center}.text-center{text-align:center}.container{max-width:1140px;width:100%;margin:15px auto}.alert{position:relative;padding:.75rem 1.25rem;margin-bottom:1rem;border:1px solid transparent;border-radius:.25rem}.alert-danger{color:#721c24;background-color:#f8d7da;border-color:#f5c6cb}h1{margin-bottom:.5rem;font-family:inherit;font-weight:500;font-size:1.5rem;line-height:1.2;color:inherit}.btn{display:inline-block;font-weight:400;color:#212529;text-align:center;vertical-align:middle;-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none;background-color:transparent;border:1px solid transparent;padding:.375rem .75rem;font-size:1rem;line-height:1.5;border-radius:.25rem;transition:color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;cursor:pointer}.btn-primary{color:#fff;background-color:#007bff;border-color:#007bff}.btn-primary:hover{color:#fff;background-color:#0069d9;border-color:#0062cc}.btn-primary.focus,.btn-primary:focus{color:#fff;background-color:#0069d9;border-color:#0062cc;box-shadow:0 0 0 .2rem rgba(38,143,255,.5)}.btn-lg{padding:.5rem 1rem;font-size:1.25rem;line-height:1.5;border-radius:.3rem}
    </style>
	<script type="text/javascript">
	document.addEventListener('DOMContentLoaded', function() {
		document.getElementsByName('rsf_backend_password')[0].focus();
	});
	</script>
</head>
<body>
	<div class="container">
		<?php if ($this->password_sent) { ?>
			<div class="alert alert-danger" role="alert">
				<h1><?php echo Text::_('COM_RSFIREWALL_ERROR'); ?></h1>
				<?php echo Text::_('COM_RSFIREWALL_PASSWORD_INCORRECT'); ?>
			</div>
		<?php } ?>
		<form method="post" action="index.php" class="form-signin text-center" autocomplete="off">
			<p><?php echo HTMLHelper::_('image', 'com_rsfirewall/icon-48-rsfirewall.png', 'RSFirewall!', array(), true); ?></p>
			<h1><?php echo Text::_('COM_RSFIREWALL_PLEASE_LOGIN_TO_CONTINUE'); ?></h1>
			<p>
				<input type="password" name="rsf_backend_password" placeholder="<?php echo $this->escape(Text::_('COM_RSFIREWALL_PASSWORD')); ?>" />
			</p>
			<p>
				<button class="btn btn-primary btn-lg" type="submit"><?php echo Text::_('COM_RSFIREWALL_LOGIN'); ?></button>
			</p>
		</form>
	</div>
</body>
</html>