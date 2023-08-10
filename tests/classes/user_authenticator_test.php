<?php

namespace mod_kialo;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../vendor/autoload.php');

use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Security\User\Result\UserAuthenticationResultInterface;
use stdClass;

class user_authenticator_test extends \advanced_testcase {
    private stdClass $course;

    private stdClass $module;

    private RegistrationInterface $registrationstub;

    protected function setUp(): void {
        parent::setUp();

        $this->resetAfterTest();

        $this->course = $this->getDataGenerator()->create_course();
        $this->registrationstub = $this->createStub(RegistrationInterface::class);
        $this->user_authenticator = new user_authenticator();

        // Creates a Kialo activity.
        $this->module = $this->getDataGenerator()->create_module('kialo', array('course' => $this->course->id));
    }

    protected function authenticate_user(string $userid): UserAuthenticationResultInterface {
        $loginhint = "{$this->course->id}/$userid";
        return $this->user_authenticator->authenticate($this->registrationstub, $loginhint);
    }

    public function test_basic_successful_auth() {
        // Given a user with a very particular set of timezone and language.
        $user = $this->getDataGenerator()->create_user([
                "username" => "hoschi",
                "email" => "hoschi@mustermann.com",
                "firstname" => "Horscht",
                "lastname" => "Mustermann",
                "middlename" => "The Machine",
        ]);
        $this->setUser($user);
        $this->getDataGenerator()->enrol_user($user->id, $this->course->id, "student");

        // When they authenticate via LTI.
        $data = $this->authenticate_user($user->id);

        // It should be successfull.
        $this->assertTrue($data->isSuccess());
        $this->assertFalse($data->isAnonymous());

        // The user details should be returned correctly.
        $identity = $data->getUserIdentity();
        $this->assertEquals("hoschi", $identity->getIdentifier());
        $this->assertEquals("hoschi@mustermann.com", $identity->getEmail());
        $this->assertEquals("Horscht", $identity->getGivenName());
        $this->assertEquals("Mustermann", $identity->getFamilyName());
        $this->assertEquals("The Machine", $identity->getMiddleName());
        $this->assertMatchesRegularExpression(
                "|https://www.example.com/moodle/theme/image.php/_s/boost/core/\d+/u/f\d+|",
                $identity->getPicture()
        );
    }

    public function test_timezone() {
        // Given a user with a very particular set of timezone and language.
        $user = $this->getDataGenerator()->create_user([
                "timezone" => "Asia/Tokyo",
        ]);
        $this->setUser($user);
        $this->getDataGenerator()->enrol_user($user->id, $this->course->id, "student");

        // When they authenticate via LTI.
        $data = $this->authenticate_user($user->id);

        // It should be successfull and return the correct language and timezone.
        $this->assertTrue($data->isSuccess());
        $this->assertEquals("Asia/Tokyo", $data->getUserIdentity()->getAdditionalProperties()->get('zoneinfo'));
    }

    public function test_locale() {
        // Given a user with a very particular set of timezone and language.
        $user = $this->getDataGenerator()->create_user([
                "lang" => "en"
        ]);
        $this->setUser($user);
        $this->getDataGenerator()->enrol_user($user->id, $this->course->id, "student");

        // When they authenticate via LTI.
        $data = $this->authenticate_user($user->id);

        // It should be successfull and return the correct language and timezone.
        $this->assertTrue($data->isSuccess());
        $this->assertEquals("en", $data->getUserIdentity()->getLocale());
    }

    public function test_user_not_enrolled_in_course() {
        // Given a user that is not enrolled in the course.
        $usernotenrolled = $this->getDataGenerator()->create_user();
        $this->setUser($usernotenrolled);

        // When they authenticate via LTI it fails since they are not enrolled.
        try {
            $this->authenticate_user($usernotenrolled->id);
            $this->fail('Exception expected because user is not enrolled in the course.');
        } catch (\moodle_exception $e) {
            $this->assertEquals('redirecterrordetected', $e->errorcode);
        }
    }

    public function test_doesnt_work_for_guest_users() {
        $this->setGuestUser();
        $this->resetAfterTest();

        try {
            $this->authenticate_user("69");
            $this->fail('Exception expected because user is not logged-in as a real user.');
        } catch (\moodle_exception $e) {
            $this->assertEquals('redirecterrordetected', $e->errorcode);
        }
    }

    public function test_fails_when_not_logged_in() {
        $this->resetAfterTest(false);

        try {
            $this->authenticate_user("69");
            $this->fail('Exception expected because user is not logged in.');
        } catch (\moodle_exception $e) {
            $this->assertEquals('redirecterrordetected', $e->errorcode);
        }
    }
}
