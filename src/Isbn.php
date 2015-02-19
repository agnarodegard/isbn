<?php

namespace Agnarodegard\Isbn;

use Agnarodegard\Isbn\Exceptions\IsbnException;

/**
 * Class Isbn
 *
 * The main class for ISBN handling
 *
 * @package Agnarodegard\Isbn
 */
class Isbn
{


    /** @var string The input, presumably an ISBN */
    public $isbn = null;

    /** @var string ISBN stripped of everything but integers and xX */
    public $unformatted = null;

    /** @var boolean */
    public $valid = false;

    /**
     * @param string|null $isbn
     * @throws IsbnException when ISBN is null
     */
    public function __construct($isbn = null)
    {
        if ($isbn === null || empty($isbn)) {
            throw new \InvalidArgumentException('ISBN cannot be empty.');
        } else {
            $this->isbn = $isbn;
            $this->unformatted = $this->removeFormatting();
            $this->valid = $this->validate();
        }
    }

    public function removeFormatting()
    {
        return strtoupper(preg_replace("/[^0-9Xx]/", "", $this->isbn));
    }

    public function validate()
    {
        $isbn = $this->unformatted;
        $digits = str_split($isbn);

        // ControlDigit can also be x, not just a digit.
        $controlDigit = strtoupper(array_pop($digits));
        $digitSum = 0;

        if ($this->is10()) {

            foreach ($digits as $key => $value) {
                $digitSum += (10 - $key) * $value;
            }

            $checksum = (11 - $digitSum % 11) % 11;
            $checkdigit = ($checksum < 10) ? $checksum : 'X';

            return $checkdigit == $controlDigit;
        }

        if ($this->is13()) {

            foreach ($digits as $key => $value) {
                $factor = ($key % 2) ? 3 : 1;
                $digitSum += $factor * $value;
            }

            $checksum = (10 - $digitSum % 10) % 10;
            $checkdigit = ($checksum < 10) ? $checksum : 'X';

            return $checkdigit == $controlDigit;
        }

        return false;
    }

    public function type()
    {
        if ($this->is10()) {
            return 'ISBN10';
        }
        if ($this->is13()) {
            return 'ISBN13';
        }
    }

    public function is10()
    {
        $isbn = $this->unformatted;

        return strlen($isbn) == 10;
    }

    public function is13()
    {
        $isbn = $this->unformatted;

        return strlen($isbn) == 13;
    }
}
