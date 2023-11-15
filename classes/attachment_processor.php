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

namespace local_extension;

use stored_file;

defined('MOODLE_INTERNAL') || die();

/**
 * Class attachment_processor
 *
 * @package     local_extension
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2018 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attachment_processor {
    /** @var string[] */
    private $existingnames = [];

    public function get_existing_filenames() {
        return $this->existingnames;
    }

    /**
     * @param stored_file[] $oldfiles
     */
    public function __construct($oldfiles) {
        foreach ($oldfiles as $oldfile) {
            if ($oldfile->is_directory()) {
                continue;
            }
            $this->existingnames[$oldfile->get_filename()] = $oldfile->get_contenthash();
        }
    }

    /**
     *
     * @param stored_file[] $files
     * @return stored_file[]
     */
    public function rename_new_files($files) {
        $renamedfiles = [];

        foreach ($files as $file) {
            if ($this->should_rename($file)) {
                $newfilename = $this->increment_file_name($file->get_filename());
                $file->rename('/', $newfilename);
            }
            $renamedfiles[$file->get_pathnamehash()] = $file;
        }

        return $renamedfiles;
    }

    private function should_rename(stored_file $file) {
        if ($file->is_directory()) {
            return false;
        }

        if (!array_key_exists($file->get_filename(), $this->existingnames)) {
            return false;
        }

        // If the contenthash matches it's likely from duplicate browser requests coming through.
        // Ignore renaming the file to be safe.
        $existingcontenthash = $this->existingnames[$file->get_filename()];
        if ($file->get_contenthash() == $existingcontenthash) {
            return false;
        }

        return true;
    }

    private function increment_file_name($filename) {
        $dotindex = strrpos($filename, '.');
        if ($dotindex === false) {
            $prefix = $filename;
            $suffix = '';
        } else {
            $prefix = substr($filename, 0, $dotindex);
            $suffix = substr($filename, $dotindex);
        }

        $n = 2;
        do {
            $newfilename = "{$prefix} ({$n}){$suffix}";
            $n++;
        } while (array_key_exists($newfilename, $this->existingnames));

        $this->existingnames[] = $newfilename;
        return $newfilename;
    }

    /**
     * Returns the diff between the files passed and the old files.
     * @param stored_file[] $files
     * @return stored_file[]
     */
    public function diff_new_files($files) {
        return array_filter($files, function($file) {
            return !array_key_exists($file->get_filename(), $this->existingnames);
        });
    }
}
