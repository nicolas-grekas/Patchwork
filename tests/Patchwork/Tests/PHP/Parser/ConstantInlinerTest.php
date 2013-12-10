<?php

namespace Patchwork\Tests\PHP;

use Patchwork\PHP\Parser;

class ConstantInlineTest extends \PHPUnit_Framework_TestCase
{
    protected function getParser($dump = false)
    {
        $consts = array('T_OPEN_TAG');

        $p = $dump ? new Parser\Dumper : new Parser;

        $p = new Parser\Normalizer($p);
        $p = new Parser\StringInfo($p);
        $p = new Parser\BracketWatcher($p);
        $p = new Parser\NamespaceInfo($p);
        $p = new Parser\ScopeInfo($p);
        $p = new Parser\ConstantInliner($p, '/foo.php', $consts);

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
                'in'  => 'namespace a; T_OPEN_TAG; \T_CLOSE_TAG; \T_OPEN_TAG; \false; false;',
                'out' => 'namespace a; T_OPEN_TAG; \T_CLOSE_TAG; ' . T_OPEN_TAG . '; \false; false;',
            ),
            array(
                'in'  => 'namespace a; __FILE__; __LINE__; __NAMESPACE__; __CLASS__; __FUNCTION__; __METHOD__; __TRAIT__;',
                'out' => "namespace a; '/foo.php'; 1; 'a'; ''; ''; ''; '';",
            ),
            array(
                'in'  => 'namespace a; function b(){__FUNCTION__;}',
                'out' => "namespace a; function b(){'a\\b';}",
            ),
            array(
                'in'  => 'namespace a; class b{function c(){__METHOD__;}}',
                'out' => "namespace a; class b{function c(){'a\\b::c';}}",
            ),
            array(
                'in'  => 'namespace a; trait b{public $c = __TRAIT__;}',
                'out' => "namespace a; trait b{public \$c = 'a\b';}",
            ),
            array(
                'in'  => 'namespace a; function (){__FUNCTION__;}',
                'out' => "namespace a; function (){'a\{closure}';}",
            ),
        );
    }
}
