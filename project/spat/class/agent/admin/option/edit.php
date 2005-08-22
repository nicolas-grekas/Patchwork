<?php

class agent_admin_option_edit extends agent
{
	public $argv = array('__1__');
	public $watch = array('option/all');

	public $previousId = 0;
	public $previousPos = 0;

	public $moveUp = false;
	public $moveDown = false;
	public $delChoice = false;

	protected $option_id;

	public function render()
	{
		if (!is_numeric($this->argv->__1__)) CIA::redirect('admin/option');

		$this->option_id = $this->argv->__1__;

		$sql = "SELECT * FROM def_option WHERE option_id=" . $this->option_id;
		$data = DB()->getRow($sql);
		if (!$data) CIA::redirect('admin/option');

		$data->choice = new loop_sql(
			"SELECT * FROM def_choice WHERE parent_option_id={$this->option_id} ORDER BY position",
			array($this, 'renderChoice')
		);

		$form = new iaForm;
		$form->autoPopulate($data);


		$form->setPrefix('edit_');
		$form->add('text'  , 'label', array('default' => $data->label));
		$form->add('text'  , 'min_default', array('valid' => 'int', 0, 'default' => $data->min_default));
		$form->add('text'  , 'max_default', array('valid' => 'int', 0, 'default' => $data->max_default));
		$form->add('select', 'tax_id', array('item' => basicData::getDic('def_tax'), 'default' => $data->tax_id));
		$form->add('check' , 'admin_only', array('item' => basicData::yesNo(), 'default' => $data->admin_only));
		$submit = $form->add('submit', 'submit');

		$submit->add(
			'label', T("Précisez l'intitulé"), '',
			'min_default', '', '',
			'max_default', '', '',
			'tax_id', '', '',
			'admin_only', '', ''
		);
		
		if ($submit->isOn()) $this->updateOption($submit->getData());


		$form->setPrefix('f_');
		$form->add('text', 'choice', array('valid' => 'int', 1), false);

		$up    = $form->add('submit', 'up');
		$down  = $form->add('submit', 'down');
		$del   = $form->add('submit', 'del');

		$up   ->add('choice', T('Choisissez un choix'), '');
		$down ->add('choice', T('Choisissez un choix'), '');
		$del  ->add('choice', T('Choisissez un choix'), '');

		     if ($up   ->isOn()) {$this->moveUp      = $up   ->getData(); $this->moveUp      = $this->moveUp     ['choice'];}
		else if ($down ->isOn()) {$this->moveDown    = $down ->getData(); $this->moveDown    = $this->moveDown   ['choice'];}
		else if ($del  ->isOn()) {$this->delChoice   = $del  ->getData(); $this->delChoice   = $this->delChoice  ['choice'];}


		$form->setPrefix('new_');
		$form->add('text'  , 'label');
		$form->add('text'  , 'price_default', array('valid' => 'int', 0));
		$form->add('text'  , 'upper_price_default', array('valid' => 'int', 0));
		$form->add('text'  , 'quota_max', array('valid' => 'int', 0));
		$form->add('check' , 'admin_only', array(
			'item' => basicData::yesNo(),
			'default' => 0
		));
		$submit = $form->add('submit', 'submit');

		$submit->add(
			'label', T("Précisez l'intitulé"), '',
			'price_default', '', '',
			'upper_price_default', '', '',
			'quota_max', '', '',
			'admin_only', '', ''
		);
		
		if ($submit->isOn()) $this->createChoice($submit->getData());

		return $data;
	}

	public function renderChoice($data)
	{
		if ($this->moveDown == $data->choice_id)
		{
			$this->moveDown = -1;
		}
		else if ($this->moveDown == -1 || $this->moveUp == $data->choice_id)
		{
			dao_option::swapChoicePosition(
				$data->choice_id , $data->position,
				$this->previousId, $this->previousPos
			);
			CIA::redirect();
		}
		else if ($this->delChoice == $data->choice_id)
		{
			dao_option::deleteChoice($data->choice_id);
			CIA::redirect();
		}

		$this->previousId = $data->choice_id;
		$this->previousPos = $data->position;

		return $data;
	}

	protected function updateOption($data)
	{
		dao_option::updateOption($this->option_id, $data);

		CIA::redirect();
	}

	protected function createChoice($data)
	{
		dao_option::addChoice($this->option_id, $data);

		CIA::redirect();
	}
}
