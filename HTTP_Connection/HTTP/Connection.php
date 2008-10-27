<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the HTTP_Connection PEAR package.
 *
 * PHP version 5.1.0+
 *
 * LICENSE: This source file is subject to the New BSD License that is
 * available through the world-wide-web at the following URI:
 * http://opensource.org/licenses/bsd-license.php
 *
 * @category HTTP
 * @package  Connection
 * @author   David Jean Louis <izimobil@gmail.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  SVN: $Id$
 * @link     http://pear.php.net/package/HTTP_Request2
 * @link     http://tools.ietf.org/html/rfc2616
 * @filesource
 */

/**
 * Include HTTP_Connection_Exception class.
 */
require_once 'HTTP/Connection/Exception.php';

/**
 * Request adapter base abstract class.
 *
 * @category HTTP
 * @package  Connection
 * @author   David Jean Louis <izimobil@gmail.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: @package_version@
 * @link     http://tools.ietf.org/html/rfc2616
 */
abstract class HTTP_Connection
{
    // properties() {{{

    /**
     * The HTTP_Request2 instance.
     *
     * @var HTTP_Request2 $request
     * @see HTTP_Request2
     */
    protected $request;

    /**
     * The oppened file to store the response body (optional).
     *
     * @var resource $handle
     */
    protected $handle;

    /**
     * Array of HTTP_Connection instances
     *
     * @var array $instances The options array
     */
    public static $instances = array();

    // }}}
    // factory() {{{

    /**
     * Factory method that returns a singleton of the given adapter.
     *
     * @param string $adapter The adapter name
     *
     * @return void
     * @throws HTTP_Connection_Exception
     * @internal Uncomment the @ on include_once if you have trouble...
     */
    public static function factory($adapter)
    {    
        if (!isset(self::$instances[$adapter])) {
            $inc = @include_once 'HTTP/Connection/' . $adapter . '.php';
            $cls = 'HTTP_Connection_' . $adapter;
            if (!$inc || !class_exists($cls)) {
                throw new HTTP_Connection_Exception(
                    'Unsupported adapter ' . $adapter
                );
            }
            self::$instances[$adapter] = new $cls();
        }
        return self::$instances[$adapter];
    }
    
    // }}}
    // sendRequest() {{{

    /**
     * Sends the given request to the remote host, read the response and 
     * returns an HTTP_Response2 instance.
     *
     * @param HTTP_Request2 $request The request to send
     *
     * @return HTTP_Response2
     * @throws HTTP_Connection_Exception
     */
    public function sendRequest(HTTP_Request2 $request)
    {
        $this->request = $request;
        if ($this->request->options['file'] !== null) {
            $this->handle = @fopen($this->request->options['file'], 'w');
            if (!$this->handle) {
                throw new HTTP_Connection_Exception(
                    'Unable to open file ' . $this->request->options['file']
                );
            }
        }
     
        // open connection and notify of the event
        $this->open();
        $this->request->state = HTTP_Request2::STATE_CONNECTED;
        $this->request->notify();

        // send the request and notify of the event
        // Adapters are responsible of sending the events:
        $this->send();
        $this->request->state = HTTP_Request2::STATE_REQUEST_SENT;
        $this->request->notify();

        // read response and notify of the event
        // Adapters are responsible of notifying of the following events in
        // their own implementation:
        // - HTTP_Request2::STATE_RESPONSE_STATUS
        // - HTTP_Request2::STATE_RESPONSE_HEADERS
        // - HTTP_Request2::STATE_RESPONSE_TICK
        $response             = $this->receive();
        $this->request->state = HTTP_Request2::STATE_RESPONSE_RECEIVED;
        $this->request->notify();
 
        // close the connection only if needed and notify of the event
        if ($response->shouldClose()) {
            $this->close();
            $this->request->state = HTTP_Request2::STATE_DISCONNECTED;
            $this->request->notify();
        }
        return $response;
    }

    // }}}
    // open() {{{

    /**
     * Opens a connection to the remote host.
     *
     * @return void
     * @throws HTTP_Connection_Exception
     */
    abstract protected function open();
    
    // }}}
    // send() {{{

    /**
     * Sends the request.
     *
     * @return void
     * @throws HTTP_Connection_Exception
     */
    abstract protected function send();
    
    // }}}
    // receive() {{{

    /**
     * Receive the response and return an HTTP_Response2 instance.
     *
     * @return HTTP_Response2
     * @throws HTTP_Connection_Exception
     */
    abstract protected function receive();
    
    // }}}
    // close() {{{

    /**
     * Close the connection to the remote host.
     *
     * @return void
     * @throws HTTP_Connection_Exception
     */
    abstract protected function close();
    
    // }}}
}
