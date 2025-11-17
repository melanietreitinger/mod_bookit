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
 * Custom PDF class for BookIt module.
 *
 * @package     mod_bookit
 * @copyright   2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\pdf;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/pdflib.php');

/**
 * Custom PDF class that modifies header text alignment to right-align.
 * Extends Moodle's PDF class with minimal changes for BookIt requirements.
 */
class bookit_pdf extends \pdf {
    /**
     * Override header method to right-align the text while keeping logo on left.
     *
     * This method is used to render the page header.
     * It is automatically called by AddPage() and could be overwritten in your own inherited class.
     * @return void
     */
    public function Header() { // phpcs:ignore moodle.NamingConventions.ValidFunctionName.LowercaseMethod
        // phpcs:disable moodle.NamingConventions.ValidVariableName.VariableNameUnderscore
        if ($this->header_xobjid === false) {
            $this->header_xobjid = $this->startTemplate($this->w, $this->tMargin);
            $headerfont = $this->getHeaderFont();
            $headerdata = $this->getHeaderData();
            $this->y = $this->header_margin;
            if ($this->rtl) {
                $this->x = $this->w - $this->original_rMargin;
            } else {
                $this->x = $this->original_lMargin;
            }

            if (($headerdata['logo']) && ($headerdata['logo'] != K_BLANK_IMAGE)) {
                if (substr($headerdata['logo'], 0, 1) === '@') {
                    $this->Image($headerdata['logo'], '', '', $headerdata['logo_width']);
                } else {
                    $this->Image(K_PATH_IMAGES . $headerdata['logo'], '', '', $headerdata['logo_width']);
                }
                $imgy = $this->getImageRBY();
            } else {
                $imgy = $this->y;
            }

            $cell_height = $this->getCellHeight($headerfont[2] / $this->k);
            if ($this->getRTL()) {
                $header_x = $this->original_rMargin + ($headerdata['logo_width'] * 1.1);
            } else {
                $header_x = $this->original_lMargin + ($headerdata['logo_width'] * 1.1);
            }
            $cw = $this->w - $this->original_lMargin - $this->original_rMargin - ($headerdata['logo_width'] * 1.1);
            $this->setTextColorArray($this->header_text_color);

            $this->setFont($headerfont[0], 'B', $headerfont[2] + 1);
            $this->setX($header_x);
            $this->Cell($cw, $cell_height, $headerdata['title'], 0, 1, 'R', 0, '', 0);

            $this->setFont($headerfont[0], $headerfont[1], $headerfont[2]);
            $this->setX($header_x);
            $this->MultiCell($cw, $cell_height, $headerdata['string'], 0, 'R', 0, 1, '', '', true, 0, false, true, 0, 'T', false);

            $this->setLineStyle([
                'width' => 0.85 / $this->k,
                'cap' => 'butt',
                'join' => 'miter',
                'dash' => 0,
                'color' => $headerdata['line_color'],
            ]);
            $this->setY((2.835 / $this->k) + max($imgy, $this->y));
            if ($this->rtl) {
                $this->setX($this->original_rMargin);
            } else {
                $this->setX($this->original_lMargin);
            }
            $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 0, '', 'T', 0, 'C');
            $this->endTemplate();
        }
        $x = 0;
        $dx = 0;
        if (!$this->header_xobj_autoreset && $this->booklet && (($this->page % 2) == 0)) {
            $dx = ($this->original_lMargin - $this->original_rMargin);
        }
        if ($this->rtl) {
            $x = $this->w + $dx;
        } else {
            $x = 0 + $dx;
        }
        $this->printTemplate($this->header_xobjid, $x, 0, 0, 0, '', '', false);
    }
    // phpcs:enable moodle.NamingConventions.ValidVariableName.VariableNameUnderscore
}
