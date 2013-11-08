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
 * The SelfLowerCaser parser backports `self` and `parent` case-insensitivity introduced in PHP 5.5.
 */
class SelfLowerCaser extends Parser
{
    protected function getTokens($code, $is_fragment)
    {
        $tokens = parent::getTokens($code, $is_fragment);

        if ($this->targetPhpVersionId < 50500 && preg_match('/(?:parent|self)(?-i)(?<!parent|self)/i', $code))
        {
            $i = 0;
            while (isset($tokens[++$i]))
                if (T_STRING === $tokens[$i][0] && ('parent' === $k = strtolower($tokens[$i][1]) or 'self' === $k))
                    $tokens[$i][1] = $k;
        }

        return $tokens;
    }
}
