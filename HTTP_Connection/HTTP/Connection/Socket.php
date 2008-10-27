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
 * @link     http://pear.php.net/package/HTTP_Connection
 * @link     http://tools.ietf.org/html/rfc2616
 * @filesource
 */

/**
 * Include HTTP_Connection abstract class.
 */
require_once 'HTTP/Connection.php';

/**
 * HTTP connection class.
 *
 * Part of this code is adapted from Zend_Framework:
 * Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 *
 * @category HTTP
 * @package  Connection
 * @author   David Jean Louis <izimobil@gmail.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: @package_version@
 * @link     http://tools.ietf.org/html/rfc2616
 */
class HTTP_Connection_Socket extends HTTP_Connection
{
    // properties {{{

    /**
     * The socket resource.
     *
     * @var resource $socket
     */
    protected $socket = null;

    /**
     * The host/port the socket is currently connected to.
     *
     * @var string $currentHost
     */
    protected $currentHost = null;
    
    // }}}
    // open() {{{

    /**
     * Opens the socket connection.
     *
     * @return void
     * @throws HTTP_Connection_Exception
     */
    protected function open()
    {
        $hostPort = $this->request->host . ':' . $this->request->port;

        // If we are already connected, just return
        if ($this->currentHost == $hostPort) {
            return;
        } else if ($this->currentHost !== null) {
            // disconnect from previous host:port
            $this->close();
        }

        // create context
        $ctx = stream_context_create();

        // handle ssl connection
        if ($this->request->isSecure()) {
            $scheme = 'ssl://';
            foreach ($this->request->options['ssl_options'] as $k => $v) {
                if ($v === null) {
                    continue;
                }
                if (!stream_context_set_option($ctx, 'ssl', $k, $v)) {
                    throw new HTTP_Connection_Exception(
                        'Unable to set ssl option ' . $k
                    );
                }
            }
        } else {
            $scheme = 'tcp://';
        }
        $flags = STREAM_CLIENT_CONNECT;
        if ($this->request->options['persistent_connection']) {
            $flags |= STREAM_CLIENT_PERSISTENT;
        }

        // Open socket connection
        $this->socket = @stream_socket_client($scheme . $hostPort,
            $errno,
            $errstr,
            (int) $this->request->options['connection_timeout'],
            $flags,
            $ctx);

        // Handle errors
        if (!$this->socket) {
            $this->close();
            throw new HTTP_Connection_Exception(
                'Unable to connect to ' . $hostPort . 
                ' (Error #' . $errno . ': ' . $errstr . ')'
            );
        }
        
        // Set the stream timeout
        if (!stream_set_timeout($this->socket, 
            (int) $this->request->options['request_timeout'])) {
            throw new HTTP_Connection_Exception(
                'Unable to set the request timeout'
            );
        }

        $this->currentHost = $hostPort;
    }
    
    // }}}
    // send() {{{

    /**
     * Writes the request to the socket.
     *
     * @return void
     * @throws HTTP_Connection_Exception
     */
    protected function send()
    {
        // write status line, headers and body
        $this->_write((string) $this->request);

        $ctypeTokens = explode(';', $this->request->headers['content-type']);
        $ctype       = $ctypeTokens[0];
        // write files content if any
        if ($ctype == 'multipart/form-data') {
            foreach ($this->request->getFileUploads() as $f) {
                $fh = fopen($f['path'], 'r');
                $this->_write($this->request->getMultipartFileHeader($f));
                while ($line = fgets($fh)) {
                    $this->_write($line);
                }
                fclose($fh);
                $this->_write("\r\n--".$this->request->getBoundary()."--\r\n");
            }
        } else if ($file = $this->request->getFile()) {
            $fh = fopen($file, 'r');
            while ($line = fgets($fh)) {
                $this->_write($line);
            }
        }
    }
    
    // }}}
    // receive() {{{

    /**
     * Reads the response from the socket and returns the response.
     *
     * @return HTTP_Response2
     * @throws HTTP_Connection_Exception
     */
    protected function receive()
    {
        $i = 0;
        include_once 'HTTP/Response2.php';
        $response = new HTTP_Response2();

        // read status and headers
        $headerStr = '';
        while ($line = fgets($this->socket)) {
            if (!trim($line)) {
                // we're done with the headers
                break;
            }
            if ($i == 0) {
                // parse status and http version
                list($version, $status) = HTTP_Response2::parseStatusLine($line);
                $this->request->state   = HTTP_Request2::STATE_RESPONSE_STATUS;
                $this->request->data    = $status;
                $this->request->notify();
                $response->code        = $status;
                $response->httpVersion = $version;
            } else {
                $headerStr .= $line;
            }
            $i++;
        }
        // parse headers
        $headers              = HTTP_Response2::parseHeaders($headerStr);
        $this->request->state = HTTP_Request2::STATE_RESPONSE_HEADERS;
        $this->request->data  = $headers;
        $this->request->notify();
        $response->headers = $headers;

        // response to head request does not have body
        if ($this->request->method == HTTP_Request2::METHOD_HEAD) {
            return $response;
        }

        // read body
        // If we got a 'transfer-encoding: chunked' header
        $enc = $response->headers['transfer-encoding'];
        if ($enc == 'chunked') {
            do {
                $line  = @fgets($this->socket);
                $chunk = $line;

                // Figure out the next chunk size
                $chunksize = trim($line);
                if (!ctype_xdigit($chunksize)) {
                    $this->close();
                    throw new HTTP_Connection_Exception(
                        'Invalid chunk size "' . $chunksize 
                        . '" unable to read chunked body');
                }

                // Convert the hexadecimal value to plain integer
                $chunksize = hexdec($chunksize);
                
                // Read chunk
                $left = $chunksize;
                while ($left > 0) {
                    $line   = @fread($this->socket, $left);
                    $chunk .= $line;
                    $left  -= strlen($line);
                    
                    // Break if the connection ended prematurely
                    if (feof($this->socket)) {
                        break;
                    }
                }
                $chunk .= @fgets($this->socket);
                if ($this->request->options['store_response_body']) {
                    $response->body .= $chunk;
                }
                $this->request->state = HTTP_Request2::STATE_RESPONSE_TICK;
                $this->request->data  = $chunk;
                $this->request->notify();
            } while ($chunksize > 0);
                
        } else if ($enc !== null) {
            throw new HTTP_Connection_Exception(
                'Cannot handle "' . $enc . '" transfer encoding'
            );
        } else if ($response->headers['content-length'] !== null) {
            $clength = $headers['content-length'];
            $chunk   = '';
            while ($clength > 0) {
                $amount   = $clength > 8192 ? 8192 : $clength;
                $chunk    = @fread($this->socket, $amount);
                $clength -= $amount;
                if ($this->request->options['store_response_body']) {
                    $response->body .= $chunk;
                }
                $this->request->state = HTTP_Request2::STATE_RESPONSE_TICK;
                $this->request->data  = $chunk;
                $this->request->notify();
                // Break if the connection ended prematurely
                if (feof($this->socket)) {
                    break;
                }
            }
        } else {
            // Fallback: just read the response until EOF
            // most servers return a content-length so this should not be a big
            // issue...
            while (($chunk = @fread($this->socket, 8192)) !== false) {
                if ($this->request->options['store_response_body']) {
                    $response->body .= $chunk;
                }
                $this->request->state = HTTP_Request2::STATE_RESPONSE_TICK;
                $this->request->data  = $chunk;
                $this->request->notify();
                if (feof($this->socket)) {
                    break;
                }
            }
            // keep-alive won't work any way... close the connection
            $this->close();
        }

        return $response;
    }
    
    // }}}
    // close() {{{

    /**
     * Close the socket connection.
     *
     * @return void
     */
    protected function close()
    {
        if (is_resource($this->socket)) {
            @fclose($this->socket);
        }
        $this->socket      = null;
        $this->currentHost = null;
    }
    
    // }}}
    // _write() {{{

    /**
     * Write to the socket the given string or throws an exception.
     *
     * @param string $str The string to write
     *
     * @return void
     */
    private function _write($str)
    {
        //echo $str;
        if (!@fwrite($this->socket, $str)) {
            throw new HTTP_Connection_Exception('Unable to write to the socket');
        }
    }
    
    // }}}
}
