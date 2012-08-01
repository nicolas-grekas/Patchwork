/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

"use strict";

// TODO Patchwork's debug console
//
// Some information is present in the event stream that is currently not displayed:
// - which request an event links to
// - timing information for each event in each request
// - memory information for each event in each request
// - requests timeline
//
// Current display can be enhanced:
// - specialize errors with an icon, file, line, each separated in its own column
// - specialize other types of event
// - shorten single line compact mode for performance
// - draw events only on first console opening for performance
// - warn more agressively for critical errors
// - add ability to resize the console window
// - events could be streamed to the console while the page is beeing generated
//
// More data could also be collected:
// - client side: timing information
// - server dumps: file and line
//
// Many more ideas are welcomed

parent.patchworkDebugger.attachKeyPressHandler(window);

var patchworkConsole = (function(doc)
{
    // Creates the div that contains the console

    var div = doc.createElement('DIV');
    div.className = 'console';

    // Define the main console object

    var console = {
        div: div,
        count: 0,
        init: function()
        {
        },
        requests: {},
        tab: function(type, label)
        {
            this.type = type;
            if (label) this.label = label;
            console.tabs[type] = this;

            var div = doc.createElement('DIV');
            div.id = 'events-' + this.type;
            div.className = 'events ' + this.type;
            console.div.appendChild(div);
            this.div = div;

            div = doc.createElement('A');
            div.href = '#' + 'events-' + this.type;
            div.className = 'empty event-title ' + this.type;
            div.innerHTML = this.label;
            console.titlesDiv.appendChild(div);
            this.titleDiv = div;
        },
        tabs: {},
        log: function(type, data, token)
        {
            var t = this.tabs[type] || this.tabs['*'];

            if (token && !this.requests[token] && data.globals)
            {
                this.requests[token] = {
                    URI: data.globals._SERVER.REQUEST_URI,
                    time: data.time,
                    mem: data.mem,
                    globals: data.globals,
                    patchwork: data.patchwork,
                };
            }

            t.log(type, data, token);
        }
    };

    // Define base layout

    div = doc.createElement('DIV');
    div.className = 'event-tabs';
    console.div.appendChild(div);
    console.titlesDiv = div;

    // Define the prototype of console.tab

    div = console.tab.prototype;
    div.div = false;
    div.label = 'Unsorted events';
    div.count = 0;
    div.init = function()
    {
        this.div.innerHTML = '<h3>' + this.label + ' <span class="count"></span></h3>';
    };
    div.log = function(type, data, token)
    {
        console.count++ || console.init();
        this.count++ || this.init();

        var div = doc.createElement('PRE');
        div.className = 'event';
        this.populate(div, data, token);
        this.div.appendChild(div);
        this.div.firstChild.firstChild.nextSibling.innerHTML = '(' + this.count + ')';
        this.titleDiv.innerHTML = this.div.firstChild.innerHTML;
        if (0 == this.titleDiv.className.indexOf('empty ')) this.titleDiv.className = this.titleDiv.className.substr(6);
    };
    div.populate = function(div, data, token)
    {
        // TODO: icons for error levels, profiling info, request context...

        var filter, patchwork;

        if (token && console.requests[token])
        {
            patchwork = console.requests[token].patchwork;

            filter = function(data, htmlEncode)
            {
                if (data.indexOf && 0 < data.indexOf(patchwork.paths['0']))
                {
                    data = data.split(patchwork.paths['0']);
                    data[0] = htmlEncode(data[0]);

                    for (var m, i = 1; i < data.length; ++i)
                    {
                        if (data[i].indexOf('zcache.php') && (m = /^\.([^\\\/]+)\.[01]([0-9]+)(-?)\.zcache\.php(.*)/.exec(data[i])))
                        {
                            data[i] = '<span title="' + htmlEncode(patchwork.paths['' + (patchwork.level - (m[3] + m[2]))])
                                + '">.../' + m[1].replace('_', '/') + '</span>' + htmlEncode(m[4]);
                        }
                        else
                        {
                            data[i] = '<span title="' + htmlEncode(patchwork.paths['0']) + '">.../</span>' + htmlEncode(data[i]);
                        }
                    }

                    return data.join('');
                }
                else return htmlEncode(data);
            };
        }

        div.innerHTML = htmlizeEvent(data.data, data.__refs, filter);
    }

    // Define defaults tabs

    div = new console.tab('php-error', 'PHP Errors');

    div.log = function(type, data, token)
    {
        if (data.data.level)
        {
            var level = data.data.level.split('/'); // TODO: report more info about data.data.level
            data.data.level = undefined; // Tag as do not display
            if (!(level[0] & level[1])) return console.tabs['silenced-php-error'].log(type, data, token);
        }

        console.tab.prototype.log.call(this, type, data, token);
    }

    div = new console.tab('server-dump', 'E (PHP)');
    div = new console.tab('sql', 'SQL');
    div = new console.tab('client-dump', 'E (JavaScript)');
    div = new console.tab('silenced-php-error', 'Silenced PHP Errors');

    div.populate = function(div, data, token)
    {
        div.className += ' silenced';
        console.tab.prototype.populate.call(this, div, data, token);
    }

    div = new console.tab('debug-shutdown', 'Requests');

    div.log = function(type, data, token)
    {
        data.data = console.requests[token] || {};
        data.data.time = data.time;
        data.data.mem = data.mem;
        console.tab.prototype.log.call(this, type, data, token);
    }

    div = new console.tab('*');

    doc.body.appendChild(console.div);

    return console;
}(document));

function htmlizeEvent(data, refs, filter)
{
    var iRefs = {},
        depth,
        counter,
        buffer = [],
        span = document.createElement('SPAN');

    refs = refs || {};
    for (counter in refs)
        for (depth in refs[counter])
            iRefs[refs[counter][depth]] = counter;

    depth = 1;
    counter = data && data._ ? parseInt(data._) - 1 : 0;

    filter = filter || htmlEncode;

    function htmlEncode(s)
    {
        span.innerText = span.textContent = s;
        return span.innerHTML;
    }

    function push(data, tags, title)
    {
        if (title && title.length) tags += '" title="' + title.join(', ');
        buffer.push('<span class="' + tags + '">' + filter(data, htmlEncode) + '</span>');
    }

    function htmlizeData(data, tags, title)
    {
        var i, e, t, b;

        ++counter;
        title = title || [];
        tags = tags || '';

        if (refs[counter]) push('#' + counter, 'ref target');
        else if (iRefs[counter]) push('r' + iRefs[counter], 'ref handle');
        else if (iRefs[-counter]) push('R' + iRefs[-counter], 'ref alias');

        switch (true)
        {
        case null === data: data = 'null';
        case true === data:
        case false === data:
        default:
            push(data, 'const' + tags, title);
            break;

        case 'string' === typeof data:
            if ('' === data)
            {
                title.push('Empty string');
                push('', 'string empty' + tags, title);
                return;
            }

            i = data.indexOf('`');
            if (-1 == i) data = ['u', data];
            else data = [data.substr(0, i), data.substr(i+1)];
            i = data[0].charAt(data[0].length - 1);

            switch (i)
            {
                case 'R':
                case 'r': return;
                case 'n': push(data[1], 'const' + tags, title); return;
                case 'b': tags += ' bin'; title.push('Binary');
                case 'u': tags = 'string' + tags;
            }

            i = parseInt(data[0]);

            title.push('Length: ' + (0 < i ? i : data[1].length));

            data = data[1].split(/\r?\n/g);

            if (data.length > 1)
            {
                for (e = 0; e < data.length; ++e)
                {
                    buffer.push('\n' + new Array(depth + 2).join(' '));
                    push(data[e], tags + ('' === data[e] ? ' empty' : ''), title);
                }
            }
            else push(data[0], tags, title);

            if (0 < i) push('...', 'cut');

            break;

        case 'object' === typeof data:

            if (1 === depth)
            {
                buffer.push('<a onclick="arrayToggle(this)"> ⊞ </a><span class="key"></span>');
                buffer.push('<span class="array-compact">');
            }

            b = ['[', ']'];
            t = data['_'] ? data['_'].split(':') : [0];

            if (undefined === t[1]) {}
            else if (undefined === t[2])
            {
                t.isObject = 1;
                if ('stdClass' !== t[1]) push(t[1], 'class');
                b = ['{', '}'];
            }
            else if ('resource' === t[1])
            {
                t.isResource = 1;
                push('resource:' + t[2], 'class');
            }
            else if ('array' === t[1])
            {
                t.isArray = 1;
            }

            e = 0;
            for (i in data) if ('_' !== i && '__cutBy' !== i && '__refs' !== i && 2 === ++e) break;

            if (!e)
            {
                buffer.push(b[0]);
                if (data.__cutBy) push('...', 'cut', ['Cut by ' + data.__cutBy]);
                buffer.push(b[1]);
                return;
            }

            depth += 2;
            buffer.push(b[0]);
            b[2] = 1 === e ? 'expanded' : 'compact';

            for (i in data)
            {
                if ('_' === i || '__cutBy' === i || '__refs' === i) continue;
                if (undefined === data[i] && ++counter) continue;

                title = [];
                tags = ' key';
                buffer.push('\n' + new Array(depth).join(' '));
                e = parseInt(i);

                if ('' + e !== i)
                {
                    e = i.indexOf(':');
                    e = -1 === e ? ['', i] : [i.substr(0, e), i.substr(e+1)];

                    if (t.isObject)
                    {
                        if ('' === e[0])
                        {
                            title.push('Public property');
                            tags += ' public';
                        }
                        else switch (e[0].charAt(e[0].length - 1))
                        {
                        case '`': title.push('Public property'); tags += ' public'; break;
                        case '*': title.push('Protected property'); tags += ' protected'; break;
                        case '~': title.push('Meta property'); tags += ' meta'; break;
                        default:
                            title.push('Private property from class ' + e[0].replace(/^[^`]*`/, ''));
                            tags += ' private';
                            break;
                        }
                    }
                    else if (t.isResource)
                    {
                        title.push('Meta property');
                        tags += ' meta';
                    }

                    e = e[0].replace(/[^`]+$/, '') + e[1];
                }

                if ('object' === typeof data[i] && null !== data[i] && 'compact' === b[2])
                {
                    buffer.push('<a onclick="arrayToggle(this)"> ⊞ </a>');
                }

                t[0] = counter;
                counter = -1;
                htmlizeData(e, tags, title);
                counter = t[0];
                e = buffer[buffer.length-1];
                buffer[buffer.length-1] = e.substr(0, e.length-7);
                push(' ⇨ ', 'arrow');
                buffer.push('</span>');

                if ('object' === typeof data[i] && null !== data[i])
                {
                    buffer.push('<span class="array-' + b[2] + '">');
                    htmlizeData(data[i], '', []);
                    buffer.push('</span>');
                }
                else htmlizeData(data[i], '', []);

                buffer.push(', ');
            }

            if (data.__cutBy)
            {
                buffer.push('\n' + new Array(depth).join(' '));
                push('...', 'cut', ['Cut by ' + data.__cutBy]);
                buffer.push(', ');
            }

            depth -= 2;
            buffer[buffer.length - 1] = '';
            buffer.push('\n' + new Array(depth).join(' '));
            buffer.push(b[1]);

            if (1 === depth) buffer.push('</span>');

            break;
        }
    }

    htmlizeData(data);

    return buffer.join('');
}

function arrayToggle(a)
{
    var s = a.nextSibling.nextSibling;

    if ('array-compact' == s.className)
    {
        a.innerHTML = ' ⊟ ';
        s.className = 'array-expanded';
    }
    else
    {
        a.innerHTML = ' ⊞ ';
        s.className = 'array-compact';
    }
}
