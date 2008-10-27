<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the HTTP_Request2 package.
 *
 * PHP version 5.1.0+
 *
 * LICENSE: This source file is subject to the New BSD License that is
 * available through the world-wide-web at the following URI:
 * http://opensource.org/licenses/bsd-license.php
 *
 * @category HTTP
 * @package  Request2
 * @author   David Jean Louis <izimobil@gmail.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  SVN: $Id$
 * @link     http://tools.ietf.org/html/rfc2616#section-5
 * @filesource
 */

/**
 * Include HTTP_Common_Message abstract class and Net_URL2 class
 */
require_once 'HTTP/Common/Message.php';
require_once 'Net/URL2.php';

/**
 * A class reprensenting an HTTP request message.
 *
 * @category HTTP
 * @package  Request2
 * @author   David Jean Louis <izimobil@gmail.com> 
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: @package_version@
 * @link     http://tools.ietf.org/html/rfc2616#section-5
 */
class HTTP_Request2 extends HTTP_Common_Message implements splSubject
{
    // constants {{{
    
    /**
     * HTTP method constants.
     *
     * @link http://tools.ietf.org/html/rfc2616#section-5.1.1
     */
    const METHOD_OPTIONS = 'OPTIONS';
    const METHOD_GET     = 'GET';
    const METHOD_HEAD    = 'HEAD';
    const METHOD_POST    = 'POST';
    const METHOD_PUT     = 'PUT';
    const METHOD_DELETE  = 'DELETE';
    const METHOD_TRACE   = 'TRACE';
    const METHOD_CONNECT = 'CONNECT';

    /**
     * Auth method constants.
     *
     * @link http://tools.ietf.org/html/rfc2617 Basic and digest auth
     * @link http://oauth.net/core/1.0/         OAuth core 1.0
     */
    const AUTH_BASIC  = 'Basic';
    const AUTH_DIGEST = 'Digest';
    const AUTH_OAUTH  = 'OAuth';

    /**
     * State constants.
     *
     * @see HTTP_Client2::event
     * @see HTTP_Client2::notify()
     */
    const STATE_NONE              = 0;
    const STATE_CONNECTED         = 1;
    const STATE_REQUEST_SENT      = 2;
    const STATE_RESPONSE_STATUS   = 3;
    const STATE_RESPONSE_HEADERS  = 4;
    const STATE_RESPONSE_TICK     = 5;
    const STATE_RESPONSE_RECEIVED = 6;
    const STATE_DISCONNECTED      = 7;
    const STATE_ERROR             = 8;
    
    // }}}
    // properties {{{

    /**
     * HTTP request method (aka "verb").
     * 
     * @var string $method HTTP request method
     * @link http://tools.ietf.org/html/rfc2616#section-5.1.1
     */
    public $method = self::METHOD_GET;

    /**
     * Array of options:
     * - use_brackets: whether to add brackets to parameters keys that have
     *   multiple value (ex: ids[]=1&ids[]=2), (default: true);
     * - max_redirects: the maximum number of redirects the client should 
     *   follow, set this to 0 to disable redirects;
     * - connection_timeout: the http connection timeout in seconds;
     * - request_timeout: the http request timeout in seconds;
     * - ssl_options: an array of ssl options as defined here:
     *   {@link http://www.php.net/manual/en/context.ssl.php};
     * - store_response: whether to store the response body in memory, set this
     *   to false for large files;
     * - file: path to a file where to store the response body (instead of
     *   storing it in memory).
     *
     * @var array $options The options array
     * @todo Document HTTP_Request2
     */
    public $options = array(
        'use_brackets'          => true,
        'max_redirects'         => 5,
        'connection_timeout'    => 30,
        'request_timeout'       => 30,
        'persistent_connection' => false,
        'ssl_options'           => array(),
        'store_response_body'   => true,
        'file'                  => null,
    );

    /**
     * The current state, it allows observers to know what's happening when
     * their update() method is called.
     *
     * @var string $state
     * @see HTTP_Request2::notify()
     */
    public $state = self::STATE_NONE;

    /**
     * The current client state data, it allows observers to access the data 
     * corresponding to the current state.
     *
     * @var mixed $data
     * @see HTTP_Request2::notify()
     */
    public $data = null;

    /**
     * Array of valid HTTP methods.
     *
     * @var array $validMethods
     */
    protected static $validMethods = array(
        self::METHOD_OPTIONS,
        self::METHOD_GET,
        self::METHOD_HEAD,
        self::METHOD_POST,
        self::METHOD_PUT,
        self::METHOD_DELETE,
        self::METHOD_TRACE,
        self::METHOD_CONNECT,
    );

    /**
     * Array of registered SplObserver instances.
     *
     * @var array $observers
     * @see HTTP_Request2::attach()
     * @see HTTP_Request2::detach()
     */
    protected $observers = array();

    /**
     * Mime type info DB.
     * 
     * @var resource $_infoDB
     * @see HTTP_Request2::addFileUpload()
     */
    private static $_infodb = null;

    /**
     * HTTP request URI.
     * Property made public via __set() and __get() magic methods.
     * 
     * @var Net_URL2 $_uri HTTP request URI
     * @link http://tools.ietf.org/html/rfc2616#section-5.1.2
     */
    private $_uri = null;

    /**
     * HTTP request proxy URI.
     * 
     * @var string $_proxy HTTP request proxy host
     */
    private $_proxy = null;

    /**
     * HTTP connection to use for the request.
     * 
     * @var HTTP_Connection $_connection HTTP connection instance.
     */
    private $_connection = null;

    /**
     * Array of post data.
     * 
     * @var array $_postParams Post data array
     */
    private $_postParams = null;

    /**
     * File containing the body.
     * 
     * @var array $file Array of files
     * @see HTTP_Request2::setFile()
     */
    private $_file = null;

    /**
     * Array of file arrays, only useful for a multipart entity.
     * 
     * @var array $_files Files array
     */
    private $_files = null;

    /**
     * Boundary string (only relevant for multipart entity).
     * 
     * @var string $boundary Boundary string
     */
    private $_boundary = null;

    /**
     * Static property that acts as a counter for redirects.
     * 
     * @var int $_redirectCount Number of redirects
     */
    private static $_redirectCount = 0;

    // }}}
    // __construct() {{{

    /**
     * Constructor.
     *
     * @param string $uri    URI of the request
     * @param string $method Request method (default is GET)
     * 
     * @return void
     * @see HTTP_Common_Message::__construct()
     */
    public function __construct($uri, $method = null)
    {
        $this->uri = $uri;
        if ($method !== null) {
            $this->method = strtoupper($method);
        }

        // set request default headers
        $this->defaultHeaders['User-Agent'] = 
            'PEAR HTTP_Request2/@package_version@';
    }

    // }}}
    // __get() {{{

    /**
     * Property getter interceptor to handle "uri" and "host" special cases.
     *
     * @param string $property The property to retrieve
     * 
     * @return mixed
     */
    public function __get($property)
    {
        switch ($property) {
        case 'uri':
            if ($this->_uri !== null) {
                return $this->_uri->getURL();
            }
            return null;
        case 'proxy':
            return $this->_proxy;
        case 'path':
            if ($this->_uri === null) {
                return null;
            }
            if ($this->httpVersion == self::HTTP_VERSION_1_0 || $this->proxy) {
                // if http 1.0 or proxy given, the request uri must be absolute
                return $this->_uri->getURL();
            }
            if (($path = $this->_uri->getPath()) === false) {
                $path = '/';
            }
            if (($query = $this->_uri->getQuery()) !== false) {
                $path .= '?' . $query;
            }
            if (($fragment = $this->_uri->getFragment()) !== false) {
                $path .= '#' . $fragment;
            }
            return $path;
        case 'host':
            if (isset($this->headers['host'])) {
                return trim($this->headers['host']);
            }
            if ($this->_proxy !== null) {
                return $this->_proxy;
            }
            if ($this->_uri !== null) {
                $host = $this->_uri->getHost();
                if (($port = $this->_uri->getPort()) !== false) {
                    $host .= ':' . $port;
                }
            }
            return $host;
        case 'port':
            $port = $this->_uri->getPort();
            if (!is_numeric($port)) {
                return $this->isSecure() ? 443 : 80;
            }
            return $port;
        case 'connection':
            if ($this->_connection === null) {
                include_once 'HTTP/Connection.php';
                $this->_connection = HTTP_Connection::factory('Socket');
            }
            return $this->_connection;
        default:
            return parent::__get($property);
        }
    }

    // }}}
    // __set() {{{

    /**
     * Property setter interceptor to handle "host" special case.
     *
     * @param string $property The property to retrieve
     * @param mixed  $value    The property value
     * 
     * @return void
     */
    public function __set($property, $value)
    {
        switch ($property) {
        case 'uri':
            if ($value == null) {
                return;
            }
            $this->_uri = new Net_URL2($value);
            $this->_uri->normalize();
            // if we have a proxy set, URI must be absolute
            if ($this->_proxy !== null && !$this->_uri->isAbsolute()) {
                throw new HTTP_Request2_Exception(
                    'URI must be absolute when using a proxy'
                );
            }
            // if we use HTTP/1.0, URI must also be absolute
            if ($this->httpVersion == self::HTTP_VERSION_1_0 && 
                !$this->_uri->isAbsolute()) {
                throw new HTTP_Request2_Exception(
                    'URI must be absolute when using ' . self::HTTP_VERSION_1_0
                );
            }
            break;
        case 'proxy':
            // if we have a proxy set, URI must be absolute
            if ($this->_uri !== null && !$this->_uri->isAbsolute()) {
                throw new HTTP_Request2_Exception(
                    'URI must be absolute when using a proxy'
                );
            }
            $this->_proxy = $value;
            break;
        case 'connection':
            if (!($value instanceof HTTP_Connection)) {
                throw new HTTP_Request2_Exception(
                    'connection must be an instance of HTTP_Connection'
                );
            }
            $this->_connection = $value;
            break;
        case 'path':
        case 'host':
        case 'port':
            throw new HTTP_Request2_Exception(
                '"' . $property . '" is a read-only property'
            );
        }
    }

    // }}}
    // __toString() {{{

    /**
     * Returns the string representation of the request.
     * Note: for memory issues, this method does *not* include file uploads 
     * data as well as the file of the body if any.
     *
     * @return string String representation of the request
     */
    public function __toString()
    {
        $str = $this->getStatusLineAndHeaders();
        if ($this->method === HTTP_Request2::METHOD_TRACE) {
            // a TRACE request can't have body so we just stop here
            return $str;
        }
        return $str . $this->getBody();
    }

    // }}}
    // getStatusLineAndHeaders() {{{

    /**
     * Returns the string representation of the status line and headers.
     * 
     * @return string Request string
     * @link http://tools.ietf.org/html/rfc2616#section-6
     */
    public function getStatusLineAndHeaders() 
    {
        $ret = sprintf("%s %s %s\r\n",
            $this->method,
            $this->path,
            $this->httpVersion);
        if ($this->headers['host'] == null && 
            $this->httpVersion == self::HTTP_VERSION_1_1) {
            $ret .= 'Host: ' . $this->host . ':' . $this->port . "\r\n";
        }
        if ($this->headers['content-length'] == null) {
            $this->headers['content-length'] = $this->getContentLength();
        }
        if (extension_loaded('zlib') && !isset($this->headers['accept-encoding'])) {
            $this->headers['accept-encoding'] = array('gzip', 'deflate');
        }
        $ret .= (string) $this->getAllHeaders() . "\r\n\r\n";
        return $ret;
    }

    // }}}
    // getBody() {{{

    /**
     * Returns the body of the request.
     * Note: for memory issues, this method does *not* include file uploads 
     * data as well as the file of the body if any.
     *
     * @return string Body of the request
     */
    public function getBody()
    {
        $body        = '';
        $ctypeTokens = explode(';', $this->headers['content-type']);
        $ctype       = $ctypeTokens[0];
        switch ($ctype) {
        case 'application/x-www-form-urlencoded':
            $padding = '';
            foreach ($this->getPostParameters() as $k => $v) {
                $body   .= $padding . $k . '=' . $v;
                $padding = '&';
            }
            if (!empty($body)) {
                $body .= "\r\n";
            }
            break;
        case 'multipart/form-data':
            foreach ($this->getPostParameters() as $k => $v) {
                $body .= $this->getMultipartPostDataHeader($k);
                $body .= rawurldecode($v) . "\r\n";
                $body .= '--'.$this->getBoundary()."--\r\n";
            }
            break;
        default:
            $body = $this->body;
        }
        return $body;
    }

    // }}}
    // send() {{{

    /**
     * Sends the request and returns the HTTP_Response.
     *
     * @return void
     */
    public function send()
    {
        // send request
        $response = $this->connection->sendRequest($this);

        // handle redirection
        if ($response->isRedirect() && 
            self::$_redirectCount < $this->options['max_redirects']) {
            if (!isset($response->headers['location'])) {
                throw new HTTP_Request2_Exception('Invalid redirect response');
            }
            $this->uri = $response->headers['location'];
            self::$_redirectCount++;
            return $this->send();
        }
        return $response;
    }

    // }}}
    // setProxy() {{{

    /**
     * Setup an HTTP proxy.
     *
     * <code>
     * require_once 'HTTP/Request2.php';
     *
     * $client = new HTTP_Request2('http://example.com');
     * $client->setProxy('localhost:8088', 'username', '5ecr3t');
     * // etc...
     * </code>
     *
     * @param string $host Proxy host:port
     * @param string $user Proxy auth user name
     * @param string $pass Proxy auth user password
     * @param string $type Proxy auth type (one of the AUTH_* constants)
     *
     * @return void
     */
    public function setProxy($host, $user = null, $pass = null,
        $type = self::AUTH_BASIC)
    {
        $this->proxy = $host;
        if ($user !== null) {
            // setup proxy authentication
            $this->addAuthHeader('proxy-authorization', $user, $pass, $type);
        }
    }

    // }}}
    // setAuth() {{{

    /**
     * Setup HTTP authentication.
     *
     * <code>
     * require_once 'HTTP/Client2.php';
     *
     * $client = new HTTP_Request2('http://example.com');
     * $client->setAuth('username', '5ecr3t', HTTP_Request2::AUTH_DIGEST);
     * // etc...
     * </code>
     *
     * @param string $user Auth user name
     * @param string $pass Auth user password
     * @param string $type Auth type (one of the AUTH_* constants)
     *
     * @return void
     */
    public function setAuth($user, $pass = null, $type = self::AUTH_BASIC)
    {
        $this->addAuthHeader('authorization', $user, $pass, $type);
    }

    // }}}
    // addQueryParameter() {{{

    /**
     * Adds a query parameter to the uri query string.
     *
     * @param string $name       Parameter name
     * @param mixed  $value      Parameter value (can be an an array)
     * @param bool   $preencoded Whether the parameter value is already 
     *                           urlencoded or not (default: false)
     *
     * @return void
     * @todo report a bug / provide a patch for Net_URL2
     */
    public function addQueryParameter($name, $value, $preencoded = false)
    {
        // FIXME: Net_URL2 query string management is broken and should
        // be reimplemented:
        // - does not encode values when passing an array,
        // - does not provide an option for already encoded values,
        // - slow (reparse all query variables each time).
        // Need a good bug report.
        $this->_uri->setQueryVariable($name, $value);
    }

    // }}}
    // addPostParameter() {{{

    /**
     * Adds a POST parameter to the request.
     *
     * @param string $key        Parameter name
     * @param mixed  $value      Parameter value (can be an an array)
     * @param bool   $preencoded Whether the parameter value is already 
     *                           urlencoded or not (default: false)
     *
     * @return void
     * @todo implement HTTP_Request2::addPostParameter()
     */
    public function addPostParameter($key, $value, $preencoded = false)
    {
        if (!$preencoded) {
            if (is_array($value)) {
                $value = array_map('rawurlencode', $value);
            } else {
                $value = rawurlencode($value);
            }
        }
        $this->_postParams[$key] = $value;
        if ($this->headers['content-type'] != 'multipart/form-data') {
            unset($this->headers['content-type']);
            $this->headers['content-type'] = 'application/x-www-form-urlencoded';
        }
    }

    // }}}
    // getPostParameters() {{{

    /**
     * Returns the request post parameters.
     * 
     * @return array An array of post parameters.
     */
    public function getPostParameters()
    {
        return $this->_postParams;
    }
    
    // }}}
    // getFile() {{{

    /**
     * Returns the file containing the body.
     * 
     * @return string Path to the local file
     * @return void
     */
    public function getFile()
    {
        return $this->_file;
    }
    
    // }}}
    // setFile() {{{

    /**
     * Sets the file containing the body.
     * 
     * @param string $fpath Path to the local file
     * @param string $mime  File mimetype, if null the method tries to detect it 
     *
     * @return void
     */
    public function setFile($fpath, $mime = null)
    {
        if (!is_readable($fpath)) {
            throw new HTTP_Request2_Exception(
                'File "' . $fpath . '" does not exist or is not readable'
            );
        }
        if ($mime === null) {
            $mime = self::detectMimetype($fpath);
        }
        $this->_file = $fpath;
        unset($this->headers['content-type']);
        $this->headers['content-type'] = $mime;
    }
    
    // }}}
    // getFileUploads() {{{

    /**
     * Returns the files to upload.
     * 
     * @return array An array of info arrays about the files to upload
     */
    public function getFileUploads()
    {
        return $this->_files;
    }
    
    // }}}
    // addFileUpload() {{{

    /**
     * Adds a file upload.
     * 
     * @param string $fname Name of the file upload field
     * @param string $fpath Path to the local file
     * @param string $mime  File mimetype, if null the method tries to detect it 
     *
     * @return void
     */
    public function addFileUpload($fname, $fpath, $mime = null)
    {
        if (!is_readable($fpath)) {
            throw new HTTP_Request2_Exception(
                'File "' . $fpath . '" does not exist or is not readable'
            );
        }
        if ($mime === null) {
            $mime = self::detectMimetype($fpath);
        }
        $this->_files[] = array(
            'name' => $fname,
            'path' => $fpath,
            'mime' => $mime,
        );
        unset($this->headers['content-type']);
        $this->headers['content-type'] = 'multipart/form-data; boundary=' 
            . $this->getBoundary();
    }

    // }}}
    // attach() {{{

    /**
     * Attach an observer to the list of observer that are notified of the 
     * object's events.
     *
     * @param SplObserver $observer Instance implementing SplObserver interface
     *
     * @return void
     * @see HTTP_Request2::detach()
     */
    public function attach(SplObserver $observer)
    {
        $this->observers[get_class($observer)] = $observer;
    }

    // }}}
    // detach() {{{

    /**
     * Detach a previously attached observer.
     *
     * @param SplObserver $observer Instance implementing SplObserver interface
     * 
     * @return bool Whether the observer was detached successfully.
     * @see HTTP_Request2::attach()
     */
    public function detach(SplObserver $observer)
    {
        $key = get_class($observer);
        if (array_key_exists($key, $this->observers)) {
            unset($this->observers[$key]);
            return true; 
        }
        return false;
    }

    // }}}
    // notify() {{{

    /**
     * Notifies all registered observers of the event that just happened.
     *
     * Observers can retrieve the type of event in the update() method by 
     * checking the HTTP_Client2::event property.
     *
     * @see HTTP_Request2::attach()
     * @see HTTP_Request2::detach()
     * @see HTTP_Request2::event
     * @see SplObserver::update()
     * @return void
     */
    public function notify()
    {
        foreach ($this->observers as $observer) {
            $observer->update($this);
        }
    }

    // }}}
    // isSecure() {{{

    /**
     * Return true is the request URI is a secure URI (https or ssl) or false 
     * otherwise.
     *
     * @return bool Whether the request URI is secure or not.
     */
    public function isSecure()
    {
        if ($this->_uri === null) {
            return false;
        }
        $scheme = $this->_uri->getScheme();
        return $scheme === 'https' || $scheme === 'ssl';
    }

    // }}}
    // getContentLength() {{{

    /**
     * Returns the content length to use in the message.
     *
     * @return string
     */
    public function getContentLength()
    {
        if ($this->body !== null) {
            return strlen($this->body);
        }
        if ($this->_file !== null) {
            return filesize($this->_file);
        }
        $length = 0;
        if (count($this->_files)) {
            foreach ($this->_files as $file) {
                $length += strlen($this->getMultipartFileHeader($file));
                $length += filesize($file['path']);
                $length += strlen("\r\n--" . $this->getBoundary() . "--\r\n");
            }
            foreach ($this->_postParams as $name => $value) {
                $length += strlen($this->getMultipartPostDataHeader($name));
                $length += strlen(rawurldecode($value));
                $length += strlen("\r\n--" . $this->getBoundary() . "--\r\n");
            }
        } else if (count($this->_postParams)) {
            $padding = '';
            foreach ($this->_postParams as $k => $v) {
                $length += strlen($padding . $k . '=' . $v);
                $padding = '&';
            }
        }
        // XXX: we must return a string to fix the "X-Pad: avoid browser bug"
        return (string) $length;
    }
    
    // }}}
    // getBoundary() {{{

    /**
     * Returns the content length to use in the message.
     *
     * @return string
     */
    public function getBoundary()
    {
        if ($this->_boundary === null) {
            $this->_boundary = 'PEARHTTP' . md5(microtime());
        }
        return $this->_boundary;
    }
    
    // }}}
    // getMultipartFileHeader() {{{

    /**
     * Returns a multipart file header.
     *
     * @param array $file The file info array
     *
     * @return string
     */
    public function getMultipartFileHeader($file)
    {
        $ret  = '--' . $this->getBoundary() . "\r\n";
        $ret .= 'Content-Disposition: form-data; name="' . $file['name'] . '";';
        $ret .= 'filename="' . basename($file['path']) . "\"\r\n";
        $ret .= "Content-Type: " . $file['mime'] . "\r\n\r\n";
        return $ret;
    }
    
    // }}}
    // getMultipartPostDataHeader() {{{

    /**
     * Returns multipart post data item header.
     *
     * @param string $name Name of the post parameter
     *
     * @return string
     */
    public function getMultipartPostDataHeader($name)
    {
        $ret  = '--' . $this->getBoundary() . "\r\n";
        $ret .= 'Content-Disposition: form-data; name="' . $name . '"';
        $ret .= "\r\n\r\n";
        return $ret;
    }
    
    // }}}
    // addAuthHeader() {{{

    /**
     * Adds an authorisation or proxy-authorization header to the request.
     *
     * @param string $name Auth header (authorisation or proxy-authorization)
     * @param string $user Auth user name
     * @param string $pass Auth user password
     * @param string $type Auth type (one of the AUTH_* constants)
     *
     * @return void
     * @throws HTTP_Request2_Exception
     * @see HTTP_Request2::AUTH_BASIC
     * @see HTTP_Request2::AUTH_DIGEST
     * @see HTTP_Request2::AUTH_OAUTH
     * @todo implement digest and oauth authentication types
     * @todo provide this as a separate package (version2 of Auth_HTTP) ?
     */
    protected function addAuthHeader($name, $user, $pass, $type)
    {
        switch ($type) {
        case self::AUTH_BASIC:
            // basic auth
            $header = $type . ' ' . base64_encode($user . ':' . $pass);
            break;
        case self::AUTH_DIGEST:
        case self::AUTH_OAUTH:
            // digest auth or oauth or other auth types
            throw new HTTP_Request2_Exception(
                $type . ' authentication is not yet implemented'
            );
        default:
            // digest auth or oauth or other auth types
            throw new HTTP_Request2_Exception('Unknown auth type ' . $type);
        }
        $this->request->headers[$name] = $header;
    }

    // }}}
    // detectMimetype() {{{

    /**
     * Tries to detect the mime type of the given file.
     *
     * @param string $fpath Path to the local file
     *
     * @return string
     */
    protected static function detectMimetype($fpath)
    {
        // try to detect the file content type
        if (function_exists('finfo_open')) {
            if (self::$_infodb === null) {
                self::$_infodb = @finfo_open(FILEINFO_MIME);
            }
            if (self::$_infodb) { 
                $mime = finfo_file(self::$_infodb, $fpath);
            }
        } else {
            $mime = mime_content_type($fpath);
        }
        if (!$mime) {
            $mime = 'application/octet-stream';
        }
        return $mime;
    }
    
    // }}}
}
