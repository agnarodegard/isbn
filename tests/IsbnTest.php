<?php

namespace Agnarodegard\Isbn\IsbnTest;

use Agnarodegard\Isbn\Isbn;

class IsbnTest extends \PHPUnit_Framework_TestCase
{
    /**
     * DataProviders
     */
    public function illegalIsbn()
    {
        return [
            'true' => [true],
            'false' => [false],
            'null' => [null],
            'empty' => [''],
            'array' => [[]],
        ];
    }

    public function invalidIsbn()
    {
        // Valid ISBNs with last digit - 1.
        return [
            'invalid0' => ['87-574-0845-8'],
            'invalid1' => ['82-00-05922-6'],
            'invalid2' => ['82-7261-050-6'],
            'invalid3' => ['0-8044-2956-x'],
            'invalid4' => ['978-82-15-01538-4'],
            'invalid5' => ['978-0-19-929781-7'],
            'invalid6' => ['978-1-56619-909-3'],
            'invalid7' => ['978-3-16-148410-9'],
        ];
    }

    public function validIsbn()

    {
        return [
            'valid0' => ['87-574-0845-9'],
            'valid1' => ['82-00-05922-7'],
            'valid2' => ['82-7261-050-7'],
            'valid3' => ['0-8044-2957-x'],
            'valid4' => ['978-82-15-01538-5'],
            'valid5' => ['978-0-19-929781-8'],
            'valid6' => ['978-1-56619-909-4'],
            'valid7' => ['978-3-16-148410-0'],
        ];
    }

    public function invalidFormattedIsbn()
    {
        return [
            'special_characters' => ['1(2/3&4"5#6¤7('],
            'letters' => ['1a2b3n4m5g6h7j'],
            'dashes' => ['1-2-3-4-5-6-7--'],
            'spaces' => [' 1   2 345  6 7'],
        ];
    }

    public function isbn10()
    {
        return [
            'is10' => ['87-574-0845-9'],
        ];
    }

    public function isbn13()
    {
        return [
            'is10' => ['978-82-15-01538-5'],
        ];
    }

    /**
     * Testing: Instantation
     * @dataProvider illegalIsbn
     */
    public function testInstantiationWithInvalidIsbn($isbn)
    {
        $this->setExpectedException('\InvalidArgumentException');
        new Isbn($isbn);
    }

    /**
     * Testing: Validation
     * @dataProvider invalidIsbn
     */
    public function testValidationWithInvalidIsbn($isbn)
    {
        $o = new Isbn($isbn);
        $this->assertFalse($o->valid);
    }

    /**
     * Testing: Validation
     * @dataProvider validIsbn
     */
    public function testValidationWithValidIsbn($isbn)
    {
        $o = new Isbn($isbn);
        $this->assertTrue($o->valid);
    }

    /**
     * Testing: RemoveFormatting
     * @dataProvider invalidFormattedIsbn
     */
    public function testRemoveFormattingWithInvalidFormattedIsbn($isbn)
    {
        $o = new Isbn($isbn);
        $this->assertEquals('1234567', $o->unformatted);
    }

    /**
     * Testing: Type ISBN10
     * @dataProvider isbn10
     */
    public function testTypeWithIs10Isbn($isbn)
    {
        $o = new Isbn($isbn);
        $this->assertEquals('ISBN10', $o->type());
    }

    /**
     * Testing: Type ISBN13
     * @dataProvider isbn13
     */
    public function testTypeWithIs13Isbn($isbn)
    {
        $o = new Isbn($isbn);
        $this->assertEquals('ISBN13', $o->type());
    }

}
