<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

namespace Patchwork\PHP\Shim;

/**
 * Adds 5.3 interface to 5.2 exceptions
 */
class Exception extends \Exception
{
/**/if (PHP_VERSION_ID < 50300):
    private $previous;

    function __construct($message = null, $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code);
        $this->previous = $previous;
    }

    final function getPrevious()
    {
        return $this->previous;
    }
/**/endif;
}
