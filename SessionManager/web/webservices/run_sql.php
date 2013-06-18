<?php
/**
 * Copyright (C) 2010-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Wojciech LICHOTA <wojciech.lichota@stxnext.pl> 2013
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
require_once(dirname(__FILE__).'/../includes/core-minimal.inc.php');
require_once(dirname(__FILE__).'/../includes/webservices.inc.php');

function parse_run_sql_XML($xml_) {
    Logger::error('main', 'parse_run_sql_XML');
	if (! $xml_ || strlen($xml_) == 0)
		return false;

	$dom = new DomDocument('1.0', 'utf-8');
    Logger::error('main', 'parse_run_sql_XML');
    
	$buf = @$dom->loadXML($xml_);
	if (! $buf)
		return false;

    Logger::error('main', 'parse_run_sql_XML');
	if (! $dom->hasChildNodes())
		return false;

    Logger::error('main', 'parse_run_sql_XML');
	$node = $dom->getElementsByTagname('run-sql')->item(0);
	if (is_null($node))
		return false;

    Logger::error('main', 'parse_run_sql_XML');
	if (! $node->hasAttribute('app_id'))
		return false;

    Logger::error('main', 'parse_run_sql_XML');
	if (! $node->hasAttribute('query'))
		return false;

    Logger::error('main', 'parse_run_sql_XML');
	$params = array();
	$param_nodes = $dom->getElementsByTagName('param');
	foreach ($param_nodes as $param_node) {
		if (! $param_node->hasAttribute('name'))
			return false;
		$params[$param_node->getAttribute('name')] = $param_node->nodeValue;
	}

    Logger::error('main', 'parse_run_sql_XML');
	return array(
		'app_id'	=>	$node->getAttribute('app_id'),
		'query'		=>	$node->getAttribute('query'),
		'params'	=>	$params
	);
}

$ret = parse_run_sql_XML(@file_get_contents('php://input'));
if (! $ret) {
	Logger::error('main', '(webservices/run/sql) Server does not send a valid XML (error_code: 1)');
	webservices_return_error(1, 'Server does not send a valid XML');
}

$server = webservices_load_server($_SERVER['REMOTE_ADDR']);;
if (! $server) {
	Logger::error('main', '(webservices/run/sql) Server does not exist (error_code: 0)');
	webservices_return_error(1, 'Server does not exist');
}

if (! $server->isAuthorized()) {
	Logger::error('main', '(webservices/run/sql) Server is not authorized (error_code: 2)');
	webservices_return_error(2, 'Server is not authorized');
}

$query = $ret['query'];
$query_params = $ret['params'];

$call_params = array($query);

function getParam($value_) {
	global $call_params, $query_params;
	array_push($call_params, $query_params[$value_]);
	return count($call_params) - 1;
};

$call_params[0] = preg_replace('/\$\(([A-Z_]+)\)/se', '\'%\'.getParam(\'$1\')', $query);

$sql = SQL::getInstance();
$res = call_user_func_array(array($sql, "DoQuery"), $call_params);

header('Content-Type: text/xml; charset=utf-8');
$dom = new DomDocument('1.0', 'utf-8');
$node = $dom->createElement('sql-result');

if($res !== false) {
	$row = $sql->FetchResult($res);
	$node->setAttribute('value', reset($row));
} else {
	$node->setAttribute('error', 1);
}

$dom->appendChild($node);
echo $dom->saveXML();
exit(0);
