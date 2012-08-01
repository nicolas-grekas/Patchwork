/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

"use strict";

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
        case '__refs' === k:
        case '__cutBy' === k:
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

!function(win)
{
    win.patchworkDebugger = {

        start: function(b)
        {
            base = b;

            this.attachKeyPressHandler(win);

            !function onBodyExists()
            {
                if (!insertDebugConsole) {}
                else if (doc.body) insertDebugConsole();
                else setTimeout(onBodyExists, 20);
            }();
        },

        stop: function()
        {
            E('Rendering time: ' + (+new Date - E.startTime) + ' ms');

            if (insertDebugIframe) insertDebugIframe();

            setTimeout(function()
            {
                var f = doc.getElementById('debugFrame'),
                    s = doc.getElementById('debugStore');

                if (f && s && s.value) f.style.visibility = s.value;
            }, 0);
        },

        attachKeyPressHandler: function(win)
        {
            var doc = win.document;
            if (doc.addEventListener) doc.addEventListener('keydown', keypressHandler(win), false);
            else if (doc.attachEvent) doc.attachEvent('onkeydown', keypressHandler(win));
        }
    };

    var base,
        doc = win.document,
        debugWin = doc.createElement('div'),
        debugIframe = doc.createElement('iframe'),
        debugFrame;

    var keypressHandler = function(w)
    {
        return function(e)
        {
            e = e || w.event;

            // TODO: catch key strokes inside the debug console

            var refresh = 0;

            switch (e.keyCode)
            {
            case 82: // R key
                if (e.ctrlKey) refresh = e.shiftKey ? 2 : 1;
                break;

            case 116: // F5 key
                refresh = e.ctrlKey ? 2 : 1;
                break;

            case 120: // F9 key
                debugClick();
                break;
            }

            if (refresh)
            {
                try {e.keyCode = 10000 + e.keyCode;} // Remap for IE
                catch (x) {}

                if (e.preventDefault) e.preventDefault();
                e.returnValue = false;

                try
                {
                    if (win.stop) win.stop();
                    else if (doc.execCommand) doc.execCommand('stop');
                }
                catch (x) {}

                e = new Image;
                e.onload = e.onerror = function() {win.location.reload(2 === refresh);};
                e.src = base + '?p:=debug:' + (2 === refresh ? 'deepReset' : 'quickReset');

                return false;
            }
        }
    }

    debugWin.id = 'debugWin';
    debugWin.innerHTML = ''
        + '<div style="position:fixed;_position:absolute;top:0;right:0;z-index:254;background-color:white;visibility:hidden;width:100%; height: 50%" id="debugFrame"></div>'
        + '<div style="position:fixed;_position:absolute;top:0;right:0;z-index:255;font-family:arial;font-size:9px;background-color:blue;color:white;text-decoration:none;border:0;cursor:pointer;">Debug</div>'
        + '<style>@media print { #debugWin {display:none;} }</style>';

    debugIframe.style.cssText = 'width: 100%; height: 100%';

    var insertDebugConsole = function()
    {
        insertDebugConsole = false;
        (doc.body || doc.documentElement).appendChild(debugWin);
        debugFrame = debugWin.firstChild;

        var n = debugFrame.nextSibling;
        if (n.addEventListener) n.addEventListener('click', debugClick, false);
        else if (n.attachEvent) n.attachEvent('onclick', debugClick);
    }

    var insertDebugIframe = function()
    {
        insertDebugIframe = false;
        if (insertDebugConsole) insertDebugConsole();
        debugFrame.appendChild(debugIframe);

        var d = debugIframe.contentWindow.document;
        d.open();
        d.write('<body onload="location.replace(&quot;' + base + '?p:=debug:stop' + '&quot;)">');
        d.close();
    }

    var debugClick = function()
    {
        var s = doc.getElementById('debugStore');

        debugFrame.style.visibility = 'hidden' == debugFrame.style.visibility ? 'visible' : 'hidden';
        if (s) s.value = debugFrame.style.visibility;
    }
}(window);
