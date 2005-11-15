<?php

class agent_index extends agent
{
	public function render()
	{
		$data = (object) array(
			'doc' => new loop_sql('SELECT * FROM doc ORDER BY label')
		);

		$form = new iaForm($data);

		$form->add('text', 'label');
		$submit = $form->add('submit', 'submit');
		$submit->add('label', T('Merci de préciser le libellé'), '');

		if ($submit->isOn())
		{
			$db = DB();

			$data = $submit->getData();
			$data['docId'] = $db->nextId('doc');

			$db->autoExecute(
				'doc',
				$data,
				DB_AUTOQUERY_INSERT
			);

			$data['label'] = T('Sans-titre 1');
			$data['tabId'] = $db->nextId('tab');

			$db->autoExecute(
				'tab',
				$data,
				DB_AUTOQUERY_INSERT
			);

			CIA::redirect('grid/' . $data['docId']);
		}

		return $data;
	}
}
