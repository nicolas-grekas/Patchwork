<?php

abstract class extends loop
{
	protected
		$type,
		$form,
		$fromDb,
		$counter,
		$contextIsSet,
		$length,
		$submit_add,
		$submit_count,
		$count_name,
		$count_value,
		$deleted = array();

	function __construct($form, $loop)
	{
		$this->loop = $loop;

		$this->form = $form;

		$this->submit_add = $this->form->add('submit', "{$this->type}_add");
		$this->submit_count = $this->form->add('hidden', "{$this->type}_count");

		$this->count_name = $this->submit_count->getName();
		$this->count_value = $this->getLength();

		$this->submit_count->setValue($this->count_value);


		$this->deleted = array_flip((array) @$_POST["deleted_{$this->type}"]);
	}

	protected function prepare()
	{
		$this->fromDb = true;
		$this->counter = 0;
		$this->contextIsSet = false;

		$this->length = max(1, $this->loop->getLength());

		if ($this->submit_count->getStatus())
		{
			$this->length = $this->submit_count->getValue();
			if ($this->submit_add->isOn()) $this->length += 1;
		}

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

			$a = (object) array('id' => $data->{"{$this->type}_id"});

			if (isset($this->deleted[$this->counter])) $a->deleted = $this->counter;
			else
			{
				if ($this->contextIsSet) $form->pullContext();
				else $this->contextIsSet = true;

				$form->pushContext($a, $this->type . '_' . $this->counter);

				if ($form->add('submit', "{$this->type}_del")->isOn())
				{
					$this->deleted[$this->counter] = true;
					$a = (object) array(
						'id' => $a->id,
						'deleted' => $this->counter
					);
				}
				else $this->populateForm($a, $data, $this->counter);
			}

			$a->count_name = $this->count_name;
			$a->count_value = $this->count_value;

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
