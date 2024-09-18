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

namespace mod_kialo\grading;

defined('MOODLE_INTERNAL') || die();

class line_item {
    /**
     * @var string|null $id
     */
    public $id;

    /**
     * @var float|null $scoremaximum
     */
    public $scoremaximum;

    /**
     * @var string|null $label
     */
    public $label;

    /**
     * @var string|null $resourceid
     */
    public $resourceid;

    /**
     * @var string|null $tag
     */
    public $resourcelinkid;

    /**
     * @var string|null $tag
     */
    public $tag;

    /**
     * ISO 8601 timestamp, see https://www.imsglobal.org/spec/lti-ags/v2p0#startdatetime.
     * @var string|null $startdatetime
     */
    public $startdatetime;

    /**
     * ISO 8601 timestamp, see https://www.imsglobal.org/spec/lti-ags/v2p0#enddatetime.
     * @var string|null $enddatetime
     */
    public $enddatetime;

    /**
     * @var bool|null $gradesreleased
     */
    public $gradesreleased;

    /**
     * LineItem constructor.
     * @param array|null $lineitem
     */
    public function __construct(?array $lineitem = null) {
        $this->id = $lineitem['id'] ?? null;
        $this->scoremaximum = $lineitem['scoreMaximum'] ?? null;
        $this->label = $lineitem['label'] ?? null;
        $this->resourceid = $lineitem['resourceId'] ?? null;
        $this->resourcelinkid = $lineitem['resourceLinkId'] ?? null;
        $this->tag = $lineitem['tag'] ?? null;
        $this->startdatetime = $lineitem['startDateTime'] ?? null;
        $this->enddatetime = $lineitem['endDateTime'] ?? null;
        $this->gradesreleased = $lineitem['gradesReleased'] ?? null;
    }
}
