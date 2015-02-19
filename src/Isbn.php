<?php

namespace Agnarodegard\Isbn;

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
     * @throws \InvalidArgumentException when ISBN is null
     */
    public function __construct($isbn = null)
    {
        if (!is_string($isbn) || $isbn === null || empty($isbn)) {
            throw new \InvalidArgumentException('ISBN must be a string and cannot be empty.');
        } else {
            $this->isbn = $isbn;
            $this->unformatted = $this->removeFormatting();
            $this->valid = $this->validate();
        }
    }

    /**
     * Entered ISBN can containt spaces and/or hyphens. We need a "cleaned"
     * variant when performing actions like validate etc.
     *
     * @return string Contains only int 0-9 and X only.
     */
    public function removeFormatting()
    {
        return strtoupper(preg_replace("/[^0-9Xx]/", "", $this->isbn));
    }

    /**
     * Validates an unformatted ISBN number according to rules as described in
     * @url http://en.wikipedia.org/wiki/International_Standard_Book_Number
     *
     * @return bool
     */
    public function validate()
    {
        // Must operate on an unformatted number.
        $isbn = $this->unformatted;

        // Need to work on individual digits.
        $digits = str_split($isbn);

        // ControlDigit can also be x, not just a digit.
        $controlDigit = strtoupper(array_pop($digits));

        $digitSum = 0;

        // Validation for ISBN10.
        if ($this->is10()) {

            // Arcane magic happens here. See, wikipedia for details.
            foreach ($digits as $key => $value) {
                $digitSum += (10 - $key) * $value;
            }
            $checksum = (11 - ($digitSum % 11)) % 11;

            // If checkdigit is 10, X is used instead.
            $checkdigit = ($checksum < 10) ? $checksum : 'X';

            return $checkdigit == $controlDigit;
        }

        // Validation for ISBN13.
        if ($this->is13()) {

            // Every other digit is multiplied with 1 or 3.
            foreach ($digits as $key => $value) {
                $factor = ($key % 2) ? 3 : 1;
                $digitSum += $factor * $value;
            }
            $checksum = (10 - $digitSum % 10) % 10;

            // If checkdigit is 10, X is used instead.
            $checkdigit = ($checksum < 10) ? $checksum : 'X';

            return $checkdigit == $controlDigit;
        }

        return false;
    }

    /**
     * Returns ISBN-type.
     *
     * @return string
     */
    public function type()
    {
        if ($this->is10()) {
            return 'ISBN10';
        }
        if ($this->is13()) {
            return 'ISBN13';
        }

        return false;
    }

    /**
     * Check is entered ISBN is type ISBN10.
     *
     * @return bool
     */
    public function is10()
    {
        $isbn = $this->unformatted;

        return strlen($isbn) === 10;
    }

    /**
     * Check is entered ISBN is type ISBN13.
     *
     * @return bool
     */
    public function is13()
    {
        $isbn = $this->unformatted;

        return strlen($isbn) === 13;
    }


    public function hyphenate()
    {

        $isbn = $this->unformatted;

        // $isbn must be 13 digits long, must be validated first.
        // ISBN = $prefix-$reggroup-$regel-$pubel-$checkdigit

        $ranges = $this->getRanges();

        // $prefix_reggroup = $prefix+$reggroup
        $prefix_reggroup = $isbn;
        while (!isset($ranges->{$prefix_reggroup})) {
            $prefix_reggroup = substr($prefix_reggroup, 0, strlen($prefix_reggroup) - 1);
        }

        // Splitting up into $prefex and $reggroup
        if ($this->type() == 'ISBN10') {
            $prefix = '';
        }
        if ($this->type() == 'ISBN13') {
            $prefix = substr($prefix_reggroup, 0, 3);
        }
        $reggroup = substr($prefix_reggroup, 3);

        // ISBN remainder: $regel+$pubel+$checkdigit
        $isbn_remainder = substr($isbn, strlen($prefix) + strlen($reggroup));

        // $isbn_remainder may be longer or shorter than 7 digits. It must be exactly 7.
        // Max 7 digits
        $remainder_range_test = substr($isbn_remainder, 0, 7);
        // Min 7 digits, pad with 0
        $remainder_range_test = str_pad($remainder_range_test, 7, "0");

        $regel_num_digits = 0;
        foreach ($ranges->{$prefix_reggroup} as $range => $numdigits) {
            list($start, $end) = explode("-", $range);
            if ($start <= $remainder_range_test && $remainder_range_test <= $end) {
                $regel_num_digits = $numdigits;
                break;
            }
        }

        // $regel is num_digits of remainder_range
        $regel = substr($isbn_remainder, 0, $regel_num_digits);
        $pubel_checkdigit = substr($isbn, strlen($prefix) + strlen($reggroup) + strlen($regel));
        $pubel = substr($pubel_checkdigit, 0, strlen($pubel_checkdigit) - 1);
        $checkdigit = substr($pubel_checkdigit, -1, 1);

        $hyphenated = $prefix . '-' . $reggroup . '-' . $regel . '-' . $pubel . '-' . $checkdigit;
        return $hyphenated;
    }

    public function getRanges()
    {

        $path = './';

        if (file_exists($path . '/ranges.data')) {
            $ranges = json_decode(file_get_contents($path . '/ranges.data'));
        } else {
            if (file_exists($path . '/RangeMessage.xml') == FALSE) {
                throw new \InvalidArgumentException('Missing RangeMessage.xml in module path, see instructions');
            }
            $xml = simplexml_load_file($path . '/RangeMessage.xml');

            $json = json_encode($xml);
            $array = json_decode($json, TRUE);

            $ranges = array();
            foreach ($array as $groups) {
                if (!is_array($groups)) {
                    continue;
                }
                // Yes, I know, but the structure is known and won't blow things up.
                foreach ($groups as $group) {
                    foreach ($group as $data) {
                        foreach ($data['Rules']['Rule'] as $range) {
                            $prefix = str_replace("-", "", $data['Prefix']);
                            if (is_array($range)) {
                                $ranges[$prefix][$range['Range']] = $range['Length'];
                            } elseif (strlen($range) == 1) {
                                $ranges[$prefix]['0000000-9999999'] = $range;
                            }
                        }
                    }
                }
            }

            file_put_contents($path . '/ranges.data', json_encode($ranges));

            if (file_exists($path . '/ranges.data')) {
                $ranges = json_decode(file_get_contents($path . '/ranges.data'));
            }

        }
        return $ranges;
    }
}
