<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
 *
 * Extending the ezcBase class for patchwork integration
 *
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

class ezcBase extends self
{
    static function getAutoload($className)
    {
        if (isset(self::$autoloadArray[$className])) return 'ezc/' . self::$autoloadArray[$className];

        if (preg_match( "/^([a-z]*)([A-Z][a-z0-9]*)([A-Z][a-z0-9]*)?/", $className, $matches ))
        {
            switch (sizeof($matches))
            {
                case 4:
                    $autoload = strtolower("class/ezc/autoload/{$matches[2]}_{$matches[3]}_autoload.php");
                    if ($autoload = patchworkPath($autoload))
                    {
                        $autoload = require $autoload;
                        self::$autoloadArray = array_merge(self::$autoloadArray, $autoload);
                        break;
                    }

                case 3:
                    $autoload = strtolower("class/ezc/autoload/{$matches[2]}_autoload.php");
                    if ($autoload = patchworkPath($autoload))
                    {
                        $autoload = require $autoload;
                        self::$autoloadArray = array_merge(self::$autoloadArray, $autoload);
                    }

                    break;
            }
        }

        return isset(self::$autoloadArray[$className])
            ? 'ezc/' . self::$autoloadArray[$className]
            : false;
    }

}
