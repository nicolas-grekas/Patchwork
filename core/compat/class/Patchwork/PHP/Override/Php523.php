<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

namespace Patchwork\PHP\Override;

/**
 * Backports some behaviors introduced in PHP 5.2.3.
 */
class Php523
{
    static function htmlspecialchars($s, $style = ENT_COMPAT, $charset = 'UTF-8', $double_enc = true)
    {
        if ($double_enc || false === strpos($s, '&') || false === strpos($s, ';')) return htmlspecialchars($s, $style, $charset);
        else return htmlspecialchars(html_entity_decode($s, $style, $charset), $style, $charset);
    }

    static function htmlentities($s, $style = ENT_COMPAT, $charset = 'UTF-8', $double_enc = true)
    {
        if ($double_enc || false === strpos($s, '&') || false === strpos($s, ';')) return htmlentities($s, $style, $charset);
        else return htmlentities(html_entity_decode($s, $style, $charset), $quote_style, $charset);
    }
}
