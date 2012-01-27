<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
 *
 *   Copyright : (C) 2012 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


class converter_txt_doc extends converter_abstract
{
    function convertFile($file)
    {
        $file = escapeshellarg($file);
        $file = `antiword -t -w 0 -m UTF-8 {$file}`;

        return FILTER::get($file, 'text');
    }
}
