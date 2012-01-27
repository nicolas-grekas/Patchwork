<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
 *
 *   Copyright : (C) 2012 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


class pForm_magicDb
{
    static function populate($table, $form, $save = false, $rxFilter = false)
    {
        $sql = 'SHOW COLUMNS FROM ' . $table;
        $result = DB()->query($sql);

        $onempty = '';
        $onerror = T('Input validation error');

        while ($row = $result->fetchRow())
        {
            if ($rxFilter && !preg_match($rxFilter, $row->Field)) continue;

            $type = strpos($row->Type, '(');
            $type = false === $type ? $row->Type : substr($row->Type, 0, $type);

            $continue = false;
            $param = array();

            switch ($type)
            {
                case 'char':
                case 'varchar':
                    $type = 'text';
                    $param['maxlength'] = (int) substr($type, strlen($type) + 1);
                    break;

                case 'longtext':   $type  = 8;
                case 'mediumtext': $type += 8;
                case 'text':       $type += 8;
                case 'tinytext':   $type += 8;
                    $param['maxlength'] = (1 << $type) - 1;
                    $type = 'textarea';
                    break;

                case 'tinyint':
                case 'bool':
                    $type = 'check';
                    $param['item'] = array(1 => T('Yes'), 0 => T('No'));
                    break;

                case 'date':
                    $type = 'text';
                    $param['valid'] = 'date';
                    break;

                case 'float':
                case 'double':
                case 'decimal':
                    $type = 'text';
                    $param['valid'] = 'float';
                    break;

                case 'set':
                case 'enum':
                    $i = eval('return array' . substr($row->Type, strlen($type)) . ';');
                    $param['item'] = array_combine($i, $i);

                    if ('set' == $type) $param['isdata'] = $param['multiple'] = true;

                    $type = 'check';
                    break;

                default: $continue = true; break;
            }

            if ($continue) continue;

            $form->add($type, $row->Field, $param);
            if ($save) $save->attach($row->Field, $onempty, $onerror);
        }
    }
}
