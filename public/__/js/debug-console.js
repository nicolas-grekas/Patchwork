function Z()
{
    scrollTo(0, window.innerHeight || document.documentElement.scrollHeight);
}

function classifyEvent(token, type, data)
{
    var target = 'requests', div = document.createElement('DIV');

    switch (type)
    {
    case 'php-error':
    case 'php-exception':
    case 'php-raw-error': target = 'php-errors'; break
    case 'client-dump':
    case 'server-dump': target = 'E'; break
    }

    var state = {
        depth: 0,
        buffer: []
    };

    // TODO: use token, type, data.time and data.mem

    div.className = 'event';
    div.innerHTML = htmlizeEvent(data.data);

    document.getElementById(target).appendChild(div);
}

function htmlizeEvent(data)
{
    var depth = 1,
        buffer = [],
        span = document.createElement('SPAN');

    function escape(s)
    {
        span.innerText = span.textContent = s;
        return span.innerHTML;
    }

    function push(data, tags, title)
    {
        if (title) tags += '" title="' + title + '"';
        buffer.push('<span class="' + tags + '">' + escape(data) + '</span>');
    }

    function htmlizeData(data, tags)
    {
        var i, e;

        tags = tags || '';

        switch (true)
        {
        default:
        case true === data:
        case false === data:
        case null === data:
            push(data, 'const' + tags);
            break;

        case 'string' === typeof data:
            if ('' === data)
            {
                push('""', 'string empty' + tags, 'Empty string');
                return;
            }

            i = data.indexOf('`');
            if (-1 == i) data = ['u', data];
            else data = [data.substr(0, i), data.substr(i+1)];

            switch (data[0].charAt(data[0].length - 1))
            {
                case 'f': push(data[1], 'const' + tags); return;
                case 'b': tags += ' bin';
                case 'u': tags = 'string' + tags;
            }

            // TODO: indent multi-line strings

            i = parseInt(data[0]);

            if (0 < i)
            {
                push(data[1], tags, 'Length: ' + i);
                push('...', 'cut');
            }
            else
            {
                push(data[1], tags, 'Length: ' + data[1].length);
            }

            break;

        case 'object' === typeof data:

            // TODO: use info from data['_']

            if (undefined !== data.__maxDepth)
            {
                push('[', 'bracket');
                push('...', 'cut', 'Max-depth reached');
                push(']', 'bracket');
                return;
            }

            e = 1;
            depth += 2;
            buffer.push('<span class="array-compact">');
            push('[', 'bracket open');
            buffer.push('<a onclick="arrayToggle(this)"> ⊞ </a>\n');
            for (i in data)
            {
                if ('_' === i || '__maxLength' === i) continue;
                buffer.push('<span class="indent">' + new Array(depth).join(' ') + '</span>')
                e = parseInt(i);
                if ('' + e === i) i = e;
                htmlizeData(i, ' key'); // TODO: handle object properties
                push(' ⇨ ', 'arrow');
                htmlizeData(data[i]);
                push(',\n', 'lf');
                e = 0;
            }
            depth -= 2;

            if (data.maxLength)
            {
                e = 0;
                buffer.push('<span class="indent">' + new Array(depth).join(' ') + '</span>')
                push('...', 'cut', 'Max-length reached, cut by: ' + data.maxLength);
                push(',\n', 'lf');
            }

            if (e)
            {
                buffer[buffer.length - 3] = '';
                buffer[buffer.length - 2] = '';
                buffer[buffer.length - 1] = '';
                push('[]', 'bracket');
            }
            else
            {
                buffer[buffer.length - 1] = '';
                push('\n', 'lf');
                if (1 < depth) buffer.push('<span class="indent">' + new Array(depth).join(' ') + '</span>')
                push(']', 'bracket close');
                buffer.push('</span>');
            }

            break;
        }
    }

    htmlizeData(data);

    return buffer.join('');
}

function arrayToggle(a)
{
    var s = a.parentNode;

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
