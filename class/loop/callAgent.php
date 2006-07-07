<?php

class loop_callAgent extends loop
{
	public $autoResolve = true;

	protected $agent;
	protected $keys;

	private $data;
	private $firstCall = true;

	function __construct($agent, $keys = false)
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
						list($CIApID, $home, $data->{'a$'}, $keys, $a) = CIA::resolveAgentTrace($this->agent);

						foreach ($a as $k => &$v) $data->$k =& $v;

						array_walk($keys, 'jsquoteRef');

						$data->{'k$'} = '[' . implode(',', $keys) . ']';

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
