<?php

namespace Iteo\DoctrineExtensions;

/**
 * Common package exception interface to allow
 * users of caching only this package specific
 * exceptions thrown
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface Exception
{
    /**
     * Following best practices for PHP5.3 package exceptions.
     * All exceptions thrown in this package will have to implement this interface
     *
     * @link http://wiki.php.net/pear/rfc/pear2_exception_policy
     */
}
