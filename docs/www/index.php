<?php
/**
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2010
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

$blacklist = array('AccountsIntegration', 'AD_Primary_Authentication', 'Installation_ApS_Lenny', 'Installation_Sources', 'SystemUpgrade', 'Vpn', 'AdminConsole', 'Support_Debian_Lenny');

$files = glob("*.html");

echo '<img src="http://doc.ulteo.com/resources/logo.png" width="120" height="67" alt="" title="" /><br />';
echo '<br />';

echo '<table border="0" cellspacing="1" cellpadding="3">';

echo '<tr><td style="text-align: center;" colspan="4"><h2 style="color: #333; margin: 0; padding: 0;">Documentations</h2></td></tr>';

foreach ($files as $file) {
 $basename = basename($file, '.html');
 if (in_array($basename, $blacklist))
   continue;

 $infos = pathinfo($file);
 echo "<tr>";
   echo "<td>";
     echo '<span style="font-size: 0.9em; color: #666; font-weight: bold;">'.str_replace('_', ' ', $infos['filename']).'</span>';
   echo "</td>";

   echo '<td style="width: 15px;"></td>';
   
   echo '<td style="text-align: center; vertical-align: middle;">';
     echo '<a style="text-decoration: none;" href="'.$infos['filename'].'.html"><img style="border: none;" src="http://doc.ulteo.com/resources/html.png" width="22" height="22" alt="HTML" title="HTML" align="middle" />&nbsp;<span style="font-size: 0.75em; font-weight: bold;">HTML</span></a>';
   echo "</td>";
   
   echo '<td style="text-align: center; vertical-align: middle;">';
     echo '<a style="text-decoration: none;" href="'.$infos['filename'].'.pdf"><img style="border: none;" src="http://doc.ulteo.com/resources/pdf.png" width="22" height="22" alt="PDF" title="PDF" align="middle" />&nbsp;<span style="font-size: 0.75em; font-weight: bold;">PDF</span></a>';
   echo "</td>";
 
 echo "</tr>";
}

