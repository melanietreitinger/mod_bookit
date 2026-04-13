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
 * Custom form element for room multi-select filter with two-row toggle layout.
 *
 * @package     mod_bookit
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\formelement;

use HTML_QuickForm_select;
use core\output\renderer_base;
use mod_bookit\local\persistent\room;
use mod_bookit\local\manager\color_manager;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/form/templatable_form_element.php');
require_once($CFG->dirroot . '/lib/form/select.php');

/**
 * Custom form element for room multi-select filter.
 *
 * Supports two rendering modes:
 * - filter: Two-row toggle layout with +/✓ icons (for table filtering)
 * - form: Traditional multiselect (for edit forms)
 *
 * @package     mod_bookit
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class roomfilter extends HTML_QuickForm_select implements \core\output\templatable {
    use \templatable_form_element {
        export_for_template as export_for_template_base;
    }

    /** @var bool Filter mode flag. */
    private $filtermode = false;

    /** @var array Room data for custom rendering. */
    private $roomdata = [];

    /** @var array|null Cached room records. */
    private $cachedrooms = null;

    /**
     * Registers the element type.
     */
    public static function register(): void {
        global $CFG;

        \MoodleQuickForm::registerElementType(
            'mod_bookit_roomfilter',
            $CFG->dirroot . '/mod/bookit/classes/local/formelement/roomfilter.php',
            '\mod_bookit\local\formelement\roomfilter'
        );
    }

    /**
     * Constructor.
     *
     * @param string $elementname Element name.
     * @param string $elementlabel Element label.
     * @param array $options Room options (optional, will auto-load if empty).
     * @param array $attributes Element attributes (mode => 'filter'|'form').
     */
    public function __construct($elementname = null, $elementlabel = null, $options = null, $attributes = null) {
        // If no options provided, load active rooms.
        if (empty($options) || !is_array($options)) {
            $options = $this->load_room_options();
        }

        // Store room data for custom rendering.
        $this->roomdata = $options;

        // Initialize parent with options for hidden select element.
        parent::__construct($elementname, $elementlabel, $options, $attributes);
        $this->setMultiple(true);

        // Add CSS class for styling.
        $class = $this->getAttribute('class') ?? '';
        $mode = is_array($attributes) ? ($attributes['mode'] ?? 'filter') : 'filter';
        $this->updateAttributes(['class' => $class . ' mod_bookit-roomfilter mod_bookit-roomfilter-' . $mode]);
        $this->filtermode = ($mode === 'filter');
    }

    /**
     * Get active rooms from database, with caching.
     *
     * @return array Room objects indexed by ID.
     */
    private function get_rooms(): array {
        if ($this->cachedrooms === null) {
            $this->cachedrooms = room::get_records(['active' => 1], 'name', 'ASC');
        }
        return $this->cachedrooms;
    }

    /**
     * Load active rooms as options.
     *
     * @return array Room options as id => name pairs.
     */
    private function load_room_options(): array {
        $rooms = $this->get_rooms();
        $options = [];

        foreach ($rooms as $room) {
            // Store simple string value (name) as option.
            $options[$room->get('id')] = $room->get('name');
        }

        return $options;
    }

    /**
     * Get room color data.
     *
     * @return array Room colors indexed by room ID.
     */
    private function get_room_colors(): array {
        $rooms = $this->get_rooms();
        $colors = [];

        foreach ($rooms as $room) {
            $colors[$room->get('id')] = $room->get('eventcolor');
        }

        return $colors;
    }

    /**
     * Export for template.
     *
     * @param renderer_base $output Renderer.
     * @return \stdClass Template context.
     */
    public function export_for_template(renderer_base $output): \stdClass {
        $mode = $this->getAttribute('mode') ?? 'filter';

        if ($mode === 'filter') {
            return $this->export_for_filter_mode($output);
        } else {
            // For form mode, use default rendering.
            return $this->export_for_template_base($output);
        }
    }

    /**
     * Export for filter mode (two-row toggle layout).
     *
     * @param renderer_base $output Renderer.
     * @return \stdClass Template context.
     */
    private function export_for_filter_mode(renderer_base $output): \stdClass {
        $context = new \stdClass();
        $context->name = $this->getName();
        $context->id = $this->getAttribute('id');
        $context->filter_section_id = $context->id . '_filter_section';
        $context->label = $this->getLabel();
        $rooms = [];

        $selectedvalues = (array) $this->getValue();
        $roomcolors = $this->get_room_colors();

        // Use our stored room data instead of $this->_options.
        foreach ($this->roomdata as $roomid => $roomname) {
            $isselected = in_array($roomid, $selectedvalues);
            $eventcolor = $roomcolors[$roomid] ?? '#6c757d';

            $rooms[] = [
                'id' => (string)$roomid,
                'name' => (string)$roomname,
                'selected' => $isselected,
                'eventcolor' => $eventcolor,
                'textcolor' => color_manager::get_textcolor_for_background($eventcolor),
            ];
        }

        // Separate into selected and unselected for two-row layout.
        $context->rooms_selected = array_values(array_filter($rooms, fn($r) => $r['selected']));
        $context->rooms_unselected = array_values(array_filter($rooms, fn($r) => !$r['selected']));

        // Render the template.
        $html = $output->render_from_template('mod_bookit/form/roomfilter', $context);

        // Return context with rendered HTML.
        $result = new \stdClass();
        $result->html = $html;

        // Note: JavaScript initialization is handled by the parent reactive component (resource_catalog.js).
        // This allows the filter to integrate with Moodle's reactive system properly.

        return $result;
    }
}
