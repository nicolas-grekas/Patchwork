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


abstract class loop_edit extends loop
{
    protected

    $type,
    $exposeLoopData = false,
    $allowAddDel = true,
    $defaultLength = 1,

    $form,
    $loop,
    $fromDb,
    $counter,
    $contextIsSet,
    $length,
    $submit_add,
    $submit_count;


    function __construct($form, $loop)
    {
        $this->form = $form;
        $this->loop = $loop;

        if ($this->allowAddDel)
        {
            $this->submit_add = $this->form->add('submit', "{$this->type}_add");
            $this->submit_count = $this->form->add('hidden', "{$this->type}_count");
            $this->submit_count->setValue($this->getLength());
        }
    }

    protected function prepare()
    {
        $this->fromDb = true;
        $this->counter = 0;
        $this->contextIsSet = false;

        if ($this->allowAddDel)
        {
            if ($this->submit_count->getStatus())
            {
                $this->length = $this->submit_count->getValue();
                if ($this->submit_add->isOn()) $this->length += 1;
            }
            else
            {
                $this->length = max($this->defaultLength, $this->loop->getLength());
            }
        }
        else $this->length = $this->loop->getLength();

        return $this->length;
    }

    protected function next()
    {
        $form = $this->form;

        if ($this->counter++ < $this->length)
        {
            $data = false;

            if ($this->fromDb) $data = $this->loop->loop();

            if (!$data)
            {
                $this->fromDb = false;
                $data = (object) array("{$this->type}_id" => '');
            }

            $a = $this->exposeLoopData ? $data : (object) array();

            isset($a->id) || $a->id = $data->{"{$this->type}_id"};

            if ($this->contextIsSet) $form->pullContext();
            else $this->contextIsSet = true;
            $form->pushContext($a, $this->type . '_' . $this->counter);

            $this->populateForm($a, $data, $this->counter);

            if ($this->allowAddDel)
            {
                $del = $form->add('hidden', 'is_deleted');

                if ($form->add('submit', "{$this->type}_del")->isOn() || $del->isOn() || !empty($a->deleted))
                {
                    $a->deleted = $this->counter;
                    $del->setValue(1);
                }
            }
            else unset($a->deleted);

            return $a;
        }
        else
        {
            if ($this->contextIsSet) $form->pullContext();
            return false;
        }

    }

    abstract public function populateForm($a, $data, $counter);
}
