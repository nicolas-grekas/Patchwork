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


abstract class agent_pForm_record extends agent_pForm
{
    public $get = array('__1__:i:1' => 0);

    protected $type = array();


    function control()
    {
        parent::control();

        $t = explode('_', substr(get_class($this), 6));

        if ('new' === end($t))
        {
            $new = true;
            array_pop($t);
        }
        else $new = false;

        $this->type || $this->type = $t;

        if ($this->data || $new) return;

        if (!empty($this->get->__1__))
        {
            $t = implode('_', $this->type);
            $sql = "SELECT * FROM `{$t}` WHERE {$t}_id=?";
            $this->data = (object) DB()->fetchAssoc($sql, array($this->get->__1__));
            $this->data || patchwork::forbidden();
        }
        else if ($this instanceof agent_pForm_record_indexable)
        {
            $this->template = implode('/', $this->type) . '/index';
        }
        else
        {
            patchwork::forbidden();
        }
    }

    function compose($o)
    {
        if ($this->data)
        {
            foreach ($this->data as $k => $v) is_scalar($v) && $o->$k = $v;

            if ($this instanceof agent_pForm_record_indexable) $o = $this->composeRecord($o);

            return parent::compose($o);
        }
        else
        {
            return $this instanceof agent_pForm_record_indexable
                ? $this->composeIndex($o)
                : parent::compose($o);
        }
    }

    protected function save($data)
    {
        $t = implode('_', $this->type);

        if (empty($this->data))
        {
            DB()->insert($t, $data);
            $id = DB()->lastInsertId();
            $this->data = (object) array("{$t}_id" => $id);
        }
        else
        {
            $id = $this->data->{"{$t}_id"};
            DB()->update($t, $data, array("{$t}_id" => $id));
        }

        return implode('/', $this->type) . "/{$id}";
    }
}

interface agent_pForm_record_indexable
{
    function composeIndex($o);
    function composeRecord($o);
}
