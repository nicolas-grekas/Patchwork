<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

use Patchwork as p;

class agent_fckeditor_browser extends agent
{
    const contentType = '';

    public $get = array(
        'Command:c:FileUpload|GetFolders|GetFoldersAndFiles|CreateFolder',
        'Type:c:File|Image|Flash|Media',
        'CurrentFolder',
        'NewFolderName'
    );


    protected

    $path = false,

    $allow_file = array('pdf','doc','odt'),
    $deny_file  = array(
//      'html','htm','php','php2','php3','php4','php5','phtml','pwml','inc','asp','aspx','ascx',
//      'jsp','cfm','cfc','pl','bat','exe','com','dll','vbs','js','reg','cgi','htaccess','asis',
//      'sh','shtml','shtm','phtm',
    ),

    $allow_image = array('jpg','gif','jpeg','png','bmp'),
    $deny_image  = array(),

    $allow_flash = array(''),
    $deny_flash  = array(),

    $allow_media = array('jpg','gif','jpeg','png','bmp','avi','mpg','mpeg'),
    $deny_media  = array();


    protected function setPath()
    {
        $this->path = patchworkPath('public/__/files/');
        $this->path && $this->watch[] = 'public/files';
    }

    function compose($o)
    {
        $this->setPath();

        $path = $this->path;

        if (!$path || 'FileUpload' != $this->get->Command) header('Content-Type: text/xml');
        if (!$path) return array('number' => 1, 'text' => 'The filemanager is disabled');

        if ('/' != substr($path, -1)) $path .= '/';

        $currentFolder = $this->get->CurrentFolder;

        if ('/' != substr($currentFolder, -1   )) $currentFolder .= '/';
        if ('/' != substr($currentFolder,  0, 1)) $currentFolder  = '/' . $currentFolder;

        if (strpos($currentFolder, '..')) return array('number' => 102, 'text' => '');


        $o->command       = $this->get->Command;
        $o->resourceType  = strtolower($this->get->Type);
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

                if (file_exists($path) && $h = opendir($path))
                {
                    while (false !== $file = readdir($h))
                    {
                        if ('.' === $file || '..' === $file) continue;
                        if (is_dir($path . $file)) $folders[] = $file;
                        else if ($getFiles) $files[$file] = filesize($path . $file);
                    }

                    closedir($h);
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
        $number = 0;

        if ($newFolderName = $this->get->NewFolderName)
        {
            if (false !== strpos($newFolderName, '..')) $number = 102;
            else
            {
                $newFolderName = $this->path . $o->resourceType . $o->currentFolder . $newFolderName . '/';

                if (!is_dir($newFolderName) || mkdir($newFolderName, 0700, true))
                    return array('number' => 102, 'originalDescription' => 'Failed creating new directory. Please check folder permissions.');

                p::touch($this->watch);
            }
        }
        else $number = 102;

        return (object) array(
            'number' => $number,
            'originalDescription' => ''
        );
    }

    protected function fileUpload($o)
    {
        $o->number = 0;
        $o->filename = '';

        if ( isset($_FILES['NewFile']) && !is_null($_FILES['NewFile']['tmp_name']) )
        {
            $file = $_FILES['NewFile'];

            $path = $this->path . $o->resourceType . $o->currentFolder;

            file_exists($path) || mkdir($path, 0700, true);

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

                for (;;)
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

                        p::touch($this->watch);

                        break;
                    }
                }
            }
            else $o->number = 202;
        }
        else $o->number = 202;

        return $o;
    }
}
