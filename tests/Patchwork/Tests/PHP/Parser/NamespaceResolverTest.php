<?php

namespace Patchwork\Tests\PHP;

use Patchwork\PHP\Parser;

class NamespaceResolverTest extends \PHPUnit_Framework_TestCase
{
    protected function getParser($dump = false)
    {
        $p = $dump ? new Parser\Dumper : new Parser;

        $p = new Parser\StringInfo($p);
        $p = new Parser\NamespaceInfo($p);
        $p = new Parser\NamespaceResolver($p);

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
                'in'  => 'use a as b, c\d; b::$a; d\f',
                'out' => '\a::$a; \c\d\f',
            ),
            array(
                'in'  => 'namespace a; use \b as c; c; c\d;',
                'out' => 'namespace a; c; \b\d;',
                'errors' => array(
                    array(
                        'type' => 512,
                        'message' => 'Unresolved namespaced identifier (c)',
                        'line' => 1,
                        'parser' => 'Patchwork\PHP\Parser\NamespaceResolver',
                    )
                )
            ),
            array(
                'in'  => 'use a as parent; parent::b;',
                'out' => 'parent::b;',
            ),
            array(
                'in'  => 'namespace\a; b; c\d',
                'out' => '\a; \b; \c\d',
            ),
            array(
                'in'  => 'namespace a; use function b\c as d, b\e; d(); e();',
                'out' => 'namespace a; \b\c(); \b\e();',
            ),
            array(
                'in'  => 'namespace a; use const b\c as d, b\e; d; e;',
                'out' => 'namespace a; \b\c; \b\e;',
            ),
            array(
                'in'  => 'use function a\b as c; c();',
                'out' => '\a\b();',
            ),
            array(
                'in'  => 'use const a\b as c; c;',
                'out' => '\a\b;',
            ),
        );
    }
}
