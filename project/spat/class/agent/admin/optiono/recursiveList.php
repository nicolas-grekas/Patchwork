<?php

class agent_admin_optiono_recursiveList extends agent
{
	public $argv = array('choice_id');
	public $canPost = true;

	protected $watchMessage;

	public function render()
	{
		$this->argv->choice_id = intval($this->argv->choice_id);
		$this->watchMessage = 'sql/option/choice_id/' . $this->argv->choice_id;
		$this->watch[] = $this->watchMessage;

		$data = (object) array(
			'choice_id' => $this->argv->choice_id,
			'option' => new loop_admin_optiono($this->argv->choice_id, $this->watchMessage),
		);

		$form = new iaForm(false, 'pchoice' . $this->argv->choice_id);
		$form->autoPopulate($data);
		$form->setPrefix('new_');

		$form->add('select', 'type', array('item' => basicData::getOptionType()));
		$form->add('select', 'tax_id', array('item' => basicData::getDic('def_tax')));
		$form->add('text', 'label');

		$submit = $form->add('submit', 'submit');
		$submit->add(
			'type', '', '',
			'tax_id', '', '',
			'label', T("Merci de préciser l'intitulé"), ''
		);

		if ($submit->isOn())
		{
			$this->addOption($submit->getData());
			CIA::redirect('admin/optiono/');
		}

		return $data;
	}

	protected function addOption($data)
	{
		$db = DB();

		$next_id = $db->nextId('option');
		$data['option_id'] = $next_id;
		$data['choice_id'] = $this->argv->choice_id;
		$data['position'] = $next_id;

		$db->autoExecute('def_option', $data, DB_AUTOQUERY_INSERT);
		CIA::touch($this->watchMessage);
	}
}
