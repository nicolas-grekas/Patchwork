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


class agent_fckeditor_js extends agent
{
    const contentType = 'text/javascript';

    protected $maxage = -1;

    function control() {}

    function compose($o)
    {
        $o->DATA = file_get_contents(patchworkPath('public/__/fckeditor/src/fckeditor.js'));
        return $o;
    }
}
