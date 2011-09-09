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
    if (typeof data.cloneForDebug === 'function')
    {
        return data.cloneForDebug();
    }

    if (typeof data === 'string' && -1 != data.indexOf('`'))
    {
        data = 'u`' + data;
    }

    if (typeof data !== 'object' || data === null)
    {
        return data;
    }

    var k,
        i = Object.prototype.toString.apply(data).match(/^\[object ((Object)|(.+))\]$/),
        clone = {'_': (i[3] || '') + ':'};

    for (i in data)
    {
        if (i in E.hiddenList) continue;

        k = i;

        if (typeof k === 'string') switch (true)
        {
        case '_' === k:
        case '__maxDepth' === k:
        case '__maxLength' === k:
        case '__refs' === k:
        case -1 != k.indexOf(':'):
            k = ':' + k;
        }

        if (data[i] === data)
        {
            // TODO: test for farther recursivity and add ref indexes
            i = Object.prototype.toString.apply(data[i]).match(/^\[object ((Object)|(.+))\]$/);
            clone[E.clone(k)] = {'_': '0:' + (i[3] || '')};
        }
        else
        {
            clone[E.clone(k)] = E.clone(data[i]);
        }
    }

    return clone;
}

E.hiddenList = {
    '_AdblockData' : 1,
    'ownerDocument' : 1,
    'top' : 1,
    'parent' : 1,
    'parentNode' : 1,
    'document' : 1
};

E.buffer = [];
E.lastTime = E.startTime = new Date/1;
