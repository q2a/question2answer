<?php
/**
 * This is a PHP library that handles calling reCAPTCHA.
 *    - Documentation and latest version
 *          https://developers.google.com/recaptcha/docs/php
 *    - Get a reCAPTCHA API Key
 *          https://www.google.com/recaptcha/admin/create
 *    - Discussion group
 *          http://groups.google.com/group/recaptcha
 *
 * @copyright Copyright (c) 2014, Google Inc.
 * @link      http://www.google.com/recaptcha
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * A ReCaptchaResponse is returned from verifyResponse().
 */
class ReCaptchaResponse
{
    public $success;
    public $errorCodes = array();
}

/**
 * Stores and formats the parameters for the request to the reCAPTCHA service.
 */
class ReCaptchaRequestParameters
{
    private $secret;
    private $response;
    private $remoteIp;
    private $version;

    /**
     * Initialise parameters.
     *
     * @param string $secret Site secret.
     * @param string $response Value from g-captcha-response form field.
     * @param string $remoteIp User's IP address.
     * @param string $version Version of this client library.
     */
    public function __construct($secret, $response, $remoteIp = null, $version = null)
    {
        $this->secret = $secret;
        $this->response = $response;
        $this->remoteIp = $remoteIp;
        $this->version = $version;
    }

    /**
     * Array representation.
     *
     * @return array Array formatted parameters.
     */
    public function toArray()
    {
        $params = array('secret' => $this->secret, 'response' => $this->response);

        if (!is_null($this->remoteIp)) {
            $params['remoteip'] = $this->remoteIp;
        }

        if (!is_null($this->version)) {
            $params['version'] = $this->version;
        }

        return $params;
    }

    /**
     * Query string representation for HTTP request.
     *
     * @return string Query string formatted parameters.
     */
    public function toQueryString()
    {
        return http_build_query($this->toArray(), '', '&');
    }
}

/**
 * Defines certain rules for a RequestMethod
 * Interface ReCaptchaRequestMethod
 */
interface ReCaptchaRequestMethod
{
    /**
     * Submit the request with the specified parameters.
     *
     * @param ReCaptchaRequestParameters $params Request parameters
     * @return string Body of the reCAPTCHA response
     */
    public function submit(ReCaptchaRequestParameters $params);
}

/**
 * Sends GET requests to the reCAPTCHA service.
 */
class ReCaptchaGetRequestMethod implements ReCaptchaRequestMethod{

    const SITE_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify?';

    /**
     * Submit the request with the specified parameters.
     *
     * @param ReCaptchaRequestParameters $params Request parameters
     * @return string Body of the reCAPTCHA response
     */
    public function submit(ReCaptchaRequestParameters $params){
        return file_get_contents(self::SITE_VERIFY_URL . $params->toQueryString());
    }
}

/**
 * Convenience wrapper around native socket and file functions to allow for
 * mocking.
 */
class ReCaptchaSocket
{
    private $handle = null;

    /**
     * fsockopen
     *
     * @see http://php.net/fsockopen
     * @param string $hostname
     * @param int $port
     * @param int $errno
     * @param string $errstr
     * @param float $timeout
     * @return resource
     */
    public function fsockopen($hostname, $port = -1, &$errno = 0, &$errstr = '', $timeout = null)
    {
        $this->handle = fsockopen($hostname, $port, $errno, $errstr, (is_null($timeout) ? ini_get("default_socket_timeout") : $timeout));

        if ($this->handle != false && $errno === 0 && $errstr === '') {
            return $this->handle;
        }

        return false;
    }

    /**
     * fwrite
     *
     * @see http://php.net/fwrite
     * @param string $string
     * @param int $length
     * @return int | bool
     */
    public function fwrite($string, $length = null)
    {
        return fwrite($this->handle, $string, (is_null($length) ? strlen($string) : $length));
    }

    /**
     * fgets
     *
     * @see http://php.net/fgets
     * @param int $length
     * @return string
     */
    public function fgets($length = null)
    {
        return fgets($this->handle, $length);
    }

    /**
     * feof
     *
     * @see http://php.net/feof
     * @return bool
     */
    public function feof()
    {
        return feof($this->handle);
    }

    /**
     * fclose
     *
     * @see http://php.net/fclose
     * @return bool
     */
    public function fclose()
    {
        return fclose($this->handle);
    }
}

/**
 * Sends a POST request to the reCAPTCHA service, but makes use of fsockopen()
 * instead of get_file_contents(). This is to account for people who may be on
 * servers where allow_furl_open is disabled.
 */
class ReCaptchaSocketPostRequestMethod implements ReCaptchaRequestMethod
{
    const RECAPTCHA_HOST = 'www.google.com';
    const SITE_VERIFY_PATH = '/recaptcha/api/siteverify';
    const BAD_REQUEST = '{"success": false, "error-codes": ["invalid-request"]}';
    const BAD_RESPONSE = '{"success": false, "error-codes": ["invalid-response"]}';
    private $socket;

    public function __construct(ReCaptchaSocket $socket = null)
    {
        if (!is_null($socket)) {
            $this->socket = $socket;
        } else {
            $this->socket = new ReCaptchaSocket();
        }
    }

    /**
     * Submit the POST request with the specified parameters.
     *
     * @param ReCaptchaRequestParameters $params Request parameters
     * @return string Body of the reCAPTCHA response
     */
    public function submit(ReCaptchaRequestParameters $params)
    {
        $errno = 0;
        $errstr = '';

        if (false === $this->socket->fsockopen('ssl://' . self::RECAPTCHA_HOST, 443, $errno, $errstr, 30)) {
            return self::BAD_REQUEST;
        }

        $content = $params->toQueryString();

        $request = "POST " . self::SITE_VERIFY_PATH . " HTTP/1.1\r\n";
        $request .= "Host: " . self::RECAPTCHA_HOST . "\r\n";
        $request .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $request .= "Content-length: " . strlen($content) . "\r\n";
        $request .= "Connection: close\r\n\r\n";
        $request .= $content . "\r\n\r\n";

        $this->socket->fwrite($request);
        $response = '';

        while (!$this->socket->feof()) {
            $response .= $this->socket->fgets(4096);
        }

        $this->socket->fclose();

        if (0 !== strpos($response, 'HTTP/1.1 200 OK')) {
            return self::BAD_RESPONSE;
        }

        $parts = preg_split("#\n\s*\n#Uis", $response);

        return $parts[1];
    }
}

class ReCaptcha
{
    private static $_signupUrl = 'https://www.google.com/recaptcha/admin';

    const VERSION = 'php_1.1.2';
    private $secret;
    private $requestMethod;

    /**
     * Constructor.
     *
     * @param string $secret shared secret between site and ReCAPTCHA server.
     */
    public function __construct($secret , ReCaptchaRequestMethod $requestMethod = null)
    {
        if ($secret == null || $secret == '') {
            die('To use reCAPTCHA you must get an API key from <a href="' . self::$_signupUrl . '">' . self::$_signupUrl . '</a>');
        }

        if (!is_string($secret)) {
            die('The provided secret must be a string');
        }

        $this->secret = $secret;

        if (!is_null($requestMethod)) {
            $this->requestMethod = $requestMethod;
        } else {
            $this->requestMethod = new ReCaptchaGetRequestMethod();
        }
    }

    /**
     * Calls the reCAPTCHA siteverify API to verify whether the user passes
     * CAPTCHA test.
     *
     * @param string $remoteIp   IP address of end user.
     * @param string $response   response string from recaptcha verification.
     *
     * @return ReCaptchaResponse
     */
    public function verifyResponse($remoteIp, $response)
    {
        // Discard empty solution submissions
        if ($response == null || strlen($response) == 0) {
            $recaptchaResponse = new ReCaptchaResponse();
            $recaptchaResponse->success = false;
            $recaptchaResponse->errorCodes = array('missing-input-response');
            return $recaptchaResponse;
        }

        $params = new ReCaptchaRequestParameters($this->secret, $response, $remoteIp, self::VERSION);

        $rawResponse = $this->requestMethod->submit($params);
        $answers = json_decode($rawResponse, true);

        $recaptchaResponse = new ReCaptchaResponse();

        if (trim($answers['success']) == true) {
            $recaptchaResponse->success = true;
        } else {
            $recaptchaResponse->success = false;
            if (isset($answers['error-codes']))
                $recaptchaResponse->errorCodes = $answers['error-codes'];
        }

        return $recaptchaResponse;
    }

    public static function getSignupUrl()
    {
        return self::$_signupUrl;
    }
}
