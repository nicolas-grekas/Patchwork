<?php

class agent_QSelect_countries extends agent_QSelect
{
	protected $maxage = -1;

	public function compose()
	{
		return (object) array(
			'DATA' => new loop_sql(
				'SELECT t.' . CIA_LANG . ' AS VALUE
				FROM sys_translate t, dic_country c
				WHERE t.__=c.label AND c.position
				ORDER BY VALUE'
			)
		);
	}
}
