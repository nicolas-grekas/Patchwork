<?php

register_shutdown_function(array('CIA', 'touch'), 'option/all');

class dao_option
{
	/**
	 * Create a new option.
	 */
	public function addOption($data)
	{
		$db = DB();

		$option_id = $db->nextId('option');
		$data = array(
			'option_id'   => $option_id,
			'type'        => $data['type'],
			'label'       => $data['label'],
			'admin_only'  => $data['admin_only'],
			'tax_id'      => $data['tax_id'],
			'min_default' => $data['min_default'],
			'max_default' => $data['max_default'],
			'position'    => $option_id,
		);

		$db->autoExecute('def_option', $data, DB_AUTOQUERY_INSERT);

		return $option_id;
	}

	/**
	 * Update an option.
	 */
	public function updateOption($option_id, $data)
	{
		$update = array();

		isset($data['label']      ) && ($update['label']       = $data['label']      );
		isset($data['admin_only'] ) && ($update['admin_only']  = $data['admin_only'] );
		isset($data['tax_id']     ) && ($update['tax_id']      = $data['tax_id']     );
		isset($data['min_default']) && ($update['min_default'] = $data['min_default']);
		isset($data['max_default']) && ($update['max_default'] = $data['max_default']);

		if ($update)
		{
			DB()->autoExecute('def_option', $update, DB_AUTOQUERY_UPDATE, 'option_id=' . $option_id);
		}
	}

	public static function swapOptionPosition($option_id1, $position1, $option_id2, $position2)
	{
		if ($option_id1 && $option_id2)
		{
			DB()->autoExecute('def_option', array('position' => $position2), DB_AUTOQUERY_UPDATE, 'option_id=' . $option_id1);
			DB()->autoExecute('def_option', array('position' => $position1), DB_AUTOQUERY_UPDATE, 'option_id=' . $option_id2);
		}
	}

	/**
	 * Clone an existing option to save some option design time.
	 */
	public function cloneOption($option_id)
	{
		$db = DB();

		$sql = "SELECT * FROM def_option WHERE option_id=" . $option_id;
		if ($row = $db->getRow($sql))
		{
			$new_option_id = $db->nextId('option');

			$row->option_id = $new_option_id;
			$row->position = $new_option_id;
			$db->autoExecute('def_option', (array) $row, DB_AUTOQUERY_INSERT);

			$sql = "SELECT * FROM def_choice WHERE parent_option_id={$option_id} ORDER BY position";
			$result = $db->query($sql);
			while ($row = $result->fetchRow())
			{
				$row->parent_option_id = $new_option_id;
				$row->choice_id = $row->position = $db->nextId('choice');
				$db->autoExecute('def_choice', (array) $row, DB_AUTOQUERY_INSERT);
			}
		}
		else $new_option_id = 0;

		return $new_option_id;
	}

	/**
	 * Delete an option
	 * 
	 * An option can not be deleted when one of its choice has been selected one time or more.
	 * However, it is disabled in the option tree.
	 * 
	 */
	public function deleteOption($option_id)
	{
		$db = DB();

		$sql = "SELECT 1 FROM def_choice WHERE quota_used!=0 AND parent_option_id=" . $option_id;
		if ($db->getRow($sql))
		{
			$db->autoExecute('def_option_branching', array('enabled' => 0), DB_AUTOQUERY_UPDATE, 'option_id=' . $option_id);
		}
		else
		{
			$sql = "DELETE FROM def_option WHERE option_id=" . $option_id;
			$db->query($sql);

			$sql = "DELETE FROM def_choice WHERE parent_option_id=" . $option_id;
			$db->query($sql);

			$sql = "SELECT node_id FROM def_option_branching WHERE option_id=" . $option_id;
			$result = $db->query($sql);
			while ($row = $result->fetchRow()) self::deleteNode($row->node_id);
			$result->free();
		}
	}

	/**
	 * Add a choice to an option
	 *
	 * Create the new choice, and complete the branching tree everywhere the option is connected
	 */
	public function addChoice($option_id, $data)
	{
		$db = DB();

		$choice_id = $db->nextId('choice');
		$data = array(
			'choice_id'           => $choice_id,
			'parent_option_id'    => $option_id,
			'label'               => $data['label'],
			'admin_only'          => $data['admin_only'],
			'price_default'       => $data['price_default'],
			'upper_price_default' => $data['upper_price_default'],
			'quota_max'           => $data['quota_max'],
			'quota_used'          => 0,
			'position'            => $choice_id,
		);

		$db->autoExecute('def_choice', $data, DB_AUTOQUERY_INSERT);


		$sql = "SELECT parent_node_id, min, max
				FROM def_option_branching
				WHERE option_id={$option_id}
				GROUP BY parent_node_id";
		$result = $db->query($sql);
		while ($row = $result->fetchRow())
		{
			$node_id = $db->nextId('option_branching');
			$branching_data = array(
				'node_id'        => $node_id,
				'parent_node_id' => $row->parent_node_id,
				'option_id'      => $option_id,
				'min'            => $row->min,
				'max'            => $row->max,
				'choice_id'      => $choice_id,
				'enabled '       => 1,
				'price'          => $data['price_default'],
				'upper_price'    => $data['upper_price_default'],
			);

			$db->autoExecute('def_option_branching', $branching_data, DB_AUTOQUERY_INSERT);
		}
	}

	public function updateChoice($choice_id, $data)
	{
		$update = array();

		isset($data['label']              ) && ($update['label']               = $data['label']              );
		isset($data['admin_only']         ) && ($update['admin_only']          = $data['admin_only']         );
		isset($data['price_default']      ) && ($update['price_default']       = $data['price_default']      );
		isset($data['upper_price_default']) && ($update['upper_price_default'] = $data['upper_price_default']);
		isset($data['quota_max']          ) && ($update['quota_max']           = $data['quota_max']          );

		if ($update)
		{
			DB()->autoExecute('def_choice', $update, DB_AUTOQUERY_UPDATE, 'choice_id=' . $choice_id);
		}
	}

	public static function swapChoicePosition($choice_id1, $position1, $choice_id2, $position2)
	{
		if ($choice_id1 && $choice_id2)
		{
			DB()->autoExecute('def_choice', array('position' => $position2), DB_AUTOQUERY_UPDATE, 'choice_id=' . $choice_id1);
			DB()->autoExecute('def_choice', array('position' => $position1), DB_AUTOQUERY_UPDATE, 'choice_id=' . $choice_id2);
		}
	}

	/**
	 * Delete a choice
	 * 
	 * A choice can not be deleted when it has been selected one time or more.
	 * However, it is disabled in the option tree.
	 * 
	 */
	public function deleteChoice($choice_id)
	{
		$db = DB();

		$sql = "DELETE FROM def_choice WHERE choice_id=" . $choice_id . " AND quota_used=0";
		$db->query($sql);

		if ($db->affectedRows())
		{
			$sql = "SELECT node_id FROM def_option_branching WHERE choice_id=" . $choice_id;
			$result = $db->query($sql);
			while ($row = $result->fetchRow()) self::deleteNode($row->node_id);
			$result->free();
		}
		else
		{
			$db->autoExecute('def_option_branching', array('enabled' => 0), DB_AUTOQUERY_UPDATE, 'choice_id=' . $choice_id);
		}
	}

	/**
	 * Connect an option in the branching tree.
	 */
	public function connectOption($option_id, $node_id)
	{
		$db = DB();

		$sql = "SELECT choice_id FROM def_choice WHERE parent_option_id=" . $option_id;
		$result = $db->query($sql);
		while ($row = $result->fetchRow())
		{
			$db->autoExecute('def_option_branching', array(
				'node_id' => $db->nextId('option_branching'),
				'parent_node_id' => $node_id,
				'option_id' => $option_id,
				'choice_id' => $row->choice_id,
				'enabled' => 1,
			), DB_AUTOQUERY_INSERT);
		}

		return $option_id;
	}

	/**
	 * Delete a node from the branching tree and all its childrens
	 */
	public function deleteNode($node_id)
	{
		$db = DB();

		$sql = "DELETE FROM def_option_branching WHERE node_id=" . $node_id;
		$db->query($sql);

		$sql = "SELECT FROM def_option_branching WHERE parent_node_id=" . $node_id;
		$result = $db->query($sql);
		while ($row = $result->fetchRow()) self::deleteNode($row->node_id);
		$result->free();
	}
}
