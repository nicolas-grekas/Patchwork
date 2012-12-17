<?php

/**/$s = stat(__FILE__);
/**/$s = array(__FILE__, $s['dev'], $s['ino'], $s['size'], $s['mtime'], $s['ctime']);
/**/$s = base64_encode(md5(implode('-', $s), true));
/**//*<*/"\$c\x9D=&Patchwork_Superloader::\$locations;\$d\x9D=1;(\$e\x9D=\$b\x9D=\$a\x9D=__FILE__.'*" . substr($s, 0, 8) . "')&&\$d\x9D&&0;"/*>*/;

Patchwork\Shim(get_parent_class, Patchwork_Superloader::get_parent_class, $class); // FIXME: collides with Patchwork\PHP\Shim\Php530 on PHP<5.3

/**/@unlink(PATCHWORK_PROJECT_PATH . '.patchwork.autoloader.php');
/**/copy(boot::$manager->getCurrentDir() . 'class/Patchwork/Autoloader.php', PATCHWORK_PROJECT_PATH . '.patchwork.autoloader.php');

/**/if (function_exists('class_alias'))
        spl_autoload_register(array('Patchwork_Superloader', 'loadAlias'), true, true);

spl_autoload_register(array('Patchwork_Superloader', 'loadAutoloader'));
