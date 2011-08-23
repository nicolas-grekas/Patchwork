/****************** vi: set fenc=utf-8 ts=4 sw=4 et: ***********************
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
 **************************************************************************/

function E(data)
{
    E.buffer.push({
        "time" : "",
        "data" : E.clone(data)
    });
}

E.clone = function(data)
{
    if (typeof data === 'string' && -1 != data.indexOf('`'))
    {
        data = 'u`' + data;
    }

    if (typeof data !== 'object' || data === null)
    {
        return data;
    }

    var i, k, clone = {};

    for (i in data)
    {
        k = i;

        if (typeof k === 'string') switch (true)
        {
        case '_' === k:
        case '__maxLength' === k:
        case '__maxDepth' === k:
        case -1 != k.indexOf(':'):
            k = ':' + k;
        }

        if (data[i] === data)
        {
            // TODO: test for farther recursivity
            clone[E.clone(k)] = {'_': '::'}; // TODO: point to the referenced object
        }
        else
        {
            clone[E.clone(k)] = E.clone(data[i]);
        }
    }

    return clone;
}

E.buffer = [];
E.lastTime = E.startTime = new Date/1;