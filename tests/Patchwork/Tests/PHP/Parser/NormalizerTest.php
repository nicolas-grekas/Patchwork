<?php

namespace Patchwork\Tests\PHP;

use Patchwork\PHP\Parser;

class NormalizerTest extends \PHPUnit_Framework_TestCase
{
    protected function getParser()
    {
        return new Parser\Normalizer;
    }

    function testLineEndings()
    {
        $parser = $this->getParser();

        $in = "<?php\r\n\n\r";
        $out = "<?php\n\n\n";

        $this->assertSame( $out, $parser->parse($in) );
    }

    function testUtf8()
    {
        $parser = $this->getParser();

        $in = "<?php \xE9";
        $out = "<?php \xE9";

        $this->assertSame( $out, $parser->parse($in) );

        $this->assertSame(
            array(
                array(
                    'type' => 512,
                    'message' => 'File encoding is not valid UTF-8',
                    'line' => 0,
                    'parser' => 'Patchwork\PHP\Parser\Normalizer',
                ),
            ),
            $parser->getErrors()
        );
    }

    function testBom()
    {
        $parser = $this->getParser();

        $in = "\xEF\xBB\xBF<?php ";
        $out = "<?php ";

        $this->assertSame( $out, $parser->parse($in) );
    }
}
