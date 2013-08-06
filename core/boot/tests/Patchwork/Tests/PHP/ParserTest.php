<?php

namespace Patchwork\Tests\PHP;

use Patchwork\PHP\Parser;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    protected function getParser()
    {
        return new Parser;
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
}
