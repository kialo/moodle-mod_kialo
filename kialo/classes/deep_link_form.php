<?php

namespace mod_kialo;

use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;

class deep_link_form {
    private LtiMessageInterface $message;

    public function __construct(LtiMessageInterface $ltideeplinkmsg) {
        $this->message = $ltideeplinkmsg;
    }

    /**
     * @param string $buttonid id of the button that should submit the form
     * @return string
     */
    public function create_form(string $buttonid, string $discussionurlinputid): string
    {
        $forminputs = [];
        $parameters = array_filter($this->message->getParameters()->all());

        $toolurl = kialo_config::get_instance()->get_tool_url();
        $formurl = $toolurl . '/lti/launch';
        $formid = sprintf('launch_%s', md5($toolurl . implode('-', $parameters)));

        foreach ($parameters as $name => $value) {
            $forminputs[] = "<input type=\"hidden\" name=\"{$name}\" value=\"{$value}\"/>";
        }
        $inputshtml = implode('', $forminputs);

        // TODO PM-42182: Remove this
        $inputshtml .= '<input type="hidden" name="preselected_discussion_url" value=""/>';

        $submitscript = "<script>
            function submit_deeplink() { 
                // TODO PM-42182: Remove the following lines
                var temp_url = document.getElementById(\"{$discussionurlinputid}\").value;
                document.getElementsByName(\"preselected_discussion_url\")[0].value = temp_url;
                var form = document.getElementById(\"{$formid}\");
                form.action = new URL(temp_url).origin + '/lti/launch';
                
                document.getElementById(\"{$formid}\").submit(); 
            }
            document.getElementById(\"{$buttonid}\").addEventListener(\"click\", submit_deeplink);
            </script>";

        $form = "<form id=\"$formid\" action=\"$formurl\" method=\"POST\" target=\"$formid\">$inputshtml</form>";

        return $form . $submitscript;
    }
}
