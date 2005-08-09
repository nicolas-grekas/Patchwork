<?php

class loop_dtree_option_choice extends loop_sql
{
	public function __construct($option_id)
	{
		parent::__construct(
			"SELECT
				choice_id,
				option_id,
				label
			FROM def_choice
			WHERE position AND option_id={$option_id}
			ORDER BY position, label"
		);
	}

	protected function next()
	{
		$data = parent::next();

		if ($data) $data->option = new loop_dtree_option($data->choice_id);

		return $data;
	}
}
