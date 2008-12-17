<?php
/**
 * Copyright (C) 2008 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com>
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
require_once(dirname(__FILE__).'/includes/core-minimal.inc.php');

function print_element($key_name,$contener,$element_key,$element) {
	global $sep;
// 	echo "'".$key_name."','".$contener."','".$element_key."'<br>";
// 	if ($element->contener != '' && !is_null($element->contener))
		$label2 = $key_name.$sep.$contener.$sep.$element->id;
// 	else
// 		$label2 = $key_name.$sep.$element->label;

	switch ($element->type) {
		case 0: //	0 : text (readonly)
			if (is_string($element->content))
				echo $element->content;
			break;
		case 1: //	1 : input text (rw)
			echo '<input type="text" id="'.$label2.'" name="'.$label2.'" value="'.$element->content.'" size="25" maxlength="100" />';
			break;
		case 7: //	7 : input password (rw)
			echo '<input type="password" id="'.$label2.'" name="'.$label2.'" value="'.$element->content.'" size="25" maxlength="100" />';
			break;
		case 2: //	2 : list of text (r) (fixed length) (only one can be selected)
				if (is_array($element->content_avalaible)) {
					echo '<select id="'.$label2.'"  name="'.$label2.'" onchange="configuration_switch(this,\''.$key_name.'\',\''.$contener.'\',\''.$element->id.'\');">';

					foreach ($element->content_avalaible as $mykey => $myval){
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
		case 3: //  3 : list of text (r) (fixed length) (more than one can be selected)
			foreach ($element->content_avalaible as $mykey => $myval){
				if ( in_array($mykey,$element->content))
					echo '<input class="input_checkbox" type="checkbox" name="'.$label2.'[]" checked="checked" value="'.$mykey.'" onchange="configuration_switch(this,\''.$key_name.'\',\''.$contener.'\',\''.$element->id.'\');"/>';
				else
					echo '<input class="input_checkbox" type="checkbox" name="'.$label2.'[]" value="'.$mykey.'" onchange="configuration_switch(this,\''.$key_name.'\',\''.$contener.'\',\''.$element->id.'\');"/>';
				// TODO targetid
				echo $myval;
				echo '<br />';
			}
			break;
	}
	if ($element->type == 4 || $element->type == 5) {
		echo '<div id="'.$label2.'">';
			echo '<table border="0" cellspacing="1" cellpadding="3">';
			$i = 0;
			foreach ($element->content as $key1 => $value1){
				echo '<tr>';
					echo '<td>';
					if ( $element->type == 4 ){
							echo '<div id="'.$label2.$sep.$i.'_diva">';
								echo '<input type="text" id="'.$label2.$sep.$i.$sep.'key" name="'.$label2.$sep.$i.$sep.'key" value="'.$key1.'" size="25" maxlength="100" />';echo "\n";
							echo '</div>';
						echo '</td>';echo "\n";
						echo '<td>';echo "\n";
					}
					else {
						echo '<input type="hidden" id="'.$label2.$sep.$i.$sep.'key" name="'.$label2.$sep.$i.$sep.'key" value="'.$i.'" size="40" maxlength="100" />';echo "\n";
					}
					echo '<div id="'.$label2.$sep.$key1.'_divb">';
						echo '<input type="text" id="'.$label2.$sep.$i.$sep.'value" name="'.$label2.$sep.$i.$sep.'value" value="'.$value1.'" size="25" maxlength="100"/>';
						echo '<a href="javascript:;" onclick="configuration4_mod(this); return false"><img src="../media/image/hide.png"/></a>';
					echo '</div>';echo "\n";
					echo '</td>';echo "\n";
				echo '</tr>';
				$i += 1;
				echo "\n";
			}
				echo '<tr>';
				echo '<td>';
					if ( $element->type == 4 ){
						echo '<div id="'.$label2.$sep.$i.'_divadda">';
							echo '<input type="text" id="'.$label2.$sep.$i.$sep.'key" name="'.$label2.$sep.$i.$sep.'key" value="" size="25" maxlength="100" />';
						echo '</div>';
					echo '</td>';
					echo '<td>';
					}
					else {
						echo '<input type="hidden" id="'.$label2.$sep.$i.$sep.'key" name="'.$label2.$sep.$i.$sep.'key" value="'.$i.'"  />';
					}
						echo '<div id="'.$label2.$sep.$i.'_divaddb">';
							echo '<input type="text" id="'.$label2.$sep.$i.$sep.'value" name="'.$label2.$sep.$i.$sep.'value" value="" size="25" maxlength="100" />';
						echo '<a href="javascript:;" onclick="configuration4_mod(this); return false"><img src="../media/image/show.png"/></a>';

						echo '</div>';
					echo '</td>';
				echo '</tr>';
			echo '</table>';
		echo '</div>';
	}
}

function print_prefs4($prefs,$key_name) {
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
		foreach ($elements as $contener => $elements2){
			if (is_object($elements2)) {
				echo '<tr class="content'.($color % 2 +1).'">';
				echo '<td style="width: 200px;" title="'.$elements2->description.'">';
				echo $elements2->label;
				echo '</td>';
				echo '<td>';
				echo "\n";
				print_element($key_name,$contener,'',$elements2);
				echo "\n";
				echo '</td>';
				echo '</tr>';
				$color++;
			}
			else {
				echo '<tr id="'.$key_name.$sep.$contener.'">';
				echo '<td colspan="2">';
				echo '<table style="width: 100%" class="main_sub" border="0" cellspacing="1" cellpadding="0">'; // TODO
				echo '<tr class="title"><th colspan="2">'.$prefs->getPrettyName($contener).'</th></tr>';
				foreach ( $elements2 as $element_key => $element) {
					// we print element
					echo '<tr class="content'.($color % 2 +1).'">';
					echo '<td style="width: 200px;" title="'.$element->description.'">';
					echo $element->label;
					echo '</td>';
					echo '<td style="padding: 3px;">';
					echo "\n";
					print_element($key_name,$contener,$element_key,$element);
					echo "\n";
					echo '</td>';
					echo '</tr>';
					$color++;
				}
				echo '</td>';
				echo '</table>';
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

// core of the page
$sep = '___';

if (isset($_POST['submit'])) {
	// saving preferences
	unset($_POST['submit']);

	$elements_form = array();
	foreach ($_POST as $key1 => $value1){
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
											if ($v9['key'] != count($v9))
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
	$prefs = new Preferences_admin($elements_form);
	$ret = $prefs->isValid();
	if ( $ret === true) {
		$ret = $prefs->backup();
		if ($ret > 0){
			// configuration saved
			redirect('index.php');
		}
		else {
			header_static(_('Configuration'));
			echo 'problem : configuration not saved<br>';  // TODO (class msg...) + gettext
			footer_static();
		}
	}
	else {
		// conf not valid
		header_static(_('Configuration'));
		echo '<p class="msg_error centered">'.$ret.'</p>';
		print_prefs($prefs);
		footer_static();
	}
}
else {
	if (isset($_GET['action']) && $_GET['action'] == 'init') {
		try {
			$prefs = new Preferences_admin();
		}
		catch (Exception $e) {
		}
		$prefs->initialize();
		header_static(_('Configuration'));
		print_prefs($prefs);
		include_once('footer.php');
	}
	else {
		try {
			$prefs = new Preferences_admin();
		}
		catch (Exception $e) {
		}
		if (is_object($prefs)) {
			require_once(dirname(__FILE__).'/header.php');
			print_prefs($prefs);
			include_once('footer.php');
		}
		else {
			die_error(_('Preferences not loaded'));
		}
	}
}

