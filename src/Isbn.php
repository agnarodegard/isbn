<?php

namespace Agnarodegard\Isbn;

/**
 * Class Isbn
 *
 * The main class for ISBN handling.
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
    private function removeFormatting()
    {
        return strtoupper(preg_replace("/[^0-9Xx]/", "", $this->isbn));
    }

    /**
     * Validates an unformatted ISBN number according to rules as described in
     * http://en.wikipedia.org/wiki/International_Standard_Book_Number
     *
     * Validation assumes complete ISBN10 or ISBN13 number.
     *
     * @return bool
     */
    public function validate()
    {
        // Must operate on an unformatted number.
        $isbn = $this->unformatted;

        // ControlDigit is the last digit in the ISBN.
        $checkDigit = substr($isbn, -1);

        return $checkDigit == $this->checkDigit();

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
    private function is10()
    {
        $isbn = $this->unformatted;

        return strlen($isbn) === 10;
    }

    /**
     * Check is entered ISBN is type ISBN13.
     *
     * @return bool
     */
    private function is13()
    {
        $isbn = $this->unformatted;

        return strlen($isbn) === 13;
    }

    /**
     * Ensures the ISBN we want to compute the checkDigit for, is actually
     * without a checkDigit.
     *
     * @return bool|int|string Returns checkDigit or false.
     */
    public function checkDigit()
    {
        $string = $this->unformatted;
        switch (strlen($string)) {
            case 9:
                return $this->calculateCheckDigit10($string);
                break;
            case 10:
                return $this->calculateCheckDigit10(substr($string, 0, -1));
                break;
            case 12:
                return $this->calculateCheckDigit13($string);
                break;
            case 13:
                return $this->calculateCheckDigit13(substr($string, 0, -1));
                break;
            default:
                return false;
        }
    }

    /**
     * Computes the checkDigit for an ISBN10.
     *
     * Rules for computation are defined here:
     * http://en.wikipedia.org/wiki/International_Standard_Book_Number#ISBN-10_check_digit_calculation
     *
     * @param $string       ISBN without checkDigit
     * @return int|string   computed checkDigit
     */
    private function calculateCheckDigit10($string)
    {
        // Need to work on individual digits.
        $digits = str_split($string);

        // Temp variable to hold running sum of calculation.
        $digitSum = 0;

        // See, wikipedia for details.
        foreach ($digits as $key => $value) {
            $digitSum += (10 - $key) * $value;
        }
        $checksum = (11 - ($digitSum % 11)) % 11;

        // If checkDigit is 10, X is used instead for ISBN10.
        $checkDigit = ($checksum < 10) ? $checksum : 'X';

        return $checkDigit;

    }

    /**
     * Computes the checkDigit for an ISBN13.
     *
     * Rules for computation are defined here:
     * http://en.wikipedia.org/wiki/International_Standard_Book_Number#ISBN-13_check_digit_calculation
     *
     * @param $string       ISBN without checkDigit
     * @return int|string   computed checkDigit
     */
    private function calculateCheckDigit13($string)
    {

        // Need to work on individual digits.
        $digits = str_split($string);

        // Temp variable to hold running sum of calculation.
        $digitSum = 0;

        // Every other digit is multiplied with 1 or 3.
        foreach ($digits as $key => $value) {
            $factor = ($key % 2) ? 3 : 1;
            $digitSum += $factor * $value;
        }

        // Take the modules of 10, twice.
        $checkDigit = (10 - $digitSum % 10) % 10;

        return $checkDigit;

    }


    /**
     * Properly formats an ISBN with optional $glue separating the groups.
     *
     * This function calculates the length of each group of an ISBN according
     * to ranges defined here:
     * https://www.isbn-international.org/range_file_generation
     * and concatenates them together with the chosen $glue. No operations are
     * performed on the digits themselves.
     *
     * An ISBN is made up of different groups:
     *
     * [EAN.UCC prefix]             => $prefix      only for ISBN13
     * [Registration Group element] => $regGroup
     * [Registrant element]         => $regEl
     * [Publication element]        => $pubEl
     * [Check-digit]                => $checkDigit
     *
     * @return bool|string
     */
    public function hyphenate($glue = '-')
    {
        // Entered ISBN must be valid in order to hyphenate.
        if ($this->valid == false) {
            return false;
        }

        $isbn = $this->unformatted;

        // Rules for hyphenating are identical for ISBN10 and ISBN 13.
        // ISBN10 is just a ISBN13 with '978' in front but with a
        // different checkDigit. We are not concerned with checkDigit
        // here since we assume the ISBN is already validated.
        if ($this->is10()) {
            $isbn = '978' . $isbn;
        }

        // The ranges are indexed with $prefix . $regGroup
        $ranges = $this->getRanges();

        // The index varies in length. We find the first two groups by checking
        // isset on key in $ranges for increasingly smaller $prefixRegGroup
        // starting with the full ISBN.
        $prefixRegGroup = $isbn;

        while (!isset($ranges->{$prefixRegGroup}) && strlen($prefixRegGroup) > 2) {

            // $prefixRegGroup not found, shortening by 1.
            $prefixRegGroup = substr($prefixRegGroup, 0, strlen($prefixRegGroup) - 1);

        }

        // $prefixRegGroup not found in index, something is wrong. Hard to say what.
        if (strlen($prefixRegGroup) == 2) {
            return false;
        }

        // $prefix is always the first 3 digits.
        $prefix = substr($prefixRegGroup, 0, 3);

        // $regGroup is whatever digits are left.
        $regGroup = substr($prefixRegGroup, 3);

        // Next we check in what range the remainder of the ISBN lies.
        // This will tell us the length of the next group, $regEl.
        // isbnRemainder: $regEl+$pubEl+$checkDigit
        $isbnRemainder = substr($isbn, strlen($prefix) + strlen($regGroup));

        // $isbnRemainder may be longer or shorter than 7 digits. The ranges
        // have all 7 digits, so $isbnRemainder must also have 7 digits.
        // Max 7 digits, cut off rest if any.
        $isbnRemainder = substr($isbnRemainder, 0, 7);
        // Min 7 digits, pad with 0.
        $isbnRemainder = str_pad($isbnRemainder, 7, "0");

        // Search through ranges to find $regElLength.
        $regElLength = 0;
        foreach ($ranges->{$prefixRegGroup} as $range => $numdigits) {

            // Ranges are gives as xxxxxxx-yyyyyyy.
            list($start, $end) = explode("-", $range);
            if ($start <= $isbnRemainder && $isbnRemainder <= $end) {
                $regElLength = $numdigits;
                break;
            }
        }

        // $regEl is the first $regElLength digits of $isbnRemainder.
        $regEl = substr($isbnRemainder, 0, $regElLength);

        // $pubEl and $checkDigit left.
        $pubElCheckDigit = substr($isbn, strlen($prefix . $regGroup . $regEl));

        // $pubEl is all digits except the last one.
        $pubEl = substr($pubElCheckDigit, 0, strlen($pubElCheckDigit) - 1);

        // The last digit is the checkDigit.
        $checkDigit = substr($isbn, -1);

        // All together now!
        if ($this->is10()) {
            $hyphenated =
                $regGroup .
                $glue . $regEl .
                $glue . $pubEl .
                $glue . $checkDigit;
        } else {
            $hyphenated =
                $prefix .
                $glue . $regGroup .
                $glue . $regEl .
                $glue . $pubEl .
                $glue . $checkDigit;
        }

        return $hyphenated;
    }

    /**
     * Provides a deterministically formatted array of ranges defining the
     * grouping of ISBNs to be used when hyphenating an ISBN.
     *
     * getRanges() need RangeMessage.xml from www.isbn-international.org.
     *
     * The ranges are sorted into an array indexed by the first two ISBN groups
     * and subarrays with key => range, value => length.
     *
     * [$prefix . $regGroup] => [
     *     range1 => length,
     *     range2 => length,
     *     ...
     * ]
     *
     * Since this process involves reading and parsing XML, the function tries
     * to save a file, ranges.data, with the formatted array for faster
     * retrieval.
     *
     * @return array The returned array is structured like this:
     */
    public function getRanges()
    {
        // Assumes all files needed are placed in the root of the library.
        $path = dirname(dirname(__FILE__)) . '/';

        // Return saved ranges if range.data exist.
        if (file_exists($path . 'ranges.data')) {
            return json_decode(file_get_contents($path . 'ranges.data'));
        }

        // Cannot continue without RangeMessage.xml.
        if (file_exists($path . 'RangeMessage.xml') == false) {
            throw new \InvalidArgumentException('Missing RangeMessage.xml. Download from https://www.isbn-international.org/range_file_generation');
        }

        $xml = simplexml_load_file($path . 'RangeMessage.xml');

        // Working on JSON is easier.
        $json = json_encode($xml);
        $array = json_decode($json, true);

        $ranges = array();
        foreach ($array as $groups) {

            // The XML contains other data too, we only want the ranges and
            // those are in arrays.
            if (!is_array($groups)) {
                continue;
            }

            // Yes, I know, but the structure is known and won't blow things up.
            foreach ($groups as $group) {
                foreach ($group as $data) {
                    foreach ($data['Rules']['Rule'] as $range) {

                        // Building the array structure.
                        $prefix = str_replace("-", "", $data['Prefix']);
                        if (is_array($range)) {
                            $ranges[$prefix][$range['Range']] = $range['Length'];
                        } // Of course, if there is only one range, it is not in an array!
                        else {
                            if (strlen($range) == 1) {
                                $ranges[$prefix]['0000000-9999999'] = $range;
                            }
                        }
                    }
                }
            }
        }

        $rangesJson = json_encode($ranges);

        // Save the structured array.
        file_put_contents($path . '/ranges.data', $rangesJson);

        return json_decode($rangesJson);
    }
}
