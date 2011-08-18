function Z()
{
    scrollTo(0, window.innerHeight||document.documentElement.scrollHeight);
}

function classifyEvents()
{
    var t, e, events = document.getElementById('events'), c = events.childNodes, i = c.length;

    while (i--)
    {
        e = c[i];
        events.removeChild(e);

        if (e.tagName !== 'PRE') continue;

        switch (e.className)
        {
        case 'event php-exception':
        case 'event php-raw-error':
        case 'event php-error': t = 'php-errors'; break;
        case 'event E': t = 'E'; break;
        default: t = 'requests'; break;
        }

        document.getElementById(t).appendChild(e);
    }
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
