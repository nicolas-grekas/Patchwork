<?php

class loop_callAgent extends loop
{
	protected $agent;
	protected $keys;

	private $data;
	private $firstCall = true;

	public function __construct($agent, $keys = false)
	{
		$this->agent = $agent;
		if (false !== $keys) $this->keys = $keys;
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

				if (!isset($this->keys))
				{
					$a = CIA::agentClass($this->agent);
					$a = get_class_vars($a);
					$a = (array) $a['argv'];
					$a = array_map(array('IA','formatJs'), $a);

					$this->data->{'*k'} = '[' . implode(',', $a) . ']';
				}
				else $this->data->{'*k'} = $this->keys;
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
