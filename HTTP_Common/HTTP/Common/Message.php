<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the HTTP_Common package.
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
 * @version  SVN: $Id$
 * @link     http://tools.ietf.org/html/rfc2616#section-5
 * @filesource
 */

/**
 * Include HTTP_Common_Message abstract class.
 */
require_once 'HTTP/Common/Headers.php';

/**
 * An abstract class representing an HTTP message (request or response).
 *
 * @category HTTP
 * @package  Common
 * @author   David Jean Louis <izimobil@gmail.com> 
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: @package_version@
 * @link     http://tools.ietf.org/html/rfc2616#section-4
 */
class HTTP_Common_Message
{
    // constants {{{
    
    /**
     * HTTP version constants.
     *
     * @link http://tools.ietf.org/html/rfc2616#section-3.1
     */
    const HTTP_VERSION_1_0 = 'HTTP/1.0';
    const HTTP_VERSION_1_1 = 'HTTP/1.1';

    // }}}
    // properties {{{

    /**
     * The HTTP version of an HTTP message (default is HTTP/1.1).
     * 
     * @var string $httpVersion HTTP version
     * @link http://tools.ietf.org/html/rfc2616#section-3.1
     */
    public $httpVersion = self::HTTP_VERSION_1_1;

    /**
     * Message default headers.
     * Note that we do not set "Date" header here because calling gmdate() 
     * would cause a parse error, this is done in getAllHeaders() method.
     * 
     * @var array $_defaultHeaders Message default headers
     * @see HTTP_Common_Message::getAllHeaders()
     */
    protected $defaultHeaders = array();

    /**
     * Message headers.
     *
     * This is an array compatible object (implementing Iterator, ArrayAccess
     * and Countable spl interfaces).
     * This property is made public via __get() and __set() magic methods.
     * 
     * @var HTTP_Common_Headers $_headers HTTP message headers
     * @see HTTP_Common_Headers
     * @link http://tools.ietf.org/html/rfc2616#section-4.2
     */
    private $_headers = null;

    /**
     * Body of the HTTP message.
     * 
     * @var string $body HTTP message body
     * @link http://tools.ietf.org/html/rfc2616#section-4.3
     */
    private $_body = null;
    
    // }}}
    // __get() {{{

    /**
     * Magic getter to handle "headers" property special case.
     *
     * @param string $property The property to retrieve
     * 
     * @return mixed
     */
    public function __get($property)
    {
        switch ($property) {
        case 'headers':
            if ($this->_headers === null) {
                $this->_headers = new HTTP_Common_Headers();
            }
            return $this->_headers;
        case 'body':
            return $this->_body;
        default:
            return null;
        }
    }

    // }}}
    // __set() {{{

    /**
     * Magic setter to handle "headers" property special case.
     *
     * @param string $property The property to set
     * @param string $value    The property value
     * 
     * @return void
     */
    public function __set($property, $value)
    {
        switch ($property) {
        case 'headers':
            if (!($value instanceof HTTP_Common_Headers)) {
                $value = new HTTP_Common_Headers($value);
            }
            $this->_headers = $value;
            break;
        case 'body':
            $this->_body = $value;
            break;
        }
    }

    // }}}
    // getAllHeaders() {{{

    /**
     * Special method that returns the headers set by the user merged with the
     * default headers.
     * 
     * @return HTTP_Common_Headers
     */
    public function getAllHeaders()
    {
        // get a copy of our headers
        $headers = clone $this->headers;

        if (!isset($headers['Date'])) {
            $headers['Date'] = gmdate(DATE_RFC2822, time());
        }

        // add default headers
        foreach ($this->defaultHeaders as $key => $val) {
            if (!isset($this->headers[$key])) {
                $headers[$key] = $val;
            }
        }
        return $headers;
    }

    // }}}
}
