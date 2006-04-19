<?php

class iaForm_magicDb
{
	public static function populate($table, $form, $save = false, $fields = false)
	{
		$db = DB();

		$sql = 'SHOW COLUMNS FROM ' . $table;
		$result = $db->query($sql);

		$onempty = '';
		$onerror = T('Input validation failed');

		while ($row = $result->fetchRow())
		{
			if ($fields)
			{
				if (false === ($i = array_search($row->Field, $fields))) continue;

				$onempty = $fields[$i + 1];
				$onerror = $fields[$i + 2];
			}

			$type = strpos($row->Type, '(');
			$type = false === $type ? $row->Type : substr($row->Type, 0, $type);

			$param = array();

			switch ($type)
			{
				case 'int':
				case 'smallint':
				case 'mediumint':
				case 'bigint':
					if (!$fields) continue;

					$type = 'text';
					$param['valid'] = 'int';
					break;

				case 'char':
				case 'varchar':
					$type = 'text';
					$param['maxlength'] = (int) substr($type, strlen($type) + 1);
					break;

				case 'longtext':   $type  = 8;
				case 'mediumtext': $type += 8;
				case 'text':       $type += 8;
				case 'tinytext':   $type += 8;
					$param['maxlength'] = (1 << $type) - 1;
					$type = 'textarea';
					break;

				case 'tinyint':
				case 'bool':
					$type = 'check';
					$param['item'] = array(1 => T('Yes'), 0 => T('No'));
					break;

				case 'date':
					$type = 'text';
					$param['valid'] = 'date';
					break;

				case 'float':
				case 'double':
				case 'decimal':
					$type = 'text';
					$param['valid'] = 'float';
					break;

				case 'set':
				case 'enum':
					$i = eval('return array' . substr($row->Type, strlen($type)) . ';');
					$param['item'] = array_combine($i, $i);

					if ('set' == $type) $param['isdata'] = $param['multiple'] = true;

					$type = 'check';
					break;

				default:
					if ($fields) E("iaForm_magicDb::populate() : this field type is not managed ({$row->Type})");
					continue;
			}

			$form->add($type, $row->Field, $param);
			if ($save) $save->add($row->Field, $onempty, $onerror);
		}
	}
}
