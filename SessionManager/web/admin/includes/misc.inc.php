<?php
/**
 * Copyright (C) 2010-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2010
 * Author Julien LANGLOIS <julien@ulteo.com> 2011, 2012
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
 
function redirect($url_=NULL) {
	if (is_null($url_)) {
		if (! isset($_SERVER['HTTP_REFERER'])) {
			global $base_url;
			$url_ = $base_url;
		} else
			$url_ = $_SERVER['HTTP_REFERER'];
	}
	
	header('Location: '.$url_);
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

function get_pagechanger($link, $number, $total) {
	if (! isset($_GET['start']) || (! is_numeric($_GET['start']) || $_GET['start'] >= $total))
		$start = 0;
	else
		$start = $_GET['start'];
	
	$totalpage = ceil($total/$number);
	
	$pagechanger = '';
	
	$pagechanger .= '<div style="margin-top: 5px; margin-bottom: 5px;">';
	if ($totalpage > 1) {
		$next_start = ($start+$number);
		$previous_start = ($start-$number);
		$thispage = (($start/$number)+1);
		
		if ($totalpage > 1) {
			if ($thispage > 6)
				$pagechanger .= '<span style="background: #ecedee; border: 1px solid #b4bac0; padding: 1px;"><a href="'.$link.'start=0" title="Page 1">1</a></span>'."\r\n";
			if ($thispage > 7)
				$pagechanger .= ' ... ';
			
			if ($total > $number) {
				$forpage = ($total/$number);
				
				for ($i = 0; $i < $forpage; $i++) {
					$p = ($i*$number);
					$n = ($i+1);
					
					if ($n >= ($thispage-5) && $n <= ($thispage+5)) {
						if ($n != $thispage)
							$pagechanger .= '<span style="background: #ecedee; border: 1px solid #b4bac0; padding: 1px;"><a href="'.$link.'start='.$p.'" title="Page '.$n.'">'.$n.'</a></span>'."\r\n";
						else
							$pagechanger .= '<span style="background: #004985; border: 1px solid #004985; color: #fff; padding: 1px;"><strong>'.$n.'</strong></span>'."\r\n";
					}
				}
			}
			
			if ($thispage < ($totalpage-5)) {
				$last_start = (($totalpage-1)*$number);
				
				if ($thispage < ($totalpage-6))
					$pagechanger .= ' ... ';
				
				$pagechanger .= '<span style="background: #ecedee; border: 1px solid #b4bac0; padding: 1px;"><a href="'.$link.'start='.$last_start.'" title="Page '.$totalpage.'">'.$totalpage.'</a></span>'."\r\n";
			}
		}
	}
	
	$pagechanger .= '</div>';
	
	return $pagechanger;
}
