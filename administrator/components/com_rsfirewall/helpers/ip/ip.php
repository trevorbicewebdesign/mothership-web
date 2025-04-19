<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Utilities\IpHelper;
use Joomla\CMS\Language\Text;

require_once __DIR__ . '/protocols/base.php';
require_once __DIR__ . '/protocols/interface.php';
require_once __DIR__ . '/protocols/v4.php';
require_once __DIR__ . '/protocols/v6.php';

class RSFirewallIP
{	
	// Holds the class that's used to perform operations on the current IP.
	protected $protocol;
	
	// Holds the version of the protocol.
	protected $version;
	
	public function __construct($ip) {		
		// Determine protocol
		$this->protocol = $this->getProtocol($ip);
	}
	
	// Determines protocol version to use.
	protected function getProtocol($ip) {
		$protocols = array(4, 6);

		foreach ($protocols as $version) {
			$class = 'RSFirewallIPv'.$version;
			if (call_user_func(array($class, 'test'), $ip)) {
				$this->version = $version;
				return new $class($ip);
			}
		}
		
		throw new Exception(Text::sprintf('COM_RSFIREWALL_PROTOCOL_ERROR', $ip));
	}
	
	// Allows accessing otherwise protected variables.
	public function __get($var) {
		switch ($var)
		{
			case 'protocol':
			case 'version':
				return $this->{$var};
			break;
			
			default:
				return null;
			break;
		}
	}
	
	// Allows accessing methods from protocol
	public function __call($name, $args) {
		$callback = array($this->protocol, $name);
		if (is_callable($callback)) {
			return call_user_func_array($callback, $args);
		}
		
		throw new Exception(Text::sprintf('COM_RSFIREWALL_PROTOCOL_METHOD_NOT_SUPPORTED', $name, get_class($this->protocol)));
	}
	
	// Determines if current IP is in specified range
	public function match($range) {
		if (strpos($range, '-') !== false) {
			// We have an IP range (eg. 192.168.1.1 - 192.168.1.255)
			
			// Get starting and ending IPs
			@list($from, $to) = explode('-', $range, 2);
			
			// Clean them up a bit
			$from 	= trim($from);
			$to		= trim($to);
			
			// No starting IP?
			if (empty($from) || !strlen($from)) {
				throw new Exception(Text::_('COM_RSFIREWALL_NO_STARTING_IP'));
			}
			
			// No ending IP?
			if (empty($to) || !strlen($to)) {
				throw new Exception(Text::_('COM_RSFIREWALL_NO_ENDING_IP'));
			}
			
			// Check if protocol versions match.
			$fromIP = new RSFirewallIP($from);
			if ($fromIP->version != $this->version) {
				throw new Exception(Text::sprintf('COM_RSFIREWALL_STARTING_IP_PROTOCOL_MISMATCH', $this->version));
			}
			
			$toIP = new RSFirewallIP($to);
			if ($toIP->version != $this->version) {
				throw new Exception(Text::sprintf('COM_RSFIREWALL_ENDING_IP_PROTOCOL_MISMATCH', $this->version));
			}
			
			$ip 	= $this->toComparable();
			$from 	= $fromIP->toComparable();
			$to		= $toIP->toComparable();
			
			return $ip >= $from && $ip <= $to;
		} elseif (strpos($range, '*') !== false) {
			// We have a wildcard notation (eg. 192.168.1.*)
			if ($this->version == 4) {
				// Wildcard notation only works on IPv4
				$haystack = explode('.', $range, 4);
				$needle   = explode('.', $this->toAddress(), 4);
				
				foreach ($haystack as $i => $fragment) {
					if ($fragment != '*' && $fragment != $needle[$i]) {
						return false;
					}
				}
				
				return true;
			} elseif ($this->version == 6) {
				$range = preg_quote($range);
				$range = str_replace('\*', '(.*?)', $range);
				if (preg_match('/' . $range . '/', $this->toAddress()))
				{
					return true;
				}
			}
			
			return false;
		} elseif (strpos($range, '/') !== false) {
			// We have a CIDR notation (eg. 192.168.1.0/24)
			
			list($network, $mask) = explode('/', $range, 2);
			
			// Clean them up a bit
			$network 	= trim($network);
			$mask		= trim($mask);
			
			// Check if protocol versions match.
			$networkIP = new RSFirewallIP($network);
			if ($networkIP->version != $this->version) {
				throw new Exception(Text::sprintf('COM_RSFIREWALL_NETWORK_PROTOCOL_MISMATCH', $this->version));
			}

			// Check if mask bits match on both addresses.
			return $this->matchIp($this->toAddress(), $range);
		}
		
		// None of the above - single IP mode.
		return $this->toAddress() === $range;
	}

	public function matchIp($ip, $cidr)
	{
		// Get mask bits
		list($net, $maskBits) = explode('/', $cidr);

		// Size
		$size = strpos($ip, ':') === false ? 4 : 16;

		// Convert to binary
		$ip = inet_pton($ip);
		$net = inet_pton($net);
		if (!$ip)
		{
			throw new Exception(Text::sprintf('COM_RSFIREWALL_COULD_NOT_UNPACK_IP', $ip));
		}
		if (!$net)
		{
			throw new Exception(Text::sprintf('COM_RSFIREWALL_COULD_NOT_UNPACK_IP', $net));
		}

		// Build mask
		$solid = floor($maskBits / 8);
		$solidBits = $solid * 8;
		$mask = str_repeat(chr(255), $solid);
		for ($i = $solidBits; $i < $maskBits; $i += 8) {
			$bits = max(0, min(8, $maskBits - $i));
			$mask .= chr((pow(2, $bits) - 1) << (8 - $bits));
		}
		$mask = str_pad($mask, $size, chr(0));

		// Compare the mask
		return ($ip & $mask) === ($net & $mask);
	}
	
	// the old class
	public static function get($check_for_proxy=true) {
		static $ip;
		
		if (!$ip) {
			$input = Factory::getApplication()->input->server;
			$ip    = IpHelper::getIp();

			if (strpos($ip, ',') !== false) {
				$tmp = explode(',', $ip);
				// grab the last IP (should be the one actual connecting)
				$ip = trim(end($tmp));
				// no longer need this
				unset($tmp);
			}

			if ($check_for_proxy && !RSFirewallConfig::getInstance()->get('use_joomla_ip')) {
				// Proxy headers
				$headers = RSFirewallConfig::getInstance()->get('check_proxy_ip_headers');
				
				// IPv4 private addresses
				$ipv4ranges = array(
					'10.0.0.0/8', 		// 10.0.0.0 - 10.255.255.255
					'172.16.0.0/12',	// 172.16.0.0 - 172.31.255.255
					'192.168.0.0/16' 	// 192.168.0.0 - 192.168.255.255
				);

				if ($headers) {
					foreach ($headers as $header) {
						if (!strlen($header)) {
							continue;
						}
						if ($proxy = $input->get($header, '', 'string')) {
							// let's see if there are multiple IPs
							if (strpos($proxy, ',') !== false) {
								$tmp = explode(', ', $proxy);
								// grab the first IP
								$proxy = reset($tmp);
								// no longer need this
								unset($tmp);
							}

							try {
								$class = new RSFirewallIP($proxy);

								// Must not grab private IPv4 addresses.
								if ($class->version == 4) {
									foreach ($ipv4ranges as $range) {
										if ($class->match($range)) {
											continue 2;
										}
									}
								}
							} catch (Exception $e) {
								// IP malformed, continue to next proxy header.
								continue;
							}

							$ip = $proxy;
							break;
						}
					}
				}
			}
		}
		return $ip;
	}
}