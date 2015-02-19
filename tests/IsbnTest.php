<?php
/**
 * Created by PhpStorm.
 * User: odegard
 * Date: 2015-02-19
 * Time: 11:44
 */

namespace Agnarodegard\Isbn\Test;

use Agnarodegard\Isbn\Isbn;

class IsbnTest extends \PHPUnit_Framework_TestCase
{
    // Validation

    public function nullIsbn()
    {
        return [
            'null_isbn' => [null],
            'empty_isbn' => [''],
        ];
    }

    public function invalidIsbn()
    {
        // Valid ISBNs with last digit - 1.
        return [
            'invalid' => [
                '978-82-15-01538-4',
                '978-0-19-929781-7',
                '82-7261-050-6',
                '0-8044-2956-x',
                '978-1-56619-909-3',
                '978-3-16-148410-9',
            ],
        ];
    }

    public function validIsbn()

    {
        return [
            'invalid' => [
                '978-82-15-01538-5',
                '978-0-19-929781-8',
                '82-7261-050-7',
                '0-8044-2957-x',
                '978-1-56619-909-4',
                '978-3-16-148410-0',
            ],
        ];
    }

    /**
     * @dataProvider nullIsbn
     */
    public function testInstantiationWithInvalidIsbn($isbn)
    {
        $this->setExpectedException('\InvalidArgumentException');
        new Isbn($isbn);
    }

    /**
     * @dataProvider invalidIsbn
     */
    public function testValidationWithInvalidIsbn($isbn)
    {
        $o = new Isbn($isbn);
        $this->assertFalse($o->validate());
    }

    /**
     * @dataProvider validIsbn
     */
    public function testValidationWithValidIsbn($isbn)
    {
        $o = new Isbn($isbn);
        $this->assertTrue($o->valid);
    }

    // Remove formatting
    public function invalidFormattedIsbn()
    {
        return [
            'special_characters' => ['1(2/3&4"5#6¤7('],
            'letters' => ['1a2b3n4m5g6h7j'],
            'dashes' => ['1-2-3-4-5-6-7--'],
            'spaces' => [' 1   2 345  6 7'],
        ];
    }

    /**
     * @dataProvider invalidFormattedIsbn
     */
    public function testRemoveFormattingWithInvalidFormattedIsbn($isbn)
    {
        $o = new Isbn($isbn);
        $this->assertEquals('1234567', $o->unformatted);
    }
}
