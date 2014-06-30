<?php
/**
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2010
 * Author Mathieu SCHIRES <laurent@ulteo.com> 2014
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

$blacklist = array('AccountsIntegration', 'AD_Primary_Authentication', 'Installation_ApS_Lenny', 'Installation_Sources', 'SystemUpgrade',
					);

$files = glob("*.html");

?>

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
	<title> Ulteo OVD Documentation - Ulteo Open Virtual Desktop - Enterprise Open Source Virtual Desktop VDI and Application Delivery solutions (SBC)</title>
	
	<meta http-equiv="content-type" content="text/html;charset=utf-8" />
	<meta http-equiv="Content-Style-Type" content="text/css" />

<!-- add code here that should appear in the document head -->
		<link rel="StyleSheet" href="//www.ulteo.com/home/default-static/global/global/styles.css" type="text/css"/>
		<link rel="StyleSheet" href="//www.ulteo.com/home/_override-static/global/global/styles.css" type="text/css"/>
		<style>
			body{
				text-align:justify;
				overflow:auto;
			}
			#content {
				width:500px;
				margin:0px auto;
				text-align:justify;
				padding:15px;
			}

		</style>
		<script type="text/javascript" src="jquery-1.11.0.min.js" ></script>
		<script type="text/javascript" src="//www.ulteo.com/main/DOMparse/menu_js.js" ></script>
		<body>
		<header class="header" style="height: 94px;">
			<div id="uHeader"></div>
	</header>
	<div class="uMenu"></div>
	<div id="content">
<?php

echo '<table width="100%" border="0" cellspacing="1" cellpadding="3">';

echo '<tr><td style="text-align: center;" colspan="4"><h1 style="color: #333; margin: 0; padding: 0;">Documentation</h1><br/></td></tr>';


foreach ($files as $file) {
 $basename = basename($file, '.html');
 if (in_array($basename, $blacklist))
   continue;
 	
 $infos = pathinfo($file);
	
	$name = null;
	
	switch ($infos['filename']){
				
		case "Installation_ApS_Windows":
			$name="Installing a Windows Application Server";
		break;
		case "Installation_Gateway_Lucid":
			$name="Installing OVD Gateway on Ubuntu Lucid";
		break;
		case "Installation_Gateway_Precise":
			$name="Installing OVD Gateway on Ubuntu Precise";
		break;
		case "Installation_Gateway_Wheezy":
			$name="Installing OVD Gateway on Debian Wheezy";
		break;
		case "Installation_Gateway_Squeeze":
			$name="Installing OVD Gateway on Debian Squeeze";
		break;
		case "Installation_Gateway_RHEL6.0":
			$name="Installing OVD Gateway on RHEL 6.0";
		break;
		case "Installation_Gateway_Centos6.0":
			$name="Installing OVD Gateway on CentOS 6.0";
		break;
		case "Installation_Gateway_SLES_11.SP1":
			$name="Installing OVD Gateway on SLES 11.SP1";
		break;
		case "Installation_Gateway_openSUSE_11.3":
			$name="Installing OVD Gateway on openSUSE 11.3";
		break;
		case "NativeClient":
			$name="Native Client";
		break;
		case "Protocol":
			$name="Protocol Overview";
		break;
		case "Premium_Edition":
			$name="Premium Edition Guide";
		break;
		case "QuickStart":
			$name="Quick Start with OVD";
		break;
		case "Support_Debian_Squeeze":
			$name="Installing OVD 4 on Debian Squeeze";
		break;
		case "Support_Debian_Wheezy":
			$name="Installing OVD 4 on Debian Wheezy";
		break;
		case "Support_RHEL_6.0":
			$name="Installing OVD 4 on RHEL 6.0";
		break;
		case "Support_Centos_6.0":
			$name="Installing OVD 4 on CentOS 6.0";
		break;
		case "Support_SLES_11.SP1":
			$name="Installing OVD 4 on SLES 11.SP1";
		break;
		case "Support_Ubuntu_Lucid":
			$name="Installing OVD 4 on Ubuntu Lucid";
		break;
		case "Support_Ubuntu_Precise":
			$name="Installing OVD 4 on Ubuntu Precise";
		break;
		case "Support_openSUSE_11.3":
			$name="Installing OVD 4 on openSUSE 11.3";
		break;
		case "WebClient":
			$name="Web Client";
		break;
		case "Web_Applications_Gateway":
			$name="Web Application Gateway";
		break;
		case "WsdlApi":
			$name="Session Manager Admin API";
		break;
	
		
	}

	if (is_null($name)) $name=$infos['filename'];
 
/* if ((strpos($infos['filename'], 'Installation_Gateway')!== FALSE) || (strpos($infos['filename'], 'NativeClient')!== FALSE) || (strpos($infos['filename'], 'Premium')!== FALSE) )
	 continue;*/
	 
	 echo "<tr>";
	   echo "<td>";
		 echo '<span style="font-size: 0.9em; color: #666; font-weight: bold;">'.str_replace('_', ' ', $name).'</span>';
	   echo "</td>";

	   echo '<td style="width: 15px;"></td>';
	   
	   echo '<td style="text-align: center; vertical-align: middle;">';
		 echo '<a target="_blank" style="text-decoration: none;" href="'.$infos['filename'].'.html"><img style="border: none;" src="http://doc.ulteo.com/resources/html.png" width="22" height="22" alt="HTML" title="HTML" align="middle" />&nbsp;<span style="font-size: 0.75em; font-weight: bold;">HTML</span></a>';
	   echo "</td>";
	   
	   echo '<td style="text-align: center; vertical-align: middle;">';
		 echo '<a style="text-decoration: none;" href="'.$infos['filename'].'.pdf"><img style="border: none;" src="http://doc.ulteo.com/resources/pdf.png" width="22" height="22" alt="PDF" title="PDF" align="middle" />&nbsp;<span style="font-size: 0.75em; font-weight: bold;">PDF</span></a>';
	   echo "</td>";
	 
	 echo "</tr>";
	
}
?>
</table>
</div>
</br>

    <footer class="footer">

</footer>
  
</body>
</html>

