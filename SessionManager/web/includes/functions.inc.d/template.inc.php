<?php
/**
 * Copyright (C) 2008-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2010
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2008-2010
 * Author Julien LANGLOIS <julien@ulteo.com> 2011
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

function die_error($error_=false, $file_=NULL, $line_=NULL, $display_=false) {
	$display_ = true; //always display the real error message instead of a generic one

	$file_ = substr(str_replace(SESSIONMANAGER_ROOT, '', $file_), 1);

	Logger::debug('main', 'die_error() called with message \''.$error_.'\' in '.$file_.':'.$line_);
	Logger::critical('main', $error_);

	header('Content-Type: text/xml; charset=utf-8');

	$dom = new DomDocument('1.0', 'utf-8');
	$node = $dom->createElement('error');
	$node->setAttribute('id', 0);
	if (in_admin() || $display_ === true)
		$node->setAttribute('message', $error_);
	else
		$node->setAttribute('message', 'The service is not available, please try again later');
	$dom->appendChild($node);

	echo $dom->saveXML();

	die();
}

function popup_error($msg_) {
	$msg_ = secure_html($msg_);

	if (! isset($_SESSION['errormsg']))
		$_SESSION['errormsg'] = array();

	if (is_array($msg_))
		foreach ($msg_ as $errormsg)
			$_SESSION['errormsg'][] = $errormsg;
	else
		$_SESSION['errormsg'][] = $msg_;

	return true;
}

function popup_info($msg_) {
	$msg_ = secure_html($msg_);

	if (! isset($_SESSION['infomsg']))
		$_SESSION['infomsg'] = array();

	if (is_array($msg_))
		foreach ($msg_ as $infomsg)
			$_SESSION['infomsg'][] = $infomsg;
	else
		$_SESSION['infomsg'][] = $msg_;

	return true;
}

