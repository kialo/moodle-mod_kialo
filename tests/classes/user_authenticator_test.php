<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Tests the LTI user authenticator implementation.
 *
 * @package    mod_kialo
 * @category   test
 * @copyright  2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_kialo;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../vendor/autoload.php');

use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Security\User\Result\UserAuthenticationResultInterface;
use stdClass;

/**
 * Tests the LTI user authenticator implementation.
 * @covers \mod_kialo\user_authenticator
 */
class user_authenticator_test extends \advanced_testcase {
    /**
     * @var stdClass The course that the activity is in.
     */
    private $course;

    /**
     * @var stdClass The activity module.
     */
    private $module;

    /**
     * @var RegistrationInterface
     */
    private $registrationstub;

    protected function setUp(): void {
        parent::setUp();

        $this->resetAfterTest();

        $this->course = $this->getDataGenerator()->create_course();
        $this->registrationstub = $this->createStub(RegistrationInterface::class);
        $this->user_authenticator = new user_authenticator();

        // Creates a Kialo activity.
        $this->module = $this->getDataGenerator()->create_module('kialo', array('course' => $this->course->id));
    }

    /**
     * Calls the authenticate method with the required parameters (given userid and current courseid).
     * @param string $userid
     * @return UserAuthenticationResultInterface
     * @throws \coding_exception
     * @throws \moodle_exception
     * @throws \require_login_exception
     */
    protected function authenticate_user(string $userid): UserAuthenticationResultInterface {
        $loginhint = "{$this->course->id}/$userid";
        return $this->user_authenticator->authenticate($this->registrationstub, $loginhint);
    }

    /**
     * Tests the user authenticator's basic success case.
     * @return void
     * @throws \coding_exception
     * @throws \moodle_exception
     * @throws \require_login_exception
     * @covers \mod_kialo\user_authenticator::authenticate
     */
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
        $this->assertEquals($user->id, $identity->getIdentifier());
        $this->assertEquals("hoschi", $identity->getAdditionalProperties()->get('preferred_username'));
        $this->assertEquals("hoschi@mustermann.com", $identity->getEmail());
        $this->assertEquals("Horscht", $identity->getGivenName());
        $this->assertEquals("Mustermann", $identity->getFamilyName());
        $this->assertEquals("The Machine", $identity->getMiddleName());
        $this->assertMatchesRegularExpression(
            "|https://www.example.com/moodle/theme/image.php/_s/boost/core/\d+/u/f\d+|",
            $identity->getPicture()
        );
    }

    /**
     * Tests that the timezone is correctly returned.
     * @return void
     * @throws \coding_exception
     * @throws \moodle_exception
     * @throws \require_login_exception
     * @covers \mod_kialo\user_authenticator::authenticate
     */
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

    /**
     * Tests that the language is correctly returned.
     * @return void
     * @throws \coding_exception
     * @throws \moodle_exception
     * @throws \require_login_exception
     * @covers \mod_kialo\user_authenticator::authenticate
     */
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

    /**
     * Tests that a user that's not enrolled in the course cannot authenticate.
     * @covers \mod_kialo\user_authenticator::authenticate
     */
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

    /**
     * Tests that a guest user cannot authenticate.
     * @covers \mod_kialo\user_authenticator::authenticate
     */
    public function test_doesnt_work_for_guest_users() {
        global $USER;
        $this->setGuestUser();
        $this->getDataGenerator()->enrol_user($USER->id, $this->course->id, "student");

        $data = $this->authenticate_user("69");
        $this->assertFalse($data->isSuccess());
        $this->assertTrue($data->isAnonymous());
    }

    /**
     * Tests that non-logged-in users cannot authenticate.
     * @covers \mod_kialo\user_authenticator::authenticate
     */
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
