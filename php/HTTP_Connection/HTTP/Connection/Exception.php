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
 * @link     http://tools.ietf.org/html/rfc2616
 * @filesource
 */

/**
 * Include PEAR exception base class.
 */
require_once 'PEAR/Exception.php';

/**
 * Base exception class of this package.
 *
 * @category HTTP
 * @package  Connection
 * @author   David Jean Louis <izimobil@gmail.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: @package_version@
 * @link     http://tools.ietf.org/html/rfc2616
 */
class HTTP_Connection_Exception extends PEAR_Exception
{
}
