<?php

class loop_admin_optiono extends loop_sql
{
	protected $watchMessage;
	protected $optionType;
	protected $def_tax;

	protected $moveUp = false;
	protected $previousId = 0;
	protected $previousPosition = 0;

	public function __construct($choice_id, $watchMessage)
	{
		$this->watchMessage = $watchMessage;
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
		$info = parent::next();

		if ($info)
		{
			$data = (object) array('option_id' => $info->option_id);

			$form = new iaForm(false, 'option'.$info->option_id);
			$form->autoPopulate($data);
			$form->setPrefix('edit_');


			/* Form : move choice up */

			$submit = $form->add('submit', 'up');
			if ($this->moveUp || $submit->isOn())
			{
				$this->moveUp($info->option_id, $info->position);
				CIA::redirect('admin/optiono/');
			}


			/* Form : move choice down */

			$submit = $form->add('submit', 'down');
			if ($submit->isOn()) $this->moveUp = true;


			/* Form : delete option */

			$submit = $form->add('submit', 'del');
			if ($submit->isOn())
			{
				$this->delOption($info->option_id);
				CIA::redirect('admin/optiono/');
			}


			/* Populate data */

			$data->choice = new loop_admin_optiono_choice($info->option_id, $this->watchMessage);
			$length = $data->choice->getLength();


			/* Form : edit option */

			$optionType = $this->optionType;
			if ($length > 1)
			{
				$optionType = array(
					'select' => $optionType['select'],
					'check' => $optionType['check']
				);
			}

			$form->add('select', 'type', array('item' => $optionType));
			$form->add('select', 'tax_id', array('item' => $this->def_tax));
			$form->add('text', 'label');

			$submit = $form->add('submit', 'submit');
			$submit->add(
				'type', '', '',
				'tax_id', '', '',
				'label', T("Merci de préciser l'intitulé"), ''
			);

			if ($submit->isOn())
			{
				$this->editOption($info->option_id, $submit->getData());
				CIA::redirect('admin/optiono/');
			}

			$data->edit_type->setValue($info->type);
			$data->edit_tax_id->setValue($info->tax_id);
			$data->edit_label->setValue($info->label);


			/* Form : new choice */

			if ($info->type == 'select' || $info->type == 'check' || ($info->type == 'int' && !$length))
			{
				$form->setPrefix('new_');

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
					$this->addChoice($info->option_id, $submit->getData());
					CIA::redirect('admin/optiono/');
				}
			}


			$this->previousId = $info->option_id;
			$this->previousPosition = $info->position;
		}
		else $data = false;

		return $data;
	}

	protected function addChoice($option_id, $data)
	{
		$db = DB();

		$next_id = $db->nextId('choice');
		$data['choice_id'] = $next_id;
		$data['option_id'] = $option_id;
		$data['position'] = $next_id;

		$db->autoExecute('def_choice', $data, DB_AUTOQUERY_INSERT);
		CIA::touch($this->watchMessage);
	}

	protected function editOption($option_id, $data)
	{
		DB()->autoExecute('def_option', $data, DB_AUTOQUERY_UPDATE, 'option_id=' . $option_id);
		CIA::touch($this->watchMessage);
	}

	protected function delOption($option_id)
	{
		DB()->autoExecute('def_option', array('position' => 0), DB_AUTOQUERY_UPDATE, 'option_id=' . $option_id);
		CIA::touch($this->watchMessage);
	}

	protected function moveUp($option_id, $position)
	{
		if ($this->previousId)
		{
			DB()->autoExecute('def_option', array('position' => $this->previousPosition), DB_AUTOQUERY_UPDATE, 'option_id=' . $option_id);
			DB()->autoExecute('def_option', array('position' => $position), DB_AUTOQUERY_UPDATE, 'option_id=' . $this->previousId);
			CIA::touch($this->watchMessage);
		}
	}
}
