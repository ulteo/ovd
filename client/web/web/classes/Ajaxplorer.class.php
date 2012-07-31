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


class Ajaxplorer {
	private $session_node;
	private $pr_nodes;
	private $sf_nodes;
	
	
	public function __construct($session_node_) {
		$this->session_node = $session_node_;
		$this->pr_nodes = $session_node_->getElementsByTagName('profile');
		$this->sf_nodes = $session_node_->getElementsByTagName('sharedfolder');
	}
	
	
	public function can_run() {
		return (is_dir(WEB_CLIENT_ROOT.'/ajaxplorer/'));
	}
	
	
	public function is_required() {
		return ($this->pr_nodes->length > 0 || $this->sf_nodes->length > 0);
	}
	
	
	public function build_data() {
		$ret = array();
		$ret['repositories'] = array();
		$ret['folders'] = array();
		$ret['applications'] = $this->generateActionsXML($this->session_node->getElementsByTagName('application'));
		
		$profile_node = $this->pr_nodes->item(0);
		if (is_object($profile_node) && self::isValidSharedFolder($profile_node)) {
			$ret['repositories'][] = $this->generateRepo(_('Profile'), $profile_node);
			$ret['folders'][] = $profile_node->getAttribute('rid');
		}
		
		foreach ($this->sf_nodes as $sharedfolder_node) {
			if (! self::isValidSharedFolder($sharedfolder_node))
				continue;
			
			$ret['repositories'][] = $this->generateRepo($sharedfolder_node->getAttribute('name'), $sharedfolder_node);
			$ret['folders'][] = $sharedfolder_node->getAttribute('rid');
		}
		
		return $ret;
	}
	
	
	private static function generateRepo($name_, $node_) {
		$uri = $node_->getAttribute('uri');
		if ($node_->hasAttribute('login') && $node_->hasAttribute('password'))
			$uri = unparse_url(array_merge(parse_url($uri), array('user' => $node_->getAttribute('login'), 'pass' => $node_->getAttribute('password'))));
		
		return array(
			'DISPLAY'		=>	$name_,
			'DRIVER'		=>	'fs',
			'DRIVER_OPTIONS'	=>	array(
				'PATH'			=>	$uri,
				'CREATE'		=>	false,
				'RECYCLE_BIN'		=>	'',
				'CHMOD_VALUE'		=>	'0660',
				'DEFAULT_RIGHTS'	=>	'',
				'PAGINATION_THRESHOLD'	=>	500,
				'PAGINATION_NUMBER'	=>	200
			),
		);
	}
	
	protected static function isValidSharedFolder($node_) {
		if (! $node_->hasAttribute('uri'))
			return false;
		
		$ret = parse_url ($node_->getAttribute('uri'));
		if ($ret === FALSE)
			return false;
		
		if (! in_array($ret['scheme'], array('webdav', 'webdavs')))
			return false;
		
		return true;
	}
	
	private static function generateActionsXML($application_nodes_) {
		$dom = new DomDocument('1.0', 'utf-8');
		
		$driver_node = $dom->createElement('driver');
		$driver_node->setAttribute('name', 'fs');
		$driver_node->setAttribute('className', 'class.fsDriver.php');
		$dom->appendChild($driver_node);
		
		$actions_node = $dom->createElement('actions');
		$driver_node->appendChild($actions_node);
		
		$actions = array();
		foreach ($application_nodes_ as $application_node) {
			$app_id = $application_node->getAttribute('id');
			$app_name = $application_node->getAttribute('name');
		
		$clientcallback_cdata = <<<EOF
var repository;
var path;
if (window.actionArguments && window.actionArguments.length > 0) {
	repository = 0;
	path = window.actionArguments[0];
} else {
	userSelection = ajaxplorer.getFilesList().getUserSelection();
	if (userSelection && userSelection.isUnique()) {
		repository = ajaxplorer.repositoryId;
		path = userSelection.getUniqueFileName();
	}
}

new Ajax.Request(
	'../start_app.php',
	{
		method: 'post',
		parameters: {
			id: $app_id,
			repository: repository,
			path: path
		}
	}
);
EOF;
			
			$mimes = array();
			foreach ($application_node->getElementsByTagName('mime') as $mime_node)
				$mimes[] = $mime_node->getAttribute('type');
			
			$actions['ulteo'.$application_node->getAttribute('id')] = array(
				'id'				=>	$app_id,
				'text'				=>	$app_name,
				'mimes'				=>	$mimes,
				'clientcallback'	=>	$clientcallback_cdata
			);
		}
		
		foreach ($actions as $k => $v) {
			$action_node = $dom->createElement('action');
			$action_node->setAttribute('name', $k);
			$action_node->setAttribute('fileDefault', 'false');
			$actions_node->appendChild($action_node);
			
			$gui_node = $dom->createElement('gui');
			$gui_node->setAttribute('text', $v['text']);
			$gui_node->setAttribute('title', $v['text']);
			$gui_node->setAttribute('src', '/ovd/icon.php?id='.$v['id']);
			$gui_node->setAttribute('hasAccessKey', 'false');
			$action_node->appendChild($gui_node);
			 
			$context_node = $dom->createElement('context');
			$context_node->setAttribute('selection', 'true');
			$context_node->setAttribute('dir', 'false');
			$context_node->setAttribute('recycle', 'false');
			$context_node->setAttribute('actionBar', 'false');
			$context_node->setAttribute('actionBarGroup', 'get');
			$context_node->setAttribute('contextMenu', 'true');
			$context_node->setAttribute('infoPanel', 'true');
			$context_node->setAttribute('inZip', 'false');
			$context_node->setAttribute('handleMimeTypes', 'true');
			$gui_node->appendChild($context_node);
			
			foreach ($v['mimes'] as $mime) {
				$mime_node = $dom->createElement('mime');
				$mime_node->setAttribute('type', $mime);
				$context_node->appendChild($mime_node);
			}
			
			$selectioncontext_node = $dom->createElement('selectionContext');
			$selectioncontext_node->setAttribute('dir', 'false');
			$selectioncontext_node->setAttribute('file', 'true');
			$selectioncontext_node->setAttribute('recycle', 'false');
			$selectioncontext_node->setAttribute('unique', 'true');
			$gui_node->appendChild($selectioncontext_node);
			
			$rightscontext_node = $dom->createElement('rightsContext');
			$rightscontext_node->setAttribute('noUser', 'true');
			$rightscontext_node->setAttribute('userLogged', 'only');
			$rightscontext_node->setAttribute('read', 'true');
			$rightscontext_node->setAttribute('write', 'false');
			$rightscontext_node->setAttribute('adminOnly', 'false');
			$action_node->appendChild($rightscontext_node);
			
			$processing_node = $dom->createElement('processing');
			$action_node->appendChild($processing_node);
			
			$clientcallback_node = $dom->createElement('clientCallback');
			$clientcallback_node->setAttribute('prepareModal', 'true');
			
			$clientcallback_cdata_node = $dom->createCDATASection($v['clientcallback']);
			$clientcallback_node->appendChild($clientcallback_cdata_node);
			
			$processing_node->appendChild($clientcallback_node);
			
			$servercallback_node = $dom->createElement('serverCallback');
			$servercallback_node->setAttribute('methodName', 'switchAction');
			$processing_node->appendChild($servercallback_node);
		}
		
		$xml = $dom->saveXML();
		return $xml;
	}
}
