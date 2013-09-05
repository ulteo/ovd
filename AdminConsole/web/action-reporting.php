<?php
/**
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2013
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
require_once(dirname(__FILE__).'/includes/core.inc.php');
require_once(dirname(__FILE__).'/includes/page_template.php');

if (! checkAuthorization('viewStatus'))
		redirect('index.php');

show_all();

function show_all() {
	$offset = 0;
	if (array_key_exists('offset', $_REQUEST) && $_REQUEST['offset'] > 0) {
		$offset+= max($_REQUEST['offset'], 0);
	}
	
	$actions = $_SESSION['service']->admin_actions_list($offset);
	if (is_null($actions)) {
		$actions = array();
	}
	
	$search_limit = $_SESSION['configuration']['max_items_per_page'];
	$count = 0;
	
	page_header();
	echo '<h1>'._('Administration actions log').'</h1>';
	echo '<div>';
	
	echo '<div style="padding: 10px;">';
	if ($offset > 0) {
		echo '<form style="display: inline;">';
		echo '<input type="hidden" name="offset" value="'.($offset-$search_limit).'" />';
		echo '<input type="submit" value="Sooner" />';
		echo '</form>';
	}
	
	if (count($actions)>=$search_limit) {
		echo ' <form style="display: inline;">';
		echo '<input type="hidden" name="offset" value="'.($offset+count($actions)).'" />';
		echo '<input type="submit" value="Older" />';
		echo '</form>';
	}
	echo '</div>';

	echo '<table class="main_sub" border="0" cellspacing="1" cellpadding="5">';
	foreach ($actions as $id => $action) {
		$content = 'content'.(($count++%2==0)?1:2);
		
		echo '<tr class="'.$content.'">';
		echo '<td>'.$action['when'].'</td>';
		echo '<td>'.$action['who'].'</td>';
		echo '<td>'.$action['where'].'</td>';
		echo '<td>'.$action['what'].'</td>';
		echo '<td>'.array2str($action['infos']).'</td>';
		echo '</tr>';
	}
	
	echo '</table>';
	
	echo '<div style="padding: 10px;">';
	if ($offset > 0) {
		echo '<form style="display: inline;">';
		echo '<input type="hidden" name="offset" value="'.($offset-$search_limit).'" />';
		echo '<input type="submit" value="Sooner" />';
		echo '</form>';
	}
	
	if (count($actions)>=$search_limit) {
		echo ' <form style="display: inline;">';
		echo '<input type="hidden" name="offset" value="'.($offset+count($actions)).'" />';
		echo '<input type="submit" value="Older" />';
		echo '</form>';
	}
	echo '</div>';
	
	echo '</div>';
	page_footer();
	die();
}

function array2str($array_) {
	if (! is_array($array_) || count($array_) == 0) {
		return '';
	}
	
	$r = array();
	foreach($array_ as $k => $v) {
		if (is_array($v)) {
			if (count($v) == 2 && array_key_exists('old', $v) && array_key_exists('new', $v)) {
				$v_old = $v['old'];
				$v_new = $v['new'];
				if (is_array($v_old)) {
					$v_old = var2html($v_old);
				}
				if (is_array($v_new)) {
					$v_new = var2html($v_new);
				}
				
				$v = $v_old.' => '.$v_new;
			}
			else {
				$v = var2html($v);
			}
		}
		
		$r[]= '<strong>'.$k.':</strong> <em>'.$v.'</em>';
	}
	
	return implode('; ', $r);
}

function var2html($var_) {
	if (is_null($var_)) {
		return 'null';
	}
	
	if (is_array($var_)) {
		if (isAssoc($var_)) {
			$var2 = array();
			foreach($var_ as $k => $v) {
				array_push($var2,  hash2html($k, $v));
			}
			
			$var_ = $var2;
		}
		
		return '[<span style="font-size: 80%;">'.implode(', ', $var_).'</span>]';
	}
	
	return $var_;
}

function hash2html($k, $v) {
    return '<strong>'.$k.':</strong> <em>'.var2html($v).'</em>';
}

function isAssoc($arr) {
    return array_keys($arr) !== range(0, count($arr) - 1);
}
