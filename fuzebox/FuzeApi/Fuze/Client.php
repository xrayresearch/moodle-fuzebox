<?php
/**
 * Fuze Partner API Client
 *
 * The API requires a valid Partner Key and the accompanying Encryption Key
 * To use the client, instantiate it with the endpoint url and the key pair.
 * To execute calls on behalf of users, the client needs to be signed in first --
 * do this by calling signin()
 *
 */

require_once 'Crypt.php';

/**
 * Fuze API Client
 *
 * After the client has been instantiated (see constructor doc for parameter
 * description,) calls to public methods will prepare the request and headers,
 * execute the call and either return the parsed json response as a stdClass
 * instance or throw a Fuze_Exception on errors. Concrete exceptions may be
 * instances of:
 *
 * Fuze_Crypt_Exception
 *      Thrown when encryption or decryption of data failed. May be thrown
 *      during creation of the client if invalid keys are supplied.
 *
 * Fuze_Client_Exception
 *      Encapsulates several client errors, see below:
 *
 * Fuze_Client_TransportException
 *      Thrown on cURL errors -- TCP or SSL -level errors, timeouts, etc.
 *
 * Fuze_Client_ServerException
 *      Thrown when the server replies with non-200 http code or invalid JSON
 *      payload
 *
 * Fuze_Client_FaultException
 *      Thrown when the server replies with a server-error code. Contains the
 *      parsed JSON payload, the actual code and error message.
 *
 * Note that besides the above, an API call may still have failed. Users of the
 * client should check for the expected response code and assert it is between
 * 200 and 299 before continuing further. Each API method may define specific
 * codes for various conditions and outcomes. Codes between 400 and 499 signify
 * "client" errors, i.e. wrong parameters, auth restrictions, etc. Codes between
 * 500 and 599 signify server errors and will cause a Fuze_Client_FaultException
 * to be thrown.
 */
class Fuze_Client
{
    /**
     * Base URL for accessing the Fuze partner API
     * @var unknown_type
     */
    const BASE_URL = '/partners/v1';

    /**
     * Partner Key
     *
     * @var     string
     */
    protected $_pk;

    /**
     * Encryption key
     *
     * @var     string
     */
    protected $_ek;

    /**
     * @var     Fuze_Crypt
     */
    protected $_crypt;

    /**
     * API endpoint
     *
     * @var     string
     */
    protected $_url;

    /**
     * When signed in as user, this contains '<sessionToken>:<partnerKey>'
     * in encrypted form, and is set as X-Token header for API calls when present
     *
     * @var     string
     */
    protected $_userToken;

    /**
     * Raw session token
     */
    protected $_sessionToken;

    /**
     * @param string $fuzeHost The schema://hostname for the Fuze backend,
     *                         ex. 'https://partnerdev.fuzemeeting.com'
     * @param string $partnerKey The Partner Key, used for identification
     * @param string $encryptionKey The private key provided upon partner signup
     * @param string $token
     */
    public function __construct($fuzeHost, $partnerKey, $encryptionKey, $token = null)
    {
        $this->_pk = $partnerKey;
        $this->_ek = $encryptionKey;
        $this->_crypt = new Fuze_Crypt($encryptionKey);
        $this->_url = rtrim($fuzeHost, '/') . self::BASE_URL;
        if ($token) {
            $this->setUserSession($token);
        }
    }

    /**
     * Utility function to encode strings for the Partner API.
     *
     * The string passed in will be appended ':<partner key>' and then
     * encrypted using the encryption key.
     *
     * @param string $secret
     * @return string encoded '$secret:<partner key>'
     */
    protected function _encodeParam($secret)
    {
        $secret = "{$secret}:{$this->_pk}";
        return $this->_crypt->encrypt($secret);
    }

    /**
     * OK codes are considered those between 200 and 299
     *
     * @param int $code
     * @return bool
     */
    protected function _isCodeOk($code)
    {
        return ($code >= 200 && $code < 300);
    }

    protected function _getFuzeHttpHeaders()
    {
        $headers = array('X-Partnerkey: ' . $this->_pk);
        if ($this->_userToken) {
            $headers[] = 'X-Token: ' . $this->_userToken;
        }
        return $headers;
    }

    protected function _getCurlHandle($url)
    {
        $ch = curl_init($url);

        // Set default cURL (HTTP) options
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_POST, 1);

        // Prepare and set the authentication header(s)
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->_getFuzeHttpHeaders());

        return $ch;
    }

    protected function _executeCurl($ch)
    {
        // Fire!
        $result = curl_exec($ch);
        if (false === $result) {
            $errno = curl_errno($ch);
            $error = curl_error($ch);
            $url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            $errorMessage = "cURL: {$errno} - {$error}; URL: {$url}";
            curl_close($ch);
            throw new Fuze_Client_TransportException($errorMessage);
        }

        $info = curl_getinfo($ch);
        curl_close($ch);

        if (empty($result) || !$info['http_code'] == 200) {
            throw new Fuze_Client_ServerException(
                "Empty response or bad http code", $result, $info['http_code']);
        }

        return $result;
    }

    /**
     * Execute the HTTP request for an API call
     *
     * This method will:
     *  * create a cURL handle, sets its options
     *  * format and set the query string from the supplied $params
     *  * add a X-Partnerkey header with the partner key of this object
     *  * if a user session is present, add a X-Token header
     *
     * @throws  Fuze_Client_Exception
     *
     * @param string $method    The method signature, eg. 'meeting/get'
     * @param array  $params    Associative array with request parameters
     * @return stdClass         The parsed JSON response
     */
    public function call($method, array $params)
    {
        // Add the format parameter, only 'json' is supported at the moment
        if (!array_key_exists('format', $params)) {
            $params['format'] = 'json';
        }

        $url = "{$this->_url}/{$method}";
        $ch = $this->_getCurlHandle($url);

        if (!$ch) {
            throw new Fuze_Client_Exception("Unable to create a cURL handle");
        }

        // Set the request parameters
        $queryString = http_build_query($params);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $queryString);

        // Fire!
        $result = $this->_executeCurl($ch);
        // All API response payloads should be valid json with 'code' and
        // 'message' members
        $json = json_decode($result);
        if ( !($json instanceof stdClass)
                || !isset($json->code)
                || !isset($json->message) ) {

            throw new Fuze_Client_ServerException(
                "Invalid JSON payload received", $result, $info['http_code']);
        }

        if ( $json->code >= 500 && $json->code < 600) {
            throw new Fuze_Client_FaultException($json);
        }

        return $json;
    }

    /**
     * Verify the validity of PK/EK
     *
     * @return bool
     */
    public function checkKeys()
    {
        $secret = $this->_encodeParam($this->_ek);
        $result = $this->call('partner/checkkeys', array(
            'secret' => $secret
        ));
        if (!$result || !$this->_isCodeOk($result->code)) {
            return false;
        }
        return true;
    }

    /**
     * Gets a user session required by other calls
     *
     * On success will make the userToken parameter available for future
     * requests. Call signout() to clear, isSignedIn() to test.
     *
     * @param array $params Required keys are
     *      email
     *      password
     *
     * @return stdClass
     */
    public function signin(array $params)
    {
        $params['password'] = $this->_encodeParam($params['password']);
        $result = $this->call('user/signin', $params);
        if ($result && isset($result->token)) {
            $token = $this->_crypt->decrypt($result->token);
            $token = $this->_encodeParam($token);
            $this->setUserSession($token);
        }

        return $result;
    }

    /**
     * Gets a URL which allows the user to upgrade their account
     *
     * @param array $params Required keys are email and password, as for signin()
     *
     * @return string The URL pointing to the upgrade page
     */
    public function getUpgradeUrl(array $params) {
        $params['password'] = $this->_encodeParam($params['password']);
        $result = $this->call('user/getupgradeurl', $params);
        if ($result && $this->_isCodeOk($result->code)) {
            return $result->upgrade_url;
        }
        return $this->_url + '/plans/list';
    }

    /**
     * Returns the user token previously acquired with signin()
     *
     * @return string encrypted token:partner_key
     */
    public function getUserSession()
    {
        if (!$this->_userToken) {
            throw new Fuze_Exception('getUserSession() only works when signed in');
        }
        return $this->_userToken;
    }

    public function getRawSessionToken()
    {
        if (!$this->_sessionToken) {
            throw new Fuze_Exception('This only works when signed in!');
        }
        return $this->_sessionToken;
    }

    /**
     * Use a token previously returned by getUserSession(). This allows a client
     * instance to be used for calling all methods without signing in first.
     * Note, that user sessions expire in 15 minutes.
     *
     * @param string $token as returned by getUserSession
     */
    public function setUserSession($token)
    {
        if ($this->_userToken) {
            $this->signout();
        }

        $decrypted = $this->_crypt->decrypt($token);
        if ( substr($decrypted, -strlen($this->_pk) - 1) == ":{$this->_pk}" ) {
            $this->_userToken = $token;
            $this->_sessionToken = substr($decrypted, 0, strlen($decrypted) - strlen($this->_pk) - 1);
        } else {
            throw new Fuze_Exception("Attempted to set an invalid token");
        }
    }

    /**
     * Clears the user session if such exists
     *
     * @return void
     */
    public function signout()
    {
        $this->_userToken = null;
        $this->_sessionToken = null;
    }

    /**
     * Check if the client has a user token. Note: it's existance does not
     * guarantee it's valid!
     *
     * @return bool
     */
    public function isSignedIn()
    {
        return $this->_userToken !== null;
    }

    /**
     * Creates new user account on behalf of a partner
     *
     * @param array $params
     *      firstname
     *      lastname
     *      password    The password, will be encrypted internally
     *      email       Used in signin()
     *      phone       (optional)
     *      packages    (optional) Currently ignored, should be
     *
     * @return stdClass
     */
    public function signup(array $params)
    {
        $params['password'] = $this->_encodeParam($params['password']);
        return $this->call('user/signup', $params);
    }

    /**
     * Returns a list of packages available for user subscriptions
     *
     * @return stdClass
     */
    public function getPackages()
    {
        return $this->call('user/getpackages', array());
    }

    /**
     * Subscribe a user for a set of packages
     *
     * @param array $packages Packages (as strings) to subscribe the user to
     *
     * @return stdClass
     */
    public function subscribe(array $packages)
    {
        $params = array('packages' => implode(',', array_values($packages)));
        return $this->call('account/subscribe', $params);
    }

    /**
     * Cancel the current user. If this method completes successfully,
     * the curent user will lose all their subscriptions.
     *
     * @return stdClass
     */
    public function cancelAccount()
    {
        if (!$this->_userToken) {
            throw new Fuze_Exception(__METHOD__ . " requires a user session");
        }
        return $this->call('account/cancel', array());
    }

    /**
     * Creates or schedules a meeting
     *
     * @param array $params
     *      sendemail       only supported value: ‘All’
     *      includetollfree (optional) [true|false] default = False
     *      includeinternationaldial
     *                      (optional) [true|false] default = False
     *      starttime       a RFC 2822 date
     *      endtime         a RFC 2822 date
     *      subject         (optional) default value = ''
     *      invitationtext  (optional) default value = ''
     *      invitees []     (optional) list of email addresses to send invitation emails to.
     *                      Formatted as: ("email":"email_value", "name":"name_value", ...)
     *      timezone        (optional) default value = 'US/Pacific'
     *      autorecording   (optional) [true|false] default value = False
     *
     * @return stdClass
     */
    public function scheduleMeeting(array $params)
    {
        if (!$this->_userToken) {
            throw new Fuze_Exception(__METHOD__ . " requires a user session");
        }
        return $this->call('meeting/schedule', $params);
    }

    /**
     * Analogous to scheduleMeeting(), but bundles a launchtoken in the response
     *
     * @param array $params Same as for scheduleMeeting()
     * @return stdClass
     */
    public function startMeeting(array $params)
    {
        if (!$this->_userToken) {
            throw new Fuze_Exception(__METHOD__ . " requires a user session");
        }
        $res = $this->call('meeting/start', $params);
        if ($this->_isCodeOk($res->code)) {
            $urlParams = array(
                'launch' => $res->launch_token,
                'token' => $this->_userToken
            );
            $hostUrl = $res->meeting->launchmeetingurl .
                '&' . http_build_query($urlParams);
            $res->signedlaunchurl = $hostUrl;
        }
        return $res;
    }

    /**
     * Get information for a meeting
     *
     * @param int $meetingId
     * @return stdClass
     */
    public function getMeeting($meetingId)
    {
        if (!$this->_userToken) {
            throw new Fuze_Exception(__METHOD__ . " requires a user session");
        }
        return $this->call('meeting/get', array('meetingid' => $meetingId));
    }

    /**
     * List meetings for a user
     *
     * @param array $params Parameters are TBD
     * @return stdClass
     */
    public function listMeetings(array $params)
    {
        if (!$this->_userToken) {
            throw new Fuze_Exception(__METHOD__ . " requires a user session");
        }
        return $this->call('meeting/list', $params);
    }

    /**
     * Retrieves status for a meeting
     *
     * @param int $meetingId
     * @return stdClass
     */
    public function getMeetingStatus($meetingId)
    {
        if (!$this->_userToken) {
            throw new Fuze_Exception(__METHOD__ . " requires a user session");
        }
        return $this->call('meeting/status', array('meetingid' => $meetingId));
    }

    /**
     * Update meeting details
     *
     * @param array $params
     * @throws Fuze_Exception
     */
    public function updateMeeting(array $params)
    {
        if (!$this->_userToken) {
            throw new Fuze_Exception(__METHOD__ . " requires a user session");
        }
        if (!array_key_exists('meetingid', $params) || !is_numeric($params['meetingid'])) {
            throw new Fuze_Exception(__METHOD__ . " requires a 'meetingid' parameter");
        }

        return $this->call('meeting/update', $params);
    }

    /**
     * Get account information
     *
     * @return stdClass
     */
    public function getAccountInfo()
    {
        if (!$this->_userToken) {
            throw new Fuze_Exception(__METHOD__ . " requires a user session");
        }
        return $this->call('account/getinfo', array());
    }

    /**
     * Obtain a launch token for a meeting
     *
     * This method will return the JSON response verbatim. If you need to compose
     * a URL to be used for launching a meeting, use getSignedLaunchURL() instead.
     *
     * @param int $meetingid
     * @return stdClass
     */
    public function getLaunchToken($meetingid)
    {
        if (!$this->_userToken) {
            throw new Fuze_Exception(__METHOD__ . " requires a user session");
        }
        return $this->call('meeting/getlaunchtoken', array('meetingid' => $meetingid));
    }

    /**
     * This method generates a URL to be used for launching a meeting in a browser
     *
     * If given, the optional $launchUrl parameter should contain the launch
     * url as returned by 'meeting/get' or 'meeting/schedule'
     *
     * @param int $meetingid
     * @param string $launchUrl
     * @return string
     */
    public function getSignedLaunchURL($meetingid, $launchUrl=null)
    {
        if (!$launchUrl) {
            $meetingResult = $this->getMeeting($meetingid);
            $launchUrl = $meetingResult->meeting->launchmeetingurl;
        }

        $tokenResult = $this->getLaunchToken($meetingid);

        $params = array(
            'launch' => $tokenResult->launch_token,
            'token' => $this->_userToken
        );
        $launchUrl .= '&' . http_build_query($params);
        return $launchUrl;
    }

    /**
     * Upload a media to FuzeMeeting, optionally attaching it to a meeting
     *
     * @param string $filePathName Absolute filesystem path of the file to upload
     * @param string $actualFileName Alternative name to give to the file
     * @param int $meetingId Optional meeting ID to attach the media to
     *
     * @return array Associative array with keys 'mediaId' and 'code'
     */
    public function uploadMedia($filePathName, $actualFileName = null, $meetingId = null)
    {
        if (!$this->_userToken) {
            throw new Fuze_Exception(__METHOD__ . " requires a user session");
        }

        if (!is_readable($filePathName)) {
            throw new Fuze_Exception("File '{$filePathName}' is not readable.");
        }

        $url = "{$this->_url}/media/partnerupload";
        $ch = $this->_getCurlHandle($url);

        $boundary = md5(time());
        $headers = $this->_getFuzeHttpHeaders();
        $headers[] = 'Content-Type: multipart/form-data; boundary=' . $boundary;

        if ($actualFileName) {
            $fileName = $actualFileName;
        } else {
            $fileName = basename($filePathName);
        }

        $eol = "\r\n";
        $data = '';

        $data .= '--' . $boundary . $eol;
        if ($meetingId) {
            $data .= 'Content-Disposition: form-data; name="meetingId"' . $eol . $eol;
            $data .= $meetingId . $eol;
            $data .= '--' . $boundary . $eol;
        }
        $data .= 'Content-Disposition: form-data; name="media"; filename="'.$fileName.'"' . $eol;
        $data .= 'Content-Type: application/octet-stream' . $eol;
        $data .= 'Content-Transfer-Encoding: binary' . $eol;
        $data .= 'Content-Length: ' . filesize($filePathName) . $eol;
        $data .= $eol;

        $data .= file_get_contents($filePathName);

        $data .= $eol . '--' . $boundary . '--' . $eol . $eol;

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $result = $this->_executeCurl($ch);

        // Result should be a valid XML string
        $xml = simplexml_load_string($result);
        if (!$xml) {
            return false;
        }

        return array(
            'mediaId' => (integer)(string)$xml->mediaId[0],
            'code' => (integer)(string)$xml->result[0]->code[0],
        );
    }
}
