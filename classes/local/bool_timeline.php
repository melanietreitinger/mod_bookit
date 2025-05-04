<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Data structure for a timeline.
 *
 * @package    mod_bookit
 * @copyright  2022 Justus Dieckmann University of Münster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_bookit\local;
/**
 * A data structure to save and edit values over ranges of time.
 *
 * @package    mod_bookit
 * @copyright  2022 Justus Dieckmann University of Münster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bool_timeline {

    /**
     * @var array[] Array of pairs [time, value]. Every value is valid from the specified time until the next value
     * is defined.
     */
    private array $array;

    /**
     * Creates a timeline.
     * @param bool $initvalue Every timestamp starts initialized with this value
     */
    public function __construct($initvalue) {
        $this->array = [[PHP_INT_MIN, $initvalue]];
    }
    /**
     * Set $value at $time.
     * @param int $time
     * @param bool $value
     * @return int index where the time can be found.
     */
    public function set(int $time, $value): int {
        $index = $this->get_index_for($time);
        if ($this->array[$index][1] === $value) {
            return $index;
        }
        if ($this->array[$index][0] === $time) {
            $this->array[$index][1] = $value;
            return $index;
        } else {
            array_splice($this->array, $index + 1, 0, [[$time, $value]]);
            return $index + 1;
        }
    }

    /**
     * Set $value at the specified $index.
     * @param int $index
     * @param bool $value
     */
    public function set_at_index(int $index, $value): void {
        $this->array[$index][1] = $value;
    }

    /**
     * Get the index for the specified $time.
     * @param int $time timestamp
     * @return int index
     */
    public function get_index_for(int $time): int {
        $min = 0;
        $max = count($this->array) - 1;
        while ($min < $max) {
            $i = (int) ceil(($min + $max) / 2);
            $v = $this->array[$i][0];
            if ($time === $v) {
                return $i;
            } else if ($time > $v) {
                $min = $i;
            } else {
                $max = $i - 1;
            }
        }
        return $min;
    }

    /**
     * Get the value at the specified $index.
     * @param int $index
     * @return bool value
     */
    public function get_value_at_index(int $index) {
        return $this->array[$index][1];
    }

    /**
     * Set the $value for the specified range.
     * @param int $starttime timestamp, inclusive
     * @param int $endtime timestamp, exclusive
     * @param mixed $value value
     */
    public function set_range(int $starttime, int $endtime, $value): void {
        if ($starttime >= $endtime) {
            return;
        }
        $oldstartindex = $this->get_index_for($starttime);
        $startvalue = $this->get_value_at_index($oldstartindex);
        $endindex = $this->get_index_for($endtime);
        $endvalue = $this->get_value_at_index($endindex);
        if ($startvalue === $value) {
            // Current value at $starttime already equals $value.
            $startindex = $oldstartindex;
        } else {
            $startindex = $this->set($starttime, $value);
        }
        // Update endindex, because start time was inserted.
        $endindex += $startindex - $oldstartindex;

        for ($i = $startindex + 1; $i < $endindex; $i++) {
            $this->set_at_index($i, $value);
        }
        $this->set($endtime, $endvalue);
        $this->normalize_index_range(max($startindex - 1, 0), min($endindex + 1, count($this->array) - 1));
    }

    /**
     * Returns whether the complete time range equals $value.
     * @param int $starttime timestamp, inclusive
     * @param int $endtime timestamp, exclusive
     * @param $value
     * @return bool
     */
    public function does_complete_range_equal(int $starttime, int $endtime, $value): bool {
        $startindex = $this->get_index_for($starttime);
        $endindex = $this->get_index_for($endtime - 1);
        for ($i = $startindex; $i <= $endindex; $i++) {
            if ($this->get_value_at_index($i) !== $value) {
                return false;
            }
        }
        return true;
    }

    /**
     * "Normalizes" the array in the specified range, meaning duplicate entries get removed.
     * @param $startindex
     * @param $endindex
     */
    private function normalize_index_range($startindex, $endindex) {
        $deletedamount = 0;
        for ($i = $startindex; $i < $endindex; $i++) {
            if ($this->array[$i - $deletedamount][1] === $this->array[$i - $deletedamount + 1][1]) {
                array_splice($this->array, $i - $deletedamount + 1, 1);
                $deletedamount++;
            }
        }
    }

    /**
     * Returns timeline as array.
     * @return array
     */
    public function export(): array {
        $obj = [];
        foreach ($this->array as $k => $v) {
            $obj[$k] = $v;
        }
        return $obj;
    }
}
