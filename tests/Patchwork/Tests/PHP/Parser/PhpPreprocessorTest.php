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

        $in  = '<?php require $a; f(include $a, $b); require f($a, $b); require 1 ? function(){label:} : 0;';
        $out = '<?php require p( $a); f(include p( $a), $b); require p( f($a, $b)); require p( 1 ? function(){label:} : 0);';

        $this->assertSame( $out, $parser->parse($in) );
        $this->assertSame( array(), $parser->getErrors() );
    }
}
