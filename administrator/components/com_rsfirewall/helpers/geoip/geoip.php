<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */
\defined('_JEXEC') or die;

class RSFirewallGeoIP
{
	protected $handle;
	protected $codes = array();
	protected $flags = array();

	public function __construct()
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_rsfirewall/helpers/geolite2/geolite2.php';

		$this->handle = RSFirewallGeolite2::getInstance();
	}

	public static function getInstance()
	{
		static $inst;
		if (!$inst)
		{
			$inst = new RSFirewallGeoIP();
		}

		return $inst;
	}

	public function getCountryCode($ip)
	{
		if (!isset($this->codes[$ip]))
		{
			$this->codes[$ip] = '';
			if ($this->handle)
			{
                $this->codes[$ip] = $this->handle->getCountryCode($ip);
			}
		}

		return $this->codes[$ip];
	}

	public function getCountryFlag($ip)
	{
		$code = $this->getCountryCode($ip);

		if (!isset($this->flags[$code]))
		{
			if (!empty($code) && file_exists(JPATH_SITE . '/media/com_rsfirewall/images/flags/' . strtolower($code) . '.png'))
			{
				$this->flags[$code] = strtolower($code) . '.png';
			}
			else
			{
				$this->flags[$code] = 'generic.png';
			}
		}

		return $this->flags[$code];
	}

	public function show($ip, $usePlaceholder = true)
	{
		static $placeholders = array();
		if (empty($placeholders))
		{
			// Load the config to get our variables
			$config               = RSFirewallConfig::getInstance();
			$placeholders['ipv4'] = $config->get('ipv4_whois');
			$placeholders['ipv6'] = $config->get('ipv6_whois');

			// Also require our IP class
			require_once JPATH_ADMINISTRATOR . '/components/com_rsfirewall/helpers/ip/ip.php';
		}

		$placeholder = '';
		if (RSFirewallIPv4::test($ip))
		{
			$placeholder = $placeholders['ipv4'];
		}
		elseif (RSFirewallIPv6::test($ip))
		{
			$placeholder = $placeholders['ipv6'];
		}

		if ($usePlaceholder && $placeholder)
		{
			$link = str_ireplace('{ip}', $ip, $placeholder);

			return '<a target="_blank" href="' . htmlspecialchars($link, ENT_COMPAT, 'utf-8') . '" class="rsf-ip-address">' . htmlentities($ip, ENT_COMPAT, 'utf-8') . '</a>';
		}

		return '<span class="rsf-ip-address">' . htmlentities($ip, ENT_COMPAT, 'utf-8') . '</span>';
	}
}