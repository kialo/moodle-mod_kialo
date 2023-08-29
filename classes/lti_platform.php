<?php

namespace mod_kialo;

class moodle_lti_platform {
    public function init_resource_link(int $courseid, int $coursemoduleid, string $deploymentid, string $moodleuserid): string {
        $kialoconfig = kialo_config::get_instance();
        $toolconfig = (object) [
                "tool_url" => $kialoconfig->get_tool_url()
        ];

        return lti_initiate_login($courseid, $coursemoduleid, null, $toolconfig, 'basic-lti-launch-request');
    }

    /**
     * Generates some of the tool configuration based on the admin configuration details
     *
     * @param int $id
     *
     * @return stdClass Configuration details
     */
    protected function lti_get_type_type_config() {
        global $DB;

        $type_config = lti_get_type_config($id);

        $type = new \stdClass();

        $type->lti_typename = $basicltitype->name;

        $type->typeid = $basicltitype->id;

        $type->toolproxyid = $basicltitype->toolproxyid;

        $type->lti_toolurl = $basicltitype->baseurl;

        $type->lti_ltiversion = $basicltitype->ltiversion;

        $type->lti_clientid = $basicltitype->clientid;
        $type->lti_clientid_disabled = $type->lti_clientid;

        $type->lti_description = $basicltitype->description;

        $type->lti_parameters = $basicltitype->parameter;

        $type->lti_icon = $basicltitype->icon;

        $type->lti_secureicon = $basicltitype->secureicon;

        if (isset($config['resourcekey'])) {
            $type->lti_resourcekey = $config['resourcekey'];
        }
        if (isset($config['password'])) {
            $type->lti_password = $config['password'];
        }
        if (isset($config['publickey'])) {
            $type->lti_publickey = $config['publickey'];
        }
        if (isset($config['publickeyset'])) {
            $type->lti_publickeyset = $config['publickeyset'];
        }
        if (isset($config['keytype'])) {
            $type->lti_keytype = $config['keytype'];
        }
        if (isset($config['initiatelogin'])) {
            $type->lti_initiatelogin = $config['initiatelogin'];
        }
        if (isset($config['redirectionuris'])) {
            $type->lti_redirectionuris = $config['redirectionuris'];
        }

        if (isset($config['sendname'])) {
            $type->lti_sendname = $config['sendname'];
        }
        if (isset($config['instructorchoicesendname'])) {
            $type->lti_instructorchoicesendname = $config['instructorchoicesendname'];
        }
        if (isset($config['sendemailaddr'])) {
            $type->lti_sendemailaddr = $config['sendemailaddr'];
        }
        if (isset($config['instructorchoicesendemailaddr'])) {
            $type->lti_instructorchoicesendemailaddr = $config['instructorchoicesendemailaddr'];
        }
        if (isset($config['acceptgrades'])) {
            $type->lti_acceptgrades = $config['acceptgrades'];
        }
        if (isset($config['instructorchoiceacceptgrades'])) {
            $type->lti_instructorchoiceacceptgrades = $config['instructorchoiceacceptgrades'];
        }
        if (isset($config['allowroster'])) {
            $type->lti_allowroster = $config['allowroster'];
        }
        if (isset($config['instructorchoiceallowroster'])) {
            $type->lti_instructorchoiceallowroster = $config['instructorchoiceallowroster'];
        }

        if (isset($config['customparameters'])) {
            $type->lti_customparameters = $config['customparameters'];
        }

        if (isset($config['forcessl'])) {
            $type->lti_forcessl = $config['forcessl'];
        }

        if (isset($config['organizationid_default'])) {
            $type->lti_organizationid_default = $config['organizationid_default'];
        } else {
            // Tool was configured before this option was available and the default then was host.
            $type->lti_organizationid_default = LTI_DEFAULT_ORGID_SITEHOST;
        }
        if (isset($config['organizationid'])) {
            $type->lti_organizationid = $config['organizationid'];
        }
        if (isset($config['organizationurl'])) {
            $type->lti_organizationurl = $config['organizationurl'];
        }
        if (isset($config['organizationdescr'])) {
            $type->lti_organizationdescr = $config['organizationdescr'];
        }
        if (isset($config['launchcontainer'])) {
            $type->lti_launchcontainer = $config['launchcontainer'];
        }

        if (isset($config['coursevisible'])) {
            $type->lti_coursevisible = $config['coursevisible'];
        }

        if (isset($config['contentitem'])) {
            $type->lti_contentitem = $config['contentitem'];
        }

        if (isset($config['toolurl_ContentItemSelectionRequest'])) {
            $type->lti_toolurl_ContentItemSelectionRequest = $config['toolurl_ContentItemSelectionRequest'];
        }

        if (isset($config['debuglaunch'])) {
            $type->lti_debuglaunch = $config['debuglaunch'];
        }

        if (isset($config['module_class_type'])) {
            $type->lti_module_class_type = $config['module_class_type'];
        }

        // Get the parameters from the LTI services.
        foreach ($config as $name => $value) {
            if (strpos($name, 'ltiservice_') === 0) {
                $type->{$name} = $config[$name];
            }
        }

        return $type;
    }
}
