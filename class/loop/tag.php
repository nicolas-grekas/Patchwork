<?php

class loop_tag extends loop
{
	protected $loop;
	protected $freqKey;
	protected $sizeKey;
	protected $range;

	protected $histo;
	protected $min;
	protected $max;

	protected $histoCumule = array();
	protected $dynamic;

	function __construct($loop, $freqKey, $sizeKey, $range)
	{
		$this->loop = $loop;
		$this->freqKey = $freqKey;
		$this->sizeKey = $sizeKey;
		$this->range = $range;
	}

	function setHisto($histo, $min, $max)
	{
		$this->histo = $histo;
		$this->min = $min;
		$this->max = $max;
	}

	protected function prepare()
	{
		$loop = $this->loop;

		if (!$this->histoCumule)
		{
			$histo =& $this->histo;
			$min =& $this->min;
			$max =& $this->max;

			if (!isset($histo))
			{
				$histo = array();
				$min = -1;
				$max = 0;

				while ($a = $loop->loop())
				{
					@++$histo[ $a->{$this->freqKey} ];
					$min = $min < 0 ? $a->{$this->freqKey} : min($min, $a->{$this->freqKey});
					$max = max($max, $a->{$this->freqKey});
				}
			}

			$histoCumule = array( $min - 1 => 0 );
			$dynamic = $max - $min;

			if ($dynamic > 0)
			{
				$histo[$min] = 0;

				do $histoCumule[$min] = $histoCumule[$min - 1] + @$histo[$min];
				while (++$min <= $max);

				$this->dynamic = 1 / ($histoCumule[$max - 1] + $histoCumule[$max] + 1);
			}
			else $this->dynamic = 0;

			$this->histoCumule =& $histoCumule;

			unset($this->histo);
		}

		return $loop->getLength();
	}

	protected function next()
	{
		$a = $this->loop->loop();

		if ($a)
		{
			$b = $a->{$this->freqKey};
			$a->{$this->sizeKey} = $this->dynamic
				? intval($this->range * $this->dynamic * ($this->histoCumule[$b-1] + $this->histoCumule[$b]))
				: $this->range;
		}

		return $a;
	}
}
