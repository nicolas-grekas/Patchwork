<?php /*********************************************************************
 *
 *   Copyright : (C) 2006 Nicolas Grekas. All rights reserved.
 *   Email     : nicolas.grekas+patchwork@espci.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL, see COPYING
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/


$version_id = realpath('.');

$appConfigSource = array();
$appInheritSeq = array();

$CIA = $version_id;
$version_id = 0;

// Linearize application inheritance graph
$cia_paths = C3MRO($CIA);

$cia_include_paths = explode(PATH_SEPARATOR, get_include_path());
$cia_include_paths = array_map('realpath', $cia_include_paths);
$cia_include_paths = array_diff($cia_include_paths, $cia_paths);
$cia_include_paths = array_merge($cia_paths, $cia_include_paths);

$cia_paths_token = substr(hash('md5', serialize($cia_include_paths)), 0, 4);

$appInheritSeq = array(
	'<?php',
	'$version_id=' . $version_id . ';',
	'$cia_paths=' . var_export($cia_paths, true) . ';',
	'$cia_paths_token=\'' . $cia_paths_token . '\';',
	'$cia_include_paths=' . var_export($cia_include_paths, true) . ';',
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

if (CIA_WINDOWS)
{
	$appInheritSeq = new COM('Scripting.FileSystemObject');
	$appInheritSeq->GetFile($appConfigSource)->Attributes |= 2; // Set hidden attribute
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

	if (!file_exists($appRealpath . '/config.php')) die('Missing file config.php in ' . htmlspecialchars($appRealpath));

	$GLOBALS['version_id'] += filemtime($appRealpath . '/config.php');

	// Get config's source and clean it
	$parent = file_get_contents($appRealpath . '/config.php');
	if (false !== strpos($parent, "\r")) $parent = strtr(str_replace("\r\n", "\n", $parent), "\r", "\n");

	$k = false;

	if ('<?' == substr($parent, 0, 2))
	{
		$seq = preg_replace("'^<\?(?:php)?\s'i", '', $parent);
		$k = $seq != $parent;
		$parent = trim($seq);
		if ('?>' == substr($parent, -2)) $parent = substr($parent, 0, -2) . ';';
	}
	else
	{
		$seq = preg_replace("#^<script\s+language\s*=\s*(|[\"'])php\1\s*>#i", '', $parent);
		$k = $seq != $parent;
		$parent = trim($seq);
		$parent = preg_replace("'</script\s*>$'i", ';', $parent);
	}

	if (!$k) die('Failed to detect PHP open tag (<?php) at the beginning of ' . htmlspecialchars($appRealpath) . '/config.php');

	$GLOBALS['appConfigSource'][$appRealpath] = $parent;

	// Get parent application(s)
	if (preg_match("'^#extends[ \t].+(?:\n#.+)*'i", $parent, $parent))
	{
		$parent = '#' . substr($parent[0], 9);
		preg_match_all("'^#(.+?)$'m", $parent, $parent);
		$parent = $parent[1];
	}
	else $parent = false;

	if (__CIA__ == $appRealpath && $parent) die('#extends clause is forbidden in root config file: ' . htmlspecialchars(__CIA__) . '/config.php');

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

		if (!$parent) die('Inconsistent application hierarchy');

		$resultSeq[] = $parent;

		foreach ($seqs as $k => &$seq)
		{
			if ($parent == current($seq)) unset($seq[key($seq)]);
			if (!$seq) unset($seqs[$k]);
		}
	}
}
