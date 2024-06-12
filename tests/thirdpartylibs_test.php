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

namespace mod_kialo;

use PHPUnit\Framework\TestCase;

/**
 * Tests that thirdpartylibs.xml properly defines the third party libraries defined in composer.json.
 *
 * @package   mod_kialo
 * @copyright 2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Kialo GmbH (support@kialo-edu.com)
 * @coversNothing
 */
final class thirdpartylibs_test extends TestCase {
    /**
     * Parses the composer.lock file and returns an array of composer packages with fields corresponding to those in
     * thirdpartylibs.xml.
     * @return array|array[]
     */
    private function parse_composer_packages() {
        $composerjson = json_decode(file_get_contents(__DIR__ . '/../composer.lock'), true);
        $this->assertNotNull($composerjson['packages']);
        $composerpackages = array_map(function ($package) {
            $license = $package['license'][0] ?? '';
            $licenseversion = '';
            if ($license) {
                // For example GPL-2.0-only, GPL-3.0-or-later.
                $parts = explode('-', $license);
                $license = $parts[0];
                $licenseversion = $parts[1] ?? '';

                if (count($parts) > 2 && $parts[2] === 'or') {
                    $licenseversion .= '+'; // E.g. GPL-3.0-or-later.
                }
            }

            return [
                    'location' => 'vendor/' . $package['name'],
                    'name' => $package['name'],
                    'version' => $package['version'],
                    'license' => $license,
                    'licenseversion' => $licenseversion,
            ];
        }, $composerjson['packages']);

        usort($composerpackages, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return $composerpackages;
    }

    /**
     * Generates the thirdpartylibs.xml content based on the composer.lock file.
     * @param array $composerpackages array of composer packages with fields corresponding to those in thirdpartylibs.xml
     * @return string thirdpartylibs.xml content
     */
    private function generate_thirdpartylibsxml(array $composerpackages) {
        $xml = "<?xml version=\"1.0\"?>\r\n<!-- see composer.json for the source of truth -->\r\n<libraries>\r\n";

        $staticdeps = [
                [
                        "name" => "autoload.php",
                        "license" => "MIT",
                ],
                [
                        "name" => "composer",
                        "license" => "MIT",
                ],
        ];

        foreach ($staticdeps as $dep) {
            $composerpackages[] = [
                    "location" => "vendor/" . $dep["name"],
                    "name" => $dep["name"],
                    "version" => "",
                    "license" => $dep["license"],
                    "licenseversion" => $dep["licenseversion"] ?? "",
            ];
        }

        foreach ($composerpackages as $package) {
            $xml .= "\t<library>\r\n";
            $xml .= "\t\t<location>vendor/" . $package["name"] . "</location>\r\n";
            $xml .= "\t\t<name>" . $package["name"] . "</name>\r\n";
            $xml .= "\t\t<version>" . $package["version"] . "</version>\r\n";
            $xml .= "\t\t<license>" . $package["license"] . "</license>\r\n";
            $xml .= "\t\t<licenseversion>" . $package["licenseversion"] . "</licenseversion>\r\n";
            $xml .= "\t</library>\r\n";
        }

        $xml .= '</libraries>';

        return $xml;
    }

    /**
     * Ensures that the thirdpartylibs.xml for Moodle matches the composer.lock dependencies. Thirdpartylibs.xml is used
     * for license information and by Moodle's linting tools like "pluginchecker".
     *
     * When executed with the "UPDATE_THIRDPARTYLIBSXML" environment variable set, this test will update the thirdpartylibs.xml
     * based on the current composer.lock.
     *
     * @return void
     */
    public function test_thirdpartylibs_match_composer(): void {
        $thirdpartylibsxml = file_get_contents(__DIR__ . '/../thirdpartylibs.xml');
        $composerpackages = $this->parse_composer_packages();

        $generatedxml = $this->generate_thirdpartylibsxml($composerpackages);

        if (!empty(getenv('UPDATE_THIRDPARTYLIBSXML'))) {
            file_put_contents(__DIR__ . '/../thirdpartylibs.xml', $generatedxml);
            $thirdpartylibsxml = $generatedxml;
            print("\n\n[ Updated thirdpartylibs.xml ]\n");
        }

        $this->assertEquals($generatedxml, $thirdpartylibsxml);
    }
}
