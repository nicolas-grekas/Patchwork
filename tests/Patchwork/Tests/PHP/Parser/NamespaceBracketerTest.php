<?php

namespace Patchwork\Tests\PHP;

use Patchwork\PHP\Parser;

class NamespaceBracketerTest extends \PHPUnit_Framework_TestCase
{
    protected function getParser($dump = false)
    {
        $p = $dump ? new Parser\Dumper : new Parser;

        $p = new Parser\Normalizer($p);
        $p = new Parser\StringInfo($p);
        $p = new Parser\NamespaceBracketer($p);

        return $p;
    }

    /**
     * @dataProvider parserProvider
     */
    function testParser($in, $out, $errors = array())
    {
        $parser = $this->getParser();

        $in = $parser->parse('<?php ' . $in);
        $in = substr($in, 6);
        $in = trim(preg_replace('/  +/', ' ', $in));

        $this->assertSame( $out, $in );
        $this->assertSame( $errors, $parser->getErrors() );
    }

    function parserProvider()
    {
        return array(
            array(
                'in'  => 'namespace a;',
                'out' => 'namespace a{}',
            ),
            array(
                'in'  => 'use a as b;namespace c;',
                'out' => 'namespace{use a as b;}namespace c{}',
            ),
        );
    }
}
