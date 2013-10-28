<?php

namespace Patchwork\Tests\PHP;

use Patchwork\PHP\Parser;

class GlobalizerTest extends \PHPUnit_Framework_TestCase
{
    protected function getParser($dump = false)
    {
        $p = $dump ? new Parser\Dumper : new Parser;

        $p = new Parser\Normalizer($p);
        $p = new Parser\StringInfo($p);
        $p = new Parser\NamespaceInfo($p);
        $p = new Parser\BracketWatcher($p);
        $p = new Parser\ScopeInfo($p);
        $p = new Parser\Globalizer($p, array('$_GET', '$CONF'));

        return $p;
    }

    /**
     * @dataProvider parserProvider
     */
    function testParser($in, $out, $errors = [])
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
        return [
            [
                'in'  => '$_GET; $CONF;',
                'out' => 'global $CONF;$_GET; $CONF;',
            ],
            [
                'in'  => 'a::$CONF;',
                'out' => 'a::$CONF;',
            ],
            [
                'in'  => 'function(){if(0)$CONF;}',
                'out' => 'function(){global $CONF;if(0)$CONF;}',
            ],
        ];
    }
}
