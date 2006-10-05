<?php

$CIA = realpath($CIA);
$appInheritConfig = array();
$appInheritSeq = array();

$version_id = 0;

// Linearize application inheritance graph
$CIA = C3MRO($CIA);

$CONFIG = array();
$cia_paths = array();

foreach ($CIA as &$CIA)
{
	$CONFIG += $appInheritConfig[$CIA];
	$cia_paths[] = dirname($CIA);
}

if (!isset($CONFIG['DEBUG'])) $CONFIG['DEBUG'] = (int) @$CONFIG['DEBUG_KEYS'][ (string) $_COOKIE['DEBUG'] ];

$appInheritConfig = $cia_paths[0] . '/.config.zcache.php';

unlink($appInheritConfig);

file_put_contents(
	$appInheritConfig,
	'<?php
$version_id=' . $version_id . ';
$cia_paths=' . var_export($cia_paths, true) . ';
$CONFIG=' . var_export($CONFIG, true) . ';'
);

if ('WIN' == substr(PHP_OS, 0, 3)) 
{
	$appInheritSeq = new COM('Scripting.FileSystemObject');
	$appInheritSeq->GetFile($appInheritConfig)->Attributes |= 2;
}

unset($appInheritConfig);
unset($appInheritSeq);

// C3 Method Resolution Order (like in Python 2.3) for application multi-inheritance
function C3MRO($appRealpath)
{
	$resultSeq =& $GLOBALS['appInheritSeq'][$appRealpath];

	// If result is cached, return it
	if (null !== $resultSeq) return $resultSeq;

	// Include application config file
	$GLOBALS['version_id'] += filemtime($appRealpath);
	$CONFIG = array();

	require $appRealpath;

	// Get parent application(s)
	if (isset($CONFIG['extends'])) $parent =& $CONFIG['extends'];
	else $parent = '../../config.php';

	unset($CONFIG['extends']);

	// Store application's $CONFIG parameters
	$GLOBALS['appInheritConfig'][$appRealpath] =& $CONFIG;

	// If no parent app, return empty array
	if (!$parent) return array($appRealpath);

	if (is_array($parent)) $resultSeq = count($parent);
	else
	{
		$parent = array($parent);
		$resultSeq = 1;
	}


	// Parent's config file path is relative to the current application's directory
	$seqs = dirname($appRealpath);
	$k = 0;
	while ($k < $resultSeq)
	{
		$seq =& $parent[$k];

		if ('/' != $seq[0] && '\\' != $seq[0] &&  ':' != $seq[1]) $seq = $seqs . '/' . $seq;

		$seq = realpath($seq);

		++$k;
	}

	// Compute C3 MRO
	// See http://python.org/2.3/mro.html
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

		if (false == $parent) throw new Exception('Inconsistent application hierarchy');

		$resultSeq[] = $parent;

		foreach ($seqs as $k => &$seq)
		{
			if ($parent == current($seq)) unset($seq[key($seq)]);
			if (!$seq) unset($seqs[$k]);
		}
	}
}
