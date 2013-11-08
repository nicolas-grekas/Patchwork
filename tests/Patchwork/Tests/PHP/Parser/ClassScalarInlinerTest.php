<?php

namespace Patchwork\Tests\PHP;

use Patchwork\PHP\Parser;

class ClassScalarInlinerTest extends \PHPUnit_Framework_TestCase
{
    protected function getParser($dump = false)
    {
        $p = $dump ? new Parser\Dumper : new Parser;

        $p = new Parser\Normalizer($p);
        $p = new Parser\StringInfo($p);
        $p = new Parser\NamespaceInfo($p);
        $p = new Parser\BracketWatcher($p);
        $p = new Parser\ScopeInfo($p);
        $p = new Parser\ClassInfo($p);
        $p = new Parser\ClassScalarInliner($p);

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
                'in'  => 'use a as b, C\d; b::class; d::class;',
                'out' => "use a as b, C\d; 'a'; 'C\\d';",
            ),
            array(
                'in'  => 'self::class;',
                'out' => 'self::self;', // for meaningful runtime error message
            ),
            array(
                'in'  => 'namespace a;class b{const c=self::class;}',
                'out' => "namespace a;class b{const c='a\\b';}",
            ),
            array(
                'in'  => 'class a{const b=parent::class;}',
                'out' => 'class a{const b=parent::class;}',

                'errors' => array(
                    array(
                        'type' => E_USER_ERROR,
                        'message' => 'parent::class cannot be used for compile-time class name resolution',
                        'line' => 1,
                        'parser' => 'Patchwork\PHP\Parser\ClassScalarInliner',
                    )
                )
            ),
            array(
                'in'  => 'class a{function b(){static::class;}}',
                'out' => 'class a{function b(){get_called_class();}}',
            ),
            array(
                'in'  => 'trait a{function b(){parent::class;}}',
                'out' => 'trait a{function b(){(get_parent_class()?:parent::self);}}', // parent::self for meaningful runtime error message
            ),
            array(
                'in'  => 'class a extends b{function b(){parent::class;}}',
                'out' => "class a extends b{function b(){'b';}}",
            ),
            array(
                'in'  => 'class a{function b(){parent::class;}}',
                'out' => "class a{function b(){parent::parent;}}", // for meaningful runtime error message
            ),
        );
    }
}
