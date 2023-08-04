<?php

namespace mod_kialo;

class deep_linking_result {
    /**
     * Unique string identifier that was sent by our Moodle plugin when the activity was created.
     * When the user selects a discussion on Kialo, this identifier is used to store the selected
     * discussion, and to associate the correct discussion later when students open the activity
     * with this deployment id.
     */
    public string $deploymentid;

    /**
     * URL of the selected discussion.
     */
    public string $discussionurl;

    public string $discussiontitle;

    public function __construct(string $deploymentid, string $discussionurl, string $discussiontitle) {
        $this->deploymentid = $deploymentid;
        $this->discussionurl = $discussionurl;
        $this->discussiontitle = $discussiontitle;
    }
}
