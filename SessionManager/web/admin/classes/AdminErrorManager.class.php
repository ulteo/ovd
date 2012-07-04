<?php
/**
 * Copyright (C) 2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com>
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

require_once(dirname(dirname(__FILE__)).'/includes/page_template_static.php');

class AdminErrorManager extends ErrorManager{
	public function perform($error_=false, $file_=NULL, $line_=NULL, $display_=false) {
		$display_ = true; //always display the real error message instead of a generic one
		
		header('HTTP/1.1 500 Internal Server Error');
		header_static(_('Error'));
		
		echo '<h2 class="centered">'._('Error').'</h2>';
		echo '<p class="msg_error centered">';
		if ($display_ === true)
			echo $error_;
		else
			echo 'The service is not available, please try again later';
		
		echo '</p>';
		
		footer_static();
		die();
	}
	
	protected function report_error_message($msg_) {
		parent::report_error_message($msg_);
		popup_error($msg_);
	}
}
