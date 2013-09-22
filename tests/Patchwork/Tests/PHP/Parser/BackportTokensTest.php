<?php

namespace Patchwork\Tests\PHP;

use Patchwork\PHP\Parser;

class BackportTokensTest extends \PHPUnit_Framework_TestCase
{
    protected function getParser()
    {
        $p = new Parser\Dumper;
        $p = new Parser\BackportTokens($p);
        return $p;
    }

    function testParse()
    {
        $parser = $this->getParser();

        $in = <<<EOPHP
<?php
goto Goto ->goto
__dir__
namespace
__namespace__
trait
callable
__trait__
insteadof
yield
finally
EOPHP;

        ob_start();
        $this->assertSame( $in, $parser->parse($in) );

        $out = <<<EOTXT
Line                    Source code Parsed code                    Token type(s)
=================================================================================
   1                         <?php⏎                                T_OPEN_TAG
   2                           goto                                T_GOTO
   2                           Goto                                T_GOTO
   2                             ->                                T_OBJECT_OPERATOR
   2                           goto                                T_STRING
   3                        __dir__                                T_DIR
   4                      namespace                                T_NAMESPACE
   5                  __namespace__                                T_NS_C
   6                          trait                                T_TRAIT
   7                       callable                                T_CALLABLE
   8                      __trait__                                T_TRAIT_C
   9                      insteadof                                T_INSTEADOF
  10                          yield                                T_YIELD
  11                        finally                                T_FINALLY

EOTXT;

        $this->assertSame( $out, ob_get_clean() );

    }
}
