<?php /*********************************************************************
 *
 *   Copyright : (C) 2005, 2006 eZ systems as. All rights reserved.
 *   License   : http://ez.no/licenses/new_bsd New BSD License
 *
 ***************************************************************************/


require processPath('class/ezc/Base/base.php');

class extends ezcBase__00
{
	static function getAutoload($className)
	{
		if ('ezcBase' == $className) return 'ezc/Base/base.php';

        if (isset(self::$autoloadArray[$className])) return 'ezc/' . self::$autoloadArray[$className];

        if (preg_match( "/^([a-z]*)([A-Z][a-z0-9]*)([A-Z][a-z0-9]*)?/", $className, $matches ))
        {
            switch ( sizeof($matches) )
            {
                case 4:
					$autoload = strtolower( "class/ezc/autoload/{$matches[2]}_{$matches[3]}_autoload.php" );
                    if ($autoload = resolvePath($autoload))
					{
						$autoload = require $autoload;
						self::$autoloadArray = array_merge( self::$autoloadArray, $autoload );
						break;
                    }

                case 3:
                    $autoload = strtolower( "class/ezc/autoload/{$matches[2]}_autoload.php" );
                    if ($autoload = resolvePath($autoload))
                    {
						$autoload = require $autoload;
						self::$autoloadArray = array_merge( self::$autoloadArray, $autoload );
                    }

					break;
            }
        }

		return isset(self::$autoloadArray[$className])
			? 'ezc/' . self::$autoloadArray[$className]
			: false;
	}
}
