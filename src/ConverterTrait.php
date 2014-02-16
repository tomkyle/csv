<?php
/**
* Bakame.csv - A lightweight CSV Coder/Decoder library
*
* @author Ignace Nyamagana Butera <nyamsprod@gmail.com>
* @copyright 2014 Ignace Nyamagana Butera
* @link https://github.com/nyamsprod/Bakame.csv
* @license http://opensource.org/licenses/MIT
* @version 4.0.0
* @package Bakame.csv
*
* MIT LICENSE
*
* Permission is hereby granted, free of charge, to any person obtaining
* a copy of this software and associated documentation files (the
* "Software"), to deal in the Software without restriction, including
* without limitation the rights to use, copy, modify, merge, publish,
* distribute, sublicense, and/or sell copies of the Software, and to
* permit persons to whom the Software is furnished to do so, subject to
* the following conditions:
*
* The above copyright notice and this permission notice shall be
* included in all copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
* EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
* MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
* NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
* LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
* OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
* WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/
namespace Bakame\Csv;

use DomDocument;
use SplTempFileObject;
use Bakame\Csv\Iterator\MapIterator;

/**
 *  A abstract class to enable basic CSV manipulation
 *
 * @package Bakame.csv
 * @since  4.0.0
 *
 */
trait ConverterTrait
{
    /**
     * Output all data on the CSV file
     */
    public function output($filename = null)
    {
        $iterator = $this->getIterator();
        //@codeCoverageIgnoreStart
        if (! is_null($filename) && AbstractCsv::isValidString($filename)) {
            header('Content-Type: text/csv; charset="'.$this->encoding.'"');
            header('Content-Disposition: attachment; filename="firstname.csv"');
            if (! $iterator instanceof SplTempFileObject) {
                header('Content-Length: '.$iterator->getSize());
            }
        }
        //@codeCoverageIgnoreEnd
        $iterator->rewind();
        $iterator->fpassthru();
    }

    /**
     * Retrieves the CSV content
     *
     * @return string
     */
    public function __toString()
    {
        ob_start();
        $this->output();

        return ob_get_clean();
    }

    /**
     * Return a HTML table representation of the CSV Table
     *
     * @param string $classname optional classname
     *
     * @return string
     */
    public function toHTML($classname = 'table-csv-data')
    {
        $doc = new DomDocument('1.0', $this->encoding);
        $table = $doc->createElement('table');
        $table->setAttribute('class', $classname);
        foreach ($this->getIterator() as $row) {
            $tr = $doc->createElement('tr');
            foreach ($row as $value) {
                $content = $doc->createTextNode($value);
                $td = $doc->createElement('td');
                $td->appendChild($content);
                $tr->appendChild($td);
            }
            $table->appendChild($tr);
        }

        return $doc->saveHTML($table);
    }

    protected function convert2Utf8()
    {
        $iterator = $this->getIterator();
        if ('UTF-8' != $this->encoding) {
            $iterator = new MapIterator($iterator, function ($row) {
                foreach ($row as &$value) {
                    $value = mb_convert_encoding($value, 'UTF-8', $this->encoding);
                }
                unset($value);

                return $row;
            });
        }

        return $iterator;
    }

    public function toXML($root_name = 'csv', $row_name = 'row', $cell_name = 'cell')
    {
        $doc = new DomDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;
        $table = $doc->createElement($root_name);
        foreach ($this->convert2Utf8() as $row) {
            $tr = $doc->createElement($row_name);
            foreach ($row as $value) {
                $content = $doc->createTextNode($value);
                $td = $doc->createElement($cell_name);
                $td->appendChild($content);
                $tr->appendChild($td);
            }
            $table->appendChild($tr);
        }
        $doc->appendChild($table);

        return $doc->saveXML();
    }

    /**
     * JsonSerializable Interface
     *
     * @return array
     */
    public function jsonSerialize()
    {
        $iterator = $this->convert2Utf8();

        return iterator_to_array($iterator, false);
    }
}