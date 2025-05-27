<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Manager for accessing and fetching events.
 *
 * @package     mod_bookit
 * @copyright   2024 Justus Dieckmann, Universität Münster
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\manager;

/**
 * Manager for accessing and fetching events.
 *
 * @package     mod_bookit
 * @copyright   2024 Justus Dieckmann, Universität Münster
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class color_manager {

    private static $colorcache = [];

    public static function get_textcolor_for_background(string $color): string {
        if (!isset(self::$colorcache[$color])) {
            self::$colorcache[$color] = self::calculate_textcolor_for_background($color);
        }
        return self::$colorcache[$color];
    }

    private static function calculate_textcolor_for_background(string $color): string {
        return self::calculate_luminosity_ratio($color, '#000') > self::calculate_luminosity_ratio($color, '#fff')
            ? '#000' : '#fff';
    }

    /**
     * Calculates the luminosity of a given RGB color.
     * The color code must be in the format of RRGGBB.
     * The luminosity equations are from the WCAG 2 requirements.
     * https://www.w3.org/TR/WCAG22/#dfn-relative-luminance
     *
     * @copyright gdkraus https://github.com/gdkraus/wcag2-color-contrast
     *
     * @param string $color
     * @return float
     */
    public static function calculate_luminosity(string $color): float {
        if (str_starts_with($color, '#')) {
            $color = substr($color, 1);
        }
        $r = hexdec(substr($color, 0, 2)) / 255; // Red value.
        $g = hexdec(substr($color, 2, 2)) / 255; // Green value.
        $b = hexdec(substr($color, 4, 2)) / 255; // Blue value.
        if ($r <= 0.03928) {
            $r = $r / 12.92;
        } else {
            $r = pow((($r + 0.055) / 1.055), 2.4);
        }

        if ($g <= 0.03928) {
            $g = $g / 12.92;
        } else {
            $g = pow((($g + 0.055) / 1.055), 2.4);
        }

        if ($b <= 0.03928) {
            $b = $b / 12.92;
        } else {
            $b = pow((($b + 0.055) / 1.055), 2.4);
        }

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Calculates the luminosity ratio of two colors.
     * The luminosity ratio equations are from the WCAG 2 requirements.
     * https://www.w3.org/TR/WCAG22/#dfn-contrast-ratio
     *
     * @copyright gdkraus https://github.com/gdkraus/wcag2-color-contrast
     *
     * @param string $color1
     * @param string $color2
     * @return float
     */
    public static function calculate_luminosity_ratio(string $color1, string $color2): float {
        $l1 = self::calculate_luminosity($color1);
        $l2 = self::calculate_luminosity($color2);

        if ($l1 > $l2) {
            $ratio = (($l1 + 0.05) / ($l2 + 0.05));
        } else {
            $ratio = (($l2 + 0.05) / ($l1 + 0.05));
        }
        return $ratio;
    }

    /**
     * Returns an array with the results of the color contrast analysis
     * It returns akey for each level (AA and AAA, both for normal and large or bold text).
     * It also returns the calculated contrast ratio.
     * The ratio levels are from the WCAG 2 requirements.
     * https://www.w3.org/WAI/WCAG22/quickref/#contrast-minimum
     * https://www.w3.org/WAI/WCAG22/quickref/#contrast-enhanced
     *
     * @copyright gdkraus https://github.com/gdkraus/wcag2-color-contrast
     *
     * @param string $color1
     * @param string $color2
     * @return array
     */
    public static function evaluate_color_contrast(string $color1, string $color2): array {
        $ratio = self::calculate_luminosity_ratio($color1, $color2);

        $colorevaluation["AA normal"] = ($ratio >= 4.5 ? 'pass' : 'fail');
        $colorevaluation["AA large"] = ($ratio >= 3 ? 'pass' : 'fail');
        $colorevaluation["AA medium bold"] = ($ratio >= 3 ? 'pass' : 'fail');
        $colorevaluation["AAA normal"] = ($ratio >= 7 ? 'pass' : 'fail');
        $colorevaluation["AAA large"] = ($ratio >= 4.5 ? 'pass' : 'fail');
        $colorevaluation["AAA medium bold"] = ($ratio >= 4.5 ? 'pass' : 'fail');
        $colorevaluation["Ratio"] = round($ratio, 2);

        return $colorevaluation;
    }

    /**
     * Print color evaluation for normal text.
     * @param string $color
     * @param string $color2
     * @return string
     */
    public static function print_color_evaluation(string $color, string $color2): string {
        $checkstring = '';
        $check = self::evaluate_color_contrast($color, $color2);
        foreach ($check as $key => $value) {
            if (str_contains($key, 'normal') || 'Ratio' == $key) {
                if ('pass' == $value) {
                    $value = '<i class="fa fa-circle-check text-success" aria-hidden="true"></i> ';
                } else if ('fail' == $value) {
                    $value = '<i class="fa fa-circle-xmark text-danger" aria-hidden="true"></i> ';
                }

                $checkstring .= $key . ': <strong>' . $value . '</strong> ';
            }
        }
        return $checkstring . ' <br>' . '<i class="fa fa-circle-info text-info"></i>
            WCAG 2.2 <a href="https://www.w3.org/TR/WCAG22/#contrast-minimum">AA</a> /
            <a href="https://www.w3.org/TR/WCAG22/#contrast-enhanced">AAA</a>.<br><br>';
    }

}