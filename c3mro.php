<?php

$CIA = realpath($CIA);
$appConfigSource = array();
$appInheritSeq = array();

$version_id = 0;

// Linearize application inheritance graph
$cia_paths = C3MRO($CIA);
$cia_paths_token = substr(md5(serialize($cia_paths)), 0, 4);

$appInheritSeq = array(
	'<?php',
	'$version_id=' . $version_id . ';',
	'$cia_paths=' . var_export($cia_paths, true) . ';',
	'$cia_paths_token=\'' . $cia_paths_token . '\';',
);

$CIA = $cia_paths[0] . '/.';
glob($CIA . $cia_paths_token . '*.zcache.php', GLOB_NOSORT)
	|| array_map('unlink', glob($CIA . '*.zcache.php', GLOB_NOSORT));

foreach ($cia_paths as $CIA)
{
	require $CIA . '/config.php';
	$appInheritSeq[] = $appConfigSource[$CIA];
}

$appConfigSource = $cia_paths[0] . '/.config.zcache.php';

@unlink($appConfigSource);

file_put_contents($appConfigSource, implode("\n", $appInheritSeq));

if ('WIN' == substr(PHP_OS, 0, 3))
{
	$appInheritSeq = new COM('Scripting.FileSystemObject');
	$appInheritSeq->GetFile($appConfigSource)->Attributes |= 2;
}

unset($appConfigSource);
unset($appInheritSeq);


// C3 Method Resolution Order (like in Python 2.3) for multiple application inheritance
// See http://python.org/2.3/mro.html
function C3MRO($appRealpath)
{
	$resultSeq =& $GLOBALS['appInheritSeq'][$appRealpath];

	// If result is cached, return it
	if (null !== $resultSeq) return $resultSeq;

	if (!file_exists($appRealpath . '/config.php')) throw new Exception('Missing config.php in ' . htmlspecialchars($appRealpath));

	$GLOBALS['version_id'] += filemtime($appRealpath . '/config.php');

	// Get config's source and clean it
	$parent = file_get_contents($appRealpath . '/config.php');
	$parent = str_replace(array("\r\n", "\r"), array("\n", "\n"), $parent);

	$k = 0;

	if ('<?' == substr($parent, 0, 2))
	{
		$parent = preg_replace("'^<\?(?:php)?\s'i", '', $parent, 1, $k);
		$parent = trim($parent);
		if ('?>' == substr($parent, -2)) $parent = substr($parent, 0, -2) . ';';
	}
	else
	{
		$parent = preg_replace("#^<script\s+language\s*=\s*(|[\"'])php\1\s*>#i", '', $parent, 1, $k);
		$parent = trim($parent);
		$parent = preg_replace("'</script\s*>$'i", ';', $parent);
	}

	if (!$k) throw new Exception('Failed to detect PHP open tag (<?php) at the beginning of ' . htmlspecialchars($appRealpath) . '/config.php');

	$GLOBALS['appConfigSource'][$appRealpath] = $parent;

	// Get parent application(s)
	if (preg_match("'^#extends[ \t].+(?:\n#.+)*'i", $parent, $parent))
	{
		$parent = '#' . substr($parent[0], 9);
		preg_match_all("'^#(.+?)$'m", $parent, $parent);
		$parent = $parent[1];
	}
	else $parent = false;

	if (__CIA__ == $appRealpath && $parent) throw new Exception('#extends clause is forbidden in root config file: ' . htmlspecialchars(__CIA__) . '/config.php');

	// If no parent app, result is trival
	if (!$parent) return array($appRealpath);

	$resultSeq = count($parent);

	// Parent's config file path is relative to the current application's directory
	$k = 0;
	while ($k < $resultSeq)
	{
		$seq =& $parent[$k];

		$seq = trim($seq);
		if ('__CIA__' == substr($seq, 0, 7)) $seq = __CIA__ . substr($seq, 7);

		if ('/' != $seq[0] && '\\' != $seq[0] &&  ':' != $seq[1]) $seq = $appRealpath . '/' . $seq;

		$seq = realpath($seq);

		++$k;
	}

	// Compute C3 MRO
	$seqs = array_merge(
		array(array($appRealpath)),
		array_map('C3MRO', $parent),
		array($parent)
	);
	$resultSeq = array();
	$parent = false;

	while (1)
	{
		if (!$seqs) return $resultSeq;

		foreach ($seqs as &$seq)
		{
			$parent = reset($seq);

			unset($seq);

			foreach ($seqs as $seq)
			{
				unset($seq[key($seq)]);

				if (in_array($parent, $seq))
				{
					$parent = false;
					break;
				}
			}

			if ($parent) break;
		}

		if (!$parent) throw new Exception('Inconsistent application hierarchy');

		$resultSeq[] = $parent;

		foreach ($seqs as $k => &$seq)
		{
			if ($parent == current($seq)) unset($seq[key($seq)]);
			if (!$seq) unset($seqs[$k]);
		}
	}
}
