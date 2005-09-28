<?php

class agent_QJsrs_save extends agent_QJsrs
{
	protected $maxage = 0;
	protected $private = true;

	public function render()
	{
		$db = DB();

		$key = @$_POST['KEY'];
		switch ($key)
		{
				case 'nom':
				case 'prenom':
				case 'email':
				case 'tel_port':
				case 'tel_fixe':
				case 'tel_parent':
				case 'adresse':
				case 'adr_parent':
				case 'birthday':
				case 'activite':
				case 'actu':
				case 'autre':

					CIA::touch('sql/table/annuaire');

					$db->autoExecute(
							'annuaire',
							array($key => trim(@$_POST['DATA'])),
							DB_AUTOQUERY_UPDATE,
							'id=' . intval(@$_POST['ID'])
					);
		}

		return parent::render();
	}
}
