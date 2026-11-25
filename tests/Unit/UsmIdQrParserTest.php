<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\UsmIdQrParser;
use PHPUnit\Framework\TestCase;

class UsmIdQrParserTest extends TestCase
{
    private UsmIdQrParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new UsmIdQrParser;
    }

    public function test_parses_tab_separated_aro_payload(): void
    {
        $parsed = $this->parser->parse("21-11048\tDECIE C. GANAO\tABPhilo\tDecember 5, 2002");

        $this->assertSame('21-11048', $parsed['student_no']);
        $this->assertSame('DECIE C. GANAO', $parsed['full_name']);
        $this->assertSame('ABPhilo', $parsed['course']);
    }

    public function test_parses_id_first_comma_payload(): void
    {
        $parsed = $this->parser->parse(
            '26-78714,KRISTINE MAE B. ONDOY,BSRT,1ST SEMESTER A.Y. 2026-2027'
        );

        $this->assertSame('26-78714', $parsed['student_no']);
        $this->assertSame('KRISTINE MAE B. ONDOY', $parsed['full_name']);
        $this->assertSame('BSRT', $parsed['course']);
    }

    public function test_parses_name_first_payload_without_student_number(): void
    {
        $parsed = $this->parser->parse('MERRYL JOANNA JOYCE T. GONZALES,BSN,,June 13, 2003');

        $this->assertNull($parsed['student_no']);
        $this->assertSame('MERRYL JOANNA JOYCE T. GONZALES', $parsed['full_name']);
        $this->assertSame('BSN', $parsed['course']);
    }

    public function test_parses_name_only_payload_with_suffix(): void
    {
        $parsed = $this->parser->parse('MUHAIMA L. UGKA SR.,BIT,,');

        $this->assertNull($parsed['student_no']);
        $this->assertSame('MUHAIMA L. UGKA SR.', $parsed['full_name']);
        $this->assertSame('BIT', $parsed['course']);
        $this->assertSame('MUHAIMAUGKA', $this->parser->normalizeName($parsed['full_name']));
    }

    public function test_normalize_name_strips_initials_and_suffixes(): void
    {
        $this->assertSame(
            'VENICEJADEFROTEZ',
            $this->parser->normalizeName('VENICE JADE A. FROTEZ')
        );
        $this->assertSame(
            'MUHAIMAUGKA',
            $this->parser->normalizeName('MUHAIMA L. UGKA SR.')
        );
    }
}
