<?php

namespace mod_kialo;

class kialo_config_test extends \basic_testcase {
    public function test_default_tool_url() {
        # The variable TARGET_KIALO_URL is only set in Kialo test environments. By default it's not defined.
        putenv("TARGET_KIALO_URL=");

        # In production, kialo-edu.com is always the endpoint for our plugin.
        $this->assertEquals("https://www.kialo-edu.com", kialo_config::get_instance()->get_tool_url());
    }

    public function test_custom_tool_url() {
        putenv("TARGET_KIALO_URL=http://localhost:5000");
        $this->assertEquals("http://localhost:5000", kialo_config::get_instance()->get_tool_url());
    }
}
