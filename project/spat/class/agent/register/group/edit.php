<?php

class agent_register_group_edit extends agent_register_group_member
{
	public $argv = array('__0__');

	public function compose()
	{
		$this->member =& SESSION::get('groupMember');
		$editList = false;
		$data = (object) array();

		if ($this->argv->__0__)
		{
			$this->argv->__0__ = G('__0__', 'int', 1, count($this->member));
			if ($this->argv->__0__ === false) CIA::redirect('register/group/member/');

			$member =& $this->member[$this->argv->__0__ - 1];
		}
		else
		{
			if (SESSION::get('editList')) $editList =& SESSION::get('editList');
			else
			{
				$data->finalStep = 1;
				$editList = array();
				foreach ($this->member as $k => $v) if (!isset($v['edited'])) $editList[] = $k+1;
			}

			$member = array(0);
		}

		$form = new iaForm($data);
		$form->sessionLink =& $member;

		if ($editList) $data->member = new loop_array($editList, array($this, 'filterMember'));
		else
		{
			$form->add('text', 'firstname');
			$form->add('text', 'lastname');
			$form->add('text', 'email');
			$form->add('text', 'phone');
			$form->add('text', 'fax');

			$form->add('text', 'company');
			$form->add('textarea', 'adress');
			$form->add('text', 'zipcode');
			$form->add('text', 'city');
			$form->add('QSelect', 'country', array('src' => 'QSelect/countries'));
		}

		$submit = $form->add('submit', 'submit');

		$data->option = new loop_register_option($form, $submit);
		$data->option->loop();

		if ($submit->isOn())
		{
			if (!$data->finalStep) $member['edited'] = true;

			if ($editList)
			{
				foreach ($editList as $id) $this->member[$id - 1] = array_merge($this->member[$id - 1], $member);
				SESSION::set('editList');

				if ($data->finalStep) CIA::redirect('register/group/summary/');
			}
			else
			{
				$this->cleanMember();
			}

			CIA::redirect('register/group/member/');
		}

		return $data;
	}

	public function filterMember($data)
	{
		$data = (object) array('VALUE' => $this->member[$data->VALUE - 1]);

		return parent::filterMember($data);
	}
}
