<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
 *
 *   Copyright : (C) 2012 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/lgpl.txt GNU/LGPL
 *
 *   This library is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Lesser General Public
 *   License as published by the Free Software Foundation; either
 *   version 3 of the License, or (at your option) any later version.
 *
 ***************************************************************************/

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
