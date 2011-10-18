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
    if (data && typeof data.cloneForDebug === 'function')
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
E.lastTime = E.startTime = +new Date;

function patchworkDebugConsoleProlog(base)
{
    var d = document;

    patchworkDebugConsoleProlog.base = base;

    !function onBodyExists()
    {
        if (d.body)
        {
            var f = d.createElement('div');

            f.id = 'debugWin';
            f.innerHTML = '<style>@media print { #debugWin {display:none;} }</style>'
                + '<div style="position:fixed;_position:absolute;top:0;right:0;z-index:254;background-color:white;visibility:hidden;width:100%; height: 50%" id="debugFrame"></div>'
                + '<div style="position:fixed;_position:absolute;top:0;right:0;z-index:255;font-family:arial;font-size:9px"><a href="javascript:void(patchworkConsoleClick());" style="background-color:blue;color:white;text-decoration:none;border:0;" id="debugLink">Debug</a>';

            d.body.appendChild(f);
        }
        else setTimeout(onBodyExists, 20);
    }();
}

function patchworkDebugConsoleConclusion(src)
{
    E('Rendering time: ' + (+new Date - E.startTime) + ' ms');

    setTimeout(function()
    {
        var f = document.getElementById('debugFrame'), s = document.getElementById('debugStore');
        if (f && s && s.value) f.style.visibility = s.value;
    }, 0);

    // The following code can be moved inside onBodyExists.
    // Events should then be streamed to the browser until "onload".

    var d = document, f = d.createElement('iframe');
    f.style.cssText = 'width: 100%; height: 100%';
    d.getElementById('debugFrame').appendChild(f);

    d = f.contentWindow.document;
    d.open();
    d.write('<body onload="window.location.replace(&quot;' + patchworkDebugConsoleProlog.base + '?p:=debug:stop' + '&quot;)"><input type="hidden" name="debugStore" id="debugStore" value="">');
    d.close();
}

function patchworkConsoleClick()
{
    var f = document.getElementById('debugFrame'), s = document.getElementById('debugStore');

    if (f)
    {
        f.style.visibility = 'hidden' == f.style.visibility ? 'visible' : 'hidden';
        if (s) s.value = f.style.visibility;
    }
}

!function()
{
    var d = document;

    function F5(e)
    {
        e = e || window.event;

        var refresh = 0;

        switch (e.keyCode)
        {
        case 82: // R key
            if (e.ctrlKey) refresh = e.shiftKey ? 2 : 1;
            break;

        case 116: // F5 key
            refresh = e.ctrlKey ? 2 : 1;
            break;
        }

        if (refresh)
        {
            e.keyCode = 10000 + e.keyCode; // Remap for IE
            if (e.preventDefault) e.preventDefault();
            e.returnValue = false;

            location.reload(2 === refresh); // Currently this is default browser behavior, but the idea here is to send some cache reset event to the server

            return false;
        }
    }

    if (d.addEventListener) d.addEventListener('keydown', F5, false);
    else if (d.attachEvent ) d.attachEvent('onkeydown', F5);
}();
