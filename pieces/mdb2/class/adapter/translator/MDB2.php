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


class adapter_translator_MDB2 extends TRANSLATOR
{
    protected

    $db,
    $table = 'translation',
    $atime = true;


    function __construct($options)
    {
        $this->db = DB();
        isset($options['table']) && $this->table = $options['table'];

        if ($this->atime)
        {
            $sql = "SHOW COLUMNS FROM {$this->table} LIKE 'atime'";
            $this->atime = (bool) $this->db->queryRow($sql);
        }
    }

    function search($string, $lang)
    {
        $qString = $this->db->quote($string);

        $sql = "SELECT {$lang} FROM {$this->table} WHERE __=BINARY {$qString}";
        if ($row = (array) $this->db->queryRow($sql))
        {
            if ($this->atime)
            {
                $sql = "UPDATE {$this->table} SET atime=NOW() WHERE __=BINARY {$qString}";
                $this->db->exec($sql);
            }
        }
        else
        {
            $row = array('__' => $string, $lang => '');

            if (PATCHWORK_I18N)
            {
                $lang_list = $CONFIG['i18n.lang_list'];
                unset($lang_list['__']);

                $lang_list = array_keys($lang_list);

                $sql = implode(',', $lang_list);
                $sql = "SELECT * FROM {$this->table} WHERE CAST({$qString} AS BINARY) IN ({$sql})";
                if ($sql = (array) $this->db->queryRow($sql))
                {
                    foreach ($lang_list as $lang_list)
                    {
                        if (isset($sql[$lang_list]) && $sql[$lang_list] == $string)
                        {
                            unset($sql['atime']);
                            $row = $sql;
                            $row['__'] = $row[$lang];
                            $row[$lang_list] = '';
                            break;
                        }
                    }
                }
            }

            $this->db->autoExecute($this->table, $row);
        }

        return $row[$lang];
    }
}
