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

use Psr\Log\LoggerInterface;

/**
 * A logger that writes to a file.
 *
 * @package     mod_kialo
 * @copyright   2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class kialo_logger implements LoggerInterface {

    /**
     * Name of the logger.
     *
     * @var string
     */
    private $name;

    /**
     * Creates a new logger that writes to a file.
     *
     * @param string $name name of the logger
     */
    public function __construct(string $name) {
        $this->name = $name;
    }

    /**
     * Write any log message.
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function write(string $level, string $message, array $context): void {
        global $CFG;
        if (!$CFG->debug) {
            return;
        }

        $log = date('Y-m-d H:i:s') . ' [' . $this->name . '] [' . $level . '] ' . $message . ' ' . json_encode($context) . PHP_EOL;
        $path = __DIR__ . '/../kialo.log';
        if (!file_exists($path)) {
            touch($path);
        }
        file_put_contents($path, $log, FILE_APPEND);
    }

    /**
     * System is unusable.
     *
     * @param string $message
     * @param mixed[] $context
     *
     * @return void
     */
    public function emergency($message, array $context = []) {
        $this->write('EMERGENCY', $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param mixed[] $context
     *
     * @return void
     */
    public function alert($message, array $context = []) {
        $this->write('ALERT', $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param mixed[] $context
     *
     * @return void
     */
    public function critical($message, array $context = []) {
        $this->write('CRITICAL', $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param mixed[] $context
     *
     * @return void
     */
    public function error($message, array $context = []) {
        $this->write('ERROR', $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param mixed[] $context
     *
     * @return void
     */
    public function warning($message, array $context = []) {
        $this->write('WARNING', $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param mixed[] $context
     *
     * @return void
     */
    public function notice($message, array $context = []) {
        $this->write('NOTICE', $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param mixed[] $context
     *
     * @return void
     */
    public function info($message, array $context = []) {
        $this->write('INFO', $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param mixed[] $context
     *
     * @return void
     */
    public function debug($message, array $context = []) {
        $this->write('DEBUG', $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param mixed[] $context
     *
     * @return void
     *
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function log($level, $message, array $context = []) {
        $this->write($level, $message, $context);
    }
}
