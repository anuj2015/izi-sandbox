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
 * @link     http://tools.ietf.org/html/rfc2616
 * @filesource
 */

/**
 * A case-insensitive container for message headers that support array and
 * object access.
 *
 * @category HTTP
 * @package  Common
 * @author   David Jean Louis <izimobil@gmail.com> 
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: @package_version@
 * @link     http://tools.ietf.org/html/rfc2616
 */
class HTTP_Common_Headers implements Iterator, ArrayAccess, Countable
{
    // properties {{{

    /**
     * Style to use when iterating over the array.
     *
     * @var string $iterationStyle Can be 'camelcase' (default) or 'lowercase'
     */
    public $iterationStyle = 'camelcase';

    /**
     * Array listing headers qualified of "General headers" by RFC 2616.
     * ThConnectionis is used for sorting purposes.
     *
     * @var array $generalHeaders Array of general headers keys
     */
    public static $generalHeaders = array(
        'Cache-Control',
        'Connection',
        'Date',
        'Pragma',
        'Trailer',
        'Transfer-Encoding',
        'Upgrade',
        'Via',
        'Warning',
    );

    /**
     * The headers array with lowercase keys.
     *
     * @var array $lowercase
     */
    protected $lowercase = array();

    /**
     * The headers array with CamelCase keys.
     *
     * @var array $camelcase
     */
    protected $camelcase = array();

    // }}}
    // camelize() {{{

    /**
     * Camelize a header key.
     * 
     * @param string $key Key of the array
     * 
     * @return string the normalized key
     */
    protected function camelize($key)
    {
        if (strpos($key, '-') === false) {
            return ucfirst($key);
        }
        list($p1, $p2) = explode('-', strtolower($key));
        return ucfirst($p1) . '-' . ucfirst($p2);
    }

    // }}}
    // __construct() {{{

    /**
     * Constructor, takes the headers to provide access to.
     * 
     * @param array $headers The associative array of headers (name => value)
     * 
     * @return void
     */
    public function __construct(array $headers = array())
    {
        foreach ($headers as $key => $value) {
            $this[$key] = $value;
        }
    }

    // }}}
    // __get() {{{

    /**
     * Magic getter for object access.
     * 
     * @param string $name Header name
     * 
     * @return mixed
     */
    public function __get($name)
    {
        return $this[$name];
    }

    // }}}
    // __set() {{{

    /**
     * Magic setter for object access.
     * 
     * @param string $name  Header name
     * @param string $value Header value
     * 
     * @return void
     */
    public function __set($name, $value)
    {
        $this[$name] = $value;
    }

    // }}}
    // __isset() {{{

    /**
     * Magic isset for object access.
     * 
     * @param string $name Header name
     * 
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this[$name]);
    }

    // }}}
    // __unset() {{{

    /**
     * Magic unset for object access.
     * 
     * @param string $name Header name
     * 
     * @return void
     */
    public function __unset($name)
    {
        unset($this[$name]);
    }

    // }}}
    // __toString() {{{

    /**
     * Returns the string representation of the headers, headers are sorted 
     * according to RFC 2616 "good practice" recommandation.
     * 
     * @return string The headers string
     */
    public function __toString()
    {
        $this->sort();
        $ret = array();
        foreach ($this->{$this->iterationStyle} as $key => $val) {
            $ret[] = $key . ': ' . $val;
        }
        return implode("\r\n", $ret);
    }

    // }}}
    // offsetExists() {{{

    /**
     * ArrayAccess::offsetExists() implementation.
     * 
     * @param string $key Key of the array
     * 
     * @return bool
     */
    public function offsetExists($key)
    {
        return array_key_exists(strtolower($key), $this->lowercase);
    }

    // }}}
    // offsetGet() {{{

    /**
     * ArrayAccess::offsetGet() implementation.
     * 
     * @param string $key Key of the array
     * 
     * @return mixed
     */
    public function offsetGet($key)
    {
        $key = strtolower($key);
        if (array_key_exists($key, $this->lowercase)) {
            return $this->lowercase[$key];
        }
        return null;
    }

    // }}}
    // offsetSet() {{{

    /**
     * ArrayAccess::offsetSet() implementation.
     * 
     * @param string $key   Key of the array
     * @param mixed  $value Value to set
     * 
     * @return void
     */
    public function offsetSet($key, $value)
    {
        if (is_array($value)) {
            $value = implode(',', $value);
        }
        $lk = strtolower($key);
        $ck = $this->camelize($key);
        if (array_key_exists($lk, $this->lowercase)) {
            $value = $this->lowercase[$lk] . ',' . $value;
        }
        $this->lowercase[$lk] = $value;
        $this->camelcase[$ck] = $value;
    }

    // }}}
    // offsetUnset() {{{

    /**
     * ArrayAccess::offsetUnset() implementation.
     * 
     * @param string $key Key of the array
     * 
     * @return void
     */
    public function offsetUnset($key)
    {
        unset($this->lowercase[strtolower($key)]);
        unset($this->camelcase[$this->camelize($key)]);
    }

    // }}}
    // count() {{{

    /**
     * Countable::count() implementation.
     * 
     * @return int The number of headers
     */
    public function count()
    {
        return count($this->lowercase);
    }

    // }}}
    // current() {{{

    /**
     * Iterator::current() implementation.
     * 
     * @return mixed
     */
    public function current()
    {
        return current($this->{$this->iterationStyle});
    }

    // }}}
    // key() {{{

    /**
     * Iterator::key() implementation.
     * 
     * @return string
     */
    public function key() 
    {
        return key($this->{$this->iterationStyle});
    }

    // }}}
    // next() {{{

    /**
     * Iterator::next() implementation.
     * 
     * @return mixed
     */
    public function next()
    {
        return next($this->{$this->iterationStyle});
    }

    // }}}
    // rewind() {{{

    /**
     * Iterator::rewind() implementation.
     * 
     * @return void
     */
    public function rewind()
    {
        reset($this->{$this->iterationStyle});
    }

    // }}}
    // valid() {{{

    /**
     * Iterator::valid() implementation.
     * 
     * @return bool
     */
    public function valid()
    {
        return (boolean) current($this->{$this->iterationStyle});
    }

    // }}}
    // sort() {{{

    /**
     * Sort headers according to RFC 2616 "good practice" recommandation.
     * 
     * @return bool
     */
    public function sort()
    {
        uksort($this->lowercase, array($this, '_sort'));
        uksort($this->camelcase, array($this, '_sort'));
    }

    // }}}
    // _sort() {{{

    /**
     * uksort callback function.
     *
     * @param string $a An header key
     * @param string $b An header key
     * 
     * @return bool
     */
    private function _sort($a, $b)
    {
        $ag = in_array($this->camelize($a), self::$generalHeaders);
        $bg = in_array($this->camelize($b), self::$generalHeaders);

        if ($ag && $bg) {
            return strcasecmp($a, $b);
        }
        if ($ag) {
            return -1;
        }
        if ($bg) {
            return 1;
        }
        return strcasecmp($a, $b);
    }

    // }}}
}
