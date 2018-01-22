<?php
// This file is part of Extension Plugin
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
 * @package     local_extension
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2018 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_extension\attachment_processor;

defined('MOODLE_INTERNAL') || die();

/**
 * @package     local_extension
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2018 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_extension_attachment_processor_test extends advanced_testcase {
    /** @var int */
    private $draftid;

    /** @var int */
    private $requestid;

    /** @var file_storage */
    private $fs;

    /** @var context_user */
    private $usercontext;

    protected function setUp() {
        global $USER;

        self::setAdminUser();
        $this->resetAfterTest();

        $this->draftid = file_get_unused_draft_itemid();
        $this->requestid = 0;
        $this->fs = get_file_storage();
        $this->usercontext = context_user::instance($USER->id);
    }

    public function test_it_renames_files() {
        $this->create_existing_file('file_a.txt', 'aaa');
        $this->create_existing_file('file_b.txt', 'bbb');
        $this->create_existing_file('file_z.txt', 'z1');
        $this->create_existing_file('file_z (2).txt', 'z2.txt');
        $this->create_existing_file('file.dotted.txt', 'dot...dot...dot');
        $this->create_attachment_processor();

        $this->create_draft_file('file_c.txt', 'ccc'); // All different.
        $this->create_draft_file('file_a1.txt', 'aaa'); // Different name, same contents.
        $this->create_draft_file('file_a.txt', 'aaa again'); // Same name. different contents.
        $this->create_draft_file('file_b.txt', 'bbb'); // Same name and contents.
        $this->create_draft_file('file_z.txt', 'z3'); // Increment even more.
        $this->create_draft_file('file.dotted.txt', '...'); // Respect dots.

        $newfiles = $this->create_attachment_processor()->rename_new_files($this->get_draft_files());
        $actual = [];
        foreach ($newfiles as $newfile) {
            $actual[] = $newfile->get_filename();
        }

        sort($actual);
        $expected = [
            '.',
            'file.dotted (2).txt',
            'file_a (2).txt',
            'file_a1.txt',
            'file_b (2).txt',
            'file_c.txt',
            'file_z (3).txt',
        ];
        self::assertSame($expected, $actual);
    }

    private function get_existing_files() {
        $oldfiles = $this->fs->get_area_files($this->usercontext->id,
                                              'local_extension',
                                              'attachments',
                                              $this->requestid,
                                              'id');
        return $oldfiles;
    }

    private function get_draft_files() {
        $files = $this->fs->get_area_files(
            $this->usercontext->id,
            'user',
            'draft',
            $this->draftid,
            'id');
        return $files;
    }

    private function create_existing_file($filename, $contents) {
        $filerecord = [
            'contextid' => $this->usercontext->id,
            'component' => 'local_extension',
            'filearea'  => 'attachments',
            'itemid'    => $this->requestid,
            'filepath'  => '/',
            'filename'  => $filename,
        ];
        $file = $this->fs->create_file_from_string($filerecord, $contents);
        return $file;
    }

    private function create_attachment_processor() {
        $oldfiles = $this->get_existing_files();
        $processor = new attachment_processor($oldfiles);
        return $processor;
    }

    private function create_draft_file($filename, $contents) {
        $filerecord = [
            'contextid' => $this->usercontext->id,
            'component' => 'user',
            'filearea'  => 'draft',
            'itemid'    => $this->draftid,
            'filepath'  => '/',
            'filename'  => $filename,
        ];
        $file = $this->fs->create_file_from_string($filerecord, $contents);
        return $file;
    }
}
