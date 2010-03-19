<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
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
require_once(dirname(__FILE__).'/../../../../includes/session_path.inc.php');

class ulteoAccessDriver extends AbstractAccessDriver {
	// USE "DIRECTORY_SEPARATOR" constant ?

	public function true_ls($type_='ls', $path_='.') {
		if ($type_ != 'ls' && $type_ != 'lsd')
			$type_ = 'ls';

		if ($path_ == '')
			$path_ = '.';

		$ret = shell_exec($this->wrapper_cmd.' '.$type_.' "'.$this->clean_path($path_).'"');
		$ret = explode("\n", $ret);
		array_pop($ret);

		$lines = array();
		foreach ($ret as $line) {
			$matches = array();
			preg_match('@^([^\ ]+)\ +([^\ ]+)\ +([^\ ]+)\ +([^\ ]+)\ +([^\ ]+)\ +([^\ ]+)\ +([^\ ]+)\ +([^\ ]+)\ +(.+)@', $line, $matches);
			if (! isset($matches[9]) || ! is_string($matches[9]) || $matches[9] == '')
				continue;

			if ($matches[1] == 'ls:')
				continue;

			if (substr($matches[9], 0, strlen($this->wrapper_homedir.'/')) == $this->wrapper_homedir.'/')
				$matches[9] = substr($matches[9], strlen($this->wrapper_homedir.'/'));

			if ($matches[9][0] == '/')
				$matches[9] = substr($matches[9], 1);

			$lines[] = array(
				'type'		=>	($matches[1][0] == 'd')?'folder':'file',
				'filesize'	=>	(int)$matches[5],
				'filemtime'	=>	time(),
				'fileperms'	=>	$this->true_fileperms($matches[1]),
				'name'		=>	$matches[9]
			);
		}

		return $lines;
	}

	public function clean_path($path_) {
		if ($path_ == '')
			$path_ = $this->wrapper_homedir.'/';
		elseif ($path_[0] == '/' && substr($path_, 0, strlen($this->wrapper_homedir.'/')) != $this->wrapper_homedir.'/')
			$path_ = $this->wrapper_homedir.'/'.substr($path_, 1);

		if (substr($path_, 0, strlen($this->wrapper_homedir.'/')) == $this->wrapper_homedir.'/')
			$path_ = substr($path_, strlen($this->wrapper_homedir.'/'));

		if (substr($path_, 0, 2) == './')
			$path_ = substr($path_, 2);

		return $path_;
	}

	public function true_file_exists($path_) {
		$cmd = $this->wrapper_cmd.' ls "'.$this->clean_path($path_).'"';

		exec($cmd, $output, $ret);

		return ($ret == 0);
	}

	public function true_mv($src_, $dst_) {
		$cmd = $this->wrapper_cmd.' mv "'.$this->clean_path($src_).'" "'.$this->clean_path($dst_).'"';

		exec($cmd, $output, $ret);

		return ($ret == 0);
	}

	public function true_cp($src_, $dst_) {
		$cmd = $this->wrapper_cmd.' cp "'.$this->clean_path($src_).'" "'.$this->clean_path($dst_).'"';

		exec($cmd, $output, $ret);

		return ($ret == 0);
	}

	public function true_rm($path_) {
		$cmd = $this->wrapper_cmd.' rm "'.$this->clean_path($path_).'"';

		exec($cmd, $output, $ret);

		return ($ret == 0);
	}

	public function true_mkdir($path_) {
		$cmd = $this->wrapper_cmd.' mkdir "'.$this->clean_path($path_).'"';

		exec($cmd, $output, $ret);

		return ($ret == 0);
	}

	public function true_touch($path_) {
		$cmd = $this->wrapper_cmd.' touch "'.$this->clean_path($path_).'"';

		exec($cmd, $output, $ret);

		return ($ret == 0);
	}

	public function true_cat($path_) {
		$cmd = $this->wrapper_cmd.' get "'.$this->clean_path($path_).'"';

		$out = fopen('php://output', 'a');
		$handle = popen($cmd, 'r');
		if (is_resource($handle)) {
			if (function_exists('stream_copy_to_stream'))
				stream_copy_to_stream($handle, $out);
			else {
				while (! feof($handle))
					fwrite($out, fread($handle, 2048));
			}
		}
		$ret = pclose($handle);
		fclose($out);

		return ($ret == 0);
	}

	public function true_echo($filepathordata_, $path_, $data_) {
		$cmd = $this->wrapper_cmd.' put "'.$this->clean_path($path_).'"';

		if ($data_) {
			$out = popen($cmd, 'w');
			if (is_resource($out))
				fwrite($out, $filepathordata_);
			$ret = pclose($out);
		} else {
			$handle = @fopen($filepathordata_, 'rb');
			$out = popen($cmd, 'w');
			if (is_resource($out)) {
				if (function_exists('stream_copy_to_stream'))
					stream_copy_to_stream($handle, $out);
				else {
					while (! feof($handle))
						fwrite($out, fread($handle, 2048));
				}
			}
			$ret = pclose($out);
			fclose($handle);
		}

		return ($ret == 0);
	}

	public function true_pwd() {
		$cmd = $this->wrapper_cmd.' pwd';

		exec($cmd, $output, $ret);

		if (! is_array($output) || count($output) == 0)
			return '';

		$output = $output[0];

		return $output;
	}

	public function true_fileperms($perms_str_) {
		/*$buf = substr($perms_str_, 1);
		$buf_user = substr($buf, 0, 3);
		$buf_group = substr($buf, 3, 3);
		$buf_all = substr($buf, 6, 3);*/

		//continue...

		return '0777';
	}

	public $repository;

	public function ulteoAccessDriver($driverName, $filePath, $repository, $optOptions = NULL) {
		parent::AbstractAccessDriver($driverName, $filePath, $repository);

		$buf = AuthService::getLoggedUser();
		$this->wrapper_login = $buf->id;
		$this->wrapper_cmd = 'aps-shell '.$this->wrapper_login;
		$this->wrapper_homedir = $this->true_pwd();

		$this->initXmlActionsFile(SESSION_PATH.'/'.$_SESSION['session'].'/parameters/ajaxplorerActions.xml');
		$this->xmlFilePath = INSTALL_PATH.'/plugins/access.ulteo/ulteoActions.xml';
	}

	public function switchAction($action, $httpVars, $fileVars) {
		if (! isset($this->actions[$action]))
			return;

		$xmlBuffer = '';

		foreach ($httpVars as $getName => $getValue)
			$$getName = Utils::securePath(SystemTextEncoding::magicDequote($getValue));

		$selection = new UserSelection();
		$selection->initFromHttpVars($httpVars);

		if (isset($dir) && $action != 'upload') {
			$safeDir = $dir;
			$dir = SystemTextEncoding::fromUTF8($dir);
		}

		if (isset($dest))
			$dest = SystemTextEncoding::fromUTF8($dest);

		$mess = ConfService::getMessages();

		// FILTER DIR PAGINATION ANCHOR
		if (isset($dir) && strstr($dir, '#') !== false) {
			$parts = split('#', $dir);
			$dir = $parts[0];
			$page = $parts[1];
		}

		switch ($action) {
			//------------------------------------
			//	COPY / MOVE
			//------------------------------------
			case 'copy':
			case 'move':
				if ($selection->isEmpty()) {
					$errorMessage = $mess[113];
					break;
				}

				$success = $error = array();

				$this->copyOrMove($dest, $selection->getFiles(), $error, $success, ($action=='move'?true:false));

				if (count($error)) {
					$errorMessage = join("\n", $error);
				} else {
					$logMessage = join("\n", $success);
					AJXP_Logger::logAction(($action=='move'?'Move':'Copy'), array(
						'files'			=>	$selection,
						'destination'	=>	$dest
					));
				}

				$reload_current_node = true;
				if (isset($dest_node))
					$reload_dest_node = $dest_node;
				$reload_file_list = true;
			break;

			//------------------------------------
			//	DELETE
			//------------------------------------
			case 'delete':
				if ($selection->isEmpty()) {
					$errorMessage = $mess[113];
					break;
				}

				$logMessages = array();
				$errorMessage = $this->delete($selection->getFiles(), $logMessages);

				if (count($logMessages))
					$logMessage = join("\n", $logMessages);

				AJXP_Logger::logAction('Delete', array(
					'files'	=>	$selection
				));

				$reload_current_node = true;
				$reload_file_list = true;
			break;

			//------------------------------------
			//	RENAME
			//------------------------------------
			case 'rename':
				$file = SystemTextEncoding::fromUTF8($file);
				$filename_new = SystemTextEncoding::fromUTF8($filename_new);

				$error = $this->rename($file, $filename_new);

				if ($error != null) {
					$errorMessage  = $error;
					break;
				}

				$logMessage = SystemTextEncoding::toUTF8($file).' '.$mess[41].' '.SystemTextEncoding::toUTF8($filename_new);

				AJXP_Logger::logAction('Rename', array(
					'original'	=>	$file,
					'new'		=>	$filename_new
				));

				$reload_current_node = true;
				$reload_file_list = basename($filename_new);
			break;

			//------------------------------------
			//	CREATE DIR
			//------------------------------------
			case 'mkdir':
				$messtmp = '';

				$dirname = Utils::processFileName(SystemTextEncoding::fromUTF8($dirname));

				$error = $this->mkdir($dir, $dirname);
				if (isset($error)) {
					$errorMessage = $error;
					break;
				}

				$messtmp .= $mess[38].' '.SystemTextEncoding::toUTF8($dirname).' '.$mess[39];
				if ($dir == '')
					$messtmp .= '/';
				else
					$messtmp .= SystemTextEncoding::toUTF8($dir);

				$logMessage = $messtmp;

				AJXP_Logger::logAction('Create Dir', array(
					'dir'	=>	$dir.'/'.$dirname
				));

				$reload_current_node = true;
				$reload_file_list = $dirname;
			break;

			//------------------------------------
			//	CREATE FILE
			//------------------------------------
			case 'mkfile':
				$messtmp = '';

				$filename = Utils::processFileName(SystemTextEncoding::fromUTF8($filename));

				$error = $this->createEmptyFile($dir, $filename);
				if (isset($error)) {
					$errorMessage = $error;
					break;
				}

				$messtmp .= $mess[34].' '.SystemTextEncoding::toUTF8($filename).' '.$mess[39];
				if ($dir == '')
					$messtmp .= '/';
				else
					$messtmp .= SystemTextEncoding::toUTF8($dir);

				$logMessage = $messtmp;

				AJXP_Logger::logAction('Create File', array(
					'file'	=>	$dir.'/'.$filename
				));

				$reload_file_list = $filename;
			break;
			
			//------------------------------------
			//	XML LISTING
			//------------------------------------
			case 'ls':
				if (! isset($dir) || $dir == '/')
					$dir = '';

				$searchMode = $fileListMode = $completeMode = false;

				if (isset($mode)) {
					if ($mode == 'search')
						$searchMode = true;
					elseif ($mode == 'file_list')
						$fileListMode = true;
					elseif ($mode == 'complete')
						$completeMode = true;
				}

				$nom_rep = $this->initName($dir);
				AJXP_Exception::errorToXml($nom_rep);

				if ($fileListMode) {
					$countFiles = $this->countFiles($nom_rep);

					$threshold = $this->repository->getOption('PAGINATION_THRESHOLD');
					if (! isset($threshold) || intval($threshold) == 0)
						$threshold = 500;

					if ($countFiles > $threshold) {
						$limitPerPage = $this->repository->getOption('PAGINATION_NUMBER');
						if (! isset($limitPerPage) || intval($limitPerPage) == 0)
							$limitPerPage = 200;

						$offset = 0;
						$crtPage = 1;

						if (isset($page)) {
							$offset = (intval($page)-1)*$limitPerPage;
							$crtPage = $page;
						}

						$totalPages = floor($countFiles / $limitPerPage) + 1;
						$reps = $this->listing($nom_rep, false, $offset, $limitPerPage);
					} else {
						$reps = $this->listing($nom_rep, $searchMode);
					}
				} else {
					$reps = $this->listing($nom_rep, ! $searchMode);
				}

				//$reps = $result[0];
				AJXP_XMLWriter::header();
				if (isset($totalPages) && isset($crtPage)) {
					print '<columns switchDisplayMode="list" switchGridMode="filelist"/>';
					print '<pagination count="'.$countFiles.'" total="'.$totalPages.'" current="'.$crtPage.'"/>';
				}

				foreach ($reps as $repIndex => $repName) {
					$attributes = '';
					if ($searchMode) {
						$currentFile = $nom_rep.'/'.$repIndex;
						if ($currentFile[0] == '/')
							$currentFile = substr($currentFile, 1);

						$file_infos = $this->true_ls('lsd', $currentFile);

						if ($file_infos['type'] == 'file') {
							$attributes = 'is_file="true" icon="'.$repName.'"';
							$repName = $repIndex;
						}
					} elseif ($fileListMode) {
						$currentFile = $nom_rep.'/'.$repIndex;
						if ($currentFile[0] == '/')
							$currentFile = substr($currentFile, 1);

						$file_infos = $this->true_ls('lsd', $currentFile);
						$file_infos = $file_infos[0];

						$atts = array();
						$atts[] = 'is_file="'.(($file_infos['type'] == 'file')?"true":"false").'"';
						$atts[] = 'is_image="'.Utils::is_image($file_infos['name']).'"';
						$atts[] = 'file_group="'.$this->wrapper_login.'"';
						$atts[] = 'file_owner="'.$this->wrapper_login.'"';
						$atts[] = 'file_perms="'.$file_infos['fileperms'].'"';

						/*if (Utils::is_image($currentFile)) {
							list($width, $height, $type, $attr) = @getimagesize($currentFile);
							$atts[] = 'image_type="'.image_type_to_mime_type($type).'"';
							$atts[] = 'image_width="'.$width.'"';
							$atts[] = 'image_height="'.$height.'"';
						}*/

						$atts[] = 'mimestring="'.Utils::mimetype($currentFile, 'type', ($file_infos['type'] == 'folder')?true:false).'"';

						$atts[] = 'ajxp_modiftime="'.$file_infos['filemtime'].'"';
						$atts[] = 'filesize="'.Utils::roundSize($file_infos['filesize']).'"';
						$atts[] = 'bytesize="'.$file_infos['filesize'].'"';

						$atts[] = 'filename="'.Utils::xmlEntities(SystemTextEncoding::toUTF8($file_infos['name'])).'"';
						$atts[] = 'icon="'.(($file_infos['type'] == 'file')?SystemTextEncoding::toUTF8($repName):(($file_infos['type'] == 'folder')?"folder.png":"mime-empty.png")).'"';

						$attributes = join(' ', $atts);
						$repName = $repIndex;
					} else {
						$folderBaseName = Utils::xmlEntities($repName);

						$link = urlencode(SystemTextEncoding::toUTF8(SERVER_ACCESS.'?dir='.$dir.'/'.$folderBaseName));

						$folderFullName = Utils::xmlEntities($dir).'/'.$folderBaseName;

						$parentFolderName = $dir;

						if (! $completeMode) {
							$icon = CLIENT_RESOURCES_FOLDER.'/images/foldericon.png';
							$openicon = CLIENT_RESOURCES_FOLDER.'/images/openfoldericon.png';

							$attributes = 'icon="'.$icon.'"  openicon="'.$openicon.'" filename="'.SystemTextEncoding::toUTF8($folderFullName).'" src="'.$link.'"';
						}
					}

					print('<tree text="'.Utils::xmlEntities(SystemTextEncoding::toUTF8($repName)).'" '.$attributes.'>');
					print('</tree>');
				}

				AJXP_XMLWriter::close();
				exit(1);
			break;

			//------------------------------------
			//	DOWNLOAD
			//------------------------------------
			case 'download':
				$buf = $this->readFile(SystemTextEncoding::fromUTF8($file), 'force-download');
				exit(0);
			break;

			//------------------------------------
			//	IMAGE PROXY
			//------------------------------------
			case 'image_proxy':
				$buf = $this->readFile(SystemTextEncoding::fromUTF8($file), 'image');
				exit(0);
			break;

			//------------------------------------
			//	MP3 PROXY
			//------------------------------------
			case 'mp3_proxy':
				$this->readFile(SystemTextEncoding::fromUTF8($file), 'mp3');
				exit(0);
			break;

			//------------------------------------
			//	ONLINE EDIT
			//------------------------------------
			case 'edit';
				if (isset($save) && $save == 1 && isset($code)) {
					$code = str_replace('&lt;', '<', stripslashes($_POST['code']));
					AJXP_Logger::logAction('Online Edition', array('file' => SystemTextEncoding::fromUTF8($file)));

					$this->writeFile($code, SystemTextEncoding::fromUTF8($file), true);

					echo $mess[115];
				} else
					$this->readFile(SystemTextEncoding::fromUTF8($file), 'plain');
				exit(0);
			break;

			//------------------------------------
			//	UPLOAD
			//------------------------------------	
			case 'upload':
				$fancyLoader = false;

				if (isset($fileVars['Filedata'])) {
					$fancyLoader = true;
					if ($dir != '')
						$dir = '/'.base64_decode($dir);
				}

				$rep_source = '';
				if ($dir != '/')
					$rep_source = $dir.'/';

				$destination = SystemTextEncoding::fromUTF8($rep_source);

				$logMessage = '';
				foreach ($fileVars as $boxName => $boxData) {
					if ($boxName != 'Filedata' && substr($boxName, 0, 9) != 'userfile_')
						continue;

					if ($boxName == 'Filedata')
						$fancyLoader = true;

					$err = Utils::parseFileDataErrors($boxData, $fancyLoader);
					if ($err != null) {
						$errorMessage = $err;
						break;
					}

					$userfile_name = $boxData['name'];

					if ($fancyLoader)
						$userfile_name = SystemTextEncoding::fromUTF8($userfile_name);

					$userfile_name = Utils::processFileName($userfile_name);
					$userfile_name = $destination.$userfile_name;
					while ($userfile_name{0} == '/')
						$userfile_name = substr($userfile_name, 1);

					if (! $this->writeFile($boxData['tmp_name'], $userfile_name)) {
						$errorMessage = ($fancyLoader?'411 ':'').$mess[33].' '.$userfile_name;
						break;
					}

					$logMessage .= $mess[34].' '.SystemTextEncoding::toUTF8($userfile_name).' '.$mess[35].' '.$dir;
					AJXP_Logger::logAction('Upload File', array('file' => SystemTextEncoding::fromUTF8($dir).'/'.$userfile_name));
				}

				if ($fancyLoader) {
					if (isset($errorMessage)) {
						header('HTTP/1.0 '.$errorMessage);
						die('Error '.$errorMessage);
					} else {
						header('HTTP/1.0 200 OK');
						die('200 OK');
					}
				} else {
					print('<html><script type="text/javascript">'."\n");
					if (isset($errorMessage))
						print("\n".'if (parent.ajaxplorer.actionBar.multi_selector) parent.ajaxplorer.actionBar.multi_selector.submitNext("'.str_replace("'", "\'", $errorMessage).'");');
					else
						print("\n".'if (parent.ajaxplorer.actionBar.multi_selector) parent.ajaxplorer.actionBar.multi_selector.submitNext();');
					print('</script></html>');
				}

				exit(0);
			break;
		}

		if (isset($logMessage) || isset($errorMessage))
			$xmlBuffer .= AJXP_XMLWriter::sendMessage(((isset($logMessage))?$logMessage:null), ((isset($errorMessage))?$errorMessage:null), false);

		if (isset($requireAuth))
			$xmlBuffer .= AJXP_XMLWriter::requireAuth(false);

		if (isset($reload_current_node) && $reload_current_node === true)
			$xmlBuffer .= AJXP_XMLWriter::reloadCurrentNode(false);

		if (isset($reload_dest_node) && $reload_dest_node != '')
			$xmlBuffer .= AJXP_XMLWriter::reloadNode($reload_dest_node, false);

		if (isset($reload_file_list))
			$xmlBuffer .= AJXP_XMLWriter::reloadFileList($reload_file_list, false);

		return $xmlBuffer;
	}

	public function initName($dir) {
		$mess = ConfService::getMessages();

		$nom_rep = '';
		if (isset($dir) && $dir != '' && $dir != '/')
			$nom_rep = $dir;

		/*if (! file_exists($racine))
			return new AJXP_Exception(72);

		if (! is_dir($nom_rep))
			return new AJXP_Exception(100);*/

		return $nom_rep;
	}

	public function countFiles($dirName) {
		$lines = $this->true_ls('ls', $dirName);

		$count = 0;
		foreach ($lines as $fof) {
			if ($fof['name'] == '.' || $fof['name'] == '..' || (Utils::isHidden($fof['name']) && ! $this->driverConf['SHOW_HIDDEN_FILES']))
				continue;

			$count++;
		}

		return $count;
	}
	
	public function listing($nom_rep, $dir_only = false, $offset = 0, $limit = 0) {
		$mess = ConfService::getMessages();

		$size_unit = $mess['byte_unit_symbol'];

		$orderDir = 0;
		$orderBy = 'filename';

		$cursor = 0;
		$lines = $this->true_ls('ls', $nom_rep);

		$list = array();
		foreach ($lines as $fof) {
			if ($fof['name'] == '.' || $fof['name'] == '..' || (Utils::isHidden($fof['name']) && ! $this->driverConf['SHOW_HIDDEN_FILES']))
				continue;

			if ($offset > 0 && $cursor < $offset) {
				$cursor++;
				continue;
			}

			if ($limit > 0 && ($cursor-$offset) >= $limit)
				break;

			$cursor++;

			if ($fof['type'] == 'folder') {
				if ($orderBy == 'mod')
					$liste_rep[$fof['name']] = $fof['filemtime'];
				else
					$liste_rep[$fof['name']] = $fof['name'];
			} else {
				if (! $dir_only) {
					if ($orderBy == 'filename')
						$liste_fic[$fof['name']] = Utils::mimetype($nom_rep.'/'.$fof['name'], 'image', false);
					elseif ($orderBy == 'filesize')
						$liste_fic[$fof['name']] = $fof['filesize'];
					elseif ($orderBy == 'mod')
						$liste_fic[$fof['name']] = $fof['filemtime'];
					elseif ($orderBy == 'filetype')
						$liste_fic[$fof['name']] = Utils::mimetype($nom_rep.'/'.$fof['name'], 'type', false);
					else
						$liste_fic[$fof['name']] = Utils::mimetype($nom_rep.'/'.$fof['name'], 'image', false);
				}
			}
		}

		if (isset($liste_rep) && is_array($liste_rep)) {
			if ($orderBy == 'mod') {
				if ($orderDir == 0)
					arsort($liste_rep);
				else
					asort($liste_rep);
			} else {
				if ($orderDir == 0)
					ksort($liste_rep);
				else
					krsort($liste_rep);
			}

			if ($orderBy != 'filename') {
				foreach ($liste_rep as $key => $value)
					$liste_rep[$key] = $key;
			}
		} else
			$liste_rep = array();

		if (isset($liste_fic) && is_array($liste_fic)) {
			if ($orderBy == 'filename') {
				if ($orderDir == 0)
					ksort($liste_fic);
				else
					krsort($liste_fic);
			} elseif ($orderBy == 'filesize') {
				if ($orderDir == 0)
					asort($liste_fic);
				else
					arsort($liste_fic);
			} elseif ($orderBy == 'mod') {
				if ($orderDir == 0)
					arsort($liste_fic);
				else
					asort($liste_fic);
			} elseif ($orderBy == 'filetype') {
				if ($orderDir == 0)
					asort($liste_fic);
				else
					arsort($liste_fic);
			} else {
				if ($orderDir == 0)
					ksort($liste_fic);
				else
					krsort($liste_fic);
			}

			if ($orderBy != 'filename') {
				foreach ($liste_fic as $key => $value)
					$liste_fic[$key] = Utils::mimetype($key, 'image', false);
			}
		} else
			$liste_fic = array();

		$liste = Utils::mergeArrays($liste_rep, $liste_fic);

		return $liste;
	}

	public function copyOrMove($destDir, $selectedFiles, &$error, &$success, $move=false) {
		$mess = ConfService::getMessages();

		foreach ($selectedFiles as $selectedFile)
			$this->copyOrMoveFile($destDir, $selectedFile, $error, $success, $move);
	}

	public function copyOrMoveFile($destDir, $srcFile, &$error, &$success, $move=false) {
		$mess = ConfService::getMessages();

		$destDir = $this->clean_path($destDir);

		$destFile = $destDir.'/'.basename($srcFile);
		$realSrcFile = $srcFile;

		if (! $this->true_file_exists($realSrcFile)) {
			$error[] = $mess[100].$srcFile;
			return;
		}

		if ($realSrcFile == $destFile) {
			$error[] = $mess[101];
			return;
		}

		if ($move)
			$res = $this->true_mv($realSrcFile, $destFile);
		else
			$res = $this->true_cp($realSrcFile, $destFile);

		if (! isset($res) || $res !== true) {
			$error[] = $mess[114];
			return;
		}
		
		if ($move)
			$success[] = $mess[34].' '.SystemTextEncoding::toUTF8(basename($srcFile)).' '.$mess[74].' '.SystemTextEncoding::toUTF8($destDir);
		else
			$success[] = $mess[34].' '.SystemTextEncoding::toUTF8(basename($srcFile)).' '.$mess[73].' '.SystemTextEncoding::toUTF8($destDir);
	}

	public function rename($filePath, $filename_new) {
		$mess = ConfService::getMessages();

		$filename_new = Utils::processFileName($filename_new);

		$new = $this->clean_path(dirname($filePath).'/'.$filename_new);

		if ($filename_new == '')
			return $mess[37];

		if (! $this->true_file_exists($filePath))
			return $mess[100].' '.basename($filePath);

		// Bug ?
		//if ($this->true_file_exists($new))
		//	return $filename_new.' '.$mess[43];

		$this->true_mv($filePath, $new);

		return null;
	}

	public function mkdir($crtDir, $newDirName) {
		$mess = ConfService::getMessages();

		if ($newDirName == '')
			return $mess[37];

		$newDir = $this->clean_path($crtDir.'/'.$newDirName);

		// Bug ?
		//if ($this->true_file_exists($newDir))
		//	return $mess[40];

		$this->true_mkdir($newDir);

		return null;		
	}
	
	public function createEmptyFile($crtDir, $newFileName) {
		$mess = ConfService::getMessages();

		if ($newFileName == '')
			return $mess[37];

		$newFile = $this->clean_path($crtDir.'/'.$newFileName);

		// Bug ?
		//if ($this->true_file_exists($newFile))
		//	return $mess[71];

		$res = $this->true_touch($newFile);

		if (! $res)
			return $mess[102].' '.$newFile;

		return null;
	}

	public function delete($selectedFiles, &$logMessages) {
		$mess = ConfService::getMessages();

		foreach ($selectedFiles as $selectedFile) {
			if ($selectedFile == '' || $selectedFile == DIRECTORY_SEPARATOR)
				return $mess[120];

			$selectedFile = $this->clean_path($selectedFile);

			//if (! $this->true_file_exists($selectedFile)) {
			//	$logMessages[] = $mess[100].' '.SystemTextEncoding::toUTF8($selectedFile);
			//	continue;
			//}

			$file_infos = $this->true_ls('lsd', $selectedFile);
			$file_infos = $file_infos[0];

			$this->true_rm($selectedFile);

			if ($file_infos['type'] == 'folder')
				$logMessages[] = $mess[38].' '.SystemTextEncoding::toUTF8($selectedFile).' '.$mess[44];
			else 
				$logMessages[] = $mess[34].' '.SystemTextEncoding::toUTF8($selectedFile).' '.$mess[44];
		}

		return null;
	}

	public function readFile($filePathOrData, $headerType='plain', $localName='', $data=false, $gzip=GZIP_DOWNLOAD) {
		$gzip = false;

		if (! $data) {
			$file_infos = $this->true_ls('lsd', $filePathOrData);
			$file_infos = $file_infos[0];

			$size = $file_infos['filesize'];
		} else
			$size = strlen($filePathOrData);

		$localName = (($localName == '')?basename($filePathOrData):$localName);

		if ($headerType == 'plain')
			header('Content-type: text/plain');
		elseif ($headerType == 'image') {
			header('Content-Type: '.Utils::getImageMimeType(basename($filePathOrData)).'; name="'.$localName.'"');
			header('Content-Length: '.$size);
			header('Cache-Control: public');
		} elseif ($headerType == 'mp3') {
			header('Content-Type: audio/mp3; name="'.$localName.'"');
			header('Content-Length: '.$size);
		} else {
			if(preg_match('@ MSIE @', $_SERVER['HTTP_USER_AGENT']) || preg_match('@ WebKit @', $_SERVER['HTTP_USER_AGENT']))
				$localName = str_replace('+', ' ', urlencode(SystemTextEncoding::toUTF8($localName)));

			if (! $data)
				header('Accept-Ranges: bytes');

			header('Content-Type: application/force-download; name="'.$localName.'"');
			header('Content-Transfer-Encoding: binary');
			header('Content-Length: '.$size);
			if (! $data && $size != 0)
				header('Content-Range: bytes 0-'.($size-1).'/'.$size.';');
			header('Content-Disposition: attachment; filename="'.$localName.'"');
			header('Expires: 0');
			header('Cache-Control: no-cache, must-revalidate');
			header('Pragma: no-cache');
			if (preg_match('@ MSIE 6@', $_SERVER['HTTP_USER_AGENT'])) {
				header('Cache-Control: max_age=0');
				header('Pragma: public');
			}

			// For SSL websites there is a bug with IE see article KB 323308
			// therefore we must reset the Cache-Control and Pragma Header
			if (ConfService::getConf('USE_HTTPS') == 1 && preg_match('@ MSIE @', $_SERVER['HTTP_USER_AGENT'])) {
				header('Cache-Control:');
				header('Pragma:');
			}
		}

		if ($data)
			print($filePathOrData);
		else
			$this->true_cat($filePathOrData);
	}

	public function writeFile($filePathOrData, $localName='', $data=false) {
		$localName = (($localName == '')?basename($filePathOrData):$localName);

		return $this->true_echo($filePathOrData, $localName, $data);
	}
}
