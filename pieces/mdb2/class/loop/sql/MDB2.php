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


class loop_sql_MDB2 extends loop
{
    protected

    $db = false,
    $sql,
    $result,
    $from = 0,
    $count = 0;


    function __construct($sql, $filter = '', $from = 0, $count = 0)
    {
        $this->sql = $sql;
        $this->from = $from;
        $this->count = $count;
        $this->addFilter($filter);
    }

    function setLimit($from, $count)
    {
        $this->from = $from;
        $this->count = $count;
    }

    protected function prepare()
    {
        $sql = $this->sql;
        $this->db || $this->db = DB();

        if ($this->count > 0)
        {
            if (0 === strncmp($this->db->phptype, 'mysql', 5)) $sql .= " LIMIT {$this->from},{$this->count}";
            else $this->db->setLimit($this->count, $this->from);
        }

        $this->result = $this->db->query($sql);

        return @PEAR::isError($this->result) ? false : $this->result->numRows();
    }

    protected function next()
    {
        $a = $this->result->fetchRow();

        if ($a) return $a;
        else $this->result->free();
    }
}
