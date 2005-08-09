<?php

class agent_admin_option extends agent
{
	public $argv = array('__1__', '__2__');
	public $watch = array('option/all');

	public $canPost = true;
	protected $touchMsg = 'option/all';

	public function render()
	{
		$data = (object) array();

		$option_id = G('__1__', 'int', 0);
		$choice_id = G('__2__', 'int', 0);

		if ($option_id===false) $option_id = 0;
		if ($choice_id===false) $choice_id = 0;

		$form = new iaForm;
		$form->autoPopulate($data);

		if (!$option_id || $choice_id) // Root or choice
		{
			if (!$choice_id)
			{
				$choice_id = 0;
				$row = (object) array('type' => 'select');
			}
			else
			{
				$sql = "SELECT c.*, o.type
						FROM def_choice c, def_option o
						WHERE c.position AND c.choice_id={$choice_id}
							AND c.option_id=o.option_id
						ORDER BY c.position, c.label";
				$row = DB()->getRow($sql);

				$form->setPrefix('edit_');


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
					$this->editChoice($choice_id, $submit->getData());
					CIA::redirect();
				}

				$data->edit_label->setValue($row->label);
				$data->edit_amount->setValue($row->amount ? $row->amount : '');
				$data->edit_amount_raised->setValue($row->amount_raised ? $row->amount_raised : '');


				/* Form : delete choice */

				$submit = $form->add('submit', 'del');
				if ($submit->isOn())
				{
					$this->delChoice($choice_id);
					CIA::redirect();
				}
			}


			/* Form : new option */

			if ($row->type == 'select' || $row->type == 'quantity')
			{
				$data->option = new loop_admin_option($choice_id, $this->touchMsg);

				$form->setPrefix('new_');

				$form->add('select', 'type', array('item' => basicData::getOptionType(), 'firstItem' => ''));
				$form->add('select', 'tax_id', array('item' => basicData::getDic('def_tax')));
				$form->add('text', 'label');

				$submit = $form->add('submit', 'submit');
				$submit->add(
					'label', T("Merci de préciser l'intitulé"), '',
					'type', T("Merci de préciser le type d'option"), '',
					'tax_id', '', ''
				);

				if ($submit->isOn())
				{
					$this->addOption($choice_id, $submit->getData());
					CIA::redirect();
				}
			}
		}
		else if (!$choice_id) // Option
		{
			$sql = "SELECT *
					FROM def_option
					WHERE option_id={$option_id}";
			$row = DB()->getRow($sql);

			$data->type = $row->type;
			$data->type_label = basicData::getOptionType();
			$data->type_label = $data->type_label[ $row->type ];
			
			$form->setPrefix('edit_');


			/* Form : edit option */

			$form->add('text', 'label');

			$submit = $form->add('submit', 'submit');
			$submit->add('label', T("Merci de préciser l'intitulé"), '');

			if ('separator' != $row->type)
			{
				$form->add('select', 'tax_id', array('item' => basicData::getDic('def_tax')));
				$submit->add('tax_id', '', '');
			}

			if ('quantity' == $row->type)
			{
				$form->setPrefix('quantity_');

				$form->add('text', 'label');
				$form->add('text', 'amount', array('valid' => 'int', 0));
				$form->add('text', 'amount_raised', array('valid' => 'int', 0));

				$sql = "SELECT *
						FROM def_choice
						WHERE position AND option_id={$option_id}";
				$quantity = DB()->getRow($sql);

				$form->setPrefix('edit_');
			}
			else $quantity = false;

			if ($submit->isOn())
			{
				$this->editOption($option_id, $submit->getData());

				if ('quantity' == $row->type)
				{
					if ($quantity)
					{
						$choice_id = $quantity->choice_id;
						$quantity = array(
							'label' => $data->quantity_label->getValue(),
							'amount' => $data->quantity_amount->getValue(),
							'amount_raised' => $data->quantity_amount_raised->getValue(),
						);

						$this->editChoice($choice_id, $quantity);
					}
					else
					{
						$quantity = array(
							'label' => $data->quantity_label->getValue(),
							'amount' => $data->quantity_amount->getValue(),
							'amount_raised' => $data->quantity_amount_raised->getValue(),
						);

						$this->addChoice($option_id, $quantity);
					}
				}

				CIA::redirect();
			}

			$data->edit_label->setValue($row->label);
			if (isset($data->edit_tax_id)) $data->edit_tax_id->setValue($row->tax_id);
			if ($quantity)
			{
				$data->quantity_label->setValue($quantity->label);
				$data->quantity_amount->setValue($quantity->amount);
				$data->quantity_amount_raised->setValue($quantity->amount_raised);
			}


			/* Form : delete option */

			$submit = $form->add('submit', 'del');
			if ($submit->isOn())
			{
				$this->delOption($option_id);
				CIA::redirect();
			}


			/* Form : new choice */

			if ($row->type == 'select' || $row->type == 'check' || $row->type == 'check-multiple')
			{
				$data->choice = new loop_admin_option_choice($option_id, $this->touchMsg);

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
					$this->addChoice($option_id, $submit->getData());
					CIA::redirect();
				}
			}			
		}

		return $data;
	}

	protected function editOption($option_id, $data)
	{
		DB()->autoExecute('def_option', $data, DB_AUTOQUERY_UPDATE, 'option_id=' . $option_id);
		CIA::touch($this->touchMsg);
	}

	protected function delOption($option_id)
	{
		DB()->autoExecute('def_option', array('position' => 0), DB_AUTOQUERY_UPDATE, 'option_id=' . $option_id);
		CIA::touch($this->touchMsg);
	}

	protected function addChoice($option_id, $data)
	{
		$db = DB();

		$next_id = $db->nextId('choice');
		$data['choice_id'] = $next_id;
		$data['option_id'] = $option_id;
		$data['position'] = $next_id;

		$db->autoExecute('def_choice', $data, DB_AUTOQUERY_INSERT);
		CIA::touch($this->touchMsg);
	}

	protected function editChoice($choice_id, $data)
	{
		DB()->autoExecute('def_choice', $data, DB_AUTOQUERY_UPDATE, 'choice_id=' . $choice_id);
		CIA::touch($this->touchMsg);
	}

	protected function delChoice($choice_id)
	{
		DB()->autoExecute('def_choice', array('position' => 0), DB_AUTOQUERY_UPDATE, 'choice_id=' . $choice_id);
		CIA::touch($this->touchMsg);
	}

	protected function addOption($choice_id, $data)
	{
		$db = DB();

		$next_id = $db->nextId('option');
		$data['option_id'] = $next_id;
		$data['choice_id'] = $choice_id;
		$data['position'] = $next_id;

		$db->autoExecute('def_option', $data, DB_AUTOQUERY_INSERT);
		CIA::touch($this->touchMsg);
	}
}
