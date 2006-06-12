<?php

class loop_callAgent extends loop
{
	public $autoResolve = true;

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
				$data = $this->get();
				$data->{'a$'} = $this->agent;

				if ($this->autoResolve)
				{
					if (!isset($this->keys) || preg_match("'^(/|https?://)'", $this->agent))
					{
						list($CIApID, $home, $data->{'a$'}, $a, $k) = CIA::resolveAgentTrace($this->agent);

						foreach ($k as $k => $v) $data->$k = $v;

						array_walk($a, array('IA_js', 'formatJs'));

						$data->{'k$'} = '[' . implode(',', $a) . ']';

						if (false !== $home)
						{
							$data->{'v$'} = $CIApID;
							$data->{'r$'} = $home;						
						}
					}
					else $data->{'k$'} = $this->keys;
				}

				$this->data = $data;
			}

			return clone $this->data;
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
