<?php

namespace Patchwork\Tests\PHP;

use Patchwork\PHP\Parser;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    protected function getParser($dump = false)
    {
        $p = $dump ? new Parser\Dumper : new Parser;

        return $p;
    }

    function testParseBinarySafeness()
    {
        $code = <<<EOPHP
\x01
<?php
\x02; // Illegal character, but should be preserved for binary safeness
__halt_compiler();
// Halt compiler data
\x03
EOPHP;

       $this->assertSame( $code, $this->getParser()->parse($code) );
    }

    function testSpecialTokens()
    {
        $parser = $this->getParser(true);

        $in = <<<'EOPHP'
<?php
"$a[b]"; // the native T_STRING for token `b` is changed to T_STR_STRING
"$a->b"; //  "
"${a}";  // the closing bracket is typed to T_CURLY_CLOSE
"{$a}";  //  "
EOPHP;

        ob_start();
        $this->assertSame( $in, $parser->parse($in) );

        $out = <<<'EOTXT'
Line                    Source code Parsed code                    Token type(s)
=================================================================================
   1                         <?php⏎                                T_OPEN_TAG
   2                              "                                "
   2                             $a                                T_VARIABLE
   2                              [                                [
   2                              b                                T_STR_STRING
   2                              ]                                ]
   2                              "                                "
   2                              ;                                ;
   3                              "                                "
   3                             $a                                T_VARIABLE
   3                             ->                                T_OBJECT_OPERATOR
   3                              b                                T_STR_STRING
   3                              "                                "
   3                              ;                                ;
   4                              "                                "
   4                             ${                                T_DOLLAR_OPEN_CURLY_BRACES
   4                              a                                T_STRING_VARNAME
   4                              }                                T_CURLY_CLOSE
   4                              "                                "
   4                              ;                                ;
   5                              "                                "
   5                              {                                T_CURLY_OPEN
   5                             $a                                T_VARIABLE
   5                              }                                T_CURLY_CLOSE
   5                              "                                "
   5                              ;                                ;

EOTXT;

        $this->assertSame( $out, ob_get_clean() );
        $this->assertSame( array(), $parser->getErrors() );
     }
}
