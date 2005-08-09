<?php

class loop_register_option extends loop_sql
{
	protected $form;
	protected $submit;
	protected $suffix = '';
	protected $pool = array();

	public function __construct($form, $submit, $choice_id = 0)
	{
		$this->form = $form;
		$this->submit = $submit;

		parent::__construct(
			"SELECT *
			FROM def_option
			WHERE position
				AND choice_id={$choice_id}
			ORDER BY position, label"
		);
	}

	protected function prepare()
	{
		$this->form->autoPopulate(false);
		return parent::prepare();
	}

	protected function next()
	{
		if ($this->pool)
		{
			$data = $this->pool[0]->render();
			if ($data)
			{
				return $data;
			}
			else
			{
				array_shift($this->pool);
				return $this->next();
			}
		}

		$data = parent::next();

		if ($data)
		{
			$form = $this->form;

			$f_option = false;
			$name = 'option' . $data->option_id . $this->suffix;

			switch ($data->type)
			{
				case 'select':
				case 'check':
					$f_option = $form->add($data->type, $name, array(
						'firstItem' => '',
						'sql' => "SELECT choice_id AS K, label AS V
								FROM def_choice
								WHERE position AND option_id={$data->option_id}
								ORDER BY position, label"
					));

					$this->submit->add($name, $data->label . T(' : que choisissez-vous pour cette option ?'), '');

					if ($v = $f_option->getValue())
					{
						array_unshift(
							$this->pool,
							new loop_register_option($form, $this->submit, $v)
						);
					}
				break;

				case 'int':
					$f_option = $form->add('text', $name, array('valid' => 'int', 0));
					
					if ($v = $f_option->getValue())
					{
						array_unshift(
							$this->pool,
							new loop_register_option_int($form, $this->submit, $v, $data->option_id)
						);
					}
				break;
			}

			if ($f_option) $data->f_option = $f_option;
		}

		return $data;
	}

	function setSuffix($suffix)
	{
		$this->suffix = $suffix;
	}
}

class loop_register_option_int extends loop
{
	protected $form;
	protected $infoData = false;
	protected $repeat;
	protected $counter;
	protected $suboption;
	protected $subon = false;

	public function __construct($form, $repeat, $option_id)
	{
		$this->form = $form;
		$this->repeat = $repeat;
		$this->infoData = DB()->getRow(
			"SELECT *
			FROM def_choice
			WHERE label!=''
				AND position
				AND option_id={$option_id}
			ORDER BY position, label"
		);

		$this->suboption = new loop_register_option($form, $this->infoData->choice_id);
	}

	protected function prepare()
	{
		$this->counter = 0;
		$this->subon = false;
		$len = $this->suboption->getLength();
		return $len && $this->infoData ? $len*$this->repeat : 0;
	}

	protected function next()
	{
		if ($this->subon)
		{
			$data = $this->suboption->render();
			if ($data) return $data;

			$this->subon = false;
		}

		if ($this->counter < $this->repeat)
		{
			$this->counter++;
			$this->subon = true;
			$this->suboption->setSuffix('_' . $this->counter);

			return (object) array(
				'type' => 'separator',
				'label' => str_replace('$', $this->counter, $this->infoData->label)
			);
		}
	}
}
