<?php /***** vi: set encoding=utf-8 expandtab shiftwidth=4: ****************
 *
 *   Copyright : (C) 2011 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


abstract class converter_abstract
{
    abstract function convertFile($file);

    function convertData($data)
    {
        $file = tempnam('.', 'converter');

        Patchwork::writeFile($file, $data);

        $data = $this->convertFile($file);

        unlink($file);

        return $data;
    }
}
