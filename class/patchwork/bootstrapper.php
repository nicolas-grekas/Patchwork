<?php /*********************************************************************
 *
 *   Copyright : (C) 2007 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


class patchwork_bootstrapper
{
	static

	$pwd,
	$cwd,
	$token = '',
	$paths,
	$zcache,
	$last,
	$appId;

	protected static $bootstrapper;


	static function initialize($caller)
	{
		self::$cwd = defined('PATCHWORK_BOOTPATH') && '' !== PATCHWORK_BOOTPATH ? PATCHWORK_BOOTPATH : '.';
		self::$cwd = rtrim(self::$cwd, '/\\') . DIRECTORY_SEPARATOR;
		self::$pwd = dirname($caller) . DIRECTORY_SEPARATOR;

		require dirname(__FILE__) . '/bootstrapper/bootstrapper.php';

		self::$bootstrapper = new patchwork_bootstrapper_bootstrapper__0($caller, self::$cwd, self::$token);
	}

	static function getLock()             {return self::$bootstrapper->getLock();}
	static function isReleased()          {return self::$bootstrapper->isReleased();}
	static function release()             {return self::$bootstrapper->release();}
	static function getCompiledFile()     {return self::$bootstrapper->getCompiledFile();}
	static function preprocessorPass1()   {return self::$bootstrapper->preprocessorPass1();}
	static function preprocessorPass2()   {return self::$bootstrapper->preprocessorPass2();}
	static function loadConfigFile($type) {return self::$bootstrapper->loadConfigFile($type);}
	static function initConfig()          {return self::$bootstrapper->initConfig();}
	static function loadConfigSource()    {return self::$bootstrapper->loadConfigSource();}
	static function getConfigSource()     {return self::$bootstrapper->getConfigSource();}

	static function initInheritance()
	{
		self::$cwd = rtrim(patchwork_realpath(self::$cwd), '/\\') . DIRECTORY_SEPARATOR;

		$a = self::$bootstrapper->getLinearizedInheritance(self::$pwd);

		self::$paths =& $a[0];
		self::$last  =  $a[1];
		self::$appId =  $a[2];
	}

	static function initZcache()
	{
		self::$zcache = self::$bootstrapper->getZcache(self::$paths, self::$last);
	}

	static function updatedb()
	{
		return self::$bootstrapper->updatedb(self::$paths, self::$last, self::$zcache);
	}
}
