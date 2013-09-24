<?php

namespace Patchwork\Tests\PHP;

use Patchwork\PHP\Parser;

class PhpPreprocessorTest extends \PHPUnit_Framework_TestCase
{
    protected function getParser($dump = false)
    {
        $p = $dump ? new Parser\Dumper : new Parser;

        $p = new Parser\PhpPreprocessor($p, 'p');

        return $p;
    }

    function testShortOpenEcho()
    {
        $parser = $this->getParser();

        $in  = '<?php require $a; f(include $a, $b); require f($a, $b);';
        $out = '<?php require p( $a); f(include p( $a), $b); require p( f($a, $b));';

        $this->assertSame( $out, $parser->parse($in) );
        $this->assertSame( array(), $parser->getErrors() );
    }
}
