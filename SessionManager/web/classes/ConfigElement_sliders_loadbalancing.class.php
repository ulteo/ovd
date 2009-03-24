<?php
/**
 * Copyright (C) 2009 Ulteo SAS
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
require_once(dirname(__FILE__).'/../includes/core.inc.php');

class ConfigElement_sliders_loadbalancing extends ConfigElement {
	public function toHTML() {
		$html_id = $this->htmlID();
		$html = '';

		$html .= '<table border="0" cellspacing="1" cellpadding="3">';
		$html .= '<tr>';
		$html .= '<td>';
		$html .= '<h2 style="text-align: center;">'._('Factor impact').'</h2>';
		$html .= '<table border="0" cellspacing="1" cellpadding="3">';
		$i = 0;
		$html .= '<tr>';
		$html .= '<td></td>';
		$html .= '<td>';
		$html .= '<div style="width: 50px; float: left; text-align: left;"><span class="msg_ok">'._('less').'</span></div> <div style="width: 50px; float: right; text-align: right;"><span class="msg_error">'._('more').'</span></div>';
		$html .= '</td>';
		$html .= '</td><td>';
		$html .= '</tr>';
		$content_load_balancing = array();
		foreach ($this->content as $key1 => $value1) {
			$label3 = $html_id.$this->formSeparator.$i.$this->formSeparator;

			$criterion_class_name = 'DecisionCriterion_'.$key1;
			$c = new $criterion_class_name(NULL); // ugly
			$content_load_balancing[] = $c->default_value();

			$html .= '<tr>';
			$html .= '<td>';
				$html .= $key1;
				$html .= '<input type="hidden" id="'.$label3.'key" name="'.$label3.'key" value="'.$key1.'" size="25" />';
			$html .= '</td>';
			$html .= '<td>';
				$html .= '<div id="'.$html_id.$this->formSeparator.$key1.'_divb">';

				// horizontal slider control
				$html .= '<script type="text/javascript">';
				$html .= '
				var slider'.$i.';
				Event.observe(window, \'load\', function() {
					slider'.$i.' = new Control.Slider(\'handle'.$i.'\', \'track'.$i.'\', {
						range: $R(0,100),
						values: [';

						for($buf5=0;$buf5<100;$buf5++) {
							$html .= $buf5.',';
						}
						$html .= $buf5;

						$html .= '],
						sliderValue: '.$value1.',
						onSlide: function(v) {
							$(\'slidetxt'.$i.'\').innerHTML = v;
							$(\''.$label3.'value\').value = v;
						},
						onChange: function(v) {
							$(\'slidetxt'.$i.'\').innerHTML = v;
							$(\''.$label3.'value\').value = v;
						}
					});
				});
				';
				$html .= '</script>';

				$html .= '<div id="track'.$i.'" style="width: 200px; background-color: rgb(204, 204, 204); height: 10px;"><div class="selected" id="handle'.$i.'" style="width: 10px; height: 15px; background-color: #004985; cursor: move; left: 190px; position: relative;"></div></div>';

				$html .= '<input type="hidden" id="'.$label3.'value" name="'.$label3.'value" value="'.$value1.'" size="25" />';
				$html .= '</div>';
			$html .= '</td>';
			$html .= '<td>';
				$html .= '<div id="slidetxt'.$i.'" style="float: right;">'.$value1.'</div>';
			$html .= '</td>';
			$html .= '</tr>';
			$i += 1;
		}
		$html .= '</table>';

$html .= '<script type="text/javascript">
function resetLoadBalancing() {';
foreach ($content_load_balancing as $k => $v) {
	$html .= 'slider'.$k.'.setValue('.$v.');';
}
$html .= '}
</script>';

		$html .= '<br /><input type="button" id="reset_loadbalancing" value="'._('Back to default').'" onclick="resetLoadBalancing(); return false;" />';
		$html .= '</td>';
		$html .= '<td style="width: 100%; vertical-align: top; border-left: 1px solid #999;">';
		$html .= '<table style="width: 50%; margin-left: auto; margin-right: auto;" border="0" cellspacing="1" cellpadding="3">';
		$html .= '<tr>';
		$html .= '<td>';
		$html .= '<h2 style="text-align: center;">'._('Examples').'</h2>';
		$html .= '</td>';
		$html .= '</tr>';
		$html .= '<tr>';
		$html .= '<td style="text-align: center; vertical-align: middle;">';
		$html .= '<img src="media/image/loadbalancing/full-cpu.png" alt="" title="" />';
		$html .= '</td>';
		$html .= '</tr>';
		$html .= '<tr>';
		$html .= '<td style="text-align: center; vertical-align: middle;">';
		$html .= 'FULL CPU (only CPU will be used to determine the server...)';
		$html .= '</td>';
		$html .= '</tr>';
		$html .= '<tr>';
		$html .= '<td>&nbsp;</td>';
		$html .= '</tr>';
		$html .= '<tr>';
		$html .= '<td style="text-align: center; vertical-align: middle;">';
		$html .= '<img src="media/image/loadbalancing/both-cpu-ram-with-random.png" alt="" title="" />';
		$html .= '</td>';
		$html .= '</tr>';
		$html .= '<tr>';
		$html .= '<td style="text-align: center; vertical-align: middle;">';
		$html .= 'Both CPU and RAM are used, with a little random...';
		$html .= '</td>';
		$html .= '</tr>';
		$html .= '</table>';
		$html .= '</td>';
		$html .= '</tr>';
		$html .= '</table>';
		return $html;
	}
}
