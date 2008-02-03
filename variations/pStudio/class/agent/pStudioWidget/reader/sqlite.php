<?php

class extends agent_pStudioWidget_reader
{
	protected

	//$language = 'sql',
	$template = 'pStudioWidget/reader/sqlite';

        //TODO si c'est un sqlite et que sqlite est présent alors
        //affichage du contenu de la base?

	function control(){}

	function compose($o)
	{
		$o = agent_pStudioWidget_reader::compose($o);

		$dbname = explode('.', $this->get->__0__);
		$dbname = array_reverse($dbname);
		$o->dbname = $dbname[0];

		// manque resolvePath....
		$db = new SQLiteDatabase($o->dbname,0666,$error);
		//$db->sqlite_open($o->dbname,0666,$error);

//		$sql = "SELECT name FROM sqlite_master WHERE (type = 'table')";

		$sql = "SELECT * FROM sqlite_master WHERE (type = 'table')";
E($db);
		$tables = array();
		while ($r =  $db->query($sql)->fetch())
		{
			$tables[] = $r;
			E($r);
		}

E($tables);


		$o->tables = new loop_array($tables, 'filter_rawArray');
E($o->tables);


		return $o;
	}


	function sqlite_list_tables ($db)
	{
		$tables = array ();

		$sql = "SELECT name FROM sqlite_master WHERE (type = 'table')";
		while($db->query($sql)->fetchObject())
		{
			$tables[] = sqlite_fetch_single($res);
		}

		return $tables;
	}


}

