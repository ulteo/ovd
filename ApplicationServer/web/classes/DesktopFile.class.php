<?php
/**
 * Copyright (C) 2008 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 **/
require_once(dirname(__FILE__).'/../includes/core.inc.php');

class DesktopFile {
    protected $filename = null;
    protected $lang = null;
    protected $lang_short = null;

    protected $name = 'unknown';
    protected $mimestypes = array();

    public function __construct($filename_, $lang_) {
        $this->filename = $filename_;
        $this->lang = $lang_;
        $buffer = explode("_",$lang_, 2);
        $this->lang_short = $buffer[0];
    }

    public function parse() {
        $content = @file_get_contents($this->filename);
        if (! $content)
            return false;
    
        $lines = explode("\n", $content);
        foreach($lines as $line) {
            $line = trim($line);

            if ($line == '')
                continue;

            if ($line == '[Desktop Entry]')
                continue;

            list($key, $value) = explode('=', $line, 2);

            if ($key == 'MimeType') {
                $this->mimestypes = explode(";", $value);
                continue;
            }

            if (str_startswith($key, 'Name')) {
                if ($key == 'Name' && $this->name == 'unknown')
                    $this->name = $value;
                elseif($key == 'Name['.$this->lang_short.']')
                    $this->name = $value;
                elseif($key == 'Name['.$this->lang.']')
                    $this->name = $value;
            }
        }

        return true;
    }

    public function getName() { return $this->name; }
    public function getMimeType() { return $this->mimestypes; }
}
