<?php
/**
 * Kialo restore task that provides all the settings and steps to perform one
 * complete restore of the activity.
 */

global $CFG;
require_once($CFG->dirroot . '/mod/kialo/backup/moodle2/restore_kialo_stepslib.php');

class restore_kialo_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Kialo only has one structure step.
        $this->add_step(new restore_kialo_activity_structure_step('kialo_structure', 'kialo.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    public static function define_decode_contents(): array {
        $contents = array();

        // We don't actually use the intro field right now, but since it's a default field we handle it here just in case
        // we are going to use it at some point.
        $contents[] = new restore_decode_content('kialo', array('intro'), 'kialo');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    public static function define_decode_rules() {
        $rules = array();

        $rules[] = new restore_decode_rule('KIALOVIEWBYID', '/mod/kialo/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('KIALOINDEX', '/mod/kialo/index.php?id=$1', 'course');

        return $rules;

    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * kialo logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    public static function define_restore_log_rules(): array {
        $rules = array();

        $rules[] = new restore_log_rule('kialo', 'add', 'view.php?id={course_module}', '{kialo}');
        $rules[] = new restore_log_rule('kialo', 'update', 'view.php?id={course_module}', '{kialo}');
        $rules[] = new restore_log_rule('kialo', 'view', 'view.php?id={course_module}', '{kialo}');
        $rules[] = new restore_log_rule('kialo', 'report', 'report.php?id={course_module}', '{kialo}');

        return $rules;
    }
}
