<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the HTTP_Response2 package.
 *
 * PHP version 5.1.0+
 *
 * LICENSE: This source file is subject to the New BSD License that is
 * available through the world-wide-web at the following URI:
 * http://opensource.org/licenses/bsd-license.php
 *
 * @category HTTP
 * @package  Common
 * @author   David Jean Louis <izimobil@gmail.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  SVN: $Id: Twitter.php 25 2008-09-27 16:54:21Z izimobil $
 * @link     http://tools.ietf.org/html/rfc2616#section-6
 * @filesource
 */

/**
 * Include HTTP_Common_Message abstract class
 */
require_once 'HTTP/Common/Message.php';
require_once 'HTTP/Response2/Exception.php';

/**
 * A class reprensenting an HTTP response message.
 *
 * @category HTTP
 * @package  Common
 * @author   David Jean Louis <izimobil@gmail.com> 
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: @package_version@
 * @link     http://tools.ietf.org/html/rfc2616#section-6
 */
class HTTP_Response2 extends HTTP_Common_Message
{
    // properties {{{

    /**
     * HTTP response code.
     * 
     * @var integer $code HTTP response code
     * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
     */
    public $code = 200;

    /**
     * Associative array of HTTP response code / reason.
     *
     * @var array $codes Array of HTTP codes / reasons
     * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
     */
    protected static $codes = array(

        // 1xx: Informational - Request received, continuing process
        100 => 'Continue',
        101 => 'Switching Protocols',

        // 2xx: Success - The action was successfully received, understood and
        // accepted
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',

        // 3xx: Redirection - Further action must be taken in order to complete
        // the request
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',  // 1.1
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',

        // 4xx: Client Error - The request contains bad syntax or cannot be 
        // fulfilled
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',

        // 5xx: Server Error - The server failed to fulfill an apparently
        // valid request
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        509 => 'Bandwidth Limit Exceeded',

    );

    /**
     * For chunked responses, bytes left to read in current chunk.
     * 
     * @var integer $_chunkLeft Bytes left to read in current chunk.
     */
    private $_chunkLeft = null;

    // }}}
    // __construct() {{{

    /**
     * Constructor.
     * 
     * @param int    $code Code of the response
     * @param string $body Body of the response
     * 
     * @return void
     */
    public function __construct($code = 200, $body = null)
    {
        $this->code = $code;
        $this->body = $body;
    }

    // }}}
    // __get() {{{

    /**
     * Property getter interceptor to handle the "reason" property special case.
     *
     * @param string $property The property to retrieve
     * 
     * @return mixed
     */
    public function __get($property)
    {
        if ($property == 'reason' && isset(self::$codes[$this->code])) {
            return self::$codes[$this->code];
        }
        return parent::__get($property);
    }

    // }}}
    // __set() {{{

    /**
     * Property setter interceptor.
     *
     * @param string $property The property to set
     * @param string $value    The property value
     * 
     * @return mixed
     */
    public function __set($property, $value)
    {
        if ($property == 'reason') {
            throw new HTTP_Response2_Exception('"reason" is a read-only property');
        }
        parent::__set($property, $value);
    }

    // }}}
    // __toString() {{{

    /**
     * Returns the string representation of the response.
     * 
     * @return string Response string
     * @link http://tools.ietf.org/html/rfc2616#section-6
     */
    public function __toString() 
    {
        $ret = sprintf("%s %d %s\r\n",
            $this->httpVersion,
            $this->code,
            $this->reason);
        
        if (count($this->headers)) {
            $ret .= (string) $this->headers . "\r\n";
        }
        return $ret . "\r\n" . $this->getBody();
    }

    // }}}
    // getBody() {{{

    /**
     * Returns a readable version of the body.
     *
     * @return string The response body
     */
    public function getBody()
    {
        if ($this->headers['transfer-encoding'] == 'chunked') {
            $encBody = $this->body;
            $body    = '';
            while (trim($encBody)) {
                if (!preg_match("/^([\da-fA-F]+)[^\r\n]*\r\n/sm", $encBody, $m)) {
                    throw new HTTP_Response2_Exception(
                        "Error parsing body - doesn't seem to be a chunked message"
                    );
                }
                $length  = hexdec(trim($m[1]));
                $cut     = strlen($m[0]);
                $body   .= substr($encBody, $cut, $length);
                $encBody = substr($encBody, $cut + $length + 2);
            }
        } else {
            $body = $this->body;
        }
        $ce = $this->headers['content-encoding'];
        if ($ce == 'deflate' || $ce == 'gzip') {
            if (!extension_loaded('zlib')) {
                throw new HTTP_Response2_Exception(
                    'You need the zlib extension to handle deflate and gzip '
                    . 'content-encodings'
                );
            }
            if ($ce == 'gzip') {
                // FIXME use a real decodeGzip(), this is just a hack
                return gzinflate(substr($body, 10));
            } else {
                return gzuncompress($body);
            }
        }
        return $body;

    }

    // }}}
    // fromString() {{{

    /**
     * Parses a raw response string and returns an HTTP_Response2
     * instance.
     * 
     * @param string $data The raw response string
     *
     * @return HTTP_Response2
     * @throws HTTP_Response2_Exception
     */
    public static function fromString($data)
    {
        $response = new HTTP_Response2();

        // split headers from body
        $chunks = preg_split("/\r?\n\r?\n/", ltrim($data), 2);
        $body   = isset($chunks[1]) ? $chunks[1] : null;

        // split status-line from headers
        $chunks = preg_split("/\r?\n/", ltrim($chunks[0]), 2);
        
        if (isset($chunks[1])) {
            // set headers
            $response->headers = self::parseHeaders($chunks[1]);
        }

        // parse status line
        list($httpVersion, $code) = self::parseStatusLine($chunks[0]);

        // set code, http version and body
        $response->httpVersion = $httpVersion;
        $response->code        = $code;
        $response->body        = $body;

        return $response;
    }

    // }}}
    // parseStatusLine() {{{

    /**
     * Parses a response status-line and returns an array of two elements:
     *  - 'httpVersion' => http version (ex: HTTP/1.1),
     *  - 'code'        => response code (ex: 200)
     * 
     * @param string $line The raw status-line
     *
     * @return array The status line line array
     */
    public static function parseStatusLine($line)
    {
        if (sscanf($line, '%s %3d', $httpVersion, $code) != 2) {
            throw new HTTP_Response2_Exception('Invalid status line ' . $line);
        }
        if (strtolower($httpVersion) == 'HTTP/1.x') {
            $httpVersion = self::HTTP_VERSION_1_1;
        }
        return array($httpVersion, (int)$code);
    }

    // }}}
    // parseHeaders() {{{

    /**
     * Parses the response headers.
     * 
     * @param string $headerStr The headers raw string
     *
     * @return HTTP_Common_Headers An array like HTTP_Common_Headers instance
     */
    public static function parseHeaders($headerStr)
    {
        include_once 'HTTP/Common/Headers.php';
        $ret   = new HTTP_Common_Headers();
        $lines = preg_split('/\r?\n/', trim($headerStr));
        foreach ($lines as $line) {
            $header = explode(':', $line, 2);
            if (count($header) < 2) {
                throw new HTTP_Response2_Exception(
                    'Invalid header line found: ' . $line
                );
            }
            $ret[trim($header[0])] = ltrim($header[1]);
        }

        return $ret;
    }

    // }}}
    // isError() {{{

    /**
     * Returns whether the response is an error response.
     *
     * @return bool Whether the response is error
     */
    public function isError()
    {
        return substr($this->code, 0, 1) == 4;
    }

    // }}}
    // isRedirect() {{{

    /**
     * Returns whether the response is a redirect response.
     *
     * @return bool Whether the response is redirect
     */
    public function isRedirect()
    {
        return substr($this->code, 0, 1) == 3;
    }

    // }}}
    // isSuccessful() {{{

    /**
     * Returns whether the response is a successful response.
     *
     * @return bool Whether the response is successful
     */
    public function isSuccessful()
    {
        return substr($this->code, 0, 1) == 2;
    }

    // }}}
    // shouldClose() {{{

    /**
     * Returns whether the connection should be closed or not after receiving
     * the response.
     *
     * @return bool Whether the connection should be closed or not
     */
    public function shouldClose()
    {
        if ($this->httpVersion == self::HTTP_VERSION_1_1) {
            return strtolower($this->headers['Connection']) == 'close';
        }

        // Some HTTP/1.0 implementations have support for persistent
        // connections, using rules different than HTTP/1.1.
        
        // Some return a "Connection: Keep-Alive" header,
        // Proxy-Connection is a netscape hack.
        return
            // For older HTTP, Keep-Alive indicates persistent connection
            (!isset($this->headers['keep-alive']))
            // Some return a "Connection: Keep-Alive" header
         && (strtolower($this->headers['Connection']) != 'keep-alive')
            // Proxy-Connection is a netscape hack.
         && (strtolower($this->headers['Proxy-Connection']) != 'keep-alive');
    }

    // }}}
}
