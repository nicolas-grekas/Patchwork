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
 * The ExceptionNotifier parser inserts an error notice at every catch block for easier debugging.
 */
class ExceptionNotifier extends Parser
{
    protected

    $errorHandler = 'null',
    $errorMessage = 'Caught',
    $callbacks = array(
        'tagCatch' => T_CATCH,
        'tagThrow' => T_THROW,
    ),
    $catchCallbacks = array(
        'tagCatchClass' => T_TYPE_HINT,
        'tagCatchVar'   => T_VARIABLE,
        'tagCatchBlock' => '{',
    ),
    $throwLevel = 0,
    $throwLevels = array(),
    $throwCallbacks = array(
        'tagOpenCurly'  => '{',
        'tagCloseCurly' => '}',
        'tagThrowEnd' => array(T_CLOSE_TAG, ';'),
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
        if (! $this->throwLevel || 2 < $this->throwLevel - end($this->throwLevels))
        {
            $this->register($this->catchCallbacks);
        }
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

    protected function tagThrow(&$token)
    {
        if (! $this->throwLevel || 2 < $this->throwLevel - end($this->throwLevels))
        {
            $this->throwLevel or $this->register($this->throwCallbacks);
            $this->throwLevels[] = $this->throwLevel;

            return $this->unshiftTokens('{', array(T_TRY, 'try'), '{', $token);
        }
    }

    protected function tagOpenCurly(&$token)
    {
        ++$this->throwLevel;
    }

    protected function tagCloseCurly(&$token)
    {
        if (--$this->throwLevel === end($this->throwLevels))
        {
            array_pop($this->throwLevels);
            if (! $this->throwLevel) $this->unregister($this->throwCallbacks);
            else $this->register(array('tagThrowEnd' => array(T_CLOSE_TAG, ';')));
        }
    }

    protected function tagThrowEnd(&$token)
    {
        if (2 === $this->throwLevel - end($this->throwLevels))
        {
            if (T_CLOSE_TAG === $token[0]) return $this->unshiftTokens(';');
            $this->unregister(array('tagThrowEnd' => array(T_CLOSE_TAG, ';')));

            $code = "\\user_error('Thrown '.\\get_class($\x9D).' $\x9D');";

            if ('null' !== $this->errorHandler)
            {
                $code = "\\set_error_handler($this->errorHandler);{$code}\\restore_error_handler();";
            }

            $this->unshiftCode("}catch(\\Exception $\x9D){{$code}throw $\x9D;}}");
        }
    }
}
