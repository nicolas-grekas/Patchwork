<?php

class loop_admin_option_choice extends loop_sql
{
	protected $moveUp = false;
	protected $previousId = 0;
	protected $previousPosition = 0;
	protected $touchMsg;

	public function __construct($option_id, $touchMsg)
	{
		$this->touchMsg = $touchMsg;

		parent::__construct(
			"SELECT *
			FROM def_choice
			WHERE position AND option_id={$option_id}
			ORDER BY position, label"
		);
	}

	protected function next()
	{
		$data = parent::next();

		if ($data)
		{
			$form = new iaForm(false, 'c'.$data->choice_id);
			$form->autoPopulate($data);
			$form->setPrefix('edit_');


			/* Form : move choice up */

			$submit = $form->add('submit', 'up');
			if ($this->moveUp || $submit->isOn())
			{
				$this->moveUp($data->choice_id, $data->position);
				CIA::redirect();
			}


			/* Form : move choice down */

			$submit = $form->add('submit', 'down');
			if ($submit->isOn()) $this->moveUp = true;

			$this->previousId = $data->choice_id;
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

	protected function moveUp($choice_id, $position)
	{
		if ($this->previousId)
		{
			DB()->autoExecute('def_choice', array('position' => $this->previousPosition), DB_AUTOQUERY_UPDATE, 'choice_id=' . $choice_id);
			DB()->autoExecute('def_choice', array('position' => $position), DB_AUTOQUERY_UPDATE, 'choice_id=' . $this->previousId);
			CIA::touch($this->touchMsg);
		}
	}
}
