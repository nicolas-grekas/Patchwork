<?php

class agent_admin_option extends agent
{
	public $watch = array('option/all');

	public $previousId = 0;
	public $previousPos = 0;

	public $moveUp = false;
	public $moveDown = false;
	public $delOption = false;
	public $cloneOption = false;

	public function render()
	{
		$data = (object) array();

		$data->option = new loop_sql(
			"SELECT * FROM def_option ORDER BY position",
			array($this, 'renderOption')
		);

		$form = new iaForm;
		$form->autoPopulate($data);

		$form->add('text', 'option', array('valid' => 'int', 1), false);

		$up    = $form->add('submit', 'up');
		$down  = $form->add('submit', 'down');
		$del   = $form->add('submit', 'del');
		$clone = $form->add('submit', 'clone');

		$up   ->add('option', T('Choisissez une option'), '');
		$down ->add('option', T('Choisissez une option'), '');
		$del  ->add('option', T('Choisissez une option'), '');
		$clone->add('option', T('Choisissez une option'), '');

		     if ($up   ->isOn()) {$this->moveUp      = $up   ->getData(); $this->moveUp      = $this->moveUp     ['option'];}
		else if ($down ->isOn()) {$this->moveDown    = $down ->getData(); $this->moveDown    = $this->moveDown   ['option'];}
		else if ($del  ->isOn()) {$this->delOption   = $del  ->getData(); $this->delOption   = $this->delOption  ['option'];}
		else if ($clone->isOn()) {$this->cloneOption = $clone->getData(); $this->cloneOption = $this->cloneOption['option'];}

		$form->add('select', 'type'  , array('firstItem' => '', 'item' => basicData::getOptionType()));
		$form->add('text'  , 'label');
		$form->add('select', 'tax_id', array('item' => basicData::getDic('def_tax')));
		$form->add('check' , 'admin_only', array(
			'item' => basicData::yesNo(),
			'default' => 0
		));
		$submit = $form->add('submit', 'submit');

		$submit->add(
			'type' , T("Précisez le type d'option"), '',
			'label', T("Précisez l'intitulé"), '',
			'tax_id', '', '',
			'admin_only', '', ''
		);
		
		if ($submit->isOn()) $this->createOption($submit->getData());

		return $data;
	}

	public function renderOption($data)
	{
		if ($this->moveDown == $data->option_id)
		{
			$this->moveDown = -1;
		}
		else if ($this->moveDown == -1 || $this->moveUp == $data->option_id)
		{
			dao_option::swapOptionPosition(
				$data->option_id , $data->position,
				$this->previousId, $this->previousPos
			);
			CIA::redirect();
		}
		else if ($this->delOption == $data->option_id)
		{
			dao_option::deleteOption($data->option_id);
			CIA::redirect();
		}
		else if ($this->cloneOption == $data->option_id)
		{
			$option_id = dao_option::cloneOption($data->option_id);
			CIA::redirect('admin/option/edit/' . $option_id);
		}

		$data->choice = new loop_sql("SELECT * FROM def_choice WHERE parent_option_id={$data->option_id} ORDER BY position");

		$this->previousId = $data->option_id;
		$this->previousPos = $data->position;

		return $data;
	}

	protected function createOption($data)
	{
		$data['min_default'] = 0;
		$data['max_default'] = 0;

		dao_option::addOption($data);

		CIA::redirect();
	}
}
