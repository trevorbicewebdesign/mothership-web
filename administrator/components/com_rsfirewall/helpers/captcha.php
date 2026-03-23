<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;

class RSFirewallCaptcha
{
	protected $code = null;
	
	protected function getFont()
	{
		return JPATH_ADMINISTRATOR.'/components/com_rsfirewall/assets/fonts/monofont.ttf';
	}
	
	protected function generateCode($chars = 4)
	{
		$possible = 'aAbBcCdDeEfFgGhHjJkKLmMnNpPqQrRstTvVwWxXyYzZ2346789';
		$count = strlen($possible) - 1;
		$this->code = '';

		for ($i = 0; $i < $chars; $i++)
		{
			$this->code .= substr($possible, mt_rand(0, $count), 1);
		}

		Factory::getSession()->set('com_rsfirewall.backend_captcha', $this->code);

		return $this->code;
	}
	
	public function getImage($chars = 4)
	{
		try
		{
			if (!function_exists('imagecreate'))
			{
				throw new Exception('imagecreate() not available.');
			}
			if (!function_exists('imagettfbbox'))
			{
				throw new Exception('imagettfbbox() not available.');
			}

			$code = $this->generateCode($chars);
			$font = $this->getFont();

			$font_size = RSFirewallConfig::getInstance()->get('backend_captcha_font_size', 32);

			if ($font_size < 24)
			{
				$font_size = 24;
			}

			$box = imagettfbbox($font_size, 0, $font, 'M');

			$char_w = abs($box[4] - $box[0]);
			$char_h = abs($box[5] - $box[1]);

			$width = $char_w * 5;
			$height = $char_h * 2;

			// Create the image
			$image = imagecreate($width, $height);

			// Get a random text color
			$r = mt_rand(0, 255);
			$g = mt_rand(0, 255);
			$b = mt_rand(0, 255);

			// Fill the background with a complementary color
			imagecolorallocate($image, ($r < 128) ? 255 : 0, ($g < 128) ? 255 : 0, ($b < 128) ? 255 : 0);

			// Allocate text color
			$color = imagecolorallocate($image, $r, $g, $b);

			// Get a random angle
			$angle = mt_rand( 0, 5 ) * (mt_rand(0, 1) == 1 ? -1 : 1);

			// Get the box size
			$text_box_size = imagettfbbox($font_size, $angle, $font, $code);

			// Calculate position
			$x = floor(($width - $text_box_size[4]) / 2);
			$y = floor(($height - $text_box_size[5]) / 2);

			// Set a shadow
			$shadow_color = imagecolorallocate($image, floor($r / 2), floor($g / 2), floor($b / 2));
			imagettftext($image, $font_size, $angle, $x - 1, $y + 3, $shadow_color, $font, $code);

			// Write our text
			imagettftext($image, $font_size, $angle, $x, $y, $color, $font, $code);

			ob_start();

			imagejpeg($image);
			imagedestroy($image);

			$data = ob_get_contents();

			ob_end_clean();

			return base64_encode($data);
		}
		catch (Exception $e)
		{
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
		}

		return false;
	}
}