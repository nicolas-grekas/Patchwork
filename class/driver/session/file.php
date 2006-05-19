<?php

class driver_session_file extends SESSION
{
	private static $path = './zcache/session/';

	public function open($path, $name) {}
	public function close() {}

	public function read($sid)
	{
		return @file_get_contents(self::$path . 'sid_' . $sid . '.txt');
	}

	public function write($sid, $value)
	{
		CIA::writeFile(self::$path . 'sid_' . $sid . '.txt', $value);
	}

	public function destroy($sid)
	{
		unlink(self::$path . 'sid_' . $sid . '.txt');
	}

	public function gc($lifetime)
	{
		if (is_dir(self::$path)) foreach (new DirectoryIterator(self::$path) as $file)
		{
			if (is_file(self::$path.$file) && CIA_TIME - filemtime(self::$path.$file) > $lifetime) unlink(self::$path.$file);
		}
	}
}
