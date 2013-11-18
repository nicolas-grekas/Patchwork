<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2013 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

namespace Patchwork\PHP\Parser;

use Patchwork\PHP\Parser;

/**
 * The CatchNotifier parser inserts an error notice at every catch block for easier debugging.
 */
class CatchNotifier extends Parser
{
    protected

    $errorHandler = 'null',
    $errorMessage = 'Caught',
    $callbacks = array(
        'tagCatch' => T_CATCH,
    ),
    $catchCallbacks = array(
        'tagCatchClass' => T_TYPE_HINT,
        'tagCatchVar'   => T_VARIABLE,
        'tagCatchBlock' => '{',
    ),
    $nsResolved,
    $dependencies = array('NamespaceInfo' => 'nsResolved');


    function __construct(parent $parent, $error_handler)
    {
        parent::__construct($parent);

        if ($error_handler)
        {
            if (is_array($error_handler)) $error_handler = implode('::', $error_handler);
            $this->errorHandler = self::export($error_handler);
        }
    }

    protected function tagCatch(&$token)
    {
        $this->register($this->catchCallbacks);
    }

    protected function tagCatchClass(&$token)
    {
        $this->errorMessage .= ' ' . $this->nsResolved;
    }

    protected function tagCatchVar(&$token)
    {
        $this->errorMessage .= ' ' . $token[1];
    }

    protected function tagCatchBlock(&$token)
    {
        $code = '\user_error(' . self::export($this->errorMessage) . ');';

        if ('null' !== $this->errorHandler)
        {
            $code = "\\set_error_handler($this->errorHandler);{$code}\\restore_error_handler();";
        }

        $this->unshiftCode($code);

        $this->errorMessage = 'Caught';
        $this->unregister($this->catchCallbacks);
    }
}
