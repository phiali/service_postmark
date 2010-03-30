<?php
/**
 * @see Zend_Service_Abstract
 */
require_once 'Zend/Service/Abstract.php';

/**
 * Postmark service implementation
 *
 * @uses       Zend_Service_Abstract
 * @copyright  Copyright (c) 2010 Alistair Phillips (http://the.0gravity.co.uk/universe/zend/service/postmark)
 */
class Service_Postmark extends Zend_Service_Abstract
{
    const FILTER_BOUNCE_HARDBOUNCE            = 'HardBounce';
    const FILTER_BOUNCE_TRANSIENT             = 'Transient';
    const FILTER_BOUNCE_UNSUBSCRIBE           = 'Unsubscribe';
    const FILTER_BOUNCE_SUBSCRIBE             = 'Subscribe';
    const FILTER_BOUNCE_AUTORESPONDER         = 'AutoResponder';
    const FILTER_BOUNCE_ADDRESSCHANGE         = 'AddressChange';
    const FILTER_BOUNCE_DNSERROR              = 'DnsError';
    const FILTER_BOUNCE_SPAMNOTIFICATION      = 'SpamNotification';
    const FILTER_BOUNCE_OPENRELAYTEST         = 'OpenRelayTest';
    const FILTER_BOUNCE_UNKNOWN               = 'Unknown';
    const FILTER_BOUNCE_SOFTBOUNCE            = 'SoftBounce';
    const FILTER_BOUNCE_VIRUSNOTIFICATION     = 'VirusNotification';
    const FILTER_BOUNCE_CHALLENGEVERIFICATION = 'ChallengeVerification';
    const FILTER_BOUNCE_BADEMAILADDRESS       = 'BadEmailAddress';
    const FILTER_BOUNCE_SPAMCOMPLAINT         = 'SpamComplaint';
    const FILTER_BOUNCE_MANUALLYDEACTIVATED   = 'ManuallyDeactivated';
    const FILTER_BOUNCE_UNCONFIRMED           = 'Unconfirmed';
    const FILTER_BOUNCE_BLOCKED               = 'Blocked';

    const FILTER_BOUNCE_ACTIVE   = 'true';
    const FILTER_BOUNCE_INACTIVE = 'false';
    const FILTER_BOUNCE_NA       = null;
    
    /**
     * Postmark server token
     * @var string
     */
    protected $_serverToken;
    
    /**
     * Constructor
     *
     * @param string $serverToken Postmark server token
     * @return void
     */
    public function __construct($serverToken)
    {
        $this->setServerToken($serverToken);
    }
    
    /**
     * Set server token
     *
     * @param string $serverToken
     * @return Service_Postmark
     */
    public function setServerToken($serverToken)
    {
        $this->_serverToken = $serverToken;
        return $this;
    }
    
    /**
     * Perform a HTTP GET request
     *
     * @param string $path
     * @param array  $params
     * @return mixed
     */
    protected function _get($path, array $params)
    {
        // Ensure that we are sending through an server token
        if (empty($this->_serverToken)) {
            require_once 'Zend/Service/Exception.php';
            throw new Zend_Service_Exception('Server token is required and must be set');
        }
        
        $uri = 'http://api.postmarkapp.com' . $path;
        $client = self::getHttpClient();
        $client->setUri($uri);
        $client->setHeaders(array(
            'Accept' => 'application/json',
            'X-Postmark-Server-Token' => $this->_serverToken
        ));
        $client->setParameterGet($params);
        $client->setMethod(Zend_Http_Client::GET);
        
        return $client->request();
    }
    
    /**
     * Perform a HTTP PUT
     *
     * @param string $path
     * @param array  $params
     * @return mixed
     */
    protected function _put($path, array $params)
    {
        // Ensure that we are sending through an server token
        if (empty($this->_serverToken)) {
            require_once 'Zend/Service/Exception.php';
            throw new Zend_Service_Exception('Server token is required and must be set');
        }
        
        $uri = 'http://api.postmarkapp.com' . $path;
        $client = self::getHttpClient();
        $client->setUri($uri);
        $client->setHeaders(array(
            'Accept' => 'application/json',
            'X-Postmark-Server-Token' => $this->_serverToken
        ));
        $client->setParameterGet($params);
        $client->setMethod(Zend_Http_Client::PUT);
        
        return $client->request();
    }
    
    /**
     * Check status code and if appropriate return the decoded JSON
     *
     * @param Zend_Http_Response $response
     * @return array
     * @throws Zend_Service_Exception
     */
    protected function _processResult( Zend_Http_Response $response )
    {
        if ($response->getStatus() != 200) {
            require_once 'Zend/Service/Exception.php';
            throw new Zend_Service_Exception('Postmark returned ' . $response->getStatus() . ' - ' . $response->getMessage());
        }
        
        return json_decode($response->getBody());
    }
    
    /**
     * Return a summary of inactive emails and bounces by type
     *
     * @return mixed
     */
    public function getDeliveryStats()
    {
        $response = $this->_get('/deliverystats', array());
        if ($response->getStatus() != 200) {
            require_once 'Zend/Service/Exception.php';
            throw new Zend_Service_Exception('Postmark returned ' . $response->getStatus() . ' - ' . $response->getMessage());
        }
        
        return $this->_processResult( $response );
    }
    
    /**
     * Fetches a portion of the bounces according to the parameters
     *
     * @param const  $type
     * @param const  $inactive
     * @param string $emailFilter
     * @param string $tag
     * @param int    $count
     * @param int    $offset
     * @return mixed
     */
    public function getBounces($type, $inactive, $emailFilter, $tag, $count, $offset)
    {
        if (!isset($type))                           $type = self::FILTER_BOUNCE_HARDBOUNCE;
        if (!isset($inactive))                       $inactive = self::FILTER_BOUNCE_NA;
        if (!isset($emailFilter))                    $emailFilter = '';
        if (!isset($tag))                            $tag = '';
        if (!isset($count)  || !is_numeric($count))  $count = 25;
        if (!isset($offset) || !is_numeric($offset)) $offset = 0;
        
        $params = array(
            'type'        => $type,
            'inactive'    => $inactive,
            'emailFilter' => $emailFilter,
            'tag'         => $tag,
            'count'       => $count,
            'offset'      => $offset
        );
        
        $response = $this->_get('/bounces', $params);
        return $this->_processResult( $response );
    }
    
    /**
     * Return details about a single bounce, optionally including the raw result
     *
     * @return mixed
     */
    public function getBounce($bounceId, $raw = false)
    {
        if (!is_numeric($bounceId)) {
            require_once 'Zend/Service/Exception.php';
            throw new Zend_Service_Exception('bounce_id must be numeric');
        }
        
        if (!is_bool( $raw ) ) $raw = false;
        
        $path = '/bounces/' . $bounceId;
        if ($raw) {
            $path.='/dump';
        }
        
        $response = $this->_get($path, array());
        return $this->_processResult( $response );
    }
    
    /**
     * Returns a list of tags used for the server
     *
     * @return mixed
     */
    public function getBounceTags()
    {
        $response = $this->_get('/bounces/tags', array());
        return $this->_processResult( $response );
    }
    
    /**
     * Activates a deactivated bounce
     *
     * @param int $bounceId
     * @return mixed
     */
    public function activateBounce($bounceId)
    {
        if (!is_numeric($bounceId)) {
            require_once 'Zend/Service/Exception.php';
            throw new Zend_Service_Exception('bounce_id must be numeric');
        }
        
        $path = '/bounces/' . $bounceId . '/activate';
        
        $response = $this->_put($path, array());
        return $this->_processResult( $response );
    }
}