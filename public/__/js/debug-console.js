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

    // TODO: use token, type, data.time and data.mem, data.patchwork and data._SERVER when available

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
        if (title && title.length) tags += '" title="' + title.join(', ');
        buffer.push('<span class="' + tags + '">' + escape(data) + '</span>');
    }

    function htmlizeData(data, tags, title)
    {
        var i, e, t, b;

        title = title || [];
        tags = tags || '';

        switch (true)
        {
        default:
        case true === data:
        case false === data:
        case null === data:
            push(data, 'const' + tags, title);
            break;

        case 'string' === typeof data:
            if ('' === data)
            {
                title.push('Empty string');
                push('""', 'string empty' + tags, title);
                return;
            }

            i = data.indexOf('`');
            if (-1 == i) data = ['u', data];
            else data = [data.substr(0, i), data.substr(i+1)];

            switch (data[0].charAt(data[0].length - 1))
            {
                case 'f': push(data[1], 'const' + tags, title); return;
                case 'b': tags += ' bin';
                case 'u': tags = 'string' + tags;
            }

            i = parseInt(data[0]);

            title.push('Length: ' + (0 < i ? i : data[1].length));

            data = data[1].split(/\r?\n/g);

            if (data.length > 1)
            {
                for (e = 0; e < data.length; ++e)
                {
                    push('\n', 'lf');
                    buffer.push('<span class="indent">' + new Array(depth + 2).join(' ') + '</span>')
                    push(data[e], tags + ('' === data[e] ? ' empty' : ''), title);
                }
            }
            else push(data[0], tags, title);

            if (0 < i) push('...', 'cut');

            break;

        case 'object' === typeof data:

            e = 1;
            depth += 2;
            b = ['[', ']'];
            t = data['_'] ? data['_'].split(':') : [];

            if (t.length)
            {
                if ('array' === t[0])
                {
                    t.type = 'array';
                    t.ref = t[1];
                    t.len = t[3];
                    t.isRef = '' === t[4];
                }
                else if ('resource' === t[0] && ('' + parseInt(t[1])) !== t[1])
                {
                    t.type = 'resource:' + t[1];
                    t.ref = t[2];
                    t.isRef = '' === t[3];

                    push(t.type, 'class');
                }
                else
                {
                    t.type = 'class';
                    t.ref = t[1];
                    t.isRef = '' === t[2];
                    t.class = t[0];

                    if ('stdClass' !== t.class) push(t.class, 'class');
                    if (t.ref && !t.isRef) push('#' + t.ref, 'ref id');

                    b = ['{', '}'];
                }
            }

            if (undefined !== data.__maxDepth)
            {
                push(b[0], 'bracket');
                push('...', 'cut', 'Max-depth reached');
                push(b[1], 'bracket');
                return;
            }

            buffer.push('<span class="array-compact">');
            push(b[0], 'bracket open');
            buffer.push('<a onclick="arrayToggle(this)"> ⊞ </a>\n');

            for (i in data)
            {
                if ('_' === i || '__maxLength' === i) continue;

                title = [];
                tags = ' key';
                buffer.push('<span class="indent">' + new Array(depth).join(' ') + '</span>')
                e = parseInt(i);

                if ('' + e !== i)
                {
                    e = i.indexOf(':');
                    e = -1 === e ? ['', i] : [i.substr(0, e), i.substr(e+1)];

                    if (t.class)
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
                        default:
                            title.push('Private property from class ' + e[0].replace(/^[^`]*`/, ''));
                            tags += ' private';
                            break;
                        }
                    }

                    e = e[0].replace(/[^`]+$/, '') + e[1];
                }

                htmlizeData(e, tags, title);
                push(' ⇨ ', 'arrow');
                htmlizeData(data[i]);
                push(',\n', 'lf');
                e = 0;
            }

            if (data.__maxLength)
            {
                e = 0;
                buffer.push('<span class="indent">' + new Array(depth).join(' ') + '</span>')
                push('...', 'cut', ['Max-length reached' + (data.__maxLength > 0 ? ', cut by ' + data.__maxLength : '')]);
                push(',\n', 'lf');
            }

            depth -= 2;
            buffer[buffer.length - 1] = '';

            if (e)
            {
                buffer[buffer.length - 3] = '';
                buffer[buffer.length - 2] = '';

                if (t.isRef)
                {
                    push(b[0], 'bracket');
                    push('#' + t.ref, 'ref');
                    push(b[1], 'bracket');
                }
                else push(b[0] + b[1], 'bracket');
            }
            else
            {
                push('\n', 'lf');
                if (1 < depth) buffer.push('<span class="indent">' + new Array(depth).join(' ') + '</span>')
                push(b[1], 'bracket close');
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
