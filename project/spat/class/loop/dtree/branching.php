<?php

class loop_dtree_branching extends loop_sql
{
	protected $previousOption = 0;
	protected $nextData = false;

	public function __construct($node_id = 0)
	{
		$sql = "SELECT
					b.node_id,
					b.parent_node_id,
					o.option_id,
					c.choice_id,
					o.label AS optionLabel,
					o.type,
					c.label AS choiceLabel
				FROM def_option_branching b,
					def_option o,
					def_choice c
				WHERE
					b.parent_node_id={$node_id}
					AND o.option_id=b.option_id
					AND c.choice_id=b.choice_id
				ORDER BY
					o.position,
					c.position";

		parent::__construct($sql);
	}

	protected function next()
	{
		if ($this->nextData)
		{
			$data = $this->nextData;
			$this->nextData = false;
		}
		else
		{
			$row = parent::next();

			if ($row)
			{
				$data = (object) array(
					'tree' => new loop_dtree_branching($row->node_id),
					'id' => 'c'.$row->node_id,
					'pid' => 'o'.$row->parent_node_id,
					'label' => $row->choiceLabel,
					'url' => 'admin/option/branching/connect/' . $row->node_id,
					'icon' => 'img/dtree/page.gif',
					'iconOpen' => 'img/dtree/page.gif',
				);
					
				if ($this->previousOption != $row->option_id)
				{
					$this->nextData = $data;

					$data = (object) array(
						'tree' => 0,
						'id' => 'o'.$row->parent_node_id,
						'pid' => 'c'.$row->parent_node_id,
						'label' => $row->optionLabel,
						'url' => 'admin/option/branching/edit/' . $row->node_id,
						'icon' => "img/dtree/{$row->type}.gif",
						'iconOpen' => "img/dtree/{$row->type}.gif",
					);
				}

				$this->previousOption = $row->option_id;
			}
			else $data = false;
		}

		return $data;
	}
}
