<?php

$dir = dirname(dirname(__FILE__));

require_once $dir . '/class/Patchwork/PHP/Parser.php';
require_once $dir . '/class/Patchwork/PHP/Parser/Backport53Tokens.php';
require_once $dir . '/class/Patchwork/PHP/Parser/Backport54Tokens.php';
require_once $dir . '/class/Patchwork/PHP/Parser/BinaryNumber.php';
require_once $dir . '/class/Patchwork/PHP/Parser/BracketWatcher.php';
require_once $dir . '/class/Patchwork/PHP/Parser/Bracket.php';
require_once $dir . '/class/Patchwork/PHP/Parser/ClassInfo.php';
require_once $dir . '/class/Patchwork/PHP/Parser/CodePathSplitter.php';
require_once $dir . '/class/Patchwork/PHP/Parser/CodePathSplitterWithXDebugHacks.php';
require_once $dir . '/class/Patchwork/PHP/Parser/CodePathElseEnlightener.php';
require_once $dir . '/class/Patchwork/PHP/Parser/CodePathLoopEnlightener.php';
require_once $dir . '/class/Patchwork/PHP/Parser/CodePathSwitchEnlightener.php';
require_once $dir . '/class/Patchwork/PHP/Parser/CodePathDefaultArgsEnlightener.php';
require_once $dir . '/class/Patchwork/PHP/Parser/ConstantExpression.php';
require_once $dir . '/class/Patchwork/PHP/Parser/ConstantInliner.php';
require_once $dir . '/class/Patchwork/PHP/Parser/ConstFuncDisabler.php';
require_once $dir . '/class/Patchwork/PHP/Parser/ConstFuncResolver.php';
require_once $dir . '/class/Patchwork/PHP/Parser/ControlStructBracketer.php';
require_once $dir . '/class/Patchwork/PHP/Parser/CurlyDollarNormalizer.php';
require_once $dir . '/class/Patchwork/PHP/Parser/DestructorCatcher.php';
require_once $dir . '/class/Patchwork/PHP/Parser/Dumper.php';
require_once $dir . '/class/Patchwork/PHP/Parser/FunctionOverriding.php';
require_once $dir . '/class/Patchwork/PHP/Parser/Globalizer.php';
require_once $dir . '/class/Patchwork/PHP/Parser/NamespaceBracketer.php';
require_once $dir . '/class/Patchwork/PHP/Parser/NamespaceInfo.php';
require_once $dir . '/class/Patchwork/PHP/Parser/NamespaceRemover.php';
require_once $dir . '/class/Patchwork/PHP/Parser/NamespaceResolver.php';
require_once $dir . '/class/Patchwork/PHP/Parser/Normalizer.php';
require_once $dir . '/class/Patchwork/PHP/Parser/ScopeInfo.php';
require_once $dir . '/class/Patchwork/PHP/Parser/Scream.php';
require_once $dir . '/class/Patchwork/PHP/Parser/ShortArray.php';
require_once $dir . '/class/Patchwork/PHP/Parser/ShortOpenEcho.php';
require_once $dir . '/class/Patchwork/PHP/Parser/StaticState.php';
require_once $dir . '/class/Patchwork/PHP/Parser/StringInfo.php';
require_once $dir . '/class/Patchwork/PHP/Parser/Bracket/Callback.php';
