<?php

/**
 * Structure step to restore one kialo activity.
 */
class restore_kialo_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {
        $paths = array();
        $paths[] = new restore_path_element('kialo', '/activity/kialo');
        return $this->prepare_activity_structure($paths);
    }

    protected function process_kialo($data) {
        global $DB;

        $data = (object) $data;
        $data->course = $this->get_courseid();

        $newitemid = $DB->insert_record('kialo', $data);
        $this->apply_activity_instance($newitemid);
    }
}
