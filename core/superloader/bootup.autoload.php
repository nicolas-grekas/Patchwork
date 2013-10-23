<?php

/**/$s = stat(__FILE__);
/**/$s = array(__FILE__, $s['dev'], $s['ino'], $s['size'], $s['mtime'], $s['ctime']);
/**/$s = base64_encode(md5(implode('-', $s), true));
/**//*<*/"\$c\x9D=&\\Patchwork\\Superloader::\$locations;\$d\x9D=1;(\$e\x9D=\$b\x9D=\$a\x9D=__FILE__.'*" . substr($s, 0, 8) . "')&&\$d\x9D&&0;"/*>*/;

Patchwork\Shim(get_parent_class, Patchwork\Superloader::get_parent_class, $class);

/**/@unlink(PATCHWORK_PROJECT_PATH . '.patchwork.autoloader.php');
/**/copy(boot::$manager->getCurrentDir() . 'class/Patchwork/Autoloader.php', PATCHWORK_PROJECT_PATH . '.patchwork.autoloader.php');

spl_autoload_register(array('Patchwork\Superloader', 'loadAlias'), true, true);
spl_autoload_register(array('Patchwork\Superloader', 'loadAutoloader'));
