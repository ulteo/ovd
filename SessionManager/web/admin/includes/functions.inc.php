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

function getAllAppsGroups(){
	Logger::debug('main','getAllAppsGroups');
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
	Logger::debug('main', 'init_db');
	$mysql_conf = $prefs_->get('general', 'mysql');
	if (!is_array($mysql_conf)) {
		Logger::error('main', 'init_db mysql conf not valid');
		return false;
	}
	$APPSGROUP_TABLE = $mysql_conf['prefix'].'gapplication';
	$LIAISON_TABLE = $mysql_conf['prefix'].'liaison';
	$USERSGROUP_APPLICATIONSGROUP_LIAISON_TABLE = $mysql_conf['prefix'].'ug_ag_link';
	$SOURCES_LIST_TABLE = $mysql_conf['prefix'].'sources_list';

	// we create the sql table
	$sql2 = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database'], $mysql_conf['prefix']);

	$APPSGROUP_structure = array(
		'id' => 'int(8) NOT NULL auto_increment',
		'name' => 'varchar(150) NOT NULL',
		'description' => 'varchar(150) NOT NULL',
		'published' => 'tinyint(1) NOT NULL');
	$ret = $sql2->buildTable($APPSGROUP_TABLE, $APPSGROUP_structure, array('id'));
	
	if ( $ret === false) {
		Logger::error('main', 'init_db table '.$APPSGROUP_TABLE.' fail to created');
		return false;
	}
	else
		Logger::debug('main', 'init_db table '.$APPSGROUP_TABLE.' created');

	$SOURCES_LIST_structure = array(
		'id' => 'int(8) NOT NULL auto_increment',
		'element' => 'varchar(200) NOT NULL',
		'group' => 'varchar(200) NOT NULL');
	$ret = $sql2->buildTable($SOURCES_LIST_TABLE, $SOURCES_LIST_structure, array('id'));
	
	if ( $ret === false) {
		Logger::error('main', 'init_db table '.$SOURCES_LIST_TABLE.' fail to created');
		return false;
	}
	else
		Logger::debug('main', 'init_db table '.$SOURCES_LIST_TABLE.' created');

	Logger::debug('main', 'init_db all tables created');

	$modules_enable = $prefs_->get('general', 'module_enable');
	foreach ($modules_enable as $module_name) {
		if (! is_null($prefs_->get($module_name,'enable'))) {
			$enable = $prefs_->get($module_name,'enable');
			if (is_string($enable)) {
				$mod_name = 'admin_'.$module_name.'_'.$enable;
				$ret_eval = call_user_func(array($mod_name, 'init'), $prefs_);
				if ($ret_eval !== true) {
					Logger::error('main', 'init_db init module \''.$mod_name.'\' failed');
					return false;
				}
			}
			elseif (is_array($enable)) {
				foreach ($enable as $sub_module) {
					$mod_name = 'admin_'.$module_name.'_'.$sub_module;
					$ret_eval = call_user_func(array($mod_name, 'init'), $prefs_);
					if ($ret_eval !== true) {
						Logger::error('main', 'init_db init module \''.$mod_name.'\' failed');
						return false;
					}
				}
			}
		}
	}
	Logger::debug('main', 'init_db modules inited');

	//TODO : do the same for plugins

	// Init of Abstract
	Abstract_Server::init($prefs_);
	Abstract_Session::init($prefs_);
	Abstract_Invite::init($prefs_);
	Abstract_Token::init($prefs_);
	Abstract_News::init($prefs_);
	Abstract_Liaison::init($prefs_);
	Abstract_Report::init($prefs_);
	Abstract_UserGroup_Rule::init($prefs_);
	Abstract_SharedFolder::init($prefs_);
	Abstract_DAV_User::init($prefs_);
	
	UserGroupDBDynamic::init($prefs_);
	UserGroupDBDynamic_cached::init($prefs_);
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
		echo 'error isValid : '.$ret."\n";
		return false;
	}
	$p->backup();
	return true;
}

function print_element($key_name,$container,$element_key,$element) {
	$element->setFormSeparator('___');  // global $sep
	$element->setPath(array('key_name' => $key_name, 'container' => $container, 'element_id' => $element->id));
	echo $element->toHTML();
}

function print_prefs5($prefs,$key_name, $container) {
	if (! isset($prefs->elements[$key_name][$container]))
		return;

	$elements2 = $prefs->elements[$key_name][$container];
	$color=0;
	echo '<table style="width: 100%" class="main_sub" border="0" cellspacing="1" cellpadding="3">'; // TODO
	echo '<tr class="title"><th colspan="2">'.$prefs->getPrettyName($container).'</th></tr>';
	foreach ( $elements2 as $element_key => $element) {
		// we print element
		echo '<tr class="content'.($color % 2 +1).'">';
		echo '<td style="width: 200px;">';
		echo '<span onmouseover="showInfoBulle(\''.str_replace("'", "&rsquo;", $element->description_detailed).'\'); return false;" onmouseout="hideInfoBulle(); return false;">'.$element->label.'</span>';
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
	if ($elements == array())
		return;
	echo '<table class="main_sub2" border="0" cellspacing="1" cellpadding="3" id="'.$key_name.'">';
	echo '<tr class="title"><th colspan="2">'.$prefs->getPrettyName($key_name).'</th></tr>';

	if (is_object($elements)) {
		echo '<tr class="content'.($color % 2 +1).'">';
		echo '<td style="width: 200px;">';
		echo '<span onmouseover="showInfoBulle(\''.str_replace("'", "&rsquo;", $elements->description_detailed).'\'); return false;" onmouseout="hideInfoBulle(); return false;">'.$elements->label.'</span>';
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
				echo '<td style="width: 200px;">';
				echo '<span onmouseover="showInfoBulle(\''.str_replace("'", "&rsquo;", $elements2->description_detailed).'\'); return false;" onmouseout="hideInfoBulle(); return false;">'.$elements2->label.'</span>';
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
	echo '</table>';
}

function print_prefs($prefs_, $print_form_=true) {
	echo '<script type="text/javascript"> configuration_switch_init();</script>';
	// printing of preferences
	$keys = $prefs_->getKeys();
	if ($print_form_) {
		echo '<form method="post" action="configuration.php">';
	}
	foreach ($keys as $key_name){
		echo '<div id="'.$key_name.'">';
		print_prefs4($prefs_,$key_name);
		echo '</div>';
	}
	if ($print_form_) {
		echo '<input type="submit" id="submit" name="submit"  value="'._('Save').'" />';
		echo '</form>';
	}
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
							$last_key = end(array_keys($old));
							foreach ($old as $k9 => $v9){
								if (is_array($v9)) {
									$v9_keys = array_keys($v9);
									if ($v9_keys == array('key','value') || $v9_keys == array('value','key')){
										if ( $v9['value'] == '') {
											if ( $last_key != $k9) {
												$new[$v9['key']] = $v9['value'];
											}
										}
										else {
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

	formToArray_cleanup($elements_form);
	formToArray_cleanup2($elements_form);
	return $elements_form;
}

function formToArray_cleanup2(&$buf, $key=NULL) {
	if (is_array($buf)) {
		$buf_keys = array_keys($buf);
		if ( count($buf) > 0) {
			$first_key =  $buf_keys[0];
			if ( is_null($key) == false) {
				if ($key === $first_key) {
					$buf3 = $buf[$first_key];
					$buf = $buf3;
				}
				if (is_array($buf)) {
					foreach ( $buf as $k=> $v) {
						formToArray_cleanup2($buf[$k], $k);
					}
				}
			}
			else {
				foreach ( $buf as $k=> $v) {
					formToArray_cleanup2($buf[$k], $k);
				}
			}
		}
	}
}

function formToArray_cleanup(&$buf) {
	if (is_array($buf)) {
		$buf_keys = array_keys($buf);
		if ( count($buf) > 0) {
			if (  $buf[$buf_keys[count($buf)-1]] == 'thisIsADirtyHack') {
				unset($buf[$buf_keys[count($buf)-1]]);
			}
			else {
				foreach ( $buf as $k=> $v) {
					formToArray_cleanup($buf[$k]);
				}
			}
		}
	}
}

function get_classes_startwith_2($start_name) {
	$files = glob('classes/'.$start_name.'*.class.php');

	$ret = array();
	foreach ($files as $file) {
	  $classname = basename($file);
	  $classname = substr($classname, 0, strlen($classname) - strlen('.class.php'));

	  $ret[] = $classname;
	}
	return $ret;
}

function getProfileMode($prefs) {
  $userDB_mode = $prefs->get('UserDB', 'enable');

  $classes = get_classes_startwith_2('Configuration_mode_');
  foreach($classes as $c) {
    $b = new $c();

    if ($b->careAbout($userDB_mode))
      return $c;
  }

  // Should never be called !!!
  return 'Configuration_mode_internal';
}

function isAuthorized($policy_) {
	if (! isset($_SESSION['admin_ovd_user']))
		return true;

	$policy = $_SESSION['admin_ovd_user']->getPolicy();
	return $policy[$policy_];
}

function checkAuthorization($policy_) {
	if (isAuthorized($policy_))
		return true;

	Logger::warning('main', 'User(id='.$_SESSION['admin_ovd_user']->getAttribute('uid').') is  not allowed to perform '.$policy_.'.');
	popup_error(_('You are not allowed to perform this action'));
	return false;
}
