<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
 *
 *   Copyright : (C) 2011 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


class loop_sql_MDB2_mysqli extends loop
{
    protected

    $db = false,
    $sql,
    $result = false,
    $from = 0,
    $count = 0;


    function __construct($sql, $filter = '', $from = 0, $count = 0)
    {
        $this->sql = $sql;
        $this->from = (int) $from;
        $this->count = (int) $count;
        $this->addFilter($filter);
    }

    function setLimit($from, $count)
    {
        $this->from  = (int) $from;
        $this->count = (int) $count;
    }

    protected function prepare()
    {
        if (!$this->result)
        {
            $sql = $this->sql;
            if ($this->count > 0) $sql .= " LIMIT {$this->from},{$this->count}";

            $this->db || $this->db = DB()->connection;
            $this->result = $this->db->query($sql);

            if (!$this->result) user_error("MySQL Error ({$sql}): {$this->db->error}");
        }

        return $this->result ? $this->result->num_rows : false;
    }

    protected function next()
    {
        $a = $this->result->fetch_object();

        if ($a) return $a;
        else $this->result->data_seek(0);
    }
}
