<?php
/*
 * @package 	RSFirewall!
 * @copyright 	(c) 2009 - 2024 RSJoomla!
 * @link 		https://www.rsjoomla.com/joomla-extensions/joomla-security.html
 * @license 	GNU General Public License https://www.gnu.org/licenses/gpl-3.0.en.html
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class RSFirewallGoogleWebRisk
{
    /**
     * @var string
     */
    private $api;
    /**
     * @var string
     */
    protected $url;

    /**
     * @var array
     */
    protected $threatsTypes = array('MALWARE', 'SOCIAL_ENGINEERING', 'UNWANTED_SOFTWARE');

    /**
     * RSFirewallGoogleWebRisk constructor.
     */
    public function __construct()
    {
        $config = RSFirewallConfig::getInstance();
        $api    = $config->get('google_webrisk_api_key');

        $this->api = trim($api);
        $this->url = $this->buildUrl();
    }

    /**
     * @return string
     */
    public function buildUrl()
    {
        $url = urlencode(Uri::root());

        return 'https://webrisk.googleapis.com/v1beta1/uris:search?key=' . $this->api.'&uri='.$url;
    }

    /**
     * @return RSFirewallGoogleWebRisk
     */
    public static function getInstance()
    {
        static $inst;
        if (!$inst)
        {
            $inst = new RSFirewallGoogleWebRisk;
        }

        return $inst;
    }

    /**
     * @return mixed
     */
    public static function getGoogleResponse($threat = '')
    {
        $gwr = RSFirewallGoogleWebRisk::getInstance();

        $headers = array(
            'Content-Type' => 'application/json'
        );

        try
        {
            $http    = HttpFactory::getHttp();
            $request = $http->get(
                $gwr->url.'&threatTypes='.$threat,
                $headers
            );

            return $request;
        }
        catch (Exception $e)
        {
            // Dummy response in case something went wrong
            return (object) array(
                'code' => 9999,
                'body' => json_encode(array('error' => array('message' => $e->getMessage())))
            );
        }
    }

    /**
     * @return array
     */
    public function check()
    {
        if (empty($this->api))
        {
            return array(
                'success' => true,
                'result'  => false,
                'message' => Text::_('COM_RSFIREWALL_GOOGLE_WEB_RISK_NO_API_KEY'),
                'details' => Text::_('COM_RSFIREWALL_GOOGLE_WEB_RISK_STEP_SKIPPED')
            );
        }

        $cache = Factory::getCache('com_rsfirewall');
        $cache->setCaching(true);

        $responses = array();
        foreach ($this->threatsTypes as $threat) {
            $request = $cache->get(array('RSFirewallGoogleWebRisk', 'getGoogleResponse'), array($threat));
            $responses[] = $this->parseRequest($request, $threat);
        }

        $summary = array(
            'success' => true,
            'result'  => true,
            'message' => array(),
            'details' => array()
        );

        foreach ($responses as $response)
        {
            // if there is an error with the request
            if (!$response['success'])
            {
                $summary['success'] = false;
            }

            // if there is any false results will consider an overall problem
            if (!$response['result'])
            {
                $summary['result'] = false;
            }

            $summary['message'][] = $response['message'];

            if (!empty($response['details']))
            {
                $summary['details'][] = $response['details'];
            }
        }

        // handle the empty messages and details, make a single string from all the messages
        if (!empty($summary['message']))
        {
	        $summary['message'] = implode('<br/>', array_unique($summary['message']));
        }
        else {
            $summary['message'] = '';
        }

        if (!empty($summary['details']))
        {
	        $summary['details'] = implode('<br/>', array_unique($summary['details']));
        }
        else {
            $summary['details'] = '';
        }

        return $summary;
    }

    /**
     * @return array
     */
    public function parseRequest($request, $threat){
        $body = @json_decode($request->body);

        $threat = str_replace('_', ' ', $threat);
        $threat = strtolower($threat);
        $threat = ucwords($threat);

        switch ($request->code)
        {
            case 200:
                $body = (array) $body;
                if (empty($body))
                {
                    return array(
                        'success' => true,
                        'result'  => true,
                        'message' => Text::sprintf('COM_RSFIREWALL_GOOGLE_WEB_RISK_VALID', $threat),
                        'details' => ''
                    );
                }

                $reason = '';
                foreach ($body['threat'] as $match)
                {
                    $threats = implode(', '. $match->threatTypes);
                    $reason .= $threats . ' ';
                }

                return array(
                    'success' => true,
                    'result'  => false,
                    'message' => Text::sprintf('COM_RSFIREWALL_GOOGLE_WEB_RISK_INVALID', $reason),
                    'details' => Text::_('COM_RSFIREWALL_GOOGLE_WEB_RISK_INVALID_DETAILS')
                );
                break;
            case 400:
                return array(
                    'success' => true,
                    'result'  => false,
                    'message' => isset($body->error->message) ? $body->error->message : Text::sprintf('COM_RSFIREWALL_GOOGLE_WEB_RISK_BAD_REQUEST', $threat),
                    'details' => ''
                );
                break;
            case 403:
                return array(
                    'success' => true,
                    'result'  => false,
                    'message' => isset($body->error->message) ? $body->error->message : Text::_('COM_RSFIREWALL_GOOGLE_WEB_RISK_BAD_API_KEY'),
                    'details' => Text::_('COM_RSFIREWALL_GOOGLE_SAFE_BROWSER_HOW_TO_GET_KEY')
                );
                break;
            case 500:
                return array(
                    'success' => true,
                    'result'  => false,
                    'message' => isset($body->error->message) ? $body->error->message : Text::_('COM_RSFIREWALL_GOOGLE_WEB_RISK_INTERNAL_SERVER_ERROR'),
                    'details' => ''
                );
                break;
            case 503:
                return array(
                    'success' => true,
                    'result'  => false,
                    'message' => isset($body->error->message) ? $body->error->message : Text::_('COM_RSFIREWALL_GOOGLE_WEB_RISK_SERVICE_UNAVAILABLE'),
                    'details' => ''
                );
                break;
            case 504:
                return array(
                    'success' => true,
                    'result'  => false,
                    'message' => isset($body->error->message) ? $body->error->message : Text::_('COM_RSFIREWALL_GOOGLE_WEB_RISK_TIMEOUT'),
                    'details' => ''
                );
                break;
            default:
                return array(
                    'success' => false,
                    'result'  => false,
                    'message' => isset($body->error->message) ? $body->error->message : Text::_('COM_RSIFREWALL_SOMETHING_WENT_WRONG'),
                    'details' => ''
                );
                break;
        }
    }
}