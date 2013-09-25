<?php

namespace Patchwork\Tests\PHP;

use Patchwork\PHP\Parser;

class StringInfoTest extends \PHPUnit_Framework_TestCase
{
    protected function getParser($dump = false)
    {
        $p = $dump ? new Parser\Dumper : new Parser;

        $p = new Parser\StringInfo($p);

        return $p;
    }

    function testStringInfo()
    {
        $parser = $this->getParser(true);

        $in  = <<<'EOPHP'
<?php

namespace a\b\c;
use a\b as c;
\a\b;
namespace\a;
a:
goto a;
const a=1,b=a;
a;
a\b;
a\b\c;
a();
a\b();
a\b\c();
class a extends b\c implements d\e {}
trait a {function b(c $d, e\f &$g){}}
EOPHP;

        $out = <<<'EOPHP'
Line                    Source code Parsed code                    Token type(s)
=================================================================================
   1                         <?php⏎                                T_OPEN_TAG
   3                      namespace                                T_NAMESPACE, T_NAME_NS
   3                              a                                T_STRING, T_NAME_NS
   3                              \                                T_NS_SEPARATOR
   3                              b                                T_STRING, T_NAME_NS
   3                              \                                T_NS_SEPARATOR
   3                              c                                T_STRING, T_NAME_NS
   3                              ;                                ;
   4                            use                                T_USE
   4                              a                                T_STRING, T_USE_NS
   4                              \                                T_NS_SEPARATOR
   4                              b                                T_STRING, T_USE_NS
   4                             as                                T_AS
   4                              c                                T_STRING, T_USE_NS
   4                              ;                                ;
   5                              \                                T_NS_SEPARATOR
   5                              a                                T_STRING, T_USE_NS
   5                              \                                T_NS_SEPARATOR
   5                              b                                T_STRING, T_USE_CONSTANT
   5                              ;                                ;
   6                      namespace                                T_NAMESPACE, T_USE_NS
   6                              \                                T_NS_SEPARATOR
   6                              a                                T_STRING, T_USE_CONSTANT
   6                              ;                                ;
   7                              a                                T_STRING, T_GOTO_LABEL
   7                              :                                :
   8                           goto                                T_GOTO
   8                              a                                T_STRING, T_GOTO_LABEL
   8                              ;                                ;
   9                          const                                T_CONST
   9                              a                                T_STRING, T_NAME_CONST
   9                              =                                =
   9                              1                                T_LNUMBER
   9                              ,                                ,
   9                              b                                T_STRING, T_NAME_CONST
   9                              =                                =
   9                              a                                T_STRING, T_USE_CONSTANT
   9                              ;                                ;
  10                              a                                T_STRING, T_USE_CONSTANT
  10                              ;                                ;
  11                              a                                T_STRING, T_USE_NS
  11                              \                                T_NS_SEPARATOR
  11                              b                                T_STRING, T_USE_CONSTANT
  11                              ;                                ;
  12                              a                                T_STRING, T_USE_NS
  12                              \                                T_NS_SEPARATOR
  12                              b                                T_STRING, T_USE_NS
  12                              \                                T_NS_SEPARATOR
  12                              c                                T_STRING, T_USE_CONSTANT
  12                              ;                                ;
  13                              a                                T_STRING, T_USE_FUNCTION
  13                              (                                (
  13                              )                                )
  13                              ;                                ;
  14                              a                                T_STRING, T_USE_NS
  14                              \                                T_NS_SEPARATOR
  14                              b                                T_STRING, T_USE_FUNCTION
  14                              (                                (
  14                              )                                )
  14                              ;                                ;
  15                              a                                T_STRING, T_USE_NS
  15                              \                                T_NS_SEPARATOR
  15                              b                                T_STRING, T_USE_NS
  15                              \                                T_NS_SEPARATOR
  15                              c                                T_STRING, T_USE_FUNCTION
  15                              (                                (
  15                              )                                )
  15                              ;                                ;
  16                          class                                T_CLASS
  16                              a                                T_STRING, T_NAME_CLASS
  16                        extends                                T_EXTENDS
  16                              b                                T_STRING, T_USE_NS
  16                              \                                T_NS_SEPARATOR
  16                              c                                T_STRING, T_USE_CLASS
  16                     implements                                T_IMPLEMENTS
  16                              d                                T_STRING, T_USE_NS
  16                              \                                T_NS_SEPARATOR
  16                              e                                T_STRING, T_USE_CLASS
  16                              {                                {
  16                              }                                }
  17                          trait                                T_TRAIT
  17                              a                                T_STRING, T_NAME_CLASS
  17                              {                                {
  17                       function                                T_FUNCTION
  17                              b                                T_STRING, T_NAME_FUNCTION
  17                              (                                (
  17                              c                                T_STRING, T_TYPE_HINT
  17                             $d                                T_VARIABLE
  17                              ,                                ,
  17                              e                                T_STRING, T_USE_NS
  17                              \                                T_NS_SEPARATOR
  17                              f                                T_STRING, T_TYPE_HINT
  17                              &                                &
  17                             $g                                T_VARIABLE
  17                              )                                )
  17                              {                                {
  17                              }                                }
  17                              }                                }

EOPHP;

        ob_start();
        $this->assertSame( $in, $parser->parse($in) );
        $this->assertSame( $out, ob_get_clean() );
        $this->assertSame( array(), $parser->getErrors() );
    }


    function testStringInfoTrait()
    {
        $parser = $this->getParser(true);

        $in  = <<<'EOPHP'
<?php

class a
{
    use b, c\d {
        c\d::e insteadof b;
        c\d::f as g;
        h as private i;
    }
}
EOPHP;

        $out = <<<'EOPHP'
Line                    Source code Parsed code                    Token type(s)
=================================================================================
   1                         <?php⏎                                T_OPEN_TAG
   3                          class                                T_CLASS
   3                              a                                T_STRING, T_NAME_CLASS
   4                              {                                {
   5                            use                                T_USE
   5                              b                                T_STRING, T_USE_CLASS
   5                              ,                                ,
   5                              c                                T_STRING, T_USE_NS
   5                              \                                T_NS_SEPARATOR
   5                              d                                T_STRING, T_USE_CLASS
   5                              {                                {
   6                              c                                T_STRING, T_USE_NS
   6                              \                                T_NS_SEPARATOR
   6                              d                                T_STRING, T_USE_CLASS
   6                             ::                                T_DOUBLE_COLON
   6                              e                                T_STRING, T_USE_NS
   6                      insteadof                                T_INSTEADOF
   6                              b                                T_STRING, T_USE_NS
   6                              ;                                ;
   7                              c                                T_STRING, T_USE_NS
   7                              \                                T_NS_SEPARATOR
   7                              d                                T_STRING, T_USE_CLASS
   7                             ::                                T_DOUBLE_COLON
   7                              f                                T_STRING, T_USE_METHOD
   7                             as                                T_AS
   7                              g                                T_STRING, T_USE_METHOD
   7                              ;                                ;
   8                              h                                T_STRING, T_USE_PROPERTY
   8                             as                                T_AS
   8                        private                                T_PRIVATE
   8                              i                                T_STRING, T_USE_PROPERTY
   8                              ;                                ;
   9                              }                                }
  10                              }                                }

EOPHP;

        ob_start();
        $this->assertSame( $in, $parser->parse($in) );
        $this->assertSame( $out, ob_get_clean() );
        $this->assertSame( array(), $parser->getErrors() );
    }
}
