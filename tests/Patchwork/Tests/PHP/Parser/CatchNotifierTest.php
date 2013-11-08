<?php

namespace Patchwork\Tests\PHP;

use Patchwork\PHP\Parser;

class CatchNotifierTest extends \PHPUnit_Framework_TestCase
{
    protected function getParser($handler, $dump = false)
    {
        $p = $dump ? new Parser\Dumper : new Parser;

        $p = new Parser\Normalizer($p);
        $p = new Parser\StringInfo($p);
        $p = new Parser\NamespaceInfo($p);
        $p = new Parser\CatchNotifier($p, $handler);

        return $p;
    }

    /**
     * @dataProvider parserProvider
     */
    function testParser($handler, $in, $out, $errors = array())
    {
        $parser = $this->getParser($handler);

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
                'handler' => false,
                'in'  => 'try{}catch(\E $e){}}',
                'out' => 'try{}catch(\E $e){\user_error(\'Caught \\\\E $e\');}}',
            ),
            array(
                'handler' => 'exh',
                'in'  => 'try{}catch(\E $e){}}',
                'out' => 'try{}catch(\E $e){\set_error_handler(\'exh\');\user_error(\'Caught \\\\E $e\');\restore_error_handler();}}',
            ),
            array(
                'handler' => array('e', 'xh'),
                'in'  => 'try{}catch(\E $e){}}',
                'out' => 'try{}catch(\E $e){\set_error_handler(array(\'e\',\'xh\'));\user_error(\'Caught \\\\E $e\');\restore_error_handler();}}',
            ),
        );
    }
}
