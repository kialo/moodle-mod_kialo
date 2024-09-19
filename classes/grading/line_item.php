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

// phpcs:disable moodle.NamingConventions.ValidFunctionName.LowercaseMethod
// phpcs:disable moodle.NamingConventions.ValidVariableName.MemberNameUnderscore

namespace mod_kialo\grading;

defined('MOODLE_INTERNAL') || die();

/**
 * Represents a line item in the LTI 1.3 Assignment and Grading Service.
 */
class line_item {
    /**
     * @var string|null $id
     */
    public $id;

    /**
     * @var float|null $scoreMaximum
     */
    public $scoreMaximum;

    /**
     * @var string|null $label
     */
    public $label;

    /**
     * @var string|null $resourceId
     */
    public $resourceId;

    /**
     * @var string|null $tag
     */
    public $resourceLinkId;

    /**
     * @var string|null $tag
     */
    public $tag;

    /**
     * ISO 8601 timestamp, see https://www.imsglobal.org/spec/lti-ags/v2p0#startdatetime.
     *
     * @var string|null $startDateTime
     */
    public $startDateTime;

    /**
     * ISO 8601 timestamp, see https://www.imsglobal.org/spec/lti-ags/v2p0#enddatetime.
     *
     * @var string|null $endDateTime
     */
    public $endDateTime;

    /**
     * @var bool|null $gradesReleased
     */
    public $gradesReleased;

    /**
     * LineItem constructor.
     * @param array|null $lineitem
     */
    public function __construct(?array $lineitem = null) {
        $this->id = $lineitem['id'] ?? null;
        $this->scoreMaximum = $lineitem['scoreMaximum'] ?? null;
        $this->label = $lineitem['label'] ?? null;
        $this->resourceId = $lineitem['resourceId'] ?? null;
        $this->resourceLinkId = $lineitem['resourceLinkId'] ?? null;
        $this->tag = $lineitem['tag'] ?? null;
        $this->startDateTime = $lineitem['startDateTime'] ?? null;
        $this->endDateTime = $lineitem['endDateTime'] ?? null;
        $this->gradesReleased = $lineitem['gradesReleased'] ?? null;
    }
}
