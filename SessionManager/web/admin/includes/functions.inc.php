<?php
/**
 * Copyright (C) 2008,2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com>
 * Author Laurent CLOUET <laurent@ulteo.com>
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
 require_once(dirname(__FILE__).'/core.inc.php');

function display_loadbar($percents_) {
	$bar_width = 1.25;

	if ($percents_ < 50)
		$bar_color = '#05a305';
	elseif ($percents_ >= 50 && $percents_ < 75)
		$bar_color = '#c60';
	elseif ($percents_ >= 75)
		$bar_color = '#a30505';

	$normal_bar = '<div style="width: '.(round(100*$bar_width)).'px; height: 10px; float: left;">';
	$normal_bar.= '<div style="width: '.round($percents_*$bar_width).'px; height: 10px; background: '.$bar_color.'; float: left;"></div>';
	$normal_bar.= '<div style="width: '.round((100*$bar_width)-round($percents_*$bar_width)).'px; height: 10px; background: #999; float: left;"></div>';
	$normal_bar.= '</div>';
	$normal_bar.= '<div style="clear: both;"></div>';

	return $normal_bar;
}

function usersgroup_appsgroup_add($ug){
	if (is_object($ug)){
		$res = "";
		$all_appgroup = getAllAppsGroups();
		$my_apps_groups = $ug->appsGroups();
		if (is_array($all_appgroup) && count($all_appgroup) >0){
			$aff = false;
			foreach ($all_appgroup as $ag1){
				if ( is_array($my_apps_groups) && in_array($ag1,$my_apps_groups) == false  ) {
					$aff = true;
					break;
				}
			}
			if ($aff){
				$res .= '<select id="usersgroup_appsgroup_add_id_appsgroup_'.$ug->id.'"  name="appsgroups">';
				foreach ($all_appgroup as $ag1){
					if ( is_array($my_apps_groups) &&  in_array($ag1,$my_apps_groups) == false  ) {
						$res .= '<option value="'.$ag1->id.'" >'.$ag1->name.'</option>';
					}
				}
				$res .= '</select>';
				$res .= '<input type="button" id="usersgroup_appsgroup_add_button_'.$ug->id.'" name="usersgroup_appsgroup_add_button" onclick="usersgroup_add_to_appsgroup(\''.$ug->id.'\'); return false"  value="'._('Add').'" />';
			}
		}
		return $res;
	}
	else
		return false;
}

function usersgroup_appsgroup_del($ug){
	if (is_object($ug)){
		$my_apps_groups = $ug->appsGroups();
		$res = "";
		if ( is_array($my_apps_groups) && count($my_apps_groups)>0){
			$res .= '<select id="usersgroup_appsgroup_del_id_appsgroup_'.$ug->id.'"  name="appsgroups">';
			foreach ($my_apps_groups as $apps_group){
				$res .= '<option value="'.$apps_group->id.'" >'.$apps_group->name.'</option>';
			}
			$res .= '</select>';
			$res .= '<input type="button" id="usersgroup_appsgroup_del_button_'.$ug->id.'" name="usersgroup_appsgroup_del_button_'.$ug->id.'" onclick="usersgroup_remove_from_appsgroup(\''.$ug->id.'\'); return false"  value="'._('Del').'" />';
		}
		return $res;
	}
	else
		return false;
}

function user_usersgroup_add($u){
	if (is_object($u)){
		$res = "";
		$all_usergroup = get_all_usergroups();
		$my_groups = $u->usersGroups();
		if (is_array($all_usergroup) && count($all_usergroup) >0){
			$group_to_add = array();
			foreach ($all_usergroup as $ug1){
				if ( is_array($my_groups) && in_array($ug1,$my_groups) == false  ) {
					$group_to_add []= $ug1;
				}
			}
			if (count($group_to_add)>0){
				$res .= '<select id="user_add_to_usersgroup_uid_'.$u->getAttribute('uid').'"  name="usersgroup">';
				foreach ($group_to_add as $ug1){
					if ( is_array($my_groups) &&  in_array($ug1,$my_groups) == false  ) {
						$res .= '<option value="'.$ug1->id.'" >'.$ug1->name.'</option>';
					}
				}
				$res .= '</select>';
				$res .= '<input type="button" id="user_usersgroup_add_button_'.$u->getAttribute('uid').'" name="user_usersgroup_add_button" onclick="user_add_to_usersgroup(\''.$u->getAttribute('login').'\',\''.$u->getAttribute('uid').'\'); return false"  value="'._('Add').'" />';
			}
		}
		return $res;
	}
	else
		return false;
}

function user_usersgroup_del($u){
	if (is_object($u)){
		$my_groups = $u->usersGroups();
		$res = "";
		if ( is_array($my_groups) && count($my_groups)>0){
			$res .= '<select id="user_del_to_usersgroup_uid_'.$u->getAttribute('uid').'"  name="usersgroup">';
			foreach ($my_groups as $group){
				$res .= '<option value="'.$group->id.'" >'.$group->name.'</option>';
			}
			$res .= '</select>';
			$res .= '<input type="button" id="user_usersgroup_del_button_'.$u->getAttribute('uid').'" name="user_usersgroup_del_button_'.$u->getAttribute('uid').'" onclick="user_remove_from_usersgroup(\''.$u->getAttribute('login').'\',\''.$u->getAttribute('uid').'\'); return false"  value="'._('Del').'" />';
		}
		return $res;
	}
	else
		return false;
}

function application_appsgroup_add($a){
	if (is_object($a)){
		$res = "";
		$all_appgroup = getAllAppsGroups();
		$my_groups = $a->groups();
		if (is_array($all_appgroup) && count($all_appgroup) >0){
			$aff = array();
			foreach ($all_appgroup as $ag1){
				if (in_array($ag1,$my_groups) == false ) {
					$aff []= $ag1;
				}
			}
			if (count($aff)>0){
				$res .= '<select id="application_add_to_appsgroup_id_'.$a->getAttribute('id').'"  name="appsgroup">';
				foreach ($aff as $ag1){
					$res .= '<option value="'.$ag1->id.'" >'.$ag1->name.'</option>';
				}
				$res .= '</select>';
				$res .= '<input type="button" id="application_add_to_appsgroup_button_'.$a->getAttribute('id').'" name="application_add_to_appsgroup_button" onclick="application_add_to_appsgroup(\''.$a->getAttribute('id').'\',$(\'application_add_to_appsgroup_id_'.$a->getAttribute('id').'\').value); return false"  value="'._('Add').'" />';
			}
		}
		return $res;
	}
	else
		return false;
}

function application_appsgroup_del($a){
	if (is_object($a)){
		$my_groups = $a->groups();
		$res = "";
		if ( is_array($my_groups) && count($my_groups)>0){
			$res .= '<select id="application_del_to_appsgroup_id_'.$a->getAttribute('id').'"  name="usersgroup">';
			foreach ($my_groups as $group){
				$res .= '<option value="'.$group->id.'" >'.$group->name.'</option>';
			}
			$res .= '</select>';
			$res .= '<input type="button" id="application_del_to_appsgroup_del_button_'.$a->getAttribute('id').'" name="application_del_to_appsgroup_del_button'.$a->getAttribute('id').'" onclick="application_remove_from_appsgroup(\''.$a->getAttribute('id').'\',$(\'application_del_to_appsgroup_id_'.$a->getAttribute('id').'\').value); return false"  value="'._('Del').'" />';
		}
		return $res;
	}
}

function getAllAppsGroups(){
	Logger::debug('main','MAINMINIMAL::getAllAppsGroups');
	$sql2 = MySQL::getInstance();
	$res = $sql2->DoQuery('SELECT @1,@2,@3,@4 FROM @5', 'id', 'name', 'description', 'published', APPSGROUP_TABLE);
	if ($res !== false){
		$result = array();
		$rows = $sql2->FetchAllResults($res);
		foreach ($rows as $row){
			$g = new AppsGroup($row['id'],$row['name'],$row['description'],$row['published']);
			$result []= $g;
		}
		return $result;
	}
	else {
		// not the right argument
		return NULL;
	}
}

function get_all_sourceslist_mirrors(){
	$sql2 = MySQL::getInstance();
	$res = $sql2->DoQuery('SELECT @1 FROM @2','element', SOURCES_LIST_TABLE);
	if ($res !== false){
		$result = array();
		$rows = $sql2->FetchAllResults($res);
		foreach ($rows as $row){
			$result []= $row['element'];
		}
		return $result;
	}
	else {
		// not the right argument
		return NULL;
	}
}

function init_db($prefs_) {
	// prefs must be valid
	Logger::debug('admin','init_db');
	$mysql_conf = $prefs_->get('general', 'mysql');
	if (!is_array($mysql_conf)) {
		Logger::error('admin','init_db mysql conf not valid');
		return false;
	}
	$APPSGROUP_TABLE = $mysql_conf['prefix'].'gapplication';
	$LIAISON_TABLE = $mysql_conf['prefix'].'liaison';
	$USERSGROUP_APPLICATIONSGROUP_LIAISON_TABLE = $mysql_conf['prefix'].'ug_ag_link';
	$SOURCES_LIST_TABLE = $mysql_conf['prefix'].'sources_list';

	// we create the sql table
	$sql2 = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

	$ret = $sql2->DoQuery(
		'CREATE TABLE IF NOT EXISTS @1 (
		@2 int(8) NOT NULL auto_increment,
		@3 varchar(150) NOT NULL,
		@4 varchar(150) NOT NULL,
		@5 tinyint(1) NOT NULL,
		PRIMARY KEY  (@2)
		)',$APPSGROUP_TABLE,'id','name','description','published');
	if ( $ret === false) {
		Logger::error('admin','init_db table '.$APPSGROUP_TABLE.' fail to created');
		return false;
	}
	else
		Logger::debug('admin','init_db table '.$APPSGROUP_TABLE.' created');

	$ret = $sql2->DoQuery(
		'CREATE TABLE IF NOT EXISTS @1 (
		@4 int(8) NOT NULL auto_increment,
		@2 varchar(200) NOT NULL,
		@3 varchar(200) NOT NULL,
		PRIMARY KEY  (@4)
		)',$SOURCES_LIST_TABLE,'element','group','id');
	if ( $ret === false) {
		Logger::error('admin','init_db table '.$SOURCES_LIST_TABLE.' fail to created');
		return false;
	}
	else
		Logger::debug('admin','init_db table '.$SOURCES_LIST_TABLE.' created');

	Logger::debug('admin','init_db all tables created');

	$modules_enable = $prefs_->get('general', 'module_enable');
	foreach ($modules_enable as $module_name) {
		$mod_name = 'admin_'.$module_name.'_'.$prefs_->get($module_name,'enable');
		$ret_eval = eval('return '.$mod_name.'::init($prefs_);');
		if ($ret_eval !== true) {
			Logger::error('admin','init_db init module \''.$mod_name.'\' failed');
			return false;
		}
	}
	Logger::debug('admin','init_db modules inited');

	//TODO : do the same for plugins

	// Init of Abstract
	Abstract_Server::init($prefs_);
	Abstract_Session::init($prefs_);
	Abstract_Invite::init($prefs_);
	Abstract_Token::init($prefs_);
	Abstract_Liaison::init($prefs_);
	Abstract_Report::init($prefs_);

	return true;
}

function init($host_, $database_, $prefix_, $user_, $password_) {
	$p = new Preferences_admin();
	$mysql_conf = array();
	$mysql_conf['host'] = $host_;
	$mysql_conf['database'] = $database_;
	$mysql_conf['user'] = $user_;
	$mysql_conf['password'] = $password_;
	$mysql_conf['prefix'] = $prefix_;
	$p->set('general','mysql', $mysql_conf);
	$ret = $p->isValid();
	if ($ret !== true) {
		echo 'error isValid : '.$ret.'<br>';
	}
	$p->backup();
	return true;
}

function array_merge2( $a1, $a2) {
	foreach ($a2 as $k2 => $v2) {
		if ( is_array($v2) && ($v2 != array())) {
			$a1[$k2] = array_merge2($a1[$k2], $a2[$k2]);
		}
		else {
			$a1[$k2] = $a2[$k2];
		}
	}
	return $a1;
}

function print_element($key_name,$container,$element_key,$element) {
	global $sep;
	$label2 = $key_name.$sep.$container.$sep.$element->id;

	switch ($element->type) {
		case ConfigElement::$TEXT: // text (readonly)
			if (is_string($element->content))
				echo $element->content;
			break;
		case ConfigElement::$INPUT: // input text (rw)
			echo '<input type="text" id="'.$label2.'" name="'.$label2.'" value="'.$element->content.'" size="25" />';
			break;
		case ConfigElement::$TEXTAREA: // text area
			echo '<textarea rows="7" cols="60" id="'.$label2.'" name="'.$label2.'">'.$element->content.'</textarea>';
			break;
		case ConfigElement::$PASSWORD: // input password (rw)
			echo '<input type="password" id="'.$label2.'" name="'.$label2.'" value="'.$element->content.'" size="25" />';
			break;
		case ConfigElement::$SELECT: // list of text (r) (fixed length) (only one can be selected)
				if (is_array($element->content_available)) {
					echo '<select id="'.$label2.'"  name="'.$label2.'" onchange="configuration_switch(this,\''.$key_name.'\',\''.$container.'\',\''.$element->id.'\');">';

					foreach ($element->content_available as $mykey => $myval){
						if ( $mykey == $element->content)
							echo '<option value="'.$mykey.'" selected="selected" >'.$myval.'</option>';
						else
							echo '<option value="'.$mykey.'" >'.$myval.'</option>';
					}
					echo '</select>';
				}
				else {
// 					echo 'bug<br>';
				}
			break;
		case ConfigElement::$MULTISELECT: // list of text (r) (fixed length) (more than one can be selected)
			foreach ($element->content_available as $mykey => $myval){
				if ( in_array($mykey,$element->content))
					echo '<input class="input_checkbox" type="checkbox" name="'.$label2.'[]" checked="checked" value="'.$mykey.'" onchange="configuration_switch(this,\''.$key_name.'\',\''.$container.'\',\''.$element->id.'\');"/>';
				else
					echo '<input class="input_checkbox" type="checkbox" name="'.$label2.'[]" value="'.$mykey.'" onchange="configuration_switch(this,\''.$key_name.'\',\''.$container.'\',\''.$element->id.'\');"/>';
				// TODO targetid
				echo $myval;
				echo '<br />';
			}
			echo '<input class="input_checkbox" type="hidden" name="'.$label2.'[]" "/>'; // dirty hack for []
			break;

		case ConfigElement::$INPUT_LIST: // list of input text (fixed length)
			echo '<table border="0" cellspacing="1" cellpadding="3">';
			$i = 0;
			foreach ($element->content as $key1 => $value1){
				echo '<tr>';
					echo '<td>';
						echo $key1;
						echo '<input type="hidden" id="'.$label2.$sep.$i.$sep.'key" name="'.$label2.$sep.$i.$sep.'key" value="'.$key1.'" size="25" />';echo "\n";
					echo '</td>';echo "\n";
					echo '<td>';echo "\n";
					echo '<div id="'.$label2.$sep.$key1.'_divb">';
						echo '<input type="text" id="'.$label2.$sep.$i.$sep.'value" name="'.$label2.$sep.$i.$sep.'value" value="'.$value1.'" size="25" />';
					echo '</div>';echo "\n";
					echo '</td>';echo "\n";
				echo '</tr>';
				$i += 1;
				echo "\n";
			}
			echo '</table>';
			break;

		case ConfigElement::$SLIDERS: // sliders (length fixed)
			echo '<table border="0" cellspacing="1" cellpadding="3">';
			$i = 0;
			foreach ($element->content as $key1 => $value1){
				echo '<tr>';
					echo '<td>';
						echo $key1;
						echo '<input type="hidden" id="'.$label2.$sep.$i.$sep.'key" name="'.$label2.$sep.$i.$sep.'key" value="'.$key1.'" size="25" />';echo "\n";
					echo '</td>';echo "\n";
					echo '<td>';echo "\n";
					echo '<div id="'.$label2.$sep.$key1.'_divb">';

			// horizontal slider control
echo '<script type="text/javascript">';
echo '
Event.observe(window, \'load\', function() {
	new Control.Slider(\'handle'.$i.'\', \'track'.$i.'\', {
		range: $R(0,100),
		values: [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63,64,65,66,67,68,69,70,71,72,73,74,75,76,77,78,79,80,81,82,83,84,85,86,87,88,89,90,91,92,93,94,95,96,97,98,99,100],
		sliderValue: '.$value1.',
		onSlide: function(v) {
			$(\'slidetxt'.$i.'\').innerHTML = v;
			$(\''.$label2.$sep.$i.$sep.'value\').value = v;
		},
		onChange: function(v) {
			$(\'slidetxt'.$i.'\').innerHTML = v;
			$(\''.$label2.$sep.$i.$sep.'value\').value = v;
		}
	});
});
';
echo '</script>';

			echo '<div id="track'.$i.'" style="width: 200px; background-color: rgb(204, 204, 204); height: 10px;"><div class="selected" id="handle'.$i.'" style="width: 10px; height: 15px; background-color: #004985; cursor: move; left: 190px; position: relative;"></div></div>';

						echo '<input type="hidden" id="'.$label2.$sep.$i.$sep.'value" name="'.$label2.$sep.$i.$sep.'value" value="'.$value1.'" size="25" />';
					echo '</div>';echo "\n";
					echo '</td>';echo "\n";
					echo '<td>';echo "\n";
					echo '<div id="slidetxt'.$i.'" style="float: right;">'.$value1.'</div>';
					echo '</td>';echo "\n";
				echo '</tr>';
				$i += 1;
				echo "\n";
			}
			echo '</table>';
			break;
	}
	if ($element->type == ConfigElement::$DICTIONARY || $element->type == ConfigElement::$LIST) {
		echo '<div id="'.$label2.'">';
			echo '<table border="0" cellspacing="1" cellpadding="3">';
			$i = 0;
			foreach ($element->content as $key1 => $value1){
				echo '<tr>';
					echo '<td>';
					if ( $element->type == ConfigElement::$DICTIONARY ){
							echo '<div id="'.$label2.$sep.$i.'_diva">';
								echo '<input type="text" id="'.$label2.$sep.$i.$sep.'key" name="'.$label2.$sep.$i.$sep.'key" value="'.$key1.'" size="25" />';echo "\n";
							echo '</div>';
						echo '</td>';echo "\n";
						echo '<td>';echo "\n";
					}
					else {
						echo '<input type="hidden" id="'.$label2.$sep.$i.$sep.'key" name="'.$label2.$sep.$i.$sep.'key" value="'.$i.'" size="40" />';echo "\n";
					}
					echo '<div id="'.$label2.$sep.$key1.'_divb">';
						echo '<input type="text" id="'.$label2.$sep.$i.$sep.'value" name="'.$label2.$sep.$i.$sep.'value" value="'.$value1.'" size="25" />';
						echo '<a href="javascript:;" onclick="configuration4_mod(this); return false"><img src="../media/image/hide.png"/></a>';
					echo '</div>';echo "\n";
					echo '</td>';echo "\n";
				echo '</tr>';
				$i += 1;
				echo "\n";
			}
				echo '<tr>';
				echo '<td>';
					if ( $element->type == ConfigElement::$DICTIONARY ){
						echo '<div id="'.$label2.$sep.$i.'_divadda">';
							echo '<input type="text" id="'.$label2.$sep.$i.$sep.'key" name="'.$label2.$sep.$i.$sep.'key" value="" size="25" />';
						echo '</div>';
					echo '</td>';
					echo '<td>';
					}
					else {
						echo '<input type="hidden" id="'.$label2.$sep.$i.$sep.'key" name="'.$label2.$sep.$i.$sep.'key" value="'.$i.'"  />';
					}
						echo '<div id="'.$label2.$sep.$i.'_divaddb">';
							echo '<input type="text" id="'.$label2.$sep.$i.$sep.'value" name="'.$label2.$sep.$i.$sep.'value" value="" size="25" />';
						echo '<a href="javascript:;" onclick="configuration4_mod(this); return false"><img src="../media/image/show.png"/></a>';

						echo '</div>';
					echo '</td>';
				echo '</tr>';
			echo '</table>';
		echo '</div>';
	}
}

function print_prefs5($prefs,$key_name, $container) {
	if (! isset($prefs->elements[$key_name][$container]))
		return;

	$elements2 = $prefs->elements[$key_name][$container];
	$color=0;
	echo '<table style="width: 100%" class="main_sub" border="0" cellspacing="1" cellpadding="0">'; // TODO
	echo '<tr class="title"><th colspan="2">'.$prefs->getPrettyName($container).'</th></tr>';
	foreach ( $elements2 as $element_key => $element) {
		// we print element
		echo '<tr class="content'.($color % 2 +1).'">';
		echo '<td style="width: 200px;" title="'.$element->description.'">';
		echo $element->label;
		echo '</td>';
		echo '<td style="padding: 3px;">';
		echo "\n";
		print_element($key_name,$container,$element_key,$element);
		echo "\n";
		echo '</td>';
		echo '</tr>';
		$color++;
	}
	echo '</table>';
}

function print_prefs4($prefs,$key_name,$recursive=true) {
	global $sep;
// 	echo "print_prefs4 '".$key_name."'<br>";
	$color = 0;
	$color2 = 0;
	$elements = $prefs->elements[$key_name];
	echo '<table class="main_sub2" border="0" cellspacing="1" cellpadding="3" id="'.$key_name.'">';
	echo '<tr class="title"><th colspan="2">'.$prefs->getPrettyName($key_name).'</th></tr>';

	if (is_object($elements)) {
		echo '<tr class="content'.($color % 2 +1).'">';
		echo '<td style="width: 200px;" title="'.$elements->description.'">';
		echo $elements->label;
		echo '</td>';
		echo '<td>';
		echo "\n";
		print_element($key_name,'','',$elements);
		echo "\n";
		echo '</td>';
		echo '</tr>';
		$color++;
	}
	else {
		foreach ($elements as $container => $elements2){
			if (is_object($elements2)) {
				echo '<tr class="content'.($color % 2 +1).'">';
				echo '<td style="width: 200px;" title="'.$elements2->description.'">';
				echo $elements2->label;
				echo '</td>';
				echo '<td>';
				echo "\n";
				print_element($key_name,$container,'',$elements2);
				echo "\n";
				echo '</td>';
				echo '</tr>';
				$color++;
			}
			else {
				if ($recursive === true) {
					echo '<tr id="'.$key_name.$sep.$container.'">';
					echo '<td colspan="2">';
					print_prefs5($prefs, $key_name,$container);
					echo '</td>';
				}
			}
		}
	}
	echo '</tr>';
	echo '</table>';
}

function print_menu($dynamic_){
	if ($dynamic_)
		include_once('header.php');
	else {
		header_static(_('Configuration'));
	}
	echo '<div id="configuration_div">';
}

function print_prefs($prefs_) {
	echo '<script type="text/javascript"> configuration_switch_init();</script>';
	// printing of preferences
	$keys = $prefs_->getKeys();
	echo '<form method="post" action="configuration.php">';
	foreach ($keys as $key_name){
		echo '<div id="'.$key_name.'">';
		print_prefs4($prefs_,$key_name);
		echo '</div>';
	}
	echo '<input type="submit" id="submit" name="submit"  value="'._('Save').'" />';
	echo '</form>';
}

function formToArray($form_) {
	global $sep;
	if (! is_array($form_)) {
		return array();
	}
	$elements_form = array();
	foreach ($form_ as $key1 => $value1){
		$expl = split($sep, $key1);
		$expl = array_reverse ($expl);
		$element2 = &$elements_form;
		while (count($expl)>0){
			$e = array_pop($expl);
			if (count($expl) >0){
				if (!isset($element2[$e])){
					$element2[$e] = array();
				}
				$element2 = &$element2[$e];
			}
			else {
				if (is_string($value1))
					$value1 = stripslashes($value1);
				// last element
				$element2[$e] = $value1;
			}
		}
	}

	foreach ($elements_form as $key1 => $value1) {
		if (is_array($value1)){
			foreach ($value1 as $key2 => $value2) {
				if (is_array($value2)){
					foreach ($value2 as $key3 => $value3) {
						if (is_array($value3)) {
							$old = &$elements_form[$key1][$key2][$key3]; //$value3;
							$new = array();
							foreach ($old as $k9 => $v9){
								if (is_array($v9)) {
									$v9_keys = array_keys($v9);
									if ($v9_keys == array('key','value') || $v9_keys == array('value','key')){
										if ($v9['value'] != '') {
											$new[$v9['key']] = $v9['value'];
										}
									}
								}
								else {
									$new[$k9] = $v9;
								}
							}
							$old = $new;
						}
					}
				}
			}
		}
	}
	
	formToArray_cleanup(&$elements_form);
	return $elements_form;
}

function formToArray_cleanup($buf) {
	if (is_array($buf)) {
		$buf_keys = array_keys($buf);
		if ( count($buf) > 0) {
			if (  $buf[$buf_keys[count($buf)-1]] == '') {
				unset($buf[$buf_keys[count($buf)-1]]);
			}
			else {
				foreach ( $buf as $k=> $v) {
					formToArray_cleanup(&$buf[$k]);
				}
			}
		}
	}
}

