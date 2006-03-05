<?php

class loop_register_option_int extends loop
{
	protected $infoData = false;
	protected $repeat;
	protected $counter;
	protected $suboption;
	protected $subon = false;

	public function __construct($form, $submit, $repeat, $option_id)
	{
		$this->repeat = $repeat;
		$this->infoData = DB()->getRow(
			"SELECT *
			FROM def_choice
			WHERE label!=''
				AND position
				AND option_id={$option_id}
			ORDER BY position, label"
		);

		$this->suboption = new loop_register_option($form, $submit, $this->infoData->choice_id);
	}

	protected function prepare()
	{
		$this->counter = 0;
		$this->subon = false;
		$len = $this->suboption->getLength();
		return $len && $this->infoData ? $len*$this->repeat : 0;
	}

	protected function next()
	{
		if ($this->subon)
		{
			$data = $this->suboption->compose();
			if ($data) return $data;

			$this->subon = false;
		}

		if ($this->counter < $this->repeat)
		{
			$this->counter++;
			$this->subon = true;
			$this->suboption->setSuffix('_' . $this->counter);

			return (object) array(
				'type' => 'separator',
				'label' => str_replace('$', $this->counter, $this->infoData->label)
			);
		}
	}
}
