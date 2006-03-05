<?php

class agent_register_group_member extends agent
{
	protected $member = array();

	public function compose()
	{
		$data = (object) array();

		$this->member =& SESSION::get('groupMember');
		if (!$this->member) $this->member = array();

		$form = new iaForm($data);

		$form->add('textarea', 'member');
		$form->add('check', 'check', array(
			'valid' => 'int', 1, count($this->member),
			'multiple' => true,
		));
		
		$edit = $form->add('submit', 'edit');
		$edit->add('check', T("Aucun membre sélectionné"), '');
		if ($edit->isOn())
		{
			SESSION::set('editList', $data->f_check->getValue());
			CIA::redirect('register/group/edit/');
		}

		$del = $form->add('submit', 'del');
		$del->add('check', T("Aucun membre sélectionné"), '');
		if ($del->isOn())
		{
			$del = (array) $data->f_check->getValue();
			rsort($del);
			foreach( $del as $id) array_splice($this->member, $id - 1, 1);
			CIA::redirect('register/group/member/');
		}

		$add = $form->add('submit', 'add');
		$submit = $form->add('submit', 'submit');

		if ($add->isOn() || (!$this->member && $submit->isOn()))
		{
			$this->addMember( $data->f_member->getValue() );
			CIA::redirect('register/group/member/');
		}
		else if ($submit->isOn())
		{
			SESSION::set('editList');
			CIA::redirect('register/group/edit/');
		}

		$data->member = new loop_array($this->member, array($this, 'filterMember'));

		return $data;
	}

	public function filterMember($data)
	{
		$data2 = (object) array(
			'lastname' => $data->VALUE['f_lastname'],
			'firstname' => $data->VALUE['f_firstname'],
			'email' => $data->VALUE['f_email'],
		);

		$data2->edited = isset($data->VALUE['edited']);

		return $data2;

	}

	protected function addMember($newmember)
	{
		$newmember = trim( $newmember );
		if ($newmember)
		{
			$newmember = preg_split("'\s*[\n\r]\s*'su", $newmember);
			$id = count($this->member);
			foreach ($newmember as $value)
			{
				$value = str_replace("\t", ';', $value);
				$value = preg_replace("'\s{2,}'su", ' ', $value);
				$value = preg_split("'\s*[;:,]\s*'su", $value);

				$this->member[] = array(
					'f_lastname' => $value[0],
					'f_firstname' => @$value[1],
					'f_email' => @$value[2],
				);
			}
		}

		$this->cleanMember();
	}

	protected function cleanMember()
	{
		LIB::sort($this->member, 'f_lastname, f_firstname, f_email');

		$prev = array(
			'f_lastname' => '',
			'f_firstname' => '',
			'f_email' => '',
		);

		$len = count($this->member);
		for ($i = 0; $i < $len; ++$i)
		{
			$v =& $this->member[$i];
			if (
				LIB::stripAccents($prev['f_lastname'], 1) == LIB::stripAccents($v['f_lastname'], 1)
				&& LIB::stripAccents($prev['f_firstname'], 1) == LIB::stripAccents($v['f_firstname'], 1)
				&& mb_strtolower($prev['f_email']) == mb_strtolower($v['f_email'])
			)
			{
				array_splice($this->member, $i, 1);
				--$len;
				--$i;
			}
			else $prev = $v;
		}
	}
}
