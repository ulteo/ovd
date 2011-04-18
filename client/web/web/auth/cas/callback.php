<?php
require_once(dirname(__FILE__).'/../../includes/core.inc.php');

require_once('CAS.php');

@file_put_contents(session_save_path().'/'.$_GET['pgtIou'].'.'.CAS_PGT_STORAGE_FILE_FORMAT_PLAIN, $_GET['pgtId']);

exit(0);
