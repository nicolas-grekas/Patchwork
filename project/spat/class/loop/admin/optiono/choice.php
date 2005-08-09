<?php

class loop_admin_optiono_choice extends loop_sql
{
	protected $watchMessage;
	protected $moveUp = false;
	protected $previousId = 0;
	protected $previousPosition = 0;

	public function __construct($option_id, $watchMessage)
	{
		$this->watchMessage = $watchMessage;
		parent::__construct(
			"SELECT *
			FROM def_choice
			WHERE position AND option_id={$option_id}
			ORDER BY position, label"
		);
	}

	protected function next()
	{
		$info = parent::next();

		if ($info)
		{
			$data = (object) array('choice_id' => $info->choice_id);

			$form = new iaForm(false, 'choice'.$info->choice_id);
			$form->autoPopulate($data);
			$form->setPrefix('edit_');


			/* Form : move choice up */

			$submit = $form->add('submit', 'up');
			if ($this->moveUp || $submit->isOn())
			{
				$this->moveUp($info->choice_id, $info->position);
				CIA::redirect('admin/optiono/');
			}


			/* Form : move choice down */

			$submit = $form->add('submit', 'down');
			if ($submit->isOn()) $this->moveUp = true;


			/* Form : delete choice */

			$submit = $form->add('submit', 'del');
			if ($submit->isOn())
			{
				$this->delChoice($info->choice_id);
				CIA::redirect('admin/optiono/');
			}


			/* Form : edit choice */

			$form->add('text', 'label');
			$form->add('text', 'amount', array('valid' => 'int', 0));
			$form->add('text', 'amount_raised', array('valid' => 'int', 0));

			$submit = $form->add('submit', 'submit');
			$submit->add(
				'label', T("Merci de préciser l'intitulé"), '',
				'amount', '', T('Le montant doit être un nombre positif'),
				'amount_raised', '', T('Le montant doit être un nombre positif')
			);

			if ($submit->isOn())
			{
				$this->editChoice($info->choice_id, $submit->getData());
				CIA::redirect('admin/optiono/');
			}

			$data->edit_label->setValue($info->label);
			$data->edit_amount->setValue($info->amount?$info->amount:'');
			$data->edit_amount_raised->setValue($info->amount_raised?$info->amount_raised:'');

			$this->previousId = $info->choice_id;
			$this->previousPosition = $info->position;
		}
		else $data = false;

		return $data;
	}

	protected function editChoice($choice_id, $data)
	{
		DB()->autoExecute('def_choice', $data, DB_AUTOQUERY_UPDATE, 'choice_id=' . $choice_id);
		CIA::touch($this->watchMessage);
	}

	protected function delChoice($choice_id)
	{
		DB()->autoExecute('def_choice', array('position' => 0), DB_AUTOQUERY_UPDATE, 'choice_id=' . $choice_id);
		CIA::touch($this->watchMessage);
	}

	protected function moveUp($choice_id, $position)
	{
		if ($this->previousId)
		{
			DB()->autoExecute('def_choice', array('position' => $this->previousPosition), DB_AUTOQUERY_UPDATE, 'choice_id=' . $choice_id);
			DB()->autoExecute('def_choice', array('position' => $position), DB_AUTOQUERY_UPDATE, 'choice_id=' . $this->previousId);
			CIA::touch($this->watchMessage);
		}
	}
}
