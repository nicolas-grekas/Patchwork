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


class extends agent
{
	const binary = true;

	public $argv = array(
		'Command:string:^FileUpload|GetFolders|GetFoldersAndFiles|CreateFolder$',
		'Type:string:^File|Image|Flash|Media$',
		'CurrentFolder',
		'NewFolderName'
	);

	protected $watch = array('public/files');

	protected $path = false;

	protected $allow_file = array('pdf','doc','odt');
	protected  $deny_file = array(
//		'html','htm','php','php2','php3','php4','php5','phtml','pwml','inc','asp','aspx','ascx',
//		'jsp','cfm','cfc','pl','bat','exe','com','dll','vbs','js','reg','cgi','htaccess','asis',
	);

	protected $allow_image = array('jpg','gif','jpeg','png','bmp');
	protected  $deny_image = array();

	protected $allow_flash = array('');
	protected  $deny_flash = array();

	protected $allow_media = array('jpg','gif','jpeg','png','bmp','avi','mpg','mpeg');
	protected  $deny_media = array();


	protected function setPath()
	{
		$this->path = resolvePath('public/__/files/');
	}

	function compose($o)
	{
		$this->setPath();

		$path = $this->path;

		if (!$path) return array('number' => 1, 'text' => 'The filemanager is disabled');

		if ('/' != substr($path, -1)) $path .= '/';

		$currentFolder = $this->argv->CurrentFolder;

		if ('/' != substr($currentFolder, -1   )) $currentFolder .= '/';
		if ('/' != substr($currentFolder,  0, 1)) $currentFolder  = '/' . $currentFolder;

		if (strpos($currentFolder, '..')) return array('number' => 102, 'text' => '');


		header('Content-Type: text/xml; charset=utf-8');

		$o->command       = $this->argv->Command;
		$o->resourceType  = strtolower($this->argv->Type);
		$o->currentFolder = $currentFolder;
		$o->currentUrl    = 'files/' . $o->resourceType . $currentFolder;

		if (!$o->resourceType) return array('number' => 1, 'text' => 'Invalid ressource type');

		switch ($o->command)
		{
			case 'GetFolders':
			case 'GetFoldersAndFiles':

				$getFiles = 'GetFoldersAndFiles' == $o->command;
				$folders = array();
				$files   = array();

				$path .= $o->resourceType . $o->currentFolder;

				CIA::makeDir($path);

				foreach (new DirectoryIterator($path) as $file)
				{
					if ($file->isDot()) continue;
					if ($file->isDir()) $folders[] = $file->getFilename();
					else if ($getFiles) $files[$file->getFilename()] = $file->getSize();
				}

				natcasesort($folders);
				$o->FOLDERS = new loop_array($folders);
				
				if ($getFiles)
				{
					uksort($files, 'strnatcasecmp');
					$o->FILES = new loop_array($files);
				}

				break;

			case 'FileUpload':
				$o->fileUpload = true;
				return $this->fileUpload($o);

			case 'CreateFolder':
				unset($o->command);
				return $this->createFolder($o);

			default:
				return array('number' => 1, 'text' => 'Invalid command');
		}

		return $o;
	}

	protected function createFolder($o)
	{
		$o = (object) array(
			'number' => 0,
			'originalDescription' => ''
		);

		if ($newFolderName = $this->argv->NewFolderName)
		{
			if (false !== strpos($newFolderName, '..')) $o->number = 102;
			else
			{
				$newFolderName = $this->path . $o->resourceType . $o->currentFolder . $newFolderName . '/';

				@CIA::makeDir($newFolderName);

				if (!is_dir($newFolderName)) return array('number' => 102, 'originalDescription' => 'Failed creating new directory. Please check folder permissions.');

				CIA::touch('public/files');
			}
		}
		else $o->number = 102;

		return $o;
	}

	protected function fileUpload($o)
	{
		header('Content-Type: text/javascript; charset=utf-8');

		$o->number = 0;
		$o->filename = '';

		if ( isset($_FILES['NewFile']) && !is_null($_FILES['NewFile']['tmp_name']) )
		{
			$file = $_FILES['NewFile'];

			$path = $this->path . $o->resourceType . $o->currentFolder;

			@CIA::makeDir($path);

			$o->filename = $file['name'];

			$extension = strtolower(pathinfo($o->filename, PATHINFO_EXTENSION));
			$filename  = $extension ? substr($o->filename, 0, -strlen($extension) - 1) : $o->filename;

			$allow = $this->{'allow_' . $o->resourceType};
			$deny = $this->{'deny_' . $o->resourceType};
	
			if (
				   (!$allow ||  in_array($extension, $allow))
				&& (!$deny  || !in_array($extension, $deny ))
			)
			{
				$i = 0;

				do
				{
					$filepath = $path . $o->filename;

					if (is_file($filepath))
					{
						$o->filename = $filename . '(' . ++$i . ').' . $extension;
						$o->number = 201;
					}
					else
					{
						move_uploaded_file($file['tmp_name'], $filepath);

						if (is_file($filepath))
						{
							$i = umask(0);
							chmod($filepath, 0777);
							umask($i);
						}

						CIA::touch('public/files');

						break;
					}
				}
				while (true);
			}
			else $o->number = 202;
		}
		else $o->number = 202;

		return $o;
	}
}
