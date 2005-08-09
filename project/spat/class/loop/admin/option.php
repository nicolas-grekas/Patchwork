<?php

class loop_admin_option extends loop_sql
{
	protected $optionType;
	protected $def_tax;

	protected $moveUp = false;
	protected $previousId = 0;
	protected $previousPosition = 0;
	protected $touchMsg;

	public function __construct($choice_id, $touchMsg)
	{
		$this->touchMsg = $touchMsg;

		$this->optionType = basicData::getOptionType();
		$this->def_tax = basicData::getDic('def_tax');

		parent::__construct(
			"SELECT *
			FROM def_option
			WHERE position
				AND choice_id={$choice_id}
			ORDER BY position, label"
		);
	}

	protected function next()
	{
		$data = parent::next();

		if ($data)
		{
			$data->type_label = $this->optionType[ $data->type ];
			$data->tax_label = $data->type == 'separator' ? '' : $this->def_tax[ $data->tax_id ];

			$form = new iaForm(false, 'o'.$data->option_id);
			$form->autoPopulate($data);
			$form->setPrefix('edit_');


			/* Form : move option up */

			$submit = $form->add('submit', 'up');
			if ($this->moveUp || $submit->isOn())
			{
				$this->moveUp($data->option_id, $data->position);
				CIA::redirect();
			}


			/* Form : move option down */

			$submit = $form->add('submit', 'down');
			if ($submit->isOn()) $this->moveUp = true;

			$this->previousId = $data->option_id;
			$this->previousPosition = $data->position;
		}
		else
		{
			$this->moveUp = false;
			$this->previousId = 0;
			$this->previousPosition = 0;
		}

		return $data;
	}

	protected function moveUp($option_id, $position)
	{
		if ($this->previousId)
		{
			DB()->autoExecute('def_option', array('position' => $this->previousPosition), DB_AUTOQUERY_UPDATE, 'option_id=' . $option_id);
			DB()->autoExecute('def_option', array('position' => $position), DB_AUTOQUERY_UPDATE, 'option_id=' . $this->previousId);
			CIA::touch($this->touchMsg);
		}
	}
}
