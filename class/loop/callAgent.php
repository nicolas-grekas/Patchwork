<?php

class loop_callAgent extends loop
{
	protected $agent;
	protected $keys;

	private $data;
	private $firstCall = true;

	public function __construct($agent = '', $keys = array())
	{
		if ($agent) $this->agent = $agent;
		if ($keys) $this->keys = $keys;
	}

	final protected function prepare() {return 1;}
	final protected function next()
	{
		if ($this->firstCall)
		{
			$this->firstCall = false;
			if (!isset($this->data))
			{
				$this->data = $this->get();
				$this->data->{'*a'} = $this->agent;

				if (!CIA_SERVERSIDE)
				{
					if (!isset($this->keys))
					{
						$a = CIA::agentClass($this->agent);
						$a = get_class_vars($a);
						$a = (array) $a['argv'];
					}
					else $a = $this->keys;

					$a = array_map(array('IA','formatJs'), $a);
					$a = implode(',', $a);

					$this->data->{'*k'} = "[$a]";
				}
			}
			return $this->data;
		}
		else
		{
			$this->firstCall = true;
			return false;
		}
	}

	protected function get()
	{
		return (object) array();
	}
}
