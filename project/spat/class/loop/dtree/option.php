<?php

class loop_dtree_option extends loop_sql
{
	public function __construct($choice_id = 0)
	{
		parent::__construct(
			"SELECT
				option_id,
				choice_id,
				label,
				type
			FROM def_option
			WHERE position AND choice_id={$choice_id}
			ORDER BY position, label"
		);
	}

	protected function next()
	{
		$data = parent::next();

		if ($data) $data->choice = new loop_dtree_option_choice($data->option_id);

		return $data;
	}
}
