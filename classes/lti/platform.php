<?php

namespace mod_kialo\lti;

abstract class lti_platform {
    abstract public function init_resource_link(int $courseid, int $coursemoduleid, string $deploymentid,
            string $moodleuserid): string;
}
