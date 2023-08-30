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

namespace mod_kialo\output;

use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Data container used for the loading page.
 *
 * @package    mod_kialo
 * @copyright  2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class loading_page implements renderable, templatable {
    /** @var string $sometext Title of the loading page (displayed in the window/tab title bar). */
    private $htmltitle = "";

    /** @var string $loadingtext Text displayed on the loading page. */
    private $loadingtext = "";

    /** @var string $extrahtml Arbitrary HTML to be inserted into the page. Used for redirect forms. */
    private $extrahtml = "";

    /**
     * Creates a new loading page data container.
     * @param string $htmltitle
     * @param string $loadingtext
     * @param string $extrahtml
     */
    public function __construct(string $htmltitle, string $loadingtext, string $extrahtml) {
        $this->htmltitle = $htmltitle;
        $this->loadingtext = $loadingtext;
        $this->extrahtml = $extrahtml;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $data = new stdClass();
        $data->htmltitle = $this->htmltitle;
        $data->loadingtext = $this->loadingtext;
        $data->extrahtml = $this->extrahtml;
        return $data;
    }
}
