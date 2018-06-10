<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
   /**
    * H3K | Tiny File Manager
    * CCP Programmers
    * http://fb.com/ccpprogrammers
    * https://github.com/prasathmani/tinyfilemanager
    */
   // Default language
   $lang = 'en';
   // Auth with login/password (set true/false to enable/disable it)
   $use_auth = false;
   // Users: array('Username' => 'Password', 'Username2' => 'Password2', ...), Password has to encripted into MD5
   $readonly_users = array();

   $auth_users = array();


   // upload path, where files from CKEDITOR will be uploaded
   $CKE_upload_path = $_SERVER['DOCUMENT_ROOT']."/../../../home/robot";;
   // Public_html folder
   $path_to_hosted_folder = $_SERVER['DOCUMENT_ROOT'];
   // Show or hide files and folders that starts with a dot
   $show_hidden_files = false;
   // Enable highlight.js (https://highlightjs.org/) on view's page
   $use_highlightjs = true;
   // highlight.js style
   $highlightjs_style = 'vs';
   // Enable ace.js (https://ace.c9.io/) on view's page
   $edit_files = true;
   // Send files though mail
   $send_mail = false;
   // Send files though mail
   $toMailId = ""; //yourmailid@mail.com
   // Default timezone for date() and time() - http://php.net/manual/en/timezones.php
   $default_timezone = 'Etc/UTC'; // UTC
   // Root path for file manager
   $root_path = $_SERVER['DOCUMENT_ROOT']."/../../../home/robot";
   // Root url for links in file manager.Relative to $http_host. Variants: '', 'path/to/subfolder'
   // Will not working if $root_path will be outside of server document root
   $root_url = '/cloud/data';
   // Server hostname. Can set manually if wrong
   $http_host = $_SERVER['HTTP_HOST'];
   // input encoding for iconv
   $iconv_input_encoding = 'UTF-8';
   // date() format for file modification date
   $datetime_format = 'd.m.y H:i';
   // allowed upload file extensions
   $upload_extensions = ''; // 'gif,png,jpg'
   // show or hide the left side tree view
   $show_tree_view = false;
   //Array of folders excluded from listing
   $GLOBALS['exclude_folders'] = array(
   
   );
   // CKEDITOR Support
    $ck =  $ck_img = isset($_GET['CKEditor'])&&isset($_GET['CKEditorFuncNum'])&&isset($_GET['langCode']);
   if($ck) {
	   $show_tree_view = false;
	   $ckd ="&CKEditor=" . htmlentities($_GET['CKEditor']) . "&CKEditorFuncNum=" . intval($_GET['CKEditor']) . "&langCode=" . htmlentities($_GET['langCode']);
   } else $ckd = "";
   define("CK_PATH",$ckd);
   // include user config php file
   if (defined('FM_CONFIG') && is_file(FM_CONFIG) ) {
   	include(FM_CONFIG);
   }
   //--- EDIT BELOW CAREFULLY OR DO NOT EDIT AT ALL
   // if fm included
   if (defined('FM_EMBED')) {
       $use_auth = false;
   } else {
       @set_time_limit(600);
       date_default_timezone_set($default_timezone);
       ini_set('default_charset', 'UTF-8');
       if (version_compare(PHP_VERSION, '5.6.0', '<') && function_exists('mb_internal_encoding')) {
           mb_internal_encoding('UTF-8');
       }
       if (function_exists('mb_regex_encoding')) {
           mb_regex_encoding('UTF-8');
       }
       session_cache_limiter('');
       session_name('filemanager');
       session_start();
   }
   if (empty($auth_users)) {
       $use_auth = false;
   }
   $is_https = isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1)
       || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https';
   // clean and check $root_path
   $root_path = rtrim($root_path, '\\/');
   $root_path = str_replace('\\', '/', $root_path);
   if (!@is_dir($root_path)) {
       echo "<h1>Root path \"{$root_path}\" not found!</h1>";
       exit;
   }
   // clean $root_url
   $root_url = fm_clean_path($root_url);
   // abs path for site
   defined('FM_SHOW_HIDDEN') || define('FM_SHOW_HIDDEN', $show_hidden_files);
   defined('FM_ROOT_PATH') || define('FM_ROOT_PATH', $root_path);
   defined('FM_ROOT_URL') || define('FM_ROOT_URL', ($is_https ? 'https' : 'http') . '://' . $http_host . (!empty($root_url) ? '/' . $root_url : ''));
   defined('FM_BASE_URL') || define('FM_BASE_URL', ($is_https ? 'https' : 'http') . '://' . $http_host);
   defined('FM_SELF_URL') || define('FM_SELF_URL', ($is_https ? 'https' : 'http') . '://' . $http_host . $_SERVER['PHP_SELF']);
   defined('FM_PUBLIC_HTML') || define('FM_PUBLIC_HTML',fm_clean_path($path_to_hosted_folder));
  
   $CKE_upload_path = fm_clean_path($CKE_upload_path);
   $CKE_upload_path = str_replace(FM_PUBLIC_HTML,FM_BASE_URL,$CKE_upload_path);

   defined('FM_UPLOAD_PATH') || define('FM_UPLOAD_PATH', ($is_https ? 'https' : 'http') . '://' . $http_host . $CKE_upload_path);

     // logout
   if (isset($_GET['logout'])) {
       unset($_SESSION['user']);
       fm_redirect(FM_SELF_URL);
   }
   // Show image here
   if (isset($_GET['img'])) {
       fm_show_image($_GET['img']);
   }
   // Show file here
   if (isset($_GET['src'])) {
       fm_show_file($_GET['src']);
   }
   //Show source files
   if (isset($_GET['getFile'])) {
	   fm_get_file($_GET['getFile']);
   }
   // Auth
if ($use_auth) {
    if (isset($_SESSION['user'], $auth_users[$_SESSION['user']])) {
        // Logged
    } elseif (isset($_POST['fm_usr'], $_POST['fm_pwd'])) {
        // Logging In
        sleep(1);
        if (isset($auth_users[$_POST['fm_usr']]) && md5($_POST['fm_pwd']) === $auth_users[$_POST['fm_usr']]) {
            $_SESSION['user'] = $_POST['fm_usr'];
            fm_set_msg('You are logged in');
            fm_redirect(FM_SELF_URL . '?p=' . CK_PATH);
        } else {
            unset($_SESSION['user']);
            fm_set_msg('Wrong password', 'error');
            fm_redirect(FM_SELF_URL);
        }
    } else {
        // Form
        unset($_SESSION['user']);
        fm_show_header_login();
        fm_show_message();
        ?>
        <div class="path login-form">
                <img src="<?php echo FM_SELF_URL ?>?img=cloud" alt="Sákrány" style="margin:20px;">
            <form action="" method="post">
                <label for="fm_usr">Username</label><input type="text" id="fm_usr" name="fm_usr" value="" placeholder="Username" required><br>
                <label for="fm_pwd">Password</label><input type="password" id="fm_pwd" name="fm_pwd" value="" placeholder="Password" required><br>
                <input type="submit" value="Login">
            </form>
        </div>
        <?php
        fm_show_footer_login();
        exit;
    }
}
   defined('FM_LANG') || define('FM_LANG', $lang);
   defined('FM_EXTENSION') || define('FM_EXTENSION', $upload_extensions);
   defined('FM_TREEVIEW') || define('FM_TREEVIEW', $show_tree_view);
   define('FM_READONLY', $use_auth && !empty($readonly_users) && isset($_SESSION['user']) && in_array($_SESSION['user'], $readonly_users));
   define('FM_IS_WIN', DIRECTORY_SEPARATOR == '\\');
   // always use ?p=
   if (!isset($_GET['p']) && empty($_FILES)) {
   fm_redirect(FM_SELF_URL . '?p=' . CK_PATH);
   }
   // get path
   $p = isset($_REQUEST['p'])?$_REQUEST['p']:'';
   // clean path
   $p = fm_clean_path($p);
   // instead globals vars
   define('FM_PATH', $p);
   define('FM_USE_AUTH', $use_auth);
   define('FM_EDIT_FILE', $edit_files);
   defined('FM_ICONV_INPUT_ENC') || define('FM_ICONV_INPUT_ENC', $iconv_input_encoding);
   defined('FM_USE_HIGHLIGHTJS') || define('FM_USE_HIGHLIGHTJS', $use_highlightjs);
   defined('FM_HIGHLIGHTJS_STYLE') || define('FM_HIGHLIGHTJS_STYLE', $highlightjs_style);
   defined('FM_DATETIME_FORMAT') || define('FM_DATETIME_FORMAT', $datetime_format);
   unset($p, $use_auth, $iconv_input_encoding, $use_highlightjs, $highlightjs_style);
   /*************************** ACTIONS ***************************/
   //AJAX Request
   if (isset($_POST['ajax']) && !FM_READONLY) {
   //search : get list of files from the current folder
   if(isset($_POST['type']) && $_POST['type']=="search") {
   $dir = $_POST['path'];
   $response = scan($dir);
   echo json_encode($response);
   }
   //Send file to mail
   if (isset($_POST['type']) && $_POST['type']=="mail") {
   //send mail Fn removed.
   }
   //backup files
   if(isset($_POST['type']) && $_POST['type']=="backup") {
   $file = $_POST['file'];
   $path = $_POST['path'];
   $date = date("dMy-His");
   $newFile = $file.'-'.$date.'.bak';
   copy($path.'/'.$file, $path.'/'.$newFile) or die("Unable to backup");
   echo "Backup $newFile Created";
   }
   exit;
   }
   // Delete file / folder
   if (isset($_GET['del']) && !FM_READONLY) {
   $del = $_GET['del'];
   $del = fm_clean_path($del);
   $del = str_replace('/', '', $del);
   if ($del != '' && $del != '..' && $del != '.') {
   $path = FM_ROOT_PATH;
   if (FM_PATH != '') {
       $path .= '/' . FM_PATH;
   }
   $is_dir = is_dir($path . '/' . $del);
   if (fm_rdelete($path . '/' . $del)) {
       $msg = $is_dir ? 'Folder <b>%s</b> deleted' : 'File <b>%s</b> deleted';
       fm_set_msg(sprintf($msg, fm_enc($del)));
   } else {
       $msg = $is_dir ? 'Folder <b>%s</b> not deleted' : 'File <b>%s</b> not deleted';
       fm_set_msg(sprintf($msg, fm_enc($del)), 'error');
   }
   } else {
   fm_set_msg('Wrong file or folder name', 'error');
   }
   fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH) . CK_PATH);
   }
   // Create folder
   if (isset($_GET['new']) && isset($_GET['type']) && !FM_READONLY) {
   $new = strip_tags($_GET['new']);
   $type = $_GET['type'];
   $new = fm_clean_path($new);
   $new = str_replace('/', '', $new);
   if ($new != '' && $new != '..' && $new != '.') {
   $path = FM_ROOT_PATH;
   if (FM_PATH != '') {
       $path .= '/' . FM_PATH;
   }
   if($_GET['type']=="file") {
       if(!file_exists($path . '/' . $new)) {
           @fopen($path . '/' . $new, 'w') or die('Cannot open file:  '.$new);
           fm_set_msg(sprintf('File <b>%s</b> created', fm_enc($new)));
       } else {
           fm_set_msg(sprintf('File <b>%s</b> already exists', fm_enc($new)), 'alert');
       }
   } else {
       if (fm_mkdir($path . '/' . $new, false) === true) {
           fm_set_msg(sprintf('Folder <b>%s</b> created', $new));
       } elseif (fm_mkdir($path . '/' . $new, false) === $path . '/' . $new) {
           fm_set_msg(sprintf('Folder <b>%s</b> already exists', fm_enc($new)), 'alert');
       } else {
           fm_set_msg(sprintf('Folder <b>%s</b> not created', fm_enc($new)), 'error');
       }
   }
   } else {
   fm_set_msg('Wrong folder name', 'error');
   }
   fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH) . CK_PATH);
   }
   // Copy folder / file
   if (isset($_GET['copy'], $_GET['finish']) && !FM_READONLY) {
   // from
   $copy = $_GET['copy'];
   $copy = fm_clean_path($copy);
   // empty path
   if ($copy == '') {
   fm_set_msg('Source path not defined', 'error');
   fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH) . CK_PATH);
   }
   // abs path from
   $from = FM_ROOT_PATH . '/' . $copy;
   // abs path to
   $dest = FM_ROOT_PATH;
   if (FM_PATH != '') {
   $dest .= '/' . FM_PATH;
   }
   $dest .= '/' . basename($from);
   // move?
   $move = isset($_GET['move']);
   // copy/move
   if ($from != $dest) {
   $msg_from = trim(FM_PATH . '/' . basename($from), '/');
   if ($move) {
       $rename = fm_rename($from, $dest);
       if ($rename) {
           fm_set_msg(sprintf('Moved from <b>%s</b> to <b>%s</b>', fm_enc($copy), fm_enc($msg_from)));
       } elseif ($rename === null) {
           fm_set_msg('File or folder with this path already exists', 'alert');
       } else {
           fm_set_msg(sprintf('Error while moving from <b>%s</b> to <b>%s</b>', fm_enc($copy), fm_enc($msg_from)), 'error');
       }
   } else {
       if (fm_rcopy($from, $dest)) {
           fm_set_msg(sprintf('Copyied from <b>%s</b> to <b>%s</b>', fm_enc($copy), fm_enc($msg_from)));
       } else {
           fm_set_msg(sprintf('Error while copying from <b>%s</b> to <b>%s</b>', fm_enc($copy), fm_enc($msg_from)), 'error');
       }
   }
   } else {
   fm_set_msg('Paths must be not equal', 'alert');
   }
   fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH) . CK_PATH);
   }
   // Mass copy files/ folders
   if (isset($_POST['file'], $_POST['copy_to'], $_POST['finish']) && !FM_READONLY) {
   // from
   $path = FM_ROOT_PATH;
   if (FM_PATH != '') {
   $path .= '/' . FM_PATH;
   }
   // to
   $copy_to_path = FM_ROOT_PATH;
   $copy_to = fm_clean_path($_POST['copy_to']);
   if ($copy_to != '') {
   $copy_to_path .= '/' . $copy_to;
   }
   if ($path == $copy_to_path) {
   fm_set_msg('Paths must be not equal', 'alert');
   fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH) . CK_PATH);
   }
   if (!is_dir($copy_to_path)) {
   if (!fm_mkdir($copy_to_path, true)) {
       fm_set_msg('Unable to create destination folder', 'error');
       fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH) . CK_PATH);
   }
   }
   // move?
   $move = isset($_POST['move']);
   // copy/move
   $errors = 0;
   $files = $_POST['file'];
   if (is_array($files) && count($files)) {
   foreach ($files as $f) {
       if ($f != '') {
           // abs path from
           $from = $path . '/' . $f;
           // abs path to
           $dest = $copy_to_path . '/' . $f;
           // do
           if ($move) {
               $rename = fm_rename($from, $dest);
               if ($rename === false) {
                   $errors++;
               }
           } else {
               if (!fm_rcopy($from, $dest)) {
                   $errors++;
               }
           }
       }
   }
   if ($errors == 0) {
       $msg = $move ? 'Selected files and folders moved' : 'Selected files and folders copied';
       fm_set_msg($msg);
   } else {
       $msg = $move ? 'Error while moving items' : 'Error while copying items';
       fm_set_msg($msg, 'error');
   }
   } else {
   fm_set_msg('Nothing selected', 'alert');
   }
   fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH) . CK_PATH);
   }
   // Rename
   if (isset($_GET['ren'], $_GET['to']) && !FM_READONLY) {
   // old name
   $old = $_GET['ren'];
   $old = fm_clean_path($old);
   $old = str_replace('/', '', $old);
   // new name
   $new = $_GET['to'];
   $new = fm_clean_path($new);
   $new = str_replace('/', '', $new);
   // path
   $path = FM_ROOT_PATH;
   if (FM_PATH != '') {
   $path .= '/' . FM_PATH;
   }
   // rename
   if ($old != '' && $new != '') {
   if (fm_rename($path . '/' . $old, $path . '/' . $new)) {
       fm_set_msg(sprintf('Renamed from <b>%s</b> to <b>%s</b>', fm_enc($old), fm_enc($new)));
   } else {
       fm_set_msg(sprintf('Error while renaming from <b>%s</b> to <b>%s</b>', fm_enc($old), fm_enc($new)), 'error');
   }
   } else {
   fm_set_msg('Names not set', 'error');
   }
   fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH) . CK_PATH);
   }
   // Download
   if (isset($_GET['dl'])) {
   $dl = $_GET['dl'];
   $dl = fm_clean_path($dl);
   $dl = str_replace('/', '', $dl);
   $path = FM_ROOT_PATH;
   if (FM_PATH != '') {
   $path .= '/' . FM_PATH;
   }
   if ($dl != '' && is_file($path . '/' . $dl)) {
   header('Content-Description: File Transfer');
   header('Content-Type: application/octet-stream');
   header('Content-Disposition: attachment; filename="' . basename($path . '/' . $dl) . '"');
   header('Content-Transfer-Encoding: binary');
   header('Connection: Keep-Alive');
   header('Expires: 0');
   header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
   header('Pragma: public');
   header('Content-Length: ' . filesize($path . '/' . $dl));
   readfile($path . '/' . $dl);
   exit;
   } else {
   fm_set_msg('File not found', 'error');
   fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH) . CK_PATH);
   }
   }
   // Upload
   if (!empty($_FILES) && !FM_READONLY) {
   $f = $_FILES;
	   $ck = false;
   if(!isset($f['file'])&&isset($f['upload'])) {
	   $f['file'] = $f['upload'];
	   $ck = true;
   }
   $path = FM_ROOT_PATH;
   if (FM_PATH != '') {
   $path .= '/' . FM_PATH;
   }
   $errors = 0;
   $uploads = 0;
   $total = count($f['file']['name']);
   $allowed = (FM_EXTENSION) ? explode(',', FM_EXTENSION) : false;
   $filename = $f['file']['name'];
   $tmp_name = $f['file']['tmp_name'];
   $ext = pathinfo($filename, PATHINFO_EXTENSION);
   $isFileAllowed = ($allowed) ? in_array($ext, $allowed) : true;
   $name = $path . '/' . $f['file']['name'];
   $i = 0;

   while(file_exists($name)) {
   $name = $path . '/' .$i. $f['file']['name'];
	$i = $i +1;
   }
   if (empty($f['file']['error']) && !empty($tmp_name) && $tmp_name != 'none' && $isFileAllowed) {
   if (move_uploaded_file($tmp_name, $name)) {
	   if(!$ck)
       die('Successfully uploaded');
   else {
	   $name = str_replace(FM_PUBLIC_HTML,FM_BASE_URL,$name);
	   $arr = array(
			"uploaded" => 1,
			"fileName" => $filename,
			"url" => $name
	   );
	   echo json_encode($arr);
	   exit();
   }
   } else {
       die(sprintf('Error while uploading files. Uploaded files: %s', $uploads));
   }
   }
   exit();
   }
   // Mass deleting
   if (isset($_POST['group'], $_POST['delete']) && !FM_READONLY) {
   $path = FM_ROOT_PATH;
   if (FM_PATH != '') {
   $path .= '/' . FM_PATH;
   }
   $errors = 0;
   $files = $_POST['file'];
   if (is_array($files) && count($files)) {
   foreach ($files as $f) {
       if ($f != '') {
           $new_path = $path . '/' . $f;
           if (!fm_rdelete($new_path)) {
               $errors++;
           }
       }
   }
   if ($errors == 0) {
       fm_set_msg('Selected files and folder deleted');
   } else {
       fm_set_msg('Error while deleting items', 'error');
   }
   } else {
   fm_set_msg('Nothing selected', 'alert');
   }
   fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH) . CK_PATH);
   }
   // Pack files
   if (isset($_POST['group'], $_POST['zip']) && !FM_READONLY) {
   $path = FM_ROOT_PATH;
   if (FM_PATH != '') {
   $path .= '/' . FM_PATH;
   }
   if (!class_exists('ZipArchive')) {
   fm_set_msg('Operations with archives are not available', 'error');
   fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH) . CK_PATH);
   }
   $files = $_POST['file'];
   if (!empty($files)) {
   chdir($path);
   if (count($files) == 1) {
       $one_file = reset($files);
       $one_file = basename($one_file);
       $zipname = $one_file . '_' . date('ymd_His') . '.zip';
   } else {
       $zipname = 'archive_' . date('ymd_His') . '.zip';
   }
   $zipper = new FM_Zipper();
   $res = $zipper->create($zipname, $files);
   if ($res) {
       fm_set_msg(sprintf('Archive <b>%s</b> created', fm_enc($zipname)));
   } else {
       fm_set_msg('Archive not created', 'error');
   }
   } else {
   fm_set_msg('Nothing selected', 'alert');
   }
   fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH) . CK_PATH);
   }
   // Unpack
   if (isset($_GET['unzip']) && !FM_READONLY) {
   $unzip = $_GET['unzip'];
   $unzip = fm_clean_path($unzip);
   $unzip = str_replace('/', '', $unzip);
   $path = FM_ROOT_PATH;
   if (FM_PATH != '') {
   $path .= '/' . FM_PATH;
   }
   if (!class_exists('ZipArchive')) {
   fm_set_msg('Operations with archives are not available', 'error');
   fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH) . CK_PATH);
   }
   if ($unzip != '' && is_file($path . '/' . $unzip)) {
   $zip_path = $path . '/' . $unzip;
   //to folder
   $tofolder = '';
   if (isset($_GET['tofolder'])) {
       $tofolder = pathinfo($zip_path, PATHINFO_FILENAME);
       if (fm_mkdir($path . '/' . $tofolder, true)) {
           $path .= '/' . $tofolder;
       }
   }
   $zipper = new FM_Zipper();
   $res = $zipper->unzip($zip_path, $path);
   if ($res) {
       fm_set_msg('Archive unpacked');
   } else {
       fm_set_msg('Archive not unpacked', 'error');
   }
   } else {
   fm_set_msg('File not found', 'error');
   }
   fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH) . CK_PATH);
   }
   // Change Perms (not for Windows)
   if (isset($_POST['chmod']) && !FM_READONLY && !FM_IS_WIN) {
   $path = FM_ROOT_PATH;
   if (FM_PATH != '') {
   $path .= '/' . FM_PATH;
   }
   $file = $_POST['chmod'];
   $file = fm_clean_path($file);
   $file = str_replace('/', '', $file);
   if ($file == '' || (!is_file($path . '/' . $file) && !is_dir($path . '/' . $file))) {
   fm_set_msg('File not found', 'error');
   fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH) . CK_PATH);
   }
   $mode = 0;
   if (!empty($_POST['ur'])) {
   $mode |= 0400;
   }
   if (!empty($_POST['uw'])) {
   $mode |= 0200;
   }
   if (!empty($_POST['ux'])) {
   $mode |= 0100;
   }
   if (!empty($_POST['gr'])) {
   $mode |= 0040;
   }
   if (!empty($_POST['gw'])) {
   $mode |= 0020;
   }
   if (!empty($_POST['gx'])) {
   $mode |= 0010;
   }
   if (!empty($_POST['or'])) {
   $mode |= 0004;
   }
   if (!empty($_POST['ow'])) {
   $mode |= 0002;
   }
   if (!empty($_POST['ox'])) {
   $mode |= 0001;
   }
   if (@chmod($path . '/' . $file, $mode)) {
   fm_set_msg('Permissions changed');
   } else {
   fm_set_msg('Permissions not changed', 'error');
   }
   fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH) . CK_PATH);
   }
   /*************************** /ACTIONS ***************************/
   // get current path
   $path = FM_ROOT_PATH;
   if (FM_PATH != '') {
   $path .= '/' . FM_PATH;
   }
   // check path
   if (!is_dir($path)) {
   fm_redirect(FM_SELF_URL . '?p=' . CK_PATH);
   }
   // get parent folder
   $parent = fm_get_parent_path(FM_PATH);
   $objects = is_readable($path) ? scandir($path) : array();
   $folders = array();
   $files = array();
   if (is_array($objects)) {
   foreach ($objects as $file) {
   if ($file == '.' || $file == '..' && in_array($file, $GLOBALS['exclude_folders'])) {
       continue;
   }
   if (!FM_SHOW_HIDDEN && substr($file, 0, 1) === '.') {
       continue;
   }
   $new_path = $path . '/' . $file;
   if (is_file($new_path)) {
       $files[] = $file;
   } elseif (is_dir($new_path) && $file != '.' && $file != '..' && !in_array($file, $GLOBALS['exclude_folders'])) {
       $folders[] = $file;
   }
   }
   }
   if (!empty($files)) {
   natcasesort($files);
   }
   if (!empty($folders)) {
   natcasesort($folders);
   }
   // upload form
   if (isset($_GET['upload']) && !FM_READONLY) {
   fm_show_header(); // HEADER
   fm_show_nav_path(FM_PATH); // current path
   ?>
<link href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.4.0/min/dropzone.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.4.0/min/dropzone.min.js"></script>
<div class="path">
   <p><b>Uploading files</b></p>
   <p class="break-word">Destination folder: <?php echo fm_enc(fm_convert_win(FM_ROOT_PATH . '/' . FM_PATH)) ?></p>
   <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]).'?p='.fm_enc(FM_PATH).CK_PATH ?>" class="dropzone" id="fileuploader" enctype="multipart/form-data">
      <input type="hidden" name="p" value="<?php echo fm_enc(FM_PATH) ?>">
      <div class="fallback">
         <input name="file" type="file" multiple />
      </div>
   </form>
</div>
<?php
   fm_show_footer();
   exit;
   }
   // copy form POST
   if (isset($_POST['copy']) && !FM_READONLY) {
   $copy_files = $_POST['file'];
   if (!is_array($copy_files) || empty($copy_files)) {
       fm_set_msg('Nothing selected', 'alert');
       fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH) . CK_PATH);
   }
   fm_show_header(); // HEADER
   fm_show_nav_path(FM_PATH); // current path
   ?>
<div class="path">
   <p><b>Copying</b></p>
   <form action="" method="post">
      <input type="hidden" name="p" value="<?php echo fm_enc(FM_PATH) ?>">
      <input type="hidden" name="finish" value="1">
      <?php
         foreach ($copy_files as $cf) {
             echo '<input type="hidden" name="file[]" value="' . fm_enc($cf) . '">' . PHP_EOL;
         }
         ?>
      <p class="break-word">Files: <b><?php echo implode('</b>, <b>', $copy_files) ?></b></p>
      <p class="break-word">Source folder: <?php echo fm_enc(fm_convert_win(FM_ROOT_PATH . '/' . FM_PATH)) ?><br>
         <label for="inp_copy_to">Destination folder:</label>
         <?php echo FM_ROOT_PATH ?>/<input type="text" name="copy_to" id="inp_copy_to" value="<?php echo fm_enc(FM_PATH) ?>">
      </p>
      <p><label><input type="checkbox" name="move" value="1"> Move'</label></p>
      <p>
         <button type="submit" class="btn"><i class="fa fa-check-circle"></i> Copy </button> &nbsp;
         <b><a href="?p=<?php echo urlencode(FM_PATH) . CK_PATH; ?>"><i class="fa fa-times-circle"></i> Cancel</a></b>
      </p>
   </form>
</div>
<?php
   fm_show_footer();
   exit;
   }
   // copy form
   if (isset($_GET['copy']) && !isset($_GET['finish']) && !FM_READONLY) {
   $copy = $_GET['copy'];
   $copy = fm_clean_path($copy);
   if ($copy == '' || !file_exists(FM_ROOT_PATH . '/' . $copy)) {
       fm_set_msg('File not found', 'error');
       fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH) . CK_PATH);
   }
   fm_show_header(); // HEADER
   fm_show_nav_path(FM_PATH); // current path
   ?>
<div class="path">
   <p><b>Copying</b></p>
   <p class="break-word">
      Source path: <?php echo fm_enc(fm_convert_win(FM_ROOT_PATH . '/' . $copy)) ?><br>
      Destination folder: <?php echo fm_enc(fm_convert_win(FM_ROOT_PATH . '/' . FM_PATH)) ?>
   </p>
   <p>
      <b><a href="?p=<?php echo urlencode(FM_PATH) . CK_PATH; ?>&amp;copy=<?php echo urlencode($copy) ?>&amp;finish=1"><i class="fa fa-check-circle"></i> Copy</a></b> &nbsp;
      <b><a href="?p=<?php echo urlencode(FM_PATH) . CK_PATH; ?>&amp;copy=<?php echo urlencode($copy) ?>&amp;finish=1&amp;move=1"><i class="fa fa-check-circle"></i> Move</a></b> &nbsp;
      <b><a href="?p=<?php echo urlencode(FM_PATH) . CK_PATH; ?>"><i class="fa fa-times-circle"></i> Cancel</a></b>
   </p>
   <p><i>Select folder</i></p>
   <ul class="folders break-word">
      <?php
         if ($parent !== false) {
             ?>
      <li><a href="?p=<?php echo urlencode($parent) ?>&amp;copy=<?php echo urlencode($copy) . CK_PATH; ?>"><i class="fa fa-chevron-circle-left"></i> ..</a></li>
      <?php
         }
         foreach ($folders as $f) {
             ?>
      <li><a href="?p=<?php echo urlencode(trim(FM_PATH . '/' . $f, '/')) ?>&amp;copy=<?php echo urlencode($copy) . CK_PATH; ?>"><i class="fa fa-folder-o"></i> <?php echo fm_convert_win($f) ?></a></li>
      <?php
         }
         ?>
   </ul>
</div>
<?php
   fm_show_footer();
   exit;
   }
   // file viewer
   if (isset($_GET['view'])) {
   $file = $_GET['view'];
   $file = fm_clean_path($file);
   $file = str_replace('/', '', $file);
   if ($file == '' || !is_file($path . '/' . $file)) {
       fm_set_msg('File not found', 'error');
       fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH) . CK_PATH);
   }
   fm_show_header(); // HEADER
   fm_show_nav_path(FM_PATH); // current path
   $file_path = $path . '/' . $file;
   $file_url = FM_BASE_URL . "/cod3r/py/manage.php?getFile=" . $file_path;

   $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
   $mime_type = fm_get_mime_type($file_path);
   $filesize = filesize($file_path);
   $is_zip = false;
   $is_image = false;
   $is_audio = false;
   $is_video = false;
   $is_text = false;
   $view_title = 'File';
   $filenames = false; // for zip
   $content = ''; // for text
   if ($ext == 'zip') {
       $is_zip = true;
       $view_title = 'Archive';
       $filenames = fm_get_zif_info($file_path);
   } elseif (in_array($ext, fm_get_image_exts())) {
       $is_image = true;
       $view_title = 'Image';
   } elseif (in_array($ext, fm_get_audio_exts())) {
       $is_audio = true;
       $view_title = 'Audio';
   } elseif (in_array($ext, fm_get_video_exts())) {
       $is_video = true;
       $view_title = 'Video';
   } elseif (in_array($ext, fm_get_text_exts()) || substr($mime_type, 0, 4) == 'text' || in_array($mime_type, fm_get_text_mimes())) {
       $is_text = true;
       $content = file_get_contents($file_path);
   }
   ?>
<div class="path">
   <p class="break-word"><b><?php echo $view_title ?> "<?php echo fm_enc(fm_convert_win($file)) ?>"</b></p>
   <p class="break-word">
      Full path: <?php echo fm_enc(fm_convert_win($file_path)) ?><br>
      File size: <?php echo fm_get_filesize($filesize) ?><?php if ($filesize >= 1000): ?> (<?php echo sprintf('%s bytes', $filesize) ?>)<?php endif; ?><br>
      MIME-type: <?php echo $mime_type ?><br>
      <?php
         // ZIP info
         if ($is_zip && $filenames !== false) {
             $total_files = 0;
             $total_comp = 0;
             $total_uncomp = 0;
             foreach ($filenames as $fn) {
                 if (!$fn['folder']) {
                     $total_files++;
                 }
                 $total_comp += $fn['compressed_size'];
                 $total_uncomp += $fn['filesize'];
             }
             ?>
      Files in archive: <?php echo $total_files ?><br>
      Total size: <?php echo fm_get_filesize($total_uncomp) ?><br>
      Size in archive: <?php echo fm_get_filesize($total_comp) ?><br>
      Compression: <?php echo round(($total_comp / $total_uncomp) * 100) ?>%<br>
      <?php
         }
         // Image info
         if ($is_image) {
             $image_size = getimagesize($file_path);
             echo 'Image sizes: ' . (isset($image_size[0]) ? $image_size[0] : '0') . ' x ' . (isset($image_size[1]) ? $image_size[1] : '0') . '<br>';
         }
         // Text info
         if ($is_text) {
             $is_utf8 = fm_is_utf8($content);
             if (function_exists('iconv')) {
                 if (!$is_utf8) {
                     $content = iconv(FM_ICONV_INPUT_ENC, 'UTF-8//IGNORE', $content);
                 }
             }
             echo 'Charset: ' . ($is_utf8 ? 'utf-8' : '8 bit') . '<br>';
         }
         echo "Extension: $ext";
         ?>
   </p>
   <p>
      <?php if($ext=="py"):?>
      <b><a title="Run" href="runner.php?f=<?php echo $file_path ?>" target="_blank"><i class="fa fa-caret-square-o-right" aria-hidden="true"></i> Run</a></b>
      <?php endif ?>
      <b><a href="?p=<?php echo urlencode(FM_PATH) ?>&amp;dl=<?php echo urlencode($file) . CK_PATH; ?>"><i class="fa fa-cloud-download"></i> Download</a></b> &nbsp;
      <b><a href="<?php echo fm_enc($file_url) ?>" target="_blank"><i class="fa fa-external-link-square"></i> Open</a></b> &nbsp;
      <?php
         // ZIP actions
         if (!FM_READONLY && $is_zip && $filenames !== false) {
             $zip_name = pathinfo($file_path, PATHINFO_FILENAME);
             ?>
      <b><a href="?p=<?php echo urlencode(FM_PATH) ?>&amp;unzip=<?php echo urlencode($file) . CK_PATH; ?>"><i class="fa fa-check-circle"></i> UnZip</a></b> &nbsp;
      <b><a href="?p=<?php echo urlencode(FM_PATH) ?>&amp;unzip=<?php echo urlencode($file) ?>&amp;tofolder=1<?php echo CK_PATH; ?>" title="UnZip to <?php echo fm_enc($zip_name) ?>"><i class="fa fa-check-circle"></i>
      UnZip to folder</a></b> &nbsp;
      <?php
         }
         if($is_text && !FM_READONLY) {
         ?>
      <!-- <b><a href="?p=<?php echo urlencode(trim(FM_PATH)) ?>&amp;edit=<?php echo urlencode($file) . CK_PATH; ?>" class="edit-file"><i class="fa fa-pencil-square"></i> Edit</a></b> &nbsp;-->
      <b><a href="?p=<?php echo urlencode(trim(FM_PATH)) ?>&amp;edit=<?php echo urlencode($file) ?>&env=ace<?php echo CK_PATH;?>" class="edit-file"><i class="fa fa-pencil-square"></i> Edit</a></b> &nbsp;
      <?php }
         if($send_mail && !FM_READONLY) {
         ?>
      <b><a href="javascript:mailto('<?php echo urlencode(trim(FM_ROOT_PATH.'/'.FM_PATH)) ?>','<?php echo urlencode($file) ?>')"><i class="fa fa-pencil-square"></i> Mail</a></b> &nbsp;
      <?php } ?>
      <b><a href="?p=<?php echo urlencode(FM_PATH) . CK_PATH; ?>"><i class="fa fa-chevron-circle-left"></i> Back</a></b>
   </p>
   <?php
      if ($is_zip) {
          // ZIP content
          if ($filenames !== false) {
              echo '<code class="maxheight">';
              foreach ($filenames as $fn) {
                  if ($fn['folder']) {
                      echo '<b>' . fm_enc($fn['name']) . '</b><br>';
                  } else {
                      echo $fn['name'] . ' (' . fm_get_filesize($fn['filesize']) . ')<br>';
                  }
              }
              echo '</code>';
          } else {
              echo '<p>Error while fetching archive info</p>';
          }
      } elseif ($is_image) {
          // Image content
          if (in_array($ext, array('gif', 'jpg', 'jpeg', 'png', 'bmp', 'ico'))) {
              echo '<p><img src="' . fm_enc($file_url) . '" alt="" class="preview-img"></p>';
          }
      } elseif ($is_audio) {
          // Audio content
          echo '<p><audio src="' . fm_enc($file_url) . '" controls preload="metadata"></audio></p>';
      } elseif ($is_video) {
          // Video content
          echo '<div class="preview-video"><video src="' . fm_enc($file_url) . '" width="640" height="360" controls preload="metadata"></video></div>';
      } elseif ($is_text) {
          if (FM_USE_HIGHLIGHTJS) {
              // highlight
              $hljs_classes = array(
                  'shtml' => 'xml',
                  'htaccess' => 'apache',
                  'phtml' => 'php',
                  'lock' => 'json',
                  'svg' => 'xml',
              );
              $hljs_class = isset($hljs_classes[$ext]) ? 'lang-' . $hljs_classes[$ext] : 'lang-' . $ext;
              if (empty($ext) || in_array(strtolower($file), fm_get_text_names()) || preg_match('#\.min\.(css|js)$#i', $file)) {
                  $hljs_class = 'nohighlight';
              }
              $content = '<pre class="with-hljs"><code class="' . $hljs_class . '">' . fm_enc($content) . '</code></pre>';
          } elseif (in_array($ext, array('php', 'php4', 'php5', 'phtml', 'phps'))) {
              // php highlight
              $content = highlight_string($content, true);
          } else {
              $content = '<pre>' . fm_enc($content) . '</pre>';
          }
          echo $content;
      }
      ?>
</div>
<?php
   fm_show_footer();
   exit;
   }
   // file editor
   if (isset($_GET['edit'])) {
   $file = $_GET['edit'];
   $file = fm_clean_path($file);
   $file = str_replace('/', '', $file);
   if ($file == '' || !is_file($path . '/' . $file)) {
       fm_set_msg('File not found', 'error');
       fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH) . CK_PATH);
   }
   fm_show_header(); // HEADER
   fm_show_nav_path(FM_PATH); // current path
   $file_url = FM_ROOT_URL . fm_convert_win((FM_PATH != '' ? '/' . FM_PATH : '') . '/' . $file);
   $file_path = $path . '/' . $file;
   //normal editer
   $isNormalEditor = true;
   if(isset($_GET['env'])) {
       if($_GET['env'] == "ace") {
           $isNormalEditor = false;
       }
   }
   //Save File
   if(isset($_POST['savedata'])) {
       $writedata = $_POST['savedata'];
    $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    
       $fd=fopen($file_path,"w");
       @fwrite($fd, $writedata);
       fclose($fd);
    if($ext=="py") {
     shell_exec("chmod +x $file_path");
    }
       fm_set_msg('File Saved Successfully', 'alert');
   }
   $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
   $mime_type = fm_get_mime_type($file_path);
   $filesize = filesize($file_path);
   $is_text = false;
   $content = ''; // for text
   if (in_array($ext, fm_get_text_exts()) || substr($mime_type, 0, 4) == 'text' || in_array($mime_type, fm_get_text_mimes())) {
       $is_text = true;
       $content = file_get_contents($file_path);
   }
   ?>
<div class="path">
   <div class="edit-file-actions">
      <a title="Cancel" href="?p=<?php echo urlencode(trim(FM_PATH)) ?>&amp;view=<?php echo urlencode($file) . CK_PATH; ?>"><i class="fa fa-reply-all"></i> Cancel</a>
      <a title="Backup" href="javascript:backup('<?php echo urlencode($path) ?>','<?php echo urlencode($file) ?>')"><i class="fa fa-database"></i> Backup</a>
      <?php if($is_text) { ?>
      <?php if($isNormalEditor) { ?>
      <a title="Advanced" href="?p=<?php echo urlencode(trim(FM_PATH)) ?>&amp;edit=<?php echo urlencode($file) ?>&amp;env=ace<?php echo CK_PATH; ?>"><i class="fa fa-paper-plane"></i> Advanced Editor</a>
      <button type="button" name="Save" data-url="<?php echo fm_enc($file_url) ?>" onclick="edit_save(this,'nrl')"><i class="fa fa-floppy-o"></i> Save</button>
      <?php } else { ?>
      <!--<a title="Plain Editor" href="?p=<?php echo urlencode(trim(FM_PATH)) ?>&amp;edit=<?php echo urlencode($file) . CK_PATH; ?>"><i class="fa fa-text-height"></i> Plain Editor</a>-->
      <button type="button" name="Save" data-url="<?php echo fm_enc($file_url) . CK_PATH; ?>" onclick="edit_save(this,'ace')"><i class="fa fa-floppy-o"></i> Save</button>
      <?php } ?>
      <?php } ?>
   </div>
   <?php
      if ($is_text && $isNormalEditor) {
          echo '<textarea id="normal-editor" rows="33" cols="120" style="width: 99.5%;">'. htmlspecialchars($content) .'</textarea>';
      } elseif ($is_text) {
          echo '<div id="editor" contenteditable="true">'. htmlspecialchars($content) .'</div>';
          if(true):?>
                 <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.2.9/ace.js"></script>
       <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.3.3/ext-modelist.js"></script>
       <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.3.3/ext-language_tools.js"></script>

       
      <script>
          ace.require("ace/ext/language_tools");

         var editor = ace.edit("editor");
         var filename = "<?php echo $file; ?>";
        // In this case "ace/mode/javascript"
        var modelist = ace.require("ace/ext/modelist");
        var mode =  modelist.getModeForPath(filename).mode;
        editor.getSession().setMode(mode);
        editor.setOptions({
        enableBasicAutocompletion: true,
        enableSnippets: true,
        enableLiveAutocompletion: false
    });
        </script>
          <?php endif;
      } else {
          fm_set_msg('FILE EXTENSION HAS NOT SUPPORTED', 'error');
      }
      ?>
</div>
<?php
   fm_show_footer();
   exit;
   }
   // chmod (not for Windows)
   if (isset($_GET['chmod']) && !FM_READONLY && !FM_IS_WIN) {
   $file = $_GET['chmod'];
   $file = fm_clean_path($file);
   $file = str_replace('/', '', $file);
   if ($file == '' || (!is_file($path . '/' . $file) && !is_dir($path . '/' . $file))) {
       fm_set_msg('File not found', 'error');
       fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH) . CK_PATH);
   }
   fm_show_header(); // HEADER
   fm_show_nav_path(FM_PATH); // current path
   $file_url = FM_ROOT_URL . (FM_PATH != '' ? '/' . FM_PATH : '') . '/' . $file;
   $file_path = $path . '/' . $file;
   $mode = fileperms($path . '/' . $file);
   ?>
<div class="path">
   <p><b><?php echo 'Change Permissions'; ?></b></p>
   <p>
      <?php echo 'Full path:'; ?> <?php echo $file_path ?><br>
   </p>
   <form action="" method="post">
      <input type="hidden" name="p" value="<?php echo fm_enc(FM_PATH) ?>">
      <input type="hidden" name="chmod" value="<?php echo fm_enc($file) ?>">
      <table class="compact-table">
         <tr>
            <td></td>
            <td><b>Owner</b></td>
            <td><b>Group</b></td>
            <td><b>Other</b></td>
         </tr>
         <tr>
            <td style="text-align: right"><b>Read</b></td>
            <td><label><input type="checkbox" name="ur" value="1"<?php echo ($mode & 00400) ? ' checked' : '' ?>></label></td>
            <td><label><input type="checkbox" name="gr" value="1"<?php echo ($mode & 00040) ? ' checked' : '' ?>></label></td>
            <td><label><input type="checkbox" name="or" value="1"<?php echo ($mode & 00004) ? ' checked' : '' ?>></label></td>
         </tr>
         <tr>
            <td style="text-align: right"><b>Write</b></td>
            <td><label><input type="checkbox" name="uw" value="1"<?php echo ($mode & 00200) ? ' checked' : '' ?>></label></td>
            <td><label><input type="checkbox" name="gw" value="1"<?php echo ($mode & 00020) ? ' checked' : '' ?>></label></td>
            <td><label><input type="checkbox" name="ow" value="1"<?php echo ($mode & 00002) ? ' checked' : '' ?>></label></td>
         </tr>
         <tr>
            <td style="text-align: right"><b>Execute</b></td>
            <td><label><input type="checkbox" name="ux" value="1"<?php echo ($mode & 00100) ? ' checked' : '' ?>></label></td>
            <td><label><input type="checkbox" name="gx" value="1"<?php echo ($mode & 00010) ? ' checked' : '' ?>></label></td>
            <td><label><input type="checkbox" name="ox" value="1"<?php echo ($mode & 00001) ? ' checked' : '' ?>></label></td>
         </tr>
      </table>
      <p>
         <button type="submit" class="btn"><i class="fa fa-check-circle"></i> Change</button> &nbsp;
         <b><a href="?p=<?php echo urlencode(FM_PATH) . CK_PATH; ?>"><i class="fa fa-times-circle"></i> Cancel</a></b>
      </p>
   </form>
</div>
<?php
   fm_show_footer();
   exit;
   }
   //--- FILEMANAGER MAIN
   fm_show_header(); // HEADER
   fm_show_nav_path(FM_PATH); // current path
   // messages
   fm_show_message();
   $num_files = count($files);
   $num_folders = count($folders);
   $all_files_size = 0;
   ?>
<form action="" method="post">
   <input type="hidden" name="p" value="<?php echo fm_enc(FM_PATH) ?>">
   <input type="hidden" name="group" value="1">
   <?php if(FM_TREEVIEW) { ?>
   <div class="file-tree-view" id="file-tree-view">
      <div class="tree-title">Browse</div>
      <?php
         //file tre view
             echo php_file_tree($_SERVER['DOCUMENT_ROOT'], "javascript:alert('You clicked on [link]');");
         ?>
   </div>
   <?php } ?>
   <table class="table" id="main-table">
   <?php
   $ck_browse = !empty(CK_PATH);
   if($ck_browse) {
// e-z params  
$dim = 150;         /* image displays proportionally within this square dimension ) */  
$cols = 4;          /* thumbnails per row */
$thumIndicator = ''; /* e.g., *image123_th.jpg*) -> if not using thumbNails then use empty string */  
   }
   if(!$ck_browse):
   ?>
      <thead>
         <tr>
            <?php if (!FM_READONLY): ?>
            <th style="width:3%"><label><input type="checkbox" title="Invert selection" onclick="checkbox_toggle()"></label></th>
            <?php endif; ?>
            <th>Name</th>
            <th style="width:10%">Size</th>
            <th style="width:12%">Modified</th>
            <?php if (!FM_IS_WIN): ?>
            <th style="width:6%">Perms</th>
            <th style="width:10%">Owner</th>
            <?php endif; ?>
            <th style="width:<?php if (!FM_READONLY): ?>13<?php else: ?>6.5<?php endif; ?>%">Actions</th>
         </tr>
      </thead>
	  <?php endif;?>
      <?php
         // link to parent folder
         if ($parent !== false) {
             ?>
      <tr>
		<?php if(!$ck_browse): ?>
         <?php if (!FM_READONLY): ?>
         <td></td>
         <?php endif; ?>
         <td colspan="<?php echo !FM_IS_WIN ? '6' : '4' ?>"><a href="?p=<?php echo urlencode($parent) ?><?php echo CK_PATH;?>"><i class="fa fa-chevron-circle-left"></i> ..</a></td>
		<?php else: ?>
         <td><a href="?p=<?php echo urlencode($parent) ?><?php echo CK_PATH;?>"><div class="folder-btn"><i class="fa fa-chevron-circle-left"></i> ..</div></a></td>
		<?php endif; ?>
	  </tr>
      <?php
         }
		 if($ck_browse) echo "<tr>";
         foreach ($folders as $f) {
             $is_link = is_link($path . '/' . $f);
             $img = $is_link ? 'icon-link_folder' : 'fa fa-folder-o';
             $modif = date(FM_DATETIME_FORMAT, filemtime($path . '/' . $f));
             $perms = substr(decoct(fileperms($path . '/' . $f)), -4);
             if (function_exists('posix_getpwuid') && function_exists('posix_getgrgid')) {
                 $owner = posix_getpwuid(fileowner($path . '/' . $f));
                 $group = posix_getgrgid(filegroup($path . '/' . $f));
             } else {
                 $owner = array('name' => '?');
                 $group = array('name' => '?');
             }
             ?>
			  <?php if (!$ck_browse): ?>
      <tr>
        

         <?php if (!FM_READONLY): ?>
         <td><label><input type="checkbox" name="file[]" value="<?php echo fm_enc($f) ?>"></label></td>
         <?php endif; ?>
         <td>
            <div class="filename"><a href="?p=<?php echo urlencode(trim(FM_PATH . '/' . $f, '/')) ?><?php echo CK_PATH;?>"><i class="<?php echo $img ?>"></i> <?php echo fm_convert_win($f) ?></a><?php echo ($is_link ? ' &rarr; <i>' . readlink($path . '/' . $f) . '</i>' : '') ?></div>
         </td>
         <td>Folder</td>
         <td><?php echo $modif ?></td>
         <?php if (!FM_IS_WIN): ?>
         <td><?php if (!FM_READONLY): ?><a title="Change Permissions" href="?p=<?php echo urlencode(FM_PATH) ?>&amp;chmod=<?php echo urlencode($f) ?><?php echo CK_PATH;?>"><?php echo $perms ?></a><?php else: ?><?php echo $perms ?><?php endif; ?></td>
         <td><?php echo $owner['name'] . ':' . $group['name'] ?></td>
         <?php endif; ?>
         <td class="inline-actions"><?php if (!FM_READONLY): ?>
            <a title="Delete" href="?p=<?php echo urlencode(FM_PATH) ?>&amp;del=<?php echo urlencode($f) ?><?php echo CK_PATH;?>" onclick="return confirm('Delete folder?');"><i class="fa fa-trash-o" aria-hidden="true"></i></a>
            <a title="Rename" href="#" onclick="rename('<?php echo fm_enc(FM_PATH) ?>', '<?php echo fm_enc($f) ?>');return false;"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a>
            <a title="Copy to..." href="?p=&amp;copy=<?php echo urlencode(trim(FM_PATH . '/' . $f, '/')) ?><?php echo CK_PATH;?>">
            <i class="fa fa-files-o" aria-hidden="true"></i>
            </a>
            <?php endif; ?>
            <a title="Direct link" href="<?php echo fm_enc(FM_ROOT_URL . (FM_PATH != '' ? '/' . FM_PATH : '') . '/' . $f . '/') ?>" target="_blank">
            <i class="fa fa-link" aria-hidden="true"></i>
            </a>
         </td>
		 		        

      </tr>
     <?php else: ?>
	<td><a href="?p=<?php echo urlencode(trim(FM_PATH . '/' . $f, '/')) ?><?php echo CK_PATH;?>"><div class="folder-btn"><i class="<?php echo $img ?>"></i> <?php echo fm_convert_win($f) ?></div></a><?php echo ($is_link ? ' &rarr; <i>' . readlink($path . '/' . $f) . '</i>' : '') ?>

</td>
            <?php endif; ?>
      <?php
         flush();
         }
		 if($ck_browse) echo "</tr>";

		 if(!$ck_browse):
		 ?><?php
         foreach ($files as $f) {
         $is_link = is_link($path . '/' . $f);
         $img = $is_link ? 'fa fa-file-text-o' : fm_get_file_icon_class($path . '/' . $f);
         $modif = date(FM_DATETIME_FORMAT, filemtime($path . '/' . $f));
         $filesize_raw = filesize($path . '/' . $f);
         $filesize = fm_get_filesize($filesize_raw);
         $filelink = '?p=' . urlencode(FM_PATH) . '&amp;view=' . urlencode($f) . CK_PATH;
         $all_files_size += $filesize_raw;
         $perms = substr(decoct(fileperms($path . '/' . $f)), -4);
         if (function_exists('posix_getpwuid') && function_exists('posix_getgrgid')) {
             $owner = posix_getpwuid(fileowner($path . '/' . $f));
             $group = posix_getgrgid(filegroup($path . '/' . $f));
         } else {
             $owner = array('name' => '?');
             $group = array('name' => '?');
         }
		 $ext = strtolower(pathinfo($path . '/' . $f, PATHINFO_EXTENSION));
		

         ?>
      <tr>
         <?php if (!FM_READONLY): ?>
         <td><label><input type="checkbox" name="file[]" value="<?php echo fm_enc($f) ?>"></label></td>
         <?php endif; ?>
         <td>
            <div class="filename">
			<a href="<?php echo $filelink ?>" title="File info"><i class="<?php echo $img ?>"></i> <?php echo fm_convert_win($f) ?></a><?php echo ($is_link ? ' &rarr; <i>' . readlink($path . '/' . $f) . '</i>' : '') ?>
			
			</div>
         </td>
         <td><span title="<?php printf('%s bytes', $filesize_raw) ?>"><?php echo $filesize ?></span></td>
         <td><?php echo $modif ?></td>
         <?php if (!FM_IS_WIN): ?>
         <td><?php if (!FM_READONLY): ?><a title="<?php echo 'Change Permissions' ?>" href="?p=<?php echo urlencode(FM_PATH) ?>&amp;chmod=<?php echo urlencode($f) ?><?php echo CK_PATH;?>"><?php echo $perms ?></a><?php else: ?><?php echo $perms ?><?php endif; ?></td>
         <td><?php echo fm_enc($owner['name'] . ':' . $group['name']) ?></td>
         <?php endif; ?>
         <td class="inline-actions">
            <?php if (!FM_READONLY): ?>
            <a title="Delete" href="?p=<?php echo urlencode(FM_PATH) ?>&amp;del=<?php echo urlencode($f) ?><?php echo CK_PATH;?>" onclick="return confirm('Delete file?');"><i class="fa fa-trash-o"></i></a>
            <a title="Rename" href="#" onclick="rename('<?php echo fm_enc(FM_PATH) ?>', '<?php echo fm_enc($f) ?>');return false;"><i class="fa fa-pencil-square-o"></i></a>
            <a title="Copy to..." href="?p=<?php echo urlencode(FM_PATH) ?>&amp;copy=<?php echo urlencode(trim(FM_PATH . '/' . $f, '/')) ?><?php echo CK_PATH;?>"><i class="fa fa-files-o"></i></a>
            <?php endif; ?>
            <a title="Direct link" href="?getFile=<?php echo fm_enc(FM_ROOT_URL . (FM_PATH != '' ? '/' . FM_PATH : '') . '/' . $f) ?>" target="_blank"><i class="fa fa-link"></i></a>
            <a title="Download" href="?p=<?php echo urlencode(FM_PATH) ?>&amp;dl=<?php echo urlencode($f) ?><?php echo CK_PATH;?>"><i class="fa fa-download"></i></a>
         </td>
      </tr>
      <?php
         flush();
         }
		 else:?>
		 <?php  

$dir = FM_ROOT_PATH . (FM_PATH != '' ? '/' . FM_PATH : '');    

$dir = rtrim($dir, '/'); // the script will add the ending slash when appropriate  

$files = scandir($dir);  

$images = array();  

foreach($files as $file){  
    // filter for thumbNail image files (use an empty string for $thumIndicator if not using thumbnails )
    if( !preg_match('/'. $thumIndicator .'\.(jpg|jpeg|png|gif)$/i', $file) )  
        continue;  

    $thumbSrc = $dir . '/' . $file;  
	$thumbSrc = str_replace(FM_PUBLIC_HTML, FM_BASE_URL, $thumbSrc);
    $fileBaseName = str_replace('_th.','.',$file);  

    $image_info = getimagesize($thumbSrc);  
    $_w = $image_info[0];  
    $_h = $image_info[1]; 

    if( $_w > $_h ) {       // $a is the longer side and $b is the shorter side
        $a = $_w;  
        $b = $_h;  
    } else {  
        $a = $_h;  
        $b = $_w;  
    }     

    $pct = $b / $a;     // the shorter sides relationship to the longer side

    if( $a > $dim )   
        $a = $dim;      // limit the longer side to the dimension specified

    $b = (int)($a * $pct);  // calculate the shorter side

    $width =    $_w > $_h ? $a : $b;  
    $height =   $_w > $_h ? $b : $a;  

    // produce an image tag
    $str = sprintf('<img src="%s" width="%d" height="%d" title="%s" alt="Click to use this image">',   
        $thumbSrc,  
        $width,  
        $height,
		$fileBaseName
    );  
    // save image tags in an array
    $images[] = str_replace("'", "\\'", $str); // an unescaped apostrophe would break js  

}

$numRows = floor( count($images) / $cols );  

// if there are any images left over then add another row
if( count($images) % $cols != 0 )  
    $numRows++;  


// produce the correct number of table rows with empty cells
for($i=0; $i<$numRows; $i++)   
    echo "\t<tr>" . implode('', array_fill(0, $cols, '<td class="img"></td>')) . "</tr>\n\n";  

?>  
		 <?php
		endif; 
				 if(!$ck_browse):?>
<?php
         if (empty($folders) && empty($files)) {
         ?>
      <tr>
         <?php if (!FM_READONLY): ?>
         <td></td>
         <?php endif; ?>
         <td colspan="<?php echo !FM_IS_WIN ? '6' : '4' ?>"><em><?php echo 'Folder is empty' ?></em></td>
      </tr>
      <?php
         } else {
             ?>
      <tr>
         <?php if (!FM_READONLY): ?>
         <td class="gray"></td>
         <?php endif; ?>
         <td class="gray" colspan="<?php echo !FM_IS_WIN ? '6' : '4' ?>">
            Full size: <span title="<?php printf('%s bytes', $all_files_size) ?>"><?php echo fm_get_filesize($all_files_size) ?></span>,
            files: <?php echo $num_files ?>,
            folders: <?php echo $num_folders ?>
         </td>
      </tr>
      <?php
         }
         ?>
   </table>
   <?php if (!FM_READONLY): ?>
   <p class="path footer-links"><a href="#/select-all" class="group-btn" onclick="select_all();return false;"><i class="fa fa-check-square"></i> Select all</a> &nbsp;
      <a href="#/unselect-all" class="group-btn" onclick="unselect_all();return false;"><i class="fa fa-window-close"></i> Unselect all</a> &nbsp;
      <a href="#/invert-all" class="group-btn" onclick="invert_all();return false;"><i class="fa fa-th-list"></i> Invert selection</a> &nbsp;
      <input type="submit" class="hidden" name="delete" id="a-delete" value="Delete" onclick="return confirm('Delete selected files and folders?')">
      <a href="javascript:document.getElementById('a-delete').click();" class="group-btn"><i class="fa fa-trash"></i> Delete </a> &nbsp;
      <input type="submit" class="hidden" name="zip" id="a-zip" value="Zip" onclick="return confirm('Create archive?')">
      <a href="javascript:document.getElementById('a-zip').click();" class="group-btn"><i class="fa fa-file-archive-o"></i> Zip </a> &nbsp;
      <input type="submit" class="hidden" name="copy" id="a-copy" value="Copy">
      <a href="javascript:document.getElementById('a-copy').click();" class="group-btn"><i class="fa fa-files-o"></i> Copy </a>
   </p>
   <?php endif; else: ?>
</table>
<script>  

// make a js array from the php array
images = [  
<?php   

foreach( $images as $v)  
    echo sprintf("\t'%s',\n", $v);  

?>];  

tbl = document.getElementById('main-table');  

td = tbl.getElementsByClassName('img');  

// fill the empty table cells with data
for(var i=0; i < images.length; i++)  
    td[i].innerHTML = images[i];  


// event handler to place clicked image into CKeditor
tbl.onclick =   

    function(e) {  

        var tgt = e.target || event.srcElement,  
            url;  

        if( tgt.nodeName != 'IMG' )  
            return;  
		// '<?php echo str_replace(FM_PUBLIC_HTML, FM_BASE_URL, $dir);?>' + '/' + tgt.title
        url = tgt.src;  

        this.onclick = null;  

        window.opener.CKEDITOR.tools.callFunction(<?php echo $_GET['CKEditorFuncNum']; ?>, url);  

        window.close();  
    }  
</script>  
   <?php endif; ?>
   </form>
<?php
   fm_show_footer();
   //--- END
   // Functions
   /**
    * Delete  file or folder (recursively)
    * @param string $path
    * @return bool
    */
   function fm_rdelete($path)
   {
       if (is_link($path)) {
           return unlink($path);
       } elseif (is_dir($path)) {
           $objects = scandir($path);
           $ok = true;
           if (is_array($objects)) {
               foreach ($objects as $file) {
                   if ($file != '.' && $file != '..') {
                       if (!fm_rdelete($path . '/' . $file)) {
                           $ok = false;
                       }
                   }
               }
           }
           return ($ok) ? rmdir($path) : false;
       } elseif (is_file($path)) {
           return unlink($path);
       }
       return false;
   }
   /**
    * Recursive chmod
    * @param string $path
    * @param int $filemode
    * @param int $dirmode
    * @return bool
    * @todo Will use in mass chmod
    */
   function fm_rchmod($path, $filemode, $dirmode)
   {
       if (is_dir($path)) {
           if (!chmod($path, $dirmode)) {
               return false;
           }
           $objects = scandir($path);
           if (is_array($objects)) {
               foreach ($objects as $file) {
                   if ($file != '.' && $file != '..') {
                       if (!fm_rchmod($path . '/' . $file, $filemode, $dirmode)) {
                           return false;
                       }
                   }
               }
           }
           return true;
       } elseif (is_link($path)) {
           return true;
       } elseif (is_file($path)) {
           return chmod($path, $filemode);
       }
       return false;
   }
   /**
    * Safely rename
    * @param string $old
    * @param string $new
    * @return bool|null
    */
   function fm_rename($old, $new)
   {
       return (!file_exists($new) && file_exists($old)) ? rename($old, $new) : null;
   }
   /**
    * Copy file or folder (recursively).
    * @param string $path
    * @param string $dest
    * @param bool $upd Update files
    * @param bool $force Create folder with same names instead file
    * @return bool
    */
   function fm_rcopy($path, $dest, $upd = true, $force = true)
   {
       if (is_dir($path)) {
           if (!fm_mkdir($dest, $force)) {
               return false;
           }
           $objects = scandir($path);
           $ok = true;
           if (is_array($objects)) {
               foreach ($objects as $file) {
                   if ($file != '.' && $file != '..') {
                       if (!fm_rcopy($path . '/' . $file, $dest . '/' . $file)) {
                           $ok = false;
                       }
                   }
               }
           }
           return $ok;
       } elseif (is_file($path)) {
           return fm_copy($path, $dest, $upd);
       }
       return false;
   }
   /**
    * Safely create folder
    * @param string $dir
    * @param bool $force
    * @return bool
    */
   function fm_mkdir($dir, $force)
   {
       if (file_exists($dir)) {
           if (is_dir($dir)) {
               return $dir;
           } elseif (!$force) {
               return false;
           }
           unlink($dir);
       }
       return mkdir($dir, 0777, true);
   }
   /**
    * Safely copy file
    * @param string $f1
    * @param string $f2
    * @param bool $upd
    * @return bool
    */
   function fm_copy($f1, $f2, $upd)
   {
       $time1 = filemtime($f1);
       if (file_exists($f2)) {
           $time2 = filemtime($f2);
           if ($time2 >= $time1 && $upd) {
               return false;
           }
       }
       $ok = copy($f1, $f2);
       if ($ok) {
           touch($f2, $time1);
       }
       return $ok;
   }
   /**
    * Get mime type
    * @param string $file_path
    * @return mixed|string
    */
   function fm_get_mime_type($file_path)
   {
       if (function_exists('finfo_open')) {
           $finfo = finfo_open(FILEINFO_MIME_TYPE);
           $mime = finfo_file($finfo, $file_path);
           finfo_close($finfo);
           return $mime;
       } elseif (function_exists('mime_content_type')) {
           return mime_content_type($file_path);
       } elseif (!stristr(ini_get('disable_functions'), 'shell_exec')) {
           $file = escapeshellarg($file_path);
           $mime = shell_exec('file -bi ' . $file);
           return $mime;
       } else {
           return '--';
       }
   }
   /**
    * HTTP Redirect
    * @param string $url
    * @param int $code
    */
   function fm_redirect($url, $code = 302)
   {
       header('Location: ' . $url, true, $code);
       exit;
   }
   /**
    * Clean path
    * @param string $path
    * @return string
    */
   function fm_clean_path($path)
   {
       $path = trim($path);
       $path = trim($path, '\\/');
       $path = str_replace(array('../', '..\\'), '', $path);
       if ($path == '..') {
           $path = '';
       }
       return str_replace('\\', '/', $path);
   }
   /**
    * Get parent path
    * @param string $path
    * @return bool|string
    */
   function fm_get_parent_path($path)
   {
       $path = fm_clean_path($path);
       if ($path != '') {
           $array = explode('/', $path);
           if (count($array) > 1) {
               $array = array_slice($array, 0, -1);
               return implode('/', $array);
           }
           return '';
       }
       return false;
   }
   /**
    * Get nice filesize
    * @param int $size
    * @return string
    */
   function fm_get_filesize($size)
   {
       if ($size < 1000) {
           return sprintf('%s B', $size);
       } elseif (($size / 1024) < 1000) {
           return sprintf('%s KiB', round(($size / 1024), 2));
       } elseif (($size / 1024 / 1024) < 1000) {
           return sprintf('%s MiB', round(($size / 1024 / 1024), 2));
       } elseif (($size / 1024 / 1024 / 1024) < 1000) {
           return sprintf('%s GiB', round(($size / 1024 / 1024 / 1024), 2));
       } else {
           return sprintf('%s TiB', round(($size / 1024 / 1024 / 1024 / 1024), 2));
       }
   }
   /**
    * Get info about zip archive
    * @param string $path
    * @return array|bool
    */
   function fm_get_zif_info($path)
   {
       if (function_exists('zip_open')) {
           $arch = zip_open($path);
           if ($arch) {
               $filenames = array();
               while ($zip_entry = zip_read($arch)) {
                   $zip_name = zip_entry_name($zip_entry);
                   $zip_folder = substr($zip_name, -1) == '/';
                   $filenames[] = array(
                       'name' => $zip_name,
                       'filesize' => zip_entry_filesize($zip_entry),
                       'compressed_size' => zip_entry_compressedsize($zip_entry),
                       'folder' => $zip_folder
                       //'compression_method' => zip_entry_compressionmethod($zip_entry),
                   );
               }
               zip_close($arch);
               return $filenames;
           }
       }
       return false;
   }
   /**
    * Encode html entities
    * @param string $text
    * @return string
    */
   function fm_enc($text)
   {
       return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
   }
   /**
    * This function scans the files folder recursively, and builds a large array
    * @param string $dir
    * @return json
    */
   function scan($dir){
       $files = array();
       $_dir = $dir;
       $dir = FM_ROOT_PATH.'/'.$dir;
       // Is there actually such a folder/file?
       if(file_exists($dir)){
           foreach(scandir($dir) as $f) {
               if(!$f || $f[0] == '.') {
                   continue; // Ignore hidden files
               }
               if(is_dir($dir . '/' . $f)) {
                   // The path is a folder
                   $files[] = array(
                       "name" => $f,
                       "type" => "folder",
                       "path" => $_dir.'/'.$f,
                       "items" => scan($dir . '/' . $f), // Recursively get the contents of the folder
                   );
               } else {
                   // It is a file
                   $files[] = array(
                       "name" => $f,
                       "type" => "file",
                       "path" => $_dir,
                       "size" => filesize($dir . '/' . $f) // Gets the size of this file
                   );
               }
           }
       }
       return $files;
   }
   /**
   * Scan directory and return tree view
   * @param string $directory
   * @param boolean $first_call
   */
   function php_file_tree_dir($directory, $first_call = true) {
   	// Recursive function called by php_file_tree() to list directories/files
   	$php_file_tree = "";
   	// Get and sort directories/files
   	if( function_exists("scandir") ) $file = scandir($directory);
   	natcasesort($file);
   	// Make directories first
   	$files = $dirs = array();
   	foreach($file as $this_file) {
   		if( is_dir("$directory/$this_file" ) ) {
         if(!in_array($this_file, $GLOBALS['exclude_folders'])){
             $dirs[] = $this_file;
         }
       } else {
         $files[] = $this_file;
       }
   	}
   	$file = array_merge($dirs, $files);
   	if( count($file) > 2 ) { // Use 2 instead of 0 to account for . and .. "directories"
   		$php_file_tree = "<ul";
   		if( $first_call ) { $php_file_tree .= " class=\"php-file-tree\""; $first_call = false; }
   		$php_file_tree .= ">";
   		foreach( $file as $this_file ) {
   			if( $this_file != "." && $this_file != ".." ) {
   				if( is_dir("$directory/$this_file") ) {
   					// Directory
   					$php_file_tree .= "<li class=\"pft-directory\"><i class=\"fa fa-folder-o\"></i><a href=\"#\">" . htmlspecialchars($this_file) . "</a>";
   					$php_file_tree .= php_file_tree_dir("$directory/$this_file", false);
   					$php_file_tree .= "</li>";
   				} else {
   					// File
                       $ext = fm_get_file_icon_class($this_file);
                       $path = str_replace($_SERVER['DOCUMENT_ROOT'],"",$directory);
   					$link = "?p="."$path" ."&view=".urlencode($this_file) . CK_PATH;
   					$php_file_tree .= "<li class=\"pft-file\"><a href=\"$link\"> <i class=\"$ext\"></i>" . htmlspecialchars($this_file) . "</a></li>";
   				}
   			}
   		}
   		$php_file_tree .= "</ul>";
   	}
   	return $php_file_tree;
   }
   /**
    * Scan directory and render tree view
    * @param string $directory
    */
   function php_file_tree($directory) {
       // Remove trailing slash
       $code = "";
       if( substr($directory, -1) == "/" ) $directory = substr($directory, 0, strlen($directory) - 1);
       if(function_exists('php_file_tree_dir')) {
           $code .= php_file_tree_dir($directory);
           return $code;
       }
   }
   /**
    * Save message in session
    * @param string $msg
    * @param string $status
    */
   function fm_set_msg($msg, $status = 'ok')
   {
       $_SESSION['message'] = $msg;
       $_SESSION['status'] = $status;
   }
   /**
    * Check if string is in UTF-8
    * @param string $string
    * @return int
    */
   function fm_is_utf8($string)
   {
       return preg_match('//u', $string);
   }
   /**
    * Convert file name to UTF-8 in Windows
    * @param string $filename
    * @return string
    */
   function fm_convert_win($filename)
   {
       if (FM_IS_WIN && function_exists('iconv')) {
           $filename = iconv(FM_ICONV_INPUT_ENC, 'UTF-8//IGNORE', $filename);
       }
       return $filename;
   }
   /**
    * Get CSS classname for file
    * @param string $path
    * @return string
    */
   function fm_get_file_icon_class($path)
   {
       // get extension
       $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
       switch ($ext) {
           case 'ico': case 'gif': case 'jpg': case 'jpeg': case 'jpc': case 'jp2':
           case 'jpx': case 'xbm': case 'wbmp': case 'png': case 'bmp': case 'tif':
           case 'tiff': case 'svg':
               $img = 'fa fa-picture-o';
               break;
           case 'passwd': case 'ftpquota': case 'sql': case 'js': case 'json': case 'sh':
           case 'config': case 'twig': case 'tpl': case 'md': case 'gitignore':
           case 'c': case 'cpp': case 'cs': case 'py': case 'map': case 'lock': case 'dtd':
               $img = 'fa fa-file-code-o';
               break;
           case 'txt': case 'ini': case 'conf': case 'log': case 'htaccess':
               $img = 'fa fa-file-text-o';
               break;
           case 'css': case 'less': case 'sass': case 'scss':
               $img = 'fa fa-css3';
               break;
           case 'zip': case 'rar': case 'gz': case 'tar': case '7z':
               $img = 'fa fa-file-archive-o';
               break;
           case 'php': case 'php4': case 'php5': case 'phps': case 'phtml':
               $img = 'fa fa-code';
               break;
           case 'htm': case 'html': case 'shtml': case 'xhtml':
               $img = 'fa fa-html5';
               break;
           case 'xml': case 'xsl':
               $img = 'fa fa-file-excel-o';
               break;
           case 'wav': case 'mp3': case 'mp2': case 'm4a': case 'aac': case 'ogg':
           case 'oga': case 'wma': case 'mka': case 'flac': case 'ac3': case 'tds':
               $img = 'fa fa-music';
               break;
           case 'm3u': case 'm3u8': case 'pls': case 'cue':
               $img = 'fa fa-headphones';
               break;
           case 'avi': case 'mpg': case 'mpeg': case 'mp4': case 'm4v': case 'flv':
           case 'f4v': case 'ogm': case 'ogv': case 'mov': case 'mkv': case '3gp':
           case 'asf': case 'wmv':
               $img = 'fa fa-file-video-o';
               break;
           case 'eml': case 'msg':
               $img = 'fa fa-envelope-o';
               break;
           case 'xls': case 'xlsx':
               $img = 'fa fa-file-excel-o';
               break;
           case 'csv':
               $img = 'fa fa-file-text-o';
               break;
           case 'bak':
               $img = 'fa fa-clipboard';
               break;
           case 'doc': case 'docx':
               $img = 'fa fa-file-word-o';
               break;
           case 'ppt': case 'pptx':
               $img = 'fa fa-file-powerpoint-o';
               break;
           case 'ttf': case 'ttc': case 'otf': case 'woff':case 'woff2': case 'eot': case 'fon':
               $img = 'fa fa-font';
               break;
           case 'pdf':
               $img = 'fa fa-file-pdf-o';
               break;
           case 'psd': case 'ai': case 'eps': case 'fla': case 'swf':
               $img = 'fa fa-file-image-o';
               break;
           case 'exe': case 'msi':
               $img = 'fa fa-file-o';
               break;
           case 'bat':
               $img = 'fa fa-terminal';
               break;
           default:
               $img = 'fa fa-info-circle';
       }
       return $img;
   }
   /**
    * Get image files extensions
    * @return array
    */
   function fm_get_image_exts()
   {
       return array('ico', 'gif', 'jpg', 'jpeg', 'jpc', 'jp2', 'jpx', 'xbm', 'wbmp', 'png', 'bmp', 'tif', 'tiff', 'psd');
   }
   /**
    * Get video files extensions
    * @return array
    */
   function fm_get_video_exts()
   {
       return array('webm', 'mp4', 'm4v', 'ogm', 'ogv', 'mov');
   }
   /**
    * Get audio files extensions
    * @return array
    */
   function fm_get_audio_exts()
   {
       return array('wav', 'mp3', 'ogg', 'm4a');
   }
   /**
    * Get text file extensions
    * @return array
    */
   function fm_get_text_exts()
   {
       return array(
           'txt', 'css', 'ini', 'conf', 'log', 'htaccess', 'passwd', 'ftpquota', 'sql', 'js', 'json', 'sh', 'config',
           'php', 'php4', 'php5', 'phps', 'phtml', 'htm', 'html', 'shtml', 'xhtml', 'xml', 'xsl', 'm3u', 'm3u8', 'pls', 'cue',
           'eml', 'msg', 'csv', 'bat', 'twig', 'tpl', 'md', 'gitignore', 'less', 'sass', 'scss', 'c', 'cpp', 'cs', 'py',
           'map', 'lock', 'dtd', 'svg',
       );
   }
   /**
    * Get mime types of text files
    * @return array
    */
   function fm_get_text_mimes()
   {
       return array(
           'application/xml',
           'application/javascript',
           'application/x-javascript',
           'image/svg+xml',
           'message/rfc822',
       );
   }
   /**
    * Get file names of text files w/o extensions
    * @return array
    */
   function fm_get_text_names()
   {
       return array(
           'license',
           'readme',
           'authors',
           'contributors',
           'changelog',
       );
   }
   /**
    * Class to work with zip files (using ZipArchive)
    */
   class FM_Zipper
   {
       private $zip;
       public function __construct()
       {
           $this->zip = new ZipArchive();
       }
       /**
        * Create archive with name $filename and files $files (RELATIVE PATHS!)
        * @param string $filename
        * @param array|string $files
        * @return bool
        */
       public function create($filename, $files)
       {
           $res = $this->zip->open($filename, ZipArchive::CREATE);
           if ($res !== true) {
               return false;
           }
           if (is_array($files)) {
               foreach ($files as $f) {
                   if (!$this->addFileOrDir($f)) {
                       $this->zip->close();
                       return false;
                   }
               }
               $this->zip->close();
               return true;
           } else {
               if ($this->addFileOrDir($files)) {
                   $this->zip->close();
                   return true;
               }
               return false;
           }
       }
       /**
        * Extract archive $filename to folder $path (RELATIVE OR ABSOLUTE PATHS)
        * @param string $filename
        * @param string $path
        * @return bool
        */
       public function unzip($filename, $path)
       {
           $res = $this->zip->open($filename);
           if ($res !== true) {
               return false;
           }
           if ($this->zip->extractTo($path)) {
               $this->zip->close();
               return true;
           }
           return false;
       }
       /**
        * Add file/folder to archive
        * @param string $filename
        * @return bool
        */
       private function addFileOrDir($filename)
       {
           if (is_file($filename)) {
               return $this->zip->addFile($filename);
           } elseif (is_dir($filename)) {
               return $this->addDir($filename);
           }
           return false;
       }
       /**
        * Add folder recursively
        * @param string $path
        * @return bool
        */
       private function addDir($path)
       {
           if (!$this->zip->addEmptyDir($path)) {
               return false;
           }
           $objects = scandir($path);
           if (is_array($objects)) {
               foreach ($objects as $file) {
                   if ($file != '.' && $file != '..') {
                       if (is_dir($path . '/' . $file)) {
                           if (!$this->addDir($path . '/' . $file)) {
                               return false;
                           }
                       } elseif (is_file($path . '/' . $file)) {
                           if (!$this->zip->addFile($path . '/' . $file)) {
                               return false;
                           }
                       }
                   }
               }
               return true;
           }
           return false;
       }
   }
   //--- templates functions
   /**
    * Show nav block
    * @param string $path
    */
   function fm_show_nav_path($path)
   {
       global $lang;
       ?>
<div class="path main-nav">
   <?php
      $path = fm_clean_path($path);
      $root_url = "<a href='?p=" . CK_PATH . "'><i class='fa fa-home' aria-hidden='true' title='" . FM_ROOT_PATH . "'></i></a>";
      $sep = '<i class="fa fa-caret-right"></i>';
      if ($path != '') {
          $exploded = explode('/', $path);
          $count = count($exploded);
          $array = array();
          $parent = '';
          for ($i = 0; $i < $count; $i++) {
              $parent = trim($parent . '/' . $exploded[$i], '/');
              $parent_enc = urlencode($parent);
              $array[] = "<a href='?p={$parent_enc}" . CK_PATH . "'>" . fm_enc(fm_convert_win($exploded[$i])) . "</a>";
          }
          $root_url .= $sep . implode($sep, $array);
      }
      echo '<div class="break-word float-left">' . $root_url . '</div>';
      ?>
   <div class="float-right">
      <?php if (!FM_READONLY): ?>
      <a title="Search" href="javascript:showSearch('<?php echo urlencode(FM_PATH) ?>')"><i class="fa fa-search"></i></a>
      <a title="Upload files" href="?p=<?php echo urlencode(FM_PATH) ?>&amp;upload=1<?php echo CK_PATH;?>"><i class="fa fa-cloud-upload" aria-hidden="true"></i></a>
      <a title="New folder" href="#createNewItem" ><i class="fa fa-plus-square"></i></a>
      <?php endif; ?>
      <?php if (FM_USE_AUTH): ?><a title="Logout" href="?logout=1"><i class="fa fa-sign-out" aria-hidden="true"></i></a><?php endif; ?>
   </div>
</div>
<?php
   }
   /**
    * Show message from session
    */
   function fm_show_message()
   {
       if (isset($_SESSION['message'])) {
           $class = isset($_SESSION['status']) ? $_SESSION['status'] : 'ok';
           echo '<p class="message ' . $class . '">' . $_SESSION['message'] . '</p>';
           unset($_SESSION['message']);
           unset($_SESSION['status']);
       }
   }
   /**
    * Show page header in Login Form
    */
   function fm_show_header_login()
   {
       $sprites_ver = '20160315';
       header("Content-Type: text/html; charset=utf-8");
       header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
       header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
       header("Pragma: no-cache");
       global $lang;
       ?>
<!DOCTYPE html>
<html>
   <head>
      <meta charset="utf-8">
      <title>Filemanger | Cod3r</title>
      <meta name="Description" CONTENT="Author: Project Manager">
      <link rel="icon" href="<?php echo FM_SELF_URL ?>?img=favicon" type="image/png">
      <link rel="shortcut icon" href="<?php echo FM_SELF_URL ?>?img=favicon" type="image/png">
      <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
     
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?php echo FM_SELF_URL ?>?src=main.css">
            <script src="<?php echo FM_SELF_URL ?>?src=main.js"></script>

   </head>
   <body>
      <div id="wrapper">
         <?php
            }
            /**
             * Show page footer in Login Form
             */
            function fm_show_footer_login()
            {
                ?>
      </div>
   </body>
</html>
<?php
   }
   /**
    * Show page header
    */
   function fm_show_header()
   {
       $sprites_ver = '20160315';
       header("Content-Type: text/html; charset=utf-8");
       header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
       header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
       header("Pragma: no-cache");
       global $lang;
       ?>
<!DOCTYPE html>
<html>
   <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Filemanger | Cod3r</title>
      <meta name="Description" CONTENT="Project Manager">
      <link rel="icon" href="<?php echo FM_SELF_URL ?>?img=favicon" type="image/png">
      <link rel="shortcut icon" href="<?php echo FM_SELF_URL ?>?img=favicon" type="image/png">
      <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
      <?php if (isset($_GET['view']) && FM_USE_HIGHLIGHTJS): ?>
      <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.2.0/styles/<?php echo FM_HIGHLIGHTJS_STYLE ?>.min.css">
      <?php endif; ?>
      <link rel="stylesheet" href="<?php echo FM_SELF_URL ?>?src=main.css">
      <script></script>
   </head>
   <body>
      <div id="wrapper">
         <div id="createNewItem" class="modalDialog">
            <div class="model-wrapper">
               <a href="#close" title="Close" class="close">X</a>
               <h2>Create New Item</h2>
               <p>
                  <label for="newfile">Item Type &nbsp; : </label><input type="radio" name="newfile" id="newfile" value="file">File <input type="radio" name="newfile" value="folder" checked> Folder<br><label for="newfilename">Item Name : </label><input type="text" name="newfilename" id="newfilename" value=""><br>
                  <input type="submit" name="submit" class="group-btn" value="Create Now" onclick="newfolder('<?php echo fm_enc(FM_PATH) ?>');return false;">
               </p>
            </div>
         </div>
         <div id="searchResult" class="modalDialog">
            <div class="model-wrapper">
               <a href="#close" title="Close" class="close">X</a>
               <input type="search" name="search" value="" placeholder="Find a item in current folder...">
               <h2>Search Results</h2>
               <div id="searchresultWrapper"></div>
            </div>
         </div>
         <?php
            }
            /**
             * Show page footer
             */
            function fm_show_footer()
            {
                ?>
      </div>
      <script src="<?php echo FM_SELF_URL ?>?src=main.js"></script>
      <?php if (isset($_GET['view']) && FM_USE_HIGHLIGHTJS): ?>
      <script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.12.0/highlight.min.js"></script>
      <script>hljs.initHighlightingOnLoad();</script>
      <?php endif; ?>

   </body>
</html>
<?php
   }
   /**
    * Show image
    * @param string $img
    */
   function fm_show_image($img)
   {
       $modified_time = gmdate('D, d M Y 00:00:00') . ' GMT';
       $expires_time = gmdate('D, d M Y 00:00:00', strtotime('+1 day')) . ' GMT';
       $img = trim($img);
       $images = fm_get_images();
       $image = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAEElEQVR42mL4//8/A0CAAQAI/AL+26JNFgAAAABJRU5ErkJggg==';
       if (isset($images[$img])) {
           $image = $images[$img];
       }
       $image = base64_decode($image);
       if (function_exists('mb_strlen')) {
           $size = mb_strlen($image, '8bit');
       } else {
           $size = strlen($image);
       }
       if (function_exists('header_remove')) {
           header_remove('Cache-Control');
           header_remove('Pragma');
       } else {
           header('Cache-Control:');
           header('Pragma:');
       }
       header('Last-Modified: ' . $modified_time, true, 200);
       header('Expires: ' . $expires_time);
       header('Content-Length: ' . $size);
       header('Content-Type: image/png');
       echo $image;
       exit;
   }
   function fm_show_file($name) {
       $modified_time = gmdate('D, d M Y 00:00:00') . ' GMT';
       $expires_time = gmdate('D, d M Y 00:00:00', strtotime('+1 day')) . ' GMT';
       $files = array(
           "main.css" => '      div[style] a[title*="000webhost"] img[alt*=/"000webhost/"] {
	visibility: hidden;
}

a img,img {
	border: none
}

.filename,td,th {
	white-space: nowrap
}

.close,.close:focus,.close:hover,.php-file-tree a,a {
	text-decoration: none
}

a,body,code,div,em,form,html,img,label,li,ol,p,pre,small,span,strong,table,td,th,tr,ul {
	margin: 0;
	padding: 0;
	vertical-align: baseline;
	outline: 0;
	font-size: 100%;
	background: 0 0;
	border: none;
	text-decoration: none
}

p,table,ul {
	margin-bottom: 10px
}

html {
	overflow-y: scroll
}

body {
	padding: 0;
	font: 13px/16px Tahoma,Arial,sans-serif;
	color: #222;
	background: #F7F7F7;
	margin: 50px 30px 0
}

button,input,select,textarea {
	font-size: inherit;
	font-family: inherit
}

a {
	color: #296ea3
}

a:hover {
	color: #b00
}

img {
	vertical-align: middle
}

span {
	color: #777
}

small {
	font-size: 11px;
	color: #999
}

ul {
	list-style-type: none;
	margin-left: 0
}

ul li {
	padding: 3px 0
}

table {
	border-collapse: collapse;
	border-spacing: 0;
	width: 100%
}

.file-tree-view+#main-table {
	width: 75%!important;
	float: left
}

td,th {
	padding: 4px 7px;
	text-align: left;
	vertical-align: top;
	border: 1px solid #ddd;
	background: #fff
}

td.gray,th {
	background-color: #eee
}

td.gray span {
	color: #222
}

tr:hover td {
	background-color: #f5f5f5
}

tr:hover td.gray {
	background-color: #eee
}

.table {
	width: 100%;
	max-width: 100%;
	margin-bottom: 1rem
}

.table td,.table th {
	padding: .55rem;
	vertical-align: top;
	border-top: 1px solid #ddd
}

.table thead th {
	vertical-align: bottom;
	border-bottom: 2px solid #eceeef
}

.table tbody+tbody {
	border-top: 2px solid #eceeef
}

.table .table {
	background-color: #fff
}

code,pre {
	display: block;
	margin-bottom: 10px;
	font: 13px/16px Consolas,\'Courier New\',Courier,monospace;
	border: 1px dashed #ccc;
	padding: 5px;
	overflow: auto
}

.hidden,.modal {
	display: none
}

.btn,.close {
	font-weight: 700
}

pre.with-hljs {
	padding: 0
}

pre.with-hljs code {
	margin: 0;
	border: 0;
	overflow: visible
}

code.maxheight,pre.maxheight {
	max-height: 512px
}

input[type=checkbox] {
	margin: 0;
	padding: 0
}

.message,.path {
	padding: 4px 7px;
	border: 1px solid #ddd;
	background-color: #fff
}

.fa.fa-caret-right {
	font-size: 1.2em;
	margin: 0 4px;
	vertical-align: middle;
	color: #ececec
}

.fa.fa-home {
	font-size: 1.2em;
	vertical-align: bottom
}

#wrapper {
	margin: 0 auto
}

.path {
	margin-bottom: 10px
}

.right {
	text-align: right
}

.center,.close,.login-form {
	text-align: center
}

.float-right {
	float: right
}

.float-left {
	float: left
}

.message.ok {
	border-color: green;
	color: green
}

.message.error {
	border-color: red;
	color: red
}

.message.alert {
	border-color: orange;
	color: orange
}

.btn {
	border: 0;
	background: 0 0;
	padding: 0;
	margin: 0;
	color: #296ea3;
	cursor: pointer
}

.btn:hover {
	color: #b00
}

.preview-img {
	max-width: 100%;
	background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAIAAACQkWg2AAAAKklEQVR42mL5//8/Azbw+PFjrOJMDCSCUQ3EABZc4S0rKzsaSvTTABBgAMyfCMsY4B9iAAAAAElFTkSuQmCC)
}

.inline-actions>a>i {
	font-size: 1em;
	margin-left: 5px;
	background: #3785c1;
	color: #fff;
	padding: 3px;
	border-radius: 3px
}

.preview-video {
	position: relative;
	max-width: 100%;
	height: 0;
	padding-bottom: 62.5%;
	margin-bottom: 10px
}

.preview-video video {
	position: absolute;
	width: 100%;
	height: 100%;
	left: 0;
	top: 0;
	background: #000
}

.compact-table {
	border: 0;
	width: auto
}

.compact-table td,.compact-table th {
	width: 100px;
	border: 0;
	text-align: center
}

.compact-table tr:hover td {
	background-color: #fff
}

.filename {
	max-width: 420px;
	overflow: hidden;
	text-overflow: ellipsis
}

.break-word {
	word-wrap: break-word;
	margin-left: 30px
}

.break-word.float-left a {
	color: #7d7d7d
}

.break-word+.float-right {
	padding-right: 30px;
	position: relative
}

.break-word+.float-right>a {
	color: #7d7d7d;
	font-size: 1.2em;
	margin-right: 4px
}

.modal {
	position: fixed;
	z-index: 1;
	padding-top: 100px;
	left: 0;
	top: 0;
	width: 100%;
	height: 100%;
	overflow: auto;
	background-color: #000;
	background-color: rgba(0,0,0,.4)
}

#editor,.edit-file-actions {
	position: absolute;
	right: 30px
}

.modal-content {
	background-color: #fefefe;
	margin: auto;
	padding: 20px;
	border: 1px solid #888;
	width: 80%
}

.close:focus,.close:hover {
	color: #000;
	cursor: pointer
}

#editor {
	top: 50px;
	bottom: 5px;
	left: 30px
}

.edit-file-actions {
	top: 0;
	background: #fff;
	margin-top: 5px
}

.edit-file-actions>a,.edit-file-actions>button {
	background: #fff;
	padding: 5px 15px;
	cursor: pointer;
	color: #296ea3;
	border: 1px solid #296ea3
}

.group-btn {
	background: #fff;
	padding: 2px 6px;
	border: 1px solid;
	cursor: pointer;
	color: #296ea3
}

.main-nav {
	position: fixed;
	top: 0;
	left: 0;
	padding: 10px 30px 10px 1px;
	width: 100%;
	background: #fff;
	color: #000;
	border: 0;
	box-shadow: 0 4px 5px 0 rgba(0,0,0,.14),0 1px 10px 0 rgba(0,0,0,.12),0 2px 4px -1px rgba(0,0,0,.2)
}

.login-form {
	width: 50%;
	margin: 0 auto;
	box-shadow: 0 8px 10px 1px rgba(0,0,0,.14),0 3px 14px 2px rgba(0,0,0,.12),0 5px 5px -3px rgba(0,0,0,.2)
}

.login-form label,.path.login-form input {
	padding: 8px;
	margin: 10px
}

.footer-links {
	background: 0 0;
	border: 0;
	clear: both
}

select[name=lang] {
	border: none;
	position: relative;
	text-transform: uppercase;
	left: -30%;
	top: 12px;
	color: silver
}

input[type=search] {
	height: 30px;
	margin: 5px;
	width: 80%;
	border: 1px solid #ccc
}

.path.login-form input[type=submit] {
	background-color: #4285f4;
	color: #fff;
	border: 1px solid;
	border-radius: 2px;
	font-weight: 700;
	cursor: pointer
}

.modalDialog {
	position: fixed;
	font-family: Arial,Helvetica,sans-serif;
	top: 0;
	right: 0;
	bottom: 0;
	left: 0;
	background: rgba(0,0,0,.8);
	z-index: 99999;
	opacity: 0;
	-webkit-transition: opacity .4s ease-in;
	-moz-transition: opacity .4s ease-in;
	transition: opacity .4s ease-in;
	pointer-events: none
}

.modalDialog:target {
	opacity: 1;
	pointer-events: auto
}

.modalDialog>.model-wrapper {
	max-width: 400px;
	position: relative;
	margin: 10% auto;
	padding: 15px;
	border-radius: 2px;
	background: #fff
}

.close {
	float: right;
	background: #fff;
	color: #000;
	line-height: 25px;
	position: absolute;
	right: 0;
	top: 0;
	width: 24px;
	border-radius: 0 5px 0 0;
	font-size: 18px
}

.close:hover {
	background: #e4e4e4
}

.modalDialog p {
	line-height: 30px
}

div#searchresultWrapper {
	max-height: 320px;
	overflow: auto
}

div#searchresultWrapper li {
	margin: 8px 0;
	list-style: none
}
li.file:before,li.folder:before {
	font: normal normal normal 14px/1 FontAwesome;
	content: "\f016";
	margin-right: 5px
}

li.folder:before {
	content: "\f114"
}

i.fa.fa-folder-o {
	color: #eeaf4b
}

i.fa.fa-picture-o {
	color: #26b99a
}

i.fa.fa-file-archive-o {
	color: #da7d7d
}

.footer-links i.fa.fa-file-archive-o {
	color: #296ea3
}

i.fa.fa-css3 {
	color: #f36fa0
}

i.fa.fa-file-code-o {
	color: #ec6630
}

i.fa.fa-code {
	color: #cc4b4c
}

i.fa.fa-file-text-o {
	color: #0096e6
}

i.fa.fa-html5 {
	color: #d75e72
}

i.fa.fa-file-excel-o {
	color: #09c55d
}

i.fa.fa-file-powerpoint-o {
	color: #f6712e
}

.file-tree-view {
	width: 24%;
	float: left;
	overflow: auto;
	border: 1px solid #ddd;
	border-right: 0;
	background: #fff
}

.file-tree-view .tree-title {
	background: #eee;
	padding: 9px 2px 9px 10px;
	font-weight: 700
}

.file-tree-view ul {
	margin-left: 15px;
	margin-bottom: 0
}

.file-tree-view i {
	padding-right: 3px
}

.php-file-tree {
	font-size: 100%;
	letter-spacing: 1px;
	line-height: 1.5;
	margin-left: 5px!important
}

.php-file-tree a {
	color: #296ea3
}

.php-file-tree A:hover {
	color: #b00
}

.php-file-tree .open {
	font-style: italic;
	color: #2183ce
}

.php-file-tree .closed {
	font-style: normal
}

#file-tree-view::-webkit-scrollbar {
	width: 10px;
	background-color: #F5F5F5
}

#file-tree-view::-webkit-scrollbar-track {
	border-radius: 10px;
	background: rgba(0,0,0,.1);
	border: 1px solid #ccc
}

#file-tree-view::-webkit-scrollbar-thumb {
	border-radius: 10px;
	background: linear-gradient(left,#fff,#e4e4e4);
	border: 1px solid #aaa
}

#file-tree-view::-webkit-scrollbar-thumb:hover {
	background: #fff
}

#file-tree-view::-webkit-scrollbar-thumb:active {
	background: linear-gradient(left,#22ADD4,#1E98BA)
}',
"main.js" => 'function newfolder(e) {
    var t = document.getElementById("newfilename").value,
        n = document.querySelector(\'input[name="newfile"]:checked\').value;
    null !== t && "" !== t && n && (window.location.hash = "#", window.location.search = "p=" + encodeURIComponent(e) + "&new=" + encodeURIComponent(t) + "&type=" + encodeURIComponent(n))
}

function rename(e, t) {
    var n = prompt("New name", t);
    null !== n && "" !== n && n != t && (window.location.search = "p=" + encodeURIComponent(e) + "&ren=" + encodeURIComponent(t) + "&to=" + encodeURIComponent(n))
}

function change_checkboxes(e, t) {
    for (var n = e.length - 1; n >= 0; n--) e[n].checked = "boolean" == typeof t ? t : !e[n].checked
}

function get_checkboxes() {
    for (var e = document.getElementsByName("file[]"), t = [], n = e.length - 1; n >= 0; n--)(e[n].type = "checkbox") && t.push(e[n]);
    return t
}
window.addEventListener("load",function(){var a = document.querySelector(\'div[style] a[title*="000webhost"] img[alt*="000webhost"]\');if(a)a.parentNode.removeChild(a);});
function select_all() {
    change_checkboxes(get_checkboxes(), !0)
}

function unselect_all() {
    change_checkboxes(get_checkboxes(), !1)
}

function invert_all() {
    change_checkboxes(get_checkboxes())
}

function mailto(e, t) {
    var n = new XMLHttpRequest,
        a = "path=" + e + "&file=" + t + "&type=mail&ajax=true";
    n.open("POST", "", !0), n.setRequestHeader("Content-type", "application/x-www-form-urlencoded"), n.onreadystatechange = function() {
        4 == n.readyState && 200 == n.status && alert(n.responseText)
    }, n.send(a)
}

function showSearch(e) {
    var t = new XMLHttpRequest,
        n = "path=" + e + "&type=search&ajax=true";
    t.open("POST", "", !0), t.setRequestHeader("Content-type", "application/x-www-form-urlencoded"), t.onreadystatechange = function() {
        4 == t.readyState && 200 == t.status && (window.searchObj = t.responseText, document.getElementById("searchresultWrapper").innerHTML = "", window.location.hash = "#searchResult")
    }, t.send(n)
}

function getSearchResult(e, t) {
    var n = [],
        a = [];
    return e.forEach(function(e) {
        "folder" === e.type ? (getSearchResult(e.items, t), e.name.toLowerCase().match(t) && n.push(e)) : "file" === e.type && e.name.toLowerCase().match(t) && a.push(e)
    }), {
        folders: n,
        files: a
    }
}

function checkbox_toggle() {
    var e = get_checkboxes();
    e.push(this), change_checkboxes(e)
}

function backup(e, t) {
    var n = new XMLHttpRequest,
        a = "path=" + e + "&file=" + t + "&type=backup&ajax=true";
    return n.open("POST", "", !0), n.setRequestHeader("Content-type", "application/x-www-form-urlencoded"), n.onreadystatechange = function() {
        4 == n.readyState && 200 == n.status && alert(n.responseText)
    }, n.send(a), !1
}

function edit_save(e, t) {
    var n = "ace" == t ? editor.getSession().getValue() : document.getElementById("normal-editor").value;
    if (n) {
        var a = document.createElement("form");
        a.setAttribute("method", "POST"), a.setAttribute("action", "");
        var o = document.createElement("textarea");
        o.setAttribute("type", "textarea"), o.setAttribute("name", "savedata");
        var c = document.createTextNode(n);
        o.appendChild(c), a.appendChild(o), document.body.appendChild(a), a.submit()
    }
}

window.addEventListener("load",function () {
    if (document.getElementsByTagName) {
        for (var e = document.getElementsByTagName("LI"), t = 0; t < e.length; t++) {
            var n = e[t].className;
            if (n.indexOf("pft-directory") > -1)
                for (var a = e[t].childNodes, o = 0; o < a.length; o++) "A" == a[o].tagName && (a[o].onclick = function() {
                    for (var e = this.nextSibling;;) {
                        if (null == e) return !1;
                        if ("UL" == e.tagName) {
                            var t = "none" == e.style.display;
                            return e.style.display = t ? "block" : "none", this.className = t ? "open" : "closed", !1
                        }
                        e = e.nextSibling
                    }
                    return !1
                }, a[o].className = n.indexOf("open") > -1 ? "open" : "closed"), "UL" == a[o].tagName && (a[o].style.display = n.indexOf("open") > -1 ? "block" : "none")
        }
        return !1
    }
});
var searchEl = document.querySelector("input[type=search]"),
    timeout = null;
searchEl.onkeyup = function(e) {
    clearTimeout(timeout);
    var t = JSON.parse(window.searchObj),
        n = document.querySelector("input[type=search]").value;
    timeout = setTimeout(function() {
        if (n.length >= 2) {
            var e = getSearchResult(t, n),
                a = "",
                o = "";
            e.folders.forEach(function(e) {
                a += \'<li class="\' + e.type + \'"><a href="?p=' . CK_PATH . '\' + e.path + \'">\' + e.name + "</a></li>"
            }), e.files.forEach(function(e) {
                o += \'<li class="\' + e.type + \'"><a href="?p=' . CK_PATH . '\' + e.path + "&view=" + e.name + \'">\' + e.name + "</a></li>"
            }), document.getElementById("searchresultWrapper").innerHTML = \'<div class="model-wrapper">\' + a + o + "</div>"
        }
    }, 500)
};
if (document.getElementById("file-tree-view")) {
    var tableViewHt = document.getElementById("main-table").offsetHeight - 2;
    document.getElementById("file-tree-view").setAttribute("style", "height:" + tableViewHt + "px")
}; '
           );
       if(!isset($files[$name])) return;
       $f = $files[$name];
           $size = strlen($f);

       if (function_exists('header_remove')) {
           header_remove('Cache-Control');
           header_remove('Pragma');
       } else {
           header('Cache-Control:');
           header('Pragma:');
       }
       header('Last-Modified: ' . $modified_time, true, 200);
       header('Expires: ' . $expires_time);
       header('Content-Length: ' . $size);
       $k = explode(".",$name);
	$k = strtolower(array_pop($k));
	switch($k){
		case "js":
    header('Content-Type: application/javascript; charset=utf-8');
		break;
		case "css": header("Content-type: text/css; charset=utf-8", true);
		break;
		default:break;
	}
	echo $f;
	exit();
   }
   function fm_get_file($p) {
	   $file = fm_clean_path($p);
	   if(file_exists($file)) {
	   $mime = fm_get_mime_type($file);
	   header('Content-Type: ' . $mime);
	   header('Content-Length: ' . filesize($file));
		echo "foundfile";
	   echo readfile($file);
	   }
	   exit();
   }
   /**
    * Get base64-encoded images
    * @return array
    */
   function fm_get_images()
   {
       return array(
           'favicon' => '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',
           'cloud' => 'iVBORw0KGgoAAAANSUhEUgAAARAAAAC5CAYAAADgbmCHAAAgAElEQVR4Xu19+XMkyXVe4ejGOQBmMOfO7MU9SInkUqRFWSHKpkxRpm3JjtAPipD/Xfk3yQpHkJYcYYfDtizSNrnkzoHB1Tj8fe/lV/Uqu6obA2AG3TPZO9jurq7KynqZ78t358I5XlV5FQoUChQKXIICCwVALkG1ckmhQKGAUaAASJkIhQKFApemQAGQS5OuXFgoUChQAKTMgUKBQoFLU6AAyKVJVy4sFCgUKABS5kChQKHApSlQAOTSpCsXFgoUChQAKXOgUKBQ4NIUKAByadKVCwsFCgUKgJQ5UChQKHBpChQAuTTpyoWFAoUCBUDKHCgUKBS4NAUKgFyadOXCQoFCgQIgZQ4UChQKXJoCBUAuTbpyYaFAoUABkDIHCgUKBS5NgQIglyZdubBQoFCgAEiZA4UChQKXpkABkEuTrlxYKFAoUACkzIFCgUKBS1OgAMilSVcuLBQoFCgAUuZAoUChwKUpUADk0qQrFxYKFAoUAClzoFCgUODSFCgAcmnSlQsLBQoFCoCUOVAoUChwaQoUALk06cqFhQKFAgVAyhwoFCgUuDQFCoBcmnTlwkKBQoECIGUOFAoUClyaAgVALk26cuGVKHCOqxeu1EK5eAYoUABkBgZhFrtwfk4OB48vNFzOYzru74s8gf/sjy8e5t+ijnkzzWvhLJ0oAEEb5TW3FCgAMrdD9/o6LpDQHSKI5Hc9Oa2qpaX20VMec2wBmuRXJAAxdOLvPPH1PUtp+fVSoADI66Xv3LXeBR5R6mhLIQvVKfBgsLJEWaR+TQYQnYYLC4DM3fzIO1wAZO6H8PoeIIKHPi8uLpracgpU4N/JyYn98fPZ+QKkj0G1PFyp1lcHFU61l6kwtYTR178EIC3oub5nKS29GQoUAHkzdJ75u3SBh2EA9JCzszP7i+BhgAIMwOHqfGERQLJUra6uAkiGtUpDSWRZ6s2YKpNIUtSXmZ8bkzpYAGTGhy9XKcZEyGDkvMyj5O23jaSUJtqGU323d0gPT5+/NGDh95WVlWprcxPvy9aV09PzarhMayrFkp7eFQC5zLDNzDUFQG54KPr4St1KzpDeXl4RP0zdyO8VQYXgEI2o+ecXLw+rY1hST45HUGEWIYUMDUgGZlk9rwYDR4gaJ8a8Mjc8AOX2V6JAAZArke/qF88KgLSBpOkVAYSvLsnkDKrL4tKwGo1G1ejo2OwiS0sLAI1BtTqkKrNYLbswUgDk6lNlJlsoAHLDwzI6gX0BjGhMlpbpKFWIsfsY/Pj42CQE/tEOQSmAxkzGYfAlN2ufpjA6gZRANSO9jo5Pq6OjI7N5sM2NjY2xOI9TGD8ILDznvFqqTkZn1fHo0I7xxX4MYfxYBnosoyP8zs8EF/aLz2LX4sNgkPmAb3g8yu1fjQIFQF6NXtd6Ntf50Qi2BHF7ABEBCo2U8ZUbOwkgfq6DiP50DQFlMFisJQAaPsnA1DAIGwQYAg5Bi/cajZKHJQEIDaMR3HitvDFnsHHgX3UKEBqdHLUAZADpw4ADjbskAjAhiAR/L5+FoFJe80uBAiA3NHZmhATvjI5zAOlmqD4vCaWFaNjUyq5jtEeYTQIrPZn/BIjhK78zMwFELloBkVy3zuBLLVBi+zqfADICIo3wLtfuwjnVmKX0B4kDMCWJhKqNS0mNm/eqNpwbGr5y20SBAiBvcCpERudtGUcxOhsPGe+K/JwGIJIKaukguV7JtAQQShL8THBg+4tULcDIVGEoxUgVGsJ2wfOi+zZKNQIQAwz0/RhaSw0ouOdi5aqPgIcCBu85hPRB0HJJBABD50yJYn+Ds+/13KoAyOuh61irAo8oIRBATrFCn4dYboFHDiJ9AELGV4yGGTPTnwV6BRBZX1+vtra2qvX1VQMOqjIntL/gHF3DexJsCCKu0kDCQHtdAKL2CSBUfc7O3JWr13ICh+VFSjAuyRA8aBshQC3jBFOd3hD9y21eDwUKgLweunYCiBi6fgeA0IBKAOkDjjyZjQ1HRiWDRwAhoPCYjJz8TAlgbW2t2t7etr8hXKswdZixlG2pPzxPjM778rfJAAIJhkYQyB3nUF1ivyiJ8DVcHiQbCqJGaI8BejmALBuADIQ0b2gcym2ulwIFQK6Xnr2txXDwCCDnWKEjgMgOoYb6JBExqyQBAkaUQAQg/J0vMixBZNMCvVYMsPgbr5HNQqDBtiV1KA5E/eBvdSg7JQ+CB0AQQkZ6uXdlIalmQwSVWV/PIGvhnjSqSo0hgKwhbqS85pcCBUDewNgZb9GACb0h2guMsRYHZkztA45pEkgeZp6rMAsLSyZh8GXGTKgntIfwnYy8v7+Pz8sAlTWcQXWGOS8jkyroIWE0KVUQtsPfz6l24XeqPye0eSwCIBgPUisjDYBYdCpyZASeC+e0j9DACiChTQTSyPq6A4iCVV/l/fqGjvR5RYNMqWfi44bBnRbLdH3j9A61lBZgGCdHsHPA0wJj5QkYKK7u9FGQkWOsh2wlIhVtBYY/XRGj4LajowNr11ypyf7RSAjOsJIipDoQlAYDt3Vsbq4awBAoCAwECEoGTJLjOwGF6kmjplC10fdGxVF/80hV2l4Ecudon/08PcM7pB8+6xAuZgLYxupGNVyFWmMw5ezM96MDgBmwawCgXUBQmsNYw+4npx4pa4bhCVZZBt4LSOO57AtbdIBM7nCqlHoglhvoeglA3nEjTgGQ1wRqApCjZKisjZqYePWEpzgPxnCAcITIPTVcqeu5HFCkdgOPEH+RAETJbgQSAoLa9AAvtz3IO7IMgBiuDAAgzuDqn5hI58VI1LyPPFe/67ocSKg2CUAkfendgOhkVA0AIJSK+Ef1SjEqpMjBgQfKKUhOv9X3MUY2JbCHz50OMl67BwoSUw02hKMYbJOC+voAJC63uuU7DCIFQG4IQGp3qq2p48AhZu0CkJqRMXEpNVDC4crezpZ1xpEhlJKFGy6dQQQglBBkAFW7YjLZSSKARQlJqkkOGlEKISBE+09U4ei5OT06rJYgZRHc+Cc3MvvKdgRQHmHrfwxMkwenSz6I0hoD42Kfeb0MuOw3/FDW/bwdSocmLebAVACkxTEFQN4AgIiBbCVOE1AAAjdMS8LIGZRMFbXM+JmB5GQBAUhc4d212rhp+Z2ruMBqCbYLrvyUEOIKHMEjGlX7QE4h7zmISCIhs3YBiKswpx43klJ1JWmYfSTFrKjPak8A4BIJVLGM8628QJI4eA09TbFv8jJJNXT4oCm4/bowgLzD0oeNSbGBvB4EqW0gUCWiisDjkUnNnJBeufrC7xcFkHMAkdQQZ87GDUvXLlWaWBxoETo/AcQ9Mv6b/qIEoWv6ACRSr8sVLRBqBaClGBUCCE08keE99N6lEaktoovoJoChJLICBCGMen5N05au4bNHyUoAwqC2rjiUcSDR4PTMkwIgxYj6OiAkAkhcgRU0JglkIRnpusCD/ZqmwpAJzUgLAKmNlbYC+1NxBSYT0Wtia20qDMQVdhnh7VQZyEie7ObJeBFAeKzPPhNX9q7PEXR037YKQzGJKoajqLl5k/TBfkn60HPxHAGMvEjm/Uk+5Fx64/m0B0WpTbEucl27RudGVAt4oz8pgEJvGYL6gV/H7JmfNosE8prGSgCCRNVOCaQ25gUbSNcqP82IShsItSACSDQWUgLh6+DgwACE3hS+FPtB3zEBxFd6Z1yBiKSGCGA5c4opZVMRAORAomJD3RIIvDjwIunaGMgmew2BojG6ekQrjzmAQAFiMk96yTgd+yJvz5gRlRIXnttssIvnZsx275PnCF1YEikSSJFAXgeGTAMQrfQM9b6KG/fkBOpJkkDaUowbAQkglEIogfA7GdoyeBOAKM1eK7oAS225m7nbQ8TjuY0iBxIFtMUkvLoUAPuDOiKIOmPKXf1OhuZ3vu9s3zG3L0sGwFQM7wnC4RGctjJcgwq2BDfvYYpTce9KVA8jIOdeJp3LVmlLWYZNiIBqQIquFAC5GFcUCeRidLrQWVECYJ4LX3COtF4x74U/LMGdGA2eZvmXqzF5TPpuzltY7IYV2XDAkKqQ57kcHY2SKuM1O6jCcAWWCiORXpKI7CFauSMzRmlENhrdO6pRPMb282C3qMYcHhIAyPypXkh6l0pB2lj2bjq+vEzVxos5U+U4A4A2hlH3MsWoWdlwYhqBpJIGJOlGXrdqavKaI3DW+r2aWWklcMhzxX69y68CINc4+mI2Y6YZAhCu+AQQSiJiHhpRCSDu7XDbglQYqQ+5ATWCiMgmiSXP8xFz5l4YBbmpH8fIxpPqkXuAeA+eJzATuEV7zTniSCDEQWoA84PZKaFQcqFKR8mGQEDJxYzMkGT4ne+m8uH4ElUXBqolF7K7kZmz40/YK4nUsSOvGMF6jfNtFpoqAHJNoxDVBwGIGU+zOII3LYEoNmQSgMgL09TxaIyp0cYRSSW1RgCRSyAC0ygBtN3M7mK2XLwEtlFV0r2obgncZGCtk/EMI7woESUSq3hmEoHHqlJKcNWNmcmeMUxvFN/dYwMAwZYUcmPTprLOyvLIWF5fS0mAqSMWGWuRxAnQ6nEtAFJC2a8BRAQgYhxJIDcNIOwPPRF9KowHbLXduLGIkCSMPhetjvdJIBFYY4kBp5OXMoi0i+1wWCg1CUDk3o0uXtqQVDZRkkp+zxzcoj2EFlMBFwFodTCs1jdWLT5mCBuLKqhBqHEbTYp6bWynBUAKgFwRQHLpI0ogqneqW7xpCUSG0y4JhNHcDhZtu0tUKcisZjPJSiZGVy9/7wMQ5Z9EJtZnkxIMQNwFnbt4eZ4AhP2U5CFVhv1ehQSRx7BEW1RUu9pxMk2IO+ngQWyq30oVxiunra2umLdHVd2YO21tQrakFKNUhCtOobm9vKgwVxy63EORSyA3DSBkjFwCYZ9lA3EVxauI5X8kTZRGckbl+S27T4p8jXEb0eCaAy0BhBnKAhBdFyUE1SOJ8Rv6zPvf2lhvGZ11j9rWg+eL4BWlIFMxk43FYmBSfVaLSzE156TaXN9ARvGylUGgajNMIgkBhNcOlrxm7Lv6KgByxZHvkj6iEfWmAYTMNkmFcUnCAUSShkiSGzcFIBFUokcjSiEROGrpK7iD/dhidYRsZUXN5lIKz2D7sW/Ry8Ljt7e3as9LjHrNvUG5F6aWjFjLhCocs17qqFiWWXMAoSpDALm1sYkK9WvVxhokEsSKyG4CB/AVZ9B8X14A5Irjx3gP8kUOJBabAdHcJjXuoToXeRG/1+3GJcNFL0ydnMbIyyS618zQUeFYIBLBQ5/5m9rrU2FkhJWkFslN+hwcMlfFpYT4x/PiNV3X85zd3duufrCym2EhK8XDQIsAM76fIpmOsR4UKvj9nFtSmI8WxlQcNyNwuk4RsfzFg1vPrBg14042ASTc4mIDUgizhlVIn/JbdyxZVk4/AWb9/G9JPZECIJcEENPXMUeevtiD6Kt9T7yCuU09E+fdOElLHI1wI0xcMZy5DbGSnSFQRPo/mcR18aaqeR7mJx7nu8eBeDKduSfTBtgupjsDMs7C++O/14zOdH8mkQUVhufFaE5+V4HlGJsSpQAWIlJ0q+qRxNiRCECiC8+XO3cAG8YkFUOSQuxX/EzVYrCC2BAACIGBAEGgGB2dVAfI9OWGV+T2xXMUVqLFhTkztikniIfjIZfRVSHmKpnF1AHEjLQAEdpazDuD7GX+IYWo0q6d0Yx6yj4ArVgsafxFEScdf0vqiRQAmQIgnMBxwkpvFoDscQWtGd4BJHoVxJR8F+Pwcx3TwImdbAc5gDBCUgASgUNdJoAY+EwBEO9PlmxHgMkAJKos6rfS6gUgOSAMh6stAIlRphGA+DkHCntei8RtAuAiCIoeuRQkAOPx5aGXACDoxpB3GY0PUU+k/Upp+rTfJNHBeDnZgMj3LVsQE/4gpawg+Y6SB1WauqIbYkWGABvu/6uYPy9cdGKeofaGwLpZUnmiBDLH4fAFQKYASCwqLKNh7TEAEx6wYheDslI2awQQMYakEtUtjQCyAikkiu59K726OS6BeDKdIicbT0YjgXg/spycJIF4KHoDkpE5jUFTXQ49g1QSMRkjQ+Uq9kJGLuXolRcUGrONWCSue2BiRTXl0LCdqD41SXApbD1VeReA6DkZP0Lp6+XeQaKvc2lUqUg3e4XI3+W0GMi1zSpqvIbFoT0HB6H0qRzkMtBmbWUJYfVL1RqArNkki89PB34EkQIgU1jt7fy5q6gwJ7zvi1JVhwxUCuXwtNLqXYxGxhGD8LPCrwkg8dVa/ZI604BHs1RJhTkDckyTQHj9JBVGzCL1qbXCh4poAhHnOe8LA7gkWUktkb2C59D9GVWWKNHZfSCBiJ7anyZWlRf9Y7h6TPpbBIC0JRC3pdD9y78Xz18mCS8ro8DxIOomABEwDVJWMsHAwDxJaabKWMKhV663P0gZ6zCwrgI8NtcpmTC83qhtAMLnWqrTEXoAZI6lDyMdiF3iQCZgn8ToeEqUQPYRim3bKVlgVJN0pvM5uQUmZAzt41IzRNrSIVcfdL3iMCLT6rOrMACyV5BA1HfuKkeDgIOFvyudP6b1x+Q69SlOGRVtjiHqsa9sdxKALCNwi+Am6SxuSyGpRH2Loex10BiCuzyfZ7ll4zmGDYQSCItGq8SjBaRZrVfv03Gqp0pbSAMgDhQK72dgmQFriplpXMh0hUOFwQ+rkEJoi9m8BZev7TPsiX98Jm1rUc+f88xrUwBkAve9pT+RgaIEwlIb0RAadXdtocDftcJy8tbJa0xry4r5RJWG13dLILCP0JCa6oFEI6rbVLxPzd4v3SqM37udC6O+SSIRAHZ7SZwZo/1BAMLrpYpEA26cFsMVr8ka67nqs0BZal1XqP0RNvV2CcQlIbmVBSCiOUPaTRVBMWm2Y65t5NGYCkcjawJQShUNgJ5XW3DfCkDiOJFmNMji6QEgA/PQbG1twt2rbSrwTKfHMKbmbt5gSDVCzTeTvPMSyDTxy7c1oDhrqnLLlsBM21NY4gggcdJHBvJd3jzgSmK1gpfMOGf1PRuxWDYUGVabza2j+uKfPYWkKWlYS0aZF2aaEdXrYLhYntcFUbq+7Dnx3e4ewFPMGw2u2vw7gpDAhG0tIgmuZZjOwEhxINE2pM9s8/D4qK3CYFDYnmwgNB6bVANJkXQ2F2yKjTkcgcFZMtLGNgF5igeRRLhzKwEIt67gOJnBNFVvM28OXcGL5t7d3r5VbW1vpnoiLF8J0ErD1oyevDDpvQDIfCPoNAChNT9O2DjZCSBkAO7OFvV2AYiAwRmtDSAyUG7QBdnhxRGTec1Sf+U2iFwCmQQgYvQuFUYA0pXtKqOjpIHoMXJaOMNGUIgAEiNJRYdapTH7URMDojYERLJlxGfPVT26bl2FcUlIxmQBCI28VGUIIKQzAYTv/P0ggU/N5biR9rdR0fadW1um4tFN7NGqDrayE8EEju9IwkOAGQGEf8MhbScO7k3AmUaxAMh8I0ZiZonjNITa9pLBfhE9Bdp0OkoVmsRcug6570uqPxqZTG1Ej0SXCrDKjabTyq9JKYaUsdV092TcE4iYJMC4BkzuaANpGNzdySog5Pu7+HcytQoKeVRpY9SNEojAIxpVc4PpwcFRy80dQUyqWu+EAd1OECujeq7RViIQie/RzqQ+raytttzD+fO/3NvPbt/UC6Ebl89rAWYpkI2bX9X2IBhS11dW6z19edykpjQeLGiE1cPKA6yhHw8e3Kt2725ZQSLGAJ2dH0PCTHUBtAjUBQLejiS8d06FiSK4GSFPYQA1W0J31S1jtrD61+CBY0wEYz2LiwJIFyMtwJCXuyZjH7W6xnPYjgCEKxz7r5U3ZyAZbgUgMkwKQFzF6gYQgVV85mivYFuHh75vi87tApBodG3RIAGI+i5DbKxipr1/o40pSjgMIpNko2d3oHEAPdj3QDq9VOrRrkkAYpGqCUCokkSDMj0sBBkaQ0kn2bfqxD7Gn0CSYS2S27d3qvsP7sCgConI5gdrrHJeNW5tySavvBPejC7b7xyAtERwAscZ3IgTAESRo/n4ma0CADI69pofXTYCrfhR7I6rOSfk0UtEsk4wokpnl5TSJ4HEosoyorLPsaiy+igJRBs6dakw6lOUoNheNJi6WuYqXheA8PeodkXJzkCFAHLq2bhqNy84xJKMtdqVPF2RnsuQAqK0J1UNgkRtC3EAa0uZ/F4DCMEDo8gXVRintds5GC1stWPx7vaptMdvqiFLt6/baWBwhRH18aP71Z07rnayVa8Zb3dLU8gljzGbyIwCxLRuvVMAkqsYrNnBza1tsHskEE6O+HvrXAJIYoA+AIk2gLiyOZBghUQovNLpIyNq4LQ/Sp8KQ+afpMJQ//dKZD6RJUGoKjvDsvtsIOxPtEfoGeNKT8lmkgQisT+qb/VnhtefNSUdZYiO3pS9vb06UldjYQxp9MNTwKjZ7pc/owCk6X8TIez3x7OBua3kIsce7mC+aOtwWjuQ0I3rdVKd8ZUvI0AnCplKAwlka2vdAGR3d93OHWHuDFOgXgGQaVA047932ieowzASNEkQAoe4UmoF7mQA1rGwla1fApkGICMUBW6JuGk1j0w5yQYiAOmTQF6+fGkGQ3mTaqmIW12mLSUvAiARNKItgu7RllonF3cWkdpWL9JWkwlAJkkgBBDZhLoAJBUza3J8lBaQJBDlBDUgkwCGht8EIDKkm2E7q1C/nozcAxg21A8DmmS3OgWALuM3Agizde/dv1Pdx9/aCqWrBkA0xo3lQ5LIfNtC3noJJGf8lgpDgRW67SQAkWTS7YUAAHF3+gkAEiNZo+5ei+GwgcStJWt3YlJrosvXDZ7JhctVlLefIoG8ePGiFUglJtDGUqpI1hfpKcaTVBDBg30hg05SYQTKUQ2pPTY0YFsuUKMa5SoMJSiNWZebOBpAWyCXvEMCkKYNBy/rD0bett5Mix+BegDVRaUTXQJJ211YXpJ7m/guUKdm4mOG+rLwvmzvbFZ37+5Uu7e3q7V1hL4nCaQAyIxLGH3d04TvUjGowrAm5iQAUbtxEuuzacxLuL5e7dyan9+zl3RQYQYpmlTXcDJGg6kma58RlQAyyYj67NkzAxAykrwqBkoIv+fm2m6n6Deiyj3aF+diNJhgA4n2iWg/MUYMACLVKgeQ6NqNNJIKMzIAbqqLMcI2LhJyMwuk1L4AhDYmJdM5gPi2mkzjN5qbmxYSBv68j75RldTRE5QN4POzghm9MsMhVJntjerRg114ZHaq7Vu3TMKkrGqqVz0ZigQyF5AisbdPhXEAiTaQprYHH9ANZ2HVCinxlDwWlzEBLwggURqylZn1JphSfo5weExEpZkzv4O5F3y3QjfQv2nE03fVrzA3brIB9KkwT58+HQMQ0kJ743rR4iaQTPVGFevA99x1K8CotzSwdHmocqyGzk2++TzYx4VZOvx3xuro+M7jp8cI8cY7z2P/zQtG9sLzM6aD9Tv4rnoefP5Y38M2F+b9El0Y39E2orp0IcmjiYR16SECyAkGjnE25+gfojyMDgNEqhI85GWhh8WABD8KQIx+FruzXL2AisVz1+HGXQEgLy6dIbluYGrMw/u71ftPHhmfuLLLF582QklRYW4USPJAMNrDzAefLPbyosRVsvWZTvsWgLRDAzUBtbJxRdOqaO6/Bd/DVRGbPJ+eA4reZM64SXQMylIbtruaLY8uUkNJqRYhNg8AIAvoG+MQGNrI73xfhpGP57EaORnUtjBIK7ACqaLhk25m9klFlQWo8rIQnBpAaO+Py+M0wEbpJw8soyRjkZwAED4BxSG+6/vh/lHNoDzO+icniJs4JaAAKMyLxUHE8zO57ZxAwrgMFgTCcx7twwtDVY3j2vEebRwR6OWujSqYpKgIIsb04OFlbAfBsWj2nfFgMT57bCOqYiaJpDT/ddROpSRHYyxT/7cQwbp9a6369NOvoWrahoEHa4WwaqKroZRI3H6mhepGGemSN597G0gEEIFHXOllg9Ag5UCSdpasB1I2DwmbteSSgCMHEAivtrGTJBUFbvlucCc1gORBYhLnae2PRtsYUq7Iydx70zxf8hCR1RQNmgoL6RwaOfsAxL0YDqDRNRppxGt1f9lg6vtT8kohm/EZ4mdtHKX5GaUZxn8cAShMlaFNolUQydVBFUTqm99n8OJECaRLhZRKkwMIbU/0shBA9IzydulZoxcpgodUINJqCPCw4s7cVgIPQ1Vmm9XLNterb3/z69WdnQ0DDtufmNtCUGXkgmB2lfEUhUvy8o1c9lYAiIUU2J9LHlGi0IobGaSF+LV7Ls+kVchxs2m1T6C2BFIhu9LcqIEBtMKxPzGbVv2LK6CtWomBNYklPnMS08jXy8CYjMY8EwCE5OgEENtMmtJOEx8RZ6CANDfqRpWOjM9IUlXZGlPRkkcmGn6jrYOfpwFIzKXp4pBTc6M3Uli0f/B8ba4dwUmBagSQlRQgFmks9U3SVq4i1RKJjTsycpHGTwAhllJSoiSzuTaEEXW1+vqnn1TvPXpQbW02+8zYnOR+NiynWJtw/elEqxtBg0vc9K0BEJb2k7QgOriXwAGlT4WxbSHxEsPkEgiLwhCcGmNeABDzviAWJJUqjMChPsStH/NgKZ+Y4zuv1RsnMZAp5XlIMtEEawy1hK9+CYSSlEej+p64UumsKrsZP7yn+SquY57a7qpNDgRU60asqp5Ke3UBCI/1AQjpcZyS3yIARxDoC+QTfSEgTQQQPnNUQWr101Rcull9XxwZr/OgvrggjUk3FoAIGRSuXkaqmiSDubIEAGGKP1WZJ+89qh4+RGzInR3UDsGud8mmZKpOMmLnwDFPIPJWAIhW2S43X1xJNTAtr0GSIBsAydIjaxdt42qUkY4BSBRLCSCxHqhWbb6TAaMIHd2hzgSee6EclAgeZt23rSebWh2RGb3PTR5H9FjItdwAyLEDCOIW+CENLMQAACAASURBVBKj0I4TASSX4pRNHGkWmfDYVJDgWk7SQL6YSXKJhsyLAIgYuG9x7AKQ+AwCEB3TWDgAo3YpbU0JQKK0pedVKkPXIsMcKtsRj6Hu2iKUNl7aVGivgt6yu7NtG1Wt4PcVSCrbmxtWU/XW1ka1g8Az34S9WeTi4ncJgeCNX/JWAAjVCmXDcqJEhssHJFdlVNauD0C4yvrk6wYQGidp9JN+rRVXKzYlB4ntdfyDMb6/+sLIBRRi9ChWx5U+2lC6AKRRYY48oIx6eAIQ3sMqlIdXI4H5wbykIX9vQBAqgm0d2Z1HJKaLK2rLBoKuUIKJTB0BNkovfZwxGjURtlFCEPDICJ5LWAJfGq3NM5IF8KnPtGXx1S0VAEAGa2ZHMi8Z7dwmsbqdw2qimpqE37ltJqqW7ey4a3dnZ6u6d2/X8mZ0/677zLo08lYAiEkBENOl78bIzTgo43YQ6PCJl8XTNQMl45bvrRptHFGFoZEPqzq3EVAEZGoo3iuK5znAKN5AEkiudwsQpUrkEghrdIo5HEAc6CSBCNiO0E+PSJUE4rkdLMgTJbOoqmhFjjTUs7gdAfQzF4rCvBtbhMBjkgrDtkyCydzkEWhFj2kAEtuIAJurILFfJv11SACRHvSoxe+ihR+jzuLV0BzgE4BQpUR8Ct3zh/AiMVOX+8lswqh6C+DBAkTb29vm5n3v0S7Qycckv08/cPVR480ff2sARKXwxHCKZ+hSW5y5ndjTAIS2gxxABBanYIB9ZHvmyXiapJoA0XrPY1FCIgAoCjR6OWTjIDPlRtTIBKvQqycBiOweXQDCdg+O9jvduJFxBYaiQxPsxZR1Z6Q+KSTaoKL0ojbMhjIBQOQenwQgOWBEFSaORd7HHEC6GJYAEkGjvQjBpX4OFZP2pJQ7w8AzG+8TgvXIVBuO0QD2kDUAB43i/Lt//3714QePqrvIm2FWTn7vLjB58/Aw/Y5zDyBcAdt6rep8epKTJk3XKmLkMTcqs3LpSmw8OXEFd7E7FVJORj+bmOAdq3Gasnkbw6avxAZQWKm7xOPmmCd0ydsixuLvMXQ9n8T6nrcf80qMLjV9mm0lpCYY4yUgjTaO+NkDzZr4EIGAPBlLKNgTQ/njWOj5u1Q43WMf22JEAImSnIAhTuN8HM8RCs+XaJ+PQZcnpWnX42+iHSmCMz9ThZEkJkO21EnWg63O+fx2VTDmulRlCXpQEanaWHRrSkXgWN+9e7e6f2+r+vDJXcSMrNnvqp9LSdrqlGDeqSbtdFa+mTPeCgDRpI6rnUTxXIfMvysVX7EIjR7d5D44A7iapNoVbIfMZwzfmjxtMT7fFiIHE9ow4uQRc+u8OIHGVTCvORoZPgcQ2jz8mdqp+DI4e1GiJpgpBxL2P3phohRBWiwhjqKhWZMrIkZWPY/outZU570EIDmIiMnjmHZJCAsI5JsmgURQiABjtgcWNOK2dYSA4A7Wd9E32qJyABkHsLS1BQCEG13RSGsqKlzmfGYCxe7t29XdO1t4X6k+/+wji4hl3yL9CV6xJu7NQMTku849gFhFsWR/0OQQeGjQcxJIOuDxQ4ROKxRdjBZFYDHkCSMnCRYpmcomWFq9+wCE7UwDEKkwEtWj/s/JlBdVzhm8CZRLgdKKRwFwyCYS1SEanOMqfwo9fRKAxEltoBnsQfS+LDIZMRuDyKRkglwCaQEIdo8TA+bjeBEJZBE2iBxAdH+2K5tPPKf5DOnA4of6AYTnSgKJkpjPMaguCy6Bed99ftT0Zayw2ZyaKmfKO9qBDeQ2Asx2twbV9777LZQC2LLrosrG79NsQDcNKnMPIHSDKXRdEycOuDb78UCz8XiPPYRaa7Xxgc9XIg81HzGHIwMQVfOWCpOvmhcBEOrHUSpqr5CVrUyR4boAxFfmJt3cDKdw15ounrxIzQrfiPs8xrByrex5210rfosRLbacYnw72C4+g7JpowQipuT9WJf0oipMpJM+LzGZMUkOXWqM4kjyc3wRaYyoWlRyKUQMHedUIwkKQNq5UixxqL5I1as3qkoFibyK+2r1MVSYzz/5wACEf1JdSPtp9p+bBg+bHyBYjAafhT69Uh9sGoQVcHzF8Ob4lASaKG7z80tsfeiTJq/pka5jbgcBBKK8M0HjtiSAsBZEDiBxEnIFytWW+J3BRHGlzVUsVWXvY3IV9NEqTpWl6StXQLlY28+nYR8xkCK9ulQk2ZHyFVz9sdSwCQDixYx8JRYzxfuxMvpVACRKIH0A0i19OIDI7apFJJd68n152jTyOJDx/jcJftx7Rq5kHyP/jWrM5tpy9a2vf1g9frjrXpmHDy1GRGpjAZBXgoLLnWze9iCFtAFEAU6NcTSqCAQNFkUWgLQnYGK4lFsid2gEEKowtK5HAMkliJzxczBRMp2AJvce9U1gtUsA0ctUBXyXS9sZtwGQyCS65ghG0lyFUdt8J4DkqkVcgU8TKHeBAK/XRlp9EkgEELZR22Ys0KsxRsc+xc8yosb75wDeByBUXZicKCNqTh8xej4zI8jThdvks4TFxeYltpcAgAjQ2Q49M1I711A/5JMP7lfvv3evevToUfXkyRNz80oF53Vx/C/HIa/3qrdCAvGBafJgJG7SNhilDhfpfTV0Rveq4FaxMk3YBgAcQKi6xOCnLgCZZAOJgVjdkkjapAp9Uu6LtmrsYppxI2ejv7uq5RPWij2nZ/UplKWNp13iD4/bcQ4RTCIwxbYa+iLV3wCkX4URrfsB5LClgkSw4v35vQ88fNwn58JEQB+Xos6mAkjOwLnAzqxjo5klVDYeQNGRUcpWlzbVESEdBKory+fV7fWl6sHd7eqLL76ofud3fsckE3lfeG7xwlwIAD21+VVfUfeSGtOWQDwDUquTEuE0qS0tvlIgFrzxbgCxd0klCr5i4ZhYIRxOVqtzYWImq3XzUoaVU52iUY4qEb5zJzQuclbBouOdIq2Yi5NFu79LfI0TtkvFMAkkqV98LoEH36cCCCyIeaRlDlBiaKkfoq9czCBLS/2rDYjBo9GlwojBuDscJQEfPw9OczHfIzp9dff54YmHLjGYCxUvVZuPqmkuBYqGOZiwHYay50ZUtqtr8q09dVzvAhCWRfCo4pQTA8mE/R0BYA8Pub0mFiLWQ4ExnrE3LGuwvIhQ+uoYcSKL1b/45z+sfvSjf27PtP/ywMojtl589CzL4lX55XWcf+MSCJlME6T7AdvAkhtDaSRlC1JlNPgSKzVpbBInz0RjKXfjI6PK+G5h7bBxSG9lDIWMgAx4ikZU1ttmLQ4W4W36LyBsv3u6uDZm5jYBnGy+/+oIRsS4N20MWY/SQOM6jAWOCFZuAxJ4mLicPEay3US65hIGV9gzREKK8VteBBA7xtKMMSBW3yV4QdoSWnJhcr6nZEat/DVwJ0+WEhF9zBwwPCitAQ6vucp+OEO6tEjpytUbpftHoI2fCQBR8ogAlweSdbWRA0YEF34eDlGvJS1+4wDn7n9fJDivEFwGiZbvVlDp7Bg5MevV82e/rv7ZH/6g+smP/7ja2fYtIWqsMOCw1akHQF594b1OILlhAFEql4up/soJ0nwXeMRBXGAgWKJu28viQ1CLxFk9j1rXRiCSTXQFg+E8AgVL5fEcqgLGiOn7aQokUw0QJmRPemlFz+t8KPT5FCuwACRGnEoP5gSO7kM9u1SwESci+E42mmj/yAGky4sxwO7y0X7QByBddgSm8dvm2tnOfLmYP6l98JO9IqNG2wf7E93ysS0+36sAyLiaOm4DyQFCKpTGOAeU4dArsOuVA7QkN4FsNHATWAZIsBsic/fTjz6sfvu3Pq+++OZvV5sbAGYuDLY1phaoxCPaMq+XX64THqa3NZcA4oPIFQurE1ZzH/R8EiYACXEPMipGcZz7khjQJAChlGJ6Khhb50cAUe7ZqwCIAEDFapoUeXZcK2yzP24ubTQ2hybr1QEQbtBDD27LAUSWf7kx+YxdAMJiODlTClylrnSBR6MWMNiysePw2shMvOckADmEtNQFIPFY/vyyZQlA+qQPtqGKYt0qjmfjugTZzJ/4eRqADJBMNw1AeO9cSnOgxuIE6eK9xw+qIWwo3MTqxz/6YfWNr39QndO7vgAbiG3OLSmdx/oX2Onsfv1n3DCAuN1AA+iP100gOZsFFLXlO9Sz0MD7hHdmayIxpYLEfVyxs1zyYlhh4uDFEIDIQGj1Oq0QcBIuUynAi0ggXQDiNgRWpmqMb+Oh0s580fYhRvAJWFV7L5GLkzxFkj4EJpK+uoBDgJIDSC6B6FqBSG5fOEE2bG6cVtvqd67CRDXiIIkgEaRy8OgDEF6jXBWxRg4m1wkgXeoM41AiYOrZdUxgLDuQ3p2OMLCeHFWPnzy0LTL3XnxV/cHvf7/6Y9hCNpnjxBKXVq+mAEgv9DmAdL3aqotPquavnjCeThlE4HaJQAKIT9g8lNt1aKWjq6oYvRg0nOYAYoV9OehpIyFKIKZy0Go64RXVj7jvrACE+41MU2GiYbPNrA4g7JOpXSkjOQJIPqFzMGExnEkSQmTMuIqLmSKACFxsGegqQJS8YBFAogQSVRe1r2eP7YkGPEcVy/qkED1vrr64ZOFxINMkkL62eZxxKJMAJN43Sk4RQDY2V6uttY3q5d5TbEz1sPo3//pPqs+/9sRtHja9wxyrjSM3a/uonxlEuNFAsmkAEiWPNogk4EgI3awObQBhXIRP/HYymSJOmYxnIiZGypgwAcgRApy0ytvApw2YqfdbeLFZ3a8OICw8o5qcfTYQgYAYWMZcGhIPjyAGT5BAFArdJ4WwlsUkCSGuulFKkJTXALQDrPoq4KxtTQbiCq9vqsdRAOm6R1wUJkkgumcfk6udXIISgCiZLgJlvHdUybrvQRWjOxXfWD/RJAcS748bVEfI3H3y4AEIMbIqZj/5lz+qvvfFNwqATFya04/jADLudWkDh1/owIJBSBv+5BNFrs28nge/S0w3hkwuUBUFIoDQtUkA8ZXcXboEEL9xKu/HLRcuACByd3YZUS3JKqlC3claDoaaoFq5JW2Y7YEV6CcASB5HkBv5WBR5kgQyGUB473Z0L/sapa5YUrALQE5SPEoufei+HCsBiDxCogPfY7ZtDgLxe5SeGjUMaiByofJAsjhvow2pC0AUyNYVP5PPyXEAdgA5PDqo3rt3z+qE7KBS2R/98AfVt77xNQALbDSp6PUYL82IS3dGbCCRPN1u2zaIND6ts1SUeGyiJ36vAaCu+N1U1LJrUg6JAOQYFcYIIIdwr7prNLk4U8Yt4z8MFBKALGYVvfKBjm7ZcRWG0SGwcZibt9uIyske7RBST0wKYXUxGAEneWHybF71T20yUPVVASSu+vmeOJHZ+Uz0kkxq/wzbKYjRxhYBHFDUZp8KE58vzoEcTGIfGkbG+KKcQFQR8jYkQfWBE6vCC+Bz2vY9VwTLQxR0OoEEsotQ9vW1QfXxh0+qP/zBP62ePHyAuXeMY1CRusCiAEha0MegtfsAed2DjBrXrA02S+lT3wYrKsrPJ51LCM0KnpLNuC+JSRbunqWtle8qjEwJxH7n5kY4vreHoJ9MhTHmSyoMref5xInqQi6BREMpz2O5f03AqO9HiSSK33H15fHney+5n7yBjJhEFn+e21eSsHn+ZluEXMxnvyRB9K7umQkoZ0CCsZ6FbRitLVI2VUdbchtCXJ0j411keoyv7E1JBUZ2mgSZoo+jW9VKDxqApur2pla0/+Jz54uU9zNZ8dOJYxJeaC+3w7C9JWwB8fz50+oe0vtXsKvdEF7Ff/+XfwG3LgyrfMmpxY6qCpZNmItQ5vWfMwMSyPSHlPHUwaPZNMoGm1sTgAsjgLjY6wDSrOC+UjCOo7EhYOJMAZCXiAoUgNjKm9lAugBEgMB3AYgkDLlw6+OpHkf0tIjhDGCyjY3aVvxzGFGbgjdiPJ1jEzQVsYnGxNiGJJC4QgukjF7BLdslwtc1DRII5MyfFyWO3gi7T5BAuhh02uzIr2nUE1f9Iv1yFYpuVJU0jMCR21XGpNukVvo1zb46cdxz4BF94zs/0wtzBBXmDjNxEfyxgl3x/uxP/1X1+7/7hdnGCoBMmQF9FtxoPI3eF4rr9WBzpzO0L/HWJRAPAJMoTZ435km6tiZRzURJNVH0qZLRJIFYycIJEshK2ldGj5kbKyOACEQiQJgKkwr6xuNqh9erz1F9UZzH4VGzLUS0B4iRIjCxj/H5TRqwbRnGj+t6MWBkiK7POXCI6QRGETgiEEUbiNp4FSDpYu4IBpLMohTSAKSn80+TQCYBCMpOt2Z4LoEIgCNAx/7tHexxVOC2RUSrbTOxWH3ni29W/+5Pf1Ldvr2ZAA6nSOKIGv4MSCEzJYE0QKFAMZc4WhOLe7AmsdBWSPyXA0jUmwUgi2Gla6/A7hHoU2EODrwgTvTCRBUmAkiXpyMHEPVV7yy+G+0fYnhNWn7n/aObVtGM5lmiGSTYT3K3pVbFPgnkCG4QTe48VoHHbff64KiLn3PQiN9j/6VaKolM4E7ajCbsKSPAm7QG5SrHtO8CD+9fU5Fs0nX5c8X5FwGka/xjUF6XCsP5y+PcZB3x7Ujuq6pbm2vVX/7Fn1eff/5ptQavjPlyuQjynAIg7ekgCSSqKXEwmf3qCNLt9x6heIsnnqHArYWeu/dEKgxVlsi0bktpqqiPkF7N+/VJINy2YZIKIwDpmjzs9rRsXG4izf4rSlV2m1zkdYOu2w6UKGcTEs89CUDYjkCpSwKhGziujlHM5/mq0xlX4TiCkwCFv7Fv7K/2CpZKqeftk0DUblShuoDkIoARF6A283PTbz5/tw1E9OqTQLw/jQTSNQckKebjqX4MEAlMFWYBC8kQeTtLyEs62H9R/fm//bPqD37we9UO9pGxV5RA9LlIIClOhvhgjO3xGnGViAASGUGDRcanrkjA0Ernk869GgQQ2RL8mnZhnWNYwCdJIAQQE39TIFm0gbC9VQsEGzekarJ31fOw+ZCu4W707GfcUCqK29Ho1/LAJKPggiWZNQWYJbFEW0AfgHgcybHjc0uvb+pwXNRNquftklbYb0tpT4bTCHjni16QJzJ57E90o3YBSHzOHEwEAF1982NIcDugl8gtlXlbPBZVkK4+siarXl3zQFuNqG9tCQizFBIGAYSRqBvrKwYgh1Br/uD3vl/9+E/+qHqM+BCfKkkK0To6IyAyMyoM5xDBoqnZ6ZNY6ognbiWPi1HU07uZDt5WYbT3iQMIM1Oj7WEhxD2w/aORqyh9KozcuH0qDAEkAkL+eVpBmBMUNOJqzJWe50pnl6GXjKfVKzdAmnSRACSqPlFFyye3wMmBCTVhASBKjY/2EjFDVxxEW+rozyMRA0b1SwZKjdnS0HNJomQQP0cjbB+AxOtzI2p+TWRyAsj+C7dBRAaP978ogPQtIhFAulQY1+BAQwAIpxIBZAWemfcRkfrDP/pB9buoEeJziv8DnajJpM/+QxdV3tyxGQOQuPtbAyBOJ/eqxEnOIDIaTRehOLIuA+t7jiCSs0zfIg2T9MJQAoFhimn3jLrE/zldMGZWvQPof1hHodoKnyp6yYjq6dhuA7HJn7wwMngOYTXPmTROpmkFaUZgYAIIbQ3axlI2D/YnxlFEycRydIJ64iDgIfuMK1AauYA2/s6qWFYbBc90dOglDfU88Z3PkWe7dqssZspOjMgZ7d+5snvgHqtw0Y5Dac+NlgKtwWoDIBEIdJ+LAMgkNUYSlDOhc1szPnDTP3s+UYWJcSA5yHlbGv+ueiWQcGxfIU98VJ2Tpt6JR0Bz8ThDzMcIxZ1oSL1/dwe1QhaRF/N7CGv/cQMgCUjS+jkL+HHzNVEpvDIhk5KC9G/lN8gA2c+gi5YMZ4WBmA/CCuP27sYxHufu60R55jzwXcf5bt8xGkzVl31BxYh1TxpRWxMUF8WJxH1P28mAbfTnBIwSEH+NdgbtB1MbVQEKLub6eU+/et5qsGFgl2XNVc2URAIC62SQYZnlyQhRFszhvi0EFmYls3BPOk8BYJaOHzI8cxVENhn1OXcjO0CqwI8DR6yL4QV/tFOeJwCqgBD7z/HJgUNSigAzEiAHgVxFyMEkrwfS/t2rhU2ygSh+JLrG4yLWeKmaOiaqa0K6eN2S5vnbhZIwL4crJmXTeMoK/ScAkuPjQ+wVs2FbX/7kT35cffDBB9hHBhtQBWmj1mCo2WQhIsqhMVfEa5ZQblQCkQGVCbGqLCU7BieNBr979aA4B+ZhGHeya0jUl6iYT7yoa2vSGvNRwkhFf5XboYnGrStbEzwVWbZjuLMbcPsT6rSiRzCMkoTUFgFIAzAugb14/rK+v/ejmRH2PEEli8wtGiiQKt4zegZog4ivnEZdABL1+NzGo7ZyFahLfOe9hkhXnyRBRCNqn5EyB6DYnvovusbfOG5XBZDcRtRuf9q2EjAyD1ZrACEtKaUdQ62+tbEJ0LhTff71T6sHD+5Vd27voHIZku62Nm2LzHXUETHhl2EKctJ0gcW7ACCwUyaR2xk5SiBxRcwnkOmDVlqwnc8RB1FW8DjJIiAx61Z2B5tkqfCQmIQiaA4gdfsAEBex+wGEg64VS5Mt6um5W1f1WbXyHWDbCb9fu/ZnDYBZKHr0MPE63j8Cq9qt+xAAJJc+eI8IILFtXa9Q8j4bQByLLhDxbNh2JGpcAKINZlwFcbiaBEAREMfP8/G7igSiPuXqje471ciL8AKOCSUQ0tLUT6h8G2vrtgH3CoBie/sWDKxrVrH9/v27Bih379xB3sx2tbmOEoqggYU62VxwmpjXN9pKWsvE9X2ZCQmEi/zI3GluNBWAkKASZyPT6zMBZHlptbUzXG7lZr0IMZAGOYqgTJqTlGBAklQHeT+ou9fMSrdppsIcHFJC6AcQ7evSNt41Xo8YJ+LntKugv9zz/ufbR+pZTpKbu2+FVxxHlEAijaIXRM/ZNb0iDSNTuAQW7Qrtz2orglhrlU797wOBLgCJ94tu77wNnteStlL8UHNeqvEywY07TYWJNMtBhL9NAxC6sXkOAYTP4vapUyTWYdsHSBrcY4ZziCkPA6g4dwAcDx/ex1YQj6qHAJLPPvkQ9pJmxFxFZFvvCICYJ+DYd3DrAhAe613degBEk5VkJYDkXono9qQEMglAmm0RUgh9BiBHNHxNeEmFiBMtrqTcF8a9S+6O1aRT6cRnz57VAOKTub2z3EkKNe9iHh7T/QUafRJIXKn7ACRfzSMj943RRQBkkgShnf3UTg7EArBc0hG9JwGIG529sE9XHwRA0f6j+ahFKNprdM8uqacLTHiMgXQ8n5tymz3LCkvTdrUEwzpc3FgeuQgssJwE+kswuY28mfffe1x98P7j6rtffNuyeFdQFpHTRwDCzxYK/7arMJz/BwjH7gOQaBvIVzpm0loAWQpH71qFtbGR3KJsg20qJ0VVWTX42oxaEy9KIDbJAoAY4CEde9JLdpwoljtguEdJO9PlAKI9eAUgcm+7q1srp1eQ73vZxAy5NGKEKIEoFyVfSSc9kxgknnNR9SKXEBlK2wcgbF/SaB9AxYpjXe1EBs+lAUmOVwGQ6ObtAth4bPz+UN1N5YCXMNXWVV9YicylK0gjyMhluqjtY8PK/eur1aP7D6oPP3hSfeOzT83gurOzYyqOjKa2WcC7oMJAg8D+tGnj6mTMjCqMjHRdE5S0NzfhBAARsyn8W0ylDZzo2hVjGcgkt22tIgQVRgASJ+U0ALGq5yHyVQAmqYcVwSKg0E2s/vB+L168NAaLFeWlXvG8fEXuUpWi+pBLIK8CIHnbXSCTj5NUkBzcRUN6w+LKHaVHfuZc6AOPeLxPAsnbboOMe8iuAiB6vthuFxj39Y/2Oxv/ZMsSqNmmIDZvEKG6wghVX/Q8gncB9o+t6uH9+9WjB7vVPRhb33vvvWp3dxcL0oqdM7DY+HdAAiGAsC5mnwQSt3aMEojZK3CAXpK4sVO+wpmUkMr9yaAaIz/JwDLcWhh8qv+he8V9R3IJhOeMkE056UXxM4rAPFcp/SaFpLIAAhFKOBFw5EZWsWdJUpKQJsc5NDaA2GYU6+FHqhlYz9ElYeS01/caCMZiLLw1xXH02Wh8a8nxNHody+NAcjDpUhdyaU/ndEkgVwWQPNtY/e4CkS4J5Bz1XHIAcVtcswEaAWMJc4VuXs0nur/v3N6qtm+tVe89elB9/PHHBiK3bm3aOVRp3moAMaay+A/EYWAVquMwVKcjmZNj/QypHUZgTjpQyLJlTfdr8lsig2iCRyZvTUIwsK41Vy7jSlKYOI8zlF0vuy7LyaGKlK+6+crYup6LQnDOLzFZKlUl03M54PgKdJjiUJo+tm0gFH0jw3cxP58nbicg5uf9lIsSJ3z8LBtD/gw5gPSusEnFyn9XP31ryemRqH0r/DQj6uRQeJVhbLakzO8T+9YFghf5vY+2PM5UDAFIC6RtnjT7/VACoUdGEvnqYFitYUuO9x/fR/Lduu2t++1vfxuu312T2vjd0iPaXvrIBtfy+Ua8MBJTcwBpskybAVV0ZrMVgk84YyiTLlyFiat8BJO+1bQ+Dl0zAohUBU0W2UDqSZABCCM1J6kRuk+XGG5eJG58lPRdAYgDmD9jDiA8HgFyMRR17gMStqM8GunsMgJ2AUhsRxJOnxoRV9yuFVYAPglAuqQI0bvLDR/7p36p/dgHtjENQLytmwMQFfmnKtcCr7SAcusSHqcNpM6XgnAxgPuda8/jR3ex++ECPDabtjXmxx9/ZOdREjFDarve0bWARmzktQMIHz5nMNklCCCnJ8jWxN4sUjO0+uu6WIBHLk8RmqHYnOCMPM2ZJDKZHrhLvGTIet0ejZN1FXcfOIUq15M8GVHVJlWMfIXpG6Ux8ZsRstwYy7Z28D9tI6F+qCIYj0fJSBGdEUBiPyKT5eAapYlchckBV/2K18TP0wCiCxyiiiEVJq7kJlzVAQAAGeRJREFU8ZpoY4jn6PNVAcSfpR9A1Jc+FWza73HudX1maUx7gRk0TnavILmZ3SNJai6R0+gK2w1cvPfubuIdpQ9hQP3e974HEPkOIlhRX9U2DLM4w9f6euMAIiKZLs9V9nQAAGnKDIrx41MrFFxgoslDD4pl3NLZlSJJ40rbNXkjyvOzNj2WVCQvjM5bRih4nLixoBGPU4XKGTcCVa7e8Nx60tPOZdZyt7h7Oy4RaT8brcCSSBoQ8ZBwbUvQJ310rczxXAFIfIb4OdpYYt/1uQ9AutrL+2JtBCNqpLP6mHs54phGhszHVedNzqVRUebJAJL3O5dyJv0eAa4LQKi+2kIVKu0ZTZNdiM9vm60z6joZzU0qBzgwd2Z1hfs7n5jKwg26v//9362+9rWv4ZqEHPPuxo0SCD+3GJ15AmdDJME1O6tpQorwVBE4iSW+xZBwq4bB4Js8HD3ZMPJJlas2/J2BZK3zbOwacXKAzbHjBJF7VcdoxO2bQJwwE93QkDwsjyckCQrI6K71iZrt7GZ7qiqxrwGQHMQ0WXN6xmfjOXlR45zx+9QzHe8CkHiPXELQ89Uglty4XeDBYwKQrt8jUHcBCI9NAxCPpO0HED3fZSWQaQDCLHCbJ3UEqXO8+P4IOw8SQJgcqsx0AxSccQoD/vnZHqJUh2ZA/eyzz6pvfeub1SeffIJ4kWT8eBsAJE5mGfTM3sFtJc8R/5/0+ih9SHTe29urk9FyCYQ2BEkgcWLGFSIOfBQRdZwA0loZEsNqwjI+Q5PTVK/AwDyuUPcITlF96qqKHiUQ29A7qS9tI6qDBFWoeH8BmPWfuTgWBtLEguQrtFatCGSR2aIbtwuE+mwfFwWQmCvSJYH4tgrj9UgiYEz6nANYLh0oJKAe5NaHs1QwaTKAtOiVwDvSedLv0wCERlQBiOa8vad+MsnU2sBc47PJg0gvzKJtuv0SEam71Te+8Y3q0aNH1ZMnj6v3338/beAekKibAFc++kZUGE0A2SnqHdQSgJzCMBmDo0RITr6XL1F1vCXiNxW2aQOh31tZtV3UiHaD2vYSJBRm4sYJHIsEG2smiaS2IwBAYl/Hfg8eHE6sPJ2/taJDArEVKASWUW+NQLe05AV3BBytOA4AyLIl17UZINKBq1bLbZw8WAK8mEyXg09Ozy51rG/lV1vTAOQU9VAmAUjXb7X0gh9zFedVAYQuz2kSSJ+EmYNIl5SSJ9u15hr7TxAgn2ebjJsBA69lVK0nv1hZCoydx3gMkEy3Ci/MEjwwi9XXPn6/+u53v2tqzNbWLQSVwS4iieZtsIFEABF42Dt493wB6eZAABfZadB099Wi+ccruFEpIVClIGOxvgRdkizrx60XTkCwrZYRUiutJnsMupL61AIVWr+ZeMRAHnuHThq+s2KYFRNCX21/XLsv+smIUJYNIPjxd3y3GiKWTs/0eXxGp1e4rYAWgrSy8DtFUi9lgJlj+8I05vIIUDmACFxs8qZKWjKkRsbSZ7alyNtohHYpiQbci8eB2ETP4j2mqTB9oeZ1/wAgOSPmoCEgy9Uvfu+ykcTzpkkgjASeBFKxfQfdPHnPOV8ScAQwHmsBSJpktplammR1OYDkdfO56XzA6zfXNywdQwsBbXIDpOFubWwBPFYQA3K7+vyzj2D7+L6FuQ9RjMgMp7zexitfBq73+2uXQETQuPrXTA0i7u1jBYKYTkZRacJYP8EDqdp1JGJBFtdxHZlJQP5x0BRXwgmkFZiTn/e2jaNgWznEbxtA7XPcX3VCKFJTrOTKSObeRFYkq56dMtgN36mr0uvD33kc9nGvZgag4/cziJxUS8y7QBcx65xQ1UrfEUtox+07yvizdkZTcMYliTgJterwGXhcNVGlmtB9J9pyaghABZYqF5DbMsRkVNHy+9XSFiazr9BNoSdex77UxtUgwQkItErynSpoXMHjKs1290E3AYFUv1gjlp9tr2L8SYTPYz8UXBclVdFEaqGYvC1lnVU7cHdy9aA3XB4ffzY3WvJlcwV/fv9BvVUG2xoh9Z7X8RrPO/Jscp7L5z843Lc2vG902zsA0LbGZ3v65W9qKZXzktcNAAJcVBmkeO/OLqKRX9jxdYSwD4fMrkb5w41b1e6drepj7B/zT773HRhOP0rjxPFpcqpyI/j1wgdJF5et6249tSdxOVchuK3k/iGqeWASRx29Fq+TEUxMlUsQftzzJZhkRnVme3sHxGVOANWiE6hA+yA4Vw56elgZi1JMeucgryDjkRIO94uhmGH1RQgUmEAQL/nO7QcJICpYxELOIxi3DlMmr20xaRW3fEsJZxgfRH4mXDSTqEmas8I7uEcMpe5awcT8kt7YpsKaOWm7DJUayi61Q/3yPqVIyGTIFVhEpoyrK9sTIFP0ZyCYxkvPKKbNx00Lh9Ek2X0OUxZ2DmKaD/KqyYAo8IpAG8FCUpbUZWUjq4/xnXTnNgoSP/VsfFe8jSqyNc+Ubd6O7FkCA+/DseBcfLH33AISlwEUzbP6M8ujaM/BIleYLwOTAjHPUEjIJSaCz9DsGAKUjY0NLI6rNo8XsQDdRy7Me4/uVx998Agg8sTC2DV2kvp47VsBIJqwUh1qFQJMxmJCjHbRhIqrHz8LlXVcE9smAq5tzqks/Xl3957VT6BKQKBgJClXCXpLDrEaeI1Tr9pOF9opK5hR9RjRJEkAgQGLwaGQFPh+iI2bRvCznyDh73yRu7lTgnCVhvaT5wAoAza0GwFEjOkTP6S8p9quYvAzNKSivlH01ud8VdUqGb1SYmgxjVbqvByC1opcGomh/ebVwvOoCLLcyGIE/h5VIgLICBNfDK4q9JIaNH5RbdRYU74+Tun89bEEujrf9H/zRrnkI0O6FhNJL2KWOkkyBc/FneniAiSgX2EcRlI1RFOOjYIa2X/NX6NfKmlp98GlwyGiPZNdgnTh+fv7e6GUoRsj4hxopKBTqCGbABDPxiaAUF2h5DFEoSEm0fHcddQCoWRNQCGwsNDQp59+jmQ65L9g75j1NZdmBKpvJYBoEKIIaxtbYxS4RgvhxQRiRopvcSXTaqN2uPLzWubM0IjEVOdVGJeY5UvwkDrDgeFqognplm6Ix54O2RLjxbw8rmu6RGju6vblr7+qQ/HZtuI3nCHYD+1NyNaCRYsRrZA+CG56RWFQn0kHTmypImKsuJqJqfUcdqfE6BL3RS9JCVrhHRho7F0xEXl1FRIZVlVu6fnyJUVnqi9UEzl5Waia5QcoAQ1RjhCiOJhnf/8lJj7jYVg+YMVWyGOqeKg3+/TpMzvuRj23BlEvp4TIZzgmQNh3qrHOSCyqw7EjQ7kNyGuoioG4QnMhIO08wI4g4+nr7Bff1W8+l6fI8xglRNpN/PwFLBzLLLxtIL/g7lIGJqbcKdJ6E9sqCKQkDfFJHLQXqg3s4UI7HBmc4y/g3d/ft2py2hZDABIlINqwdne2LUSd7UntJn2U7EkVlSoPQYpJdZQ0mPfy4YcfVre3161OCGXdOGejRPrWSCA1l4SHtVT6ZMQzIuCP/BZXCjGujmllkDi8jAnmpd62LJx3OHTVge1wkpH4nDSaFJoENZBha0JO2mg74D05EaL4HFdQ/k5gOcAG3NyYycsKOAjxnlp9KZXEXJJYVUxFiPNsUzG46MXfJwEIf9PkkWSgCcT+xGQ+6fQSo31yMSWAjDSotiC53drcMpH+6VfPqmfYs3WJKyNBDow6RN0JfqcR0OiOlW9r81a19+JZ9fzZnq2cSwixXoChaAS6sFbK4QHUP6h8x1bsmnEL3IqUwh9sXgAgSnGu5gGgMFZsfwQA2ceWorQfrAHQeD/+vsQVGPdl/7iHMfulGrB8p/GQv/NdtV9XV1C0Odk4VBvWymknz7fFV6TNtVxi8/q4nI2k7aP3Hhg4cB7RnvPll78EYO4bXalWPEQi25MnT+zzL3/5y+oXP/8/5jnk3/PnewaABp2KWU/Jg3YQrpe7sGOsoS0BFwHnzi7VcO4HQ7vHut2fc5wAf/cuK5KxT8jyBsMMuA6luadx16IllSzy3nV/fiM2kK5O26RPFmJOn/g6tUxE2jdOEGkHvQ+j7YxJHZEVyyhJ+I5et27dssFcg7Ez7jKZvFg+TqlxAZQbvryuxqKtbL6a0lSBuQ7DHoxmUE0IELbxEFZU1u60sGOI3HsHWF2wsj6jfQV6zhLuv4qVF42hKvxx9fTZi+o5ft/Dyszl1T2trN1hpnXzzuj9FEZW/k59mNfHd12n4/IC8fgQcQBMxFtBUhWPs30yJL/zXV6iAfqt69xrRLxDYB6Os5o99wY/hyQ0XF6pbm1vIrtzx6Ka9rB6Pnvx1CpVHLJoEp5jyJUSzzuCTWgA9+IaAOQhJjMlFUqKAl3SVRtJEUD5mcxHxosGRzMksoyfebVAfnDDMoCF7e+jEttL7I+yim0fTlksGxIbf1+BaE+85vk87wgAxXenI1RM9I/vdHvyOM/ndx+H9rvNDarRpke7BOOqjc+ru/fuVL/1W1+30oKsBvbll19W//iP/2AA8/jxY5MCWGKQTM3n+tnPflb99V//x+oXv/i/bstA2x5o6HYTqj9LoHutMoLO9++hAjswRuoZ63qwbbbJgkIElPUNBxACAkFtDeOg15mBnV8vaUNSzlsNIHxoFTQ+ByExdeyIVmbyGu0Dy4t0s/lxmCmtejtrcKhiF6UOR16XYdx4CeORsjwDgNREN6OqSwzLmHCcOBQF5b6lWnN8CNsHMyXd52oMR4ZlSDGDz15iP5E9MMbRMRqiZR2/83kO9w+qr7BVwNPffAUGANBgZRZgUN0XY2tbiXNG4yYAsdR+ivjpncfXAKC8P/vBsHvuI8P7qD981/UEjmXak9L1vI7PQQbldXxnOzyPAMT3AUBkANF4Yw26NMRxMiwZjavy0QjPBwYlI5NhV6CTk5F5nIzuK+Id0xeihGjSGVRGAgonNpkr7qhnjOu+egNy0tO338CKCmDi9hwCEN6HAEIgW4Z3Yn11w/ohoCB9jyHZsL86j+/8zuO8nn4xAoj5xyjh0oCVcpqOYdvioiAGpnRCxv3oow+qTz79GOHhqPhFVy9owuJOT5/+xtSJx49hf7iz3Vr4/uf/+nn1t3/7n6qf/vSn1a9++WubYwQMt9X5MxM0KVGsr21AoltC3grtdWcmcbBcIQFkEwWCqKrwu+W9JM2X+eeksxmK07JIAJG6mtu2dG6rk9f85bVLINOcPFwlSCEyfBN/509JgnnSvmnOrfecDlzdaQblHri2qtLCzYlne+I2wCSAsn1mIrgoWCO9a6ItYwLw1pzgnICcyHw3BoNHZw8rjELxyTjMjeFqzNX2CL9x1TKwTIDV9gKw7kOTSBcnglYT2nW8iIzbU8SIMmpq4st4qeMyCH711Vct9Y19qd2sWNFZY2IDKxxVQK1y0WDK6ymO894q0Kz0Ak76TRj4yHArUBVoEyAg0IhIOlBSlO2Ev9OG4XYh2jfcY/Xzn/8CFel81zqpYKQR6cc/ua/JHJQ0uWC4qur2it/85je17UCG0Uin6IXJvXjG4JWv5mzPt9iozAj/rW//toHH1paPv15my0nfGcnDecn+e77KQvXr3zyr/uqv/kP1N3/zt9Uv/9+XuH7HY5wsXpBlCiHp3do2CYdqyJ07WAAXTywB7qOPPjIac6c6K6B8D5INrmWffEFsIo7RoknHlLCavrUTV98KAJkGeBZUc+XXOEAIKCa/h7mRjJpW78NChKe8m0i8UP2Pf/iF2XFkaGMVdenAZJI65gSSgERQPi6P81xnfBrk3FDWVJ1qNtLipCKzqrgSr+U9yMjcM0SGZzGQDKx8f/r0aUUQoEQgDwuZlROZovnjJ4/Mk8C2rfYmrpExkO9k4ujGjbYgGjffQ/g0QWgNK6rXd6UxELah5P2iRMjj6+ubtpLTJiAA4QZgf//3f2/gKhe1AFJGdAIwAYPP7+5Vrwt6H9W4KOZL3+dxXmvGSwA4VSbSh+fyM9snjUm758/hZsW9VyDdrQ29bQL15q11V10gVT3CznC373iQWf+LQYPwDpkjgDEhcAtDSh1hsfnZT/+zg8gvv6yePX1hKjbVGdovPvv06x4fUyGbdncLdpa7pg6xD5pH7k3SnXMe0Xcuuq851HQKBV67BDING64OIJcHIF9IMADRYDKtw62laLHaT5GnjC+Rrs8JSgMav3MyUwqSl4grPcVYifkvoArl8QHSZ81LAaYgE8SVl5NMBt7d3dvGrAQUMo+Sx+TadWPe85qh2B4nJxmQTELpYxmSiKz+bFveAAEJH1mSUwQQHuf2AwQQGfvkLYrueB7jCs28NWou4F3z0vAZnj59bpG8MfAvBsspEE3tse8U89l/vgv0CI4CUgV+ydhNEJUUpTHhc65BHdrZugPGvQ2V4bZJYSuoUMex2rxFaalrMrTn2ykzopmvBFYmbUxixeslFof//Q//aDaRv/u7/2Ih6Ux4++KL7xhY8LlXoDrev0fJD3YNC4D0+yka27/1gYf/ihDKi87Y13LejQPIOIFe9TkvDyB+p6sBiEWbctDxdwy1his9Gfkl3KBaEWmkJQDQxUzxmIzE8AK6PumdsImQAqvyd66YZECXEKh+jNOHEw5YZUDCScw2pPa43v60+tWvfmU6PH+jfs2Eq/sPqGM7U3sUsE9e94Q1NVbk5WmrX77zHStjySjoINT0j201q2hznABiXjHaZGwLzPFyDLoX2zZ6JjWKQEXg4GpNFeDgwG0ADrquKkbvGX/j9fLmsZ1f//rXNk6kxeOH71tbtDesr1MaTPYKSy9wmjQvzbWgSvAZuS9RbZ+AFJZ0HNKRC8l/+6//3cZjZ+eOxSkRcOkmv3Nn3fbD5emq/q8o0vF76kg+3995ABHKSsd71fculH4VELoCgHDg6ZazmBKfaTT+agLHLSHo7eEqLDczz6UB3XaG7HmRAckQDjDNXzy9FWbScR6Bigzzi1/8wqQhiuhcCckwLLyrFJzOxTbrF0FSORraonFoCOTV6i2qlqodjZZJBdSK7LRxWwDduqpub+54u3kz7nFrTMalMACQ4Mjjpgph5dbrEACsFAj+7gDS7A1M2wtVJsWZUK2kIfQl3MQEqQ8ePwE4047TqAJsg6DGeJYGQMbBQ+Mtgz2/my0uRR+7ZOljyHeTvGC0XbZC3oicXnPjOK+RvSICCGmjcgPtoWhUmFZs0atM+2s6dwYkkKs+yVUlkEve3yOjDDz8FYHPmYVMwgnhcSge8EZDYteqrF4kD6+3TZdrkgrEuJqUk9pwBvA/SiwjRNlSCuGq69b+7bpdu48ld7nNxxzJF0GT1GFWzop7wZJRvW/dC4G8ZFamAGe9hKtbQX0EMzNup3fGk5A5Iy2njZZJULZHMN3lcMEzUROAxXaXzG3NOBbCPmxOUN3MipARM8bLTJeQu20QGjsbd9yFtzhE+gMlJRX7aasq6ckS7TmGNEK3i4p3zfV33AYybUJc7PcbABHGsaBznPxdJhTFtmil5XOI+S/yTLn3yfxRCbTiihfbMkbTOZixlIp4naQjidlRKsj7ovt29TH3kpkjPolIUr0u8mytPhO7JgBW37M68DXPFtvUM8hWZHCmyNxWjT8YXiGiWWAbXaMcS8sMb8pcKpmw/7kmA4iuI5Bw8Rh7dU2etGhQSpmu0hQAuZwR0zjyVafr9Z3vjJoYVjUd0tLtjOYDyxU398+3e/Fq4CcX6xjjE9AoOidbin6n7q9wah5TLo0Ypo8iitSNfe8HiY5nSP2RTUaG4ly8iZuFO73aHKVQfNlhYh9czZjMQMqRyZ9TLt0hpBBWhGtezaQiODa/ZffJimvrekbAel8dhAZQW0eImyEwMRDPxiB5hBYoDQU11YEu2F1ucH5flFNmR4XJ4jB8eeeMmvJ+0Se95vM00V1fdzcyReMocfiEaLJvu7pAMZWiOlWIrvfcphBFfN7X8n2wgtLKH20HVltVcTDJNsHvlgyG8z0+xt2niljsAjoyoI53/m5G21R8OtW7UM6J76Tm/WLGdR6PQwa1IDsOcTAi53SKYBajLQkCKtjUlQui/sqzFHNemhQDmcD7uLUHoBKAsGo+Q/q7XgZAyahM0FhOBq+zZJOxPCek56eqho1qm+a9uYhTav41T99ra252AOTaHukmGsrjUF61D5eNY7nqda/az77zL9uP67r/VdqZJgG+DhVB9LpKv2fj2gIgszEOpReFAnNJgQIgczlspdOFArNBgQIgszEOpReFAnNJgQIgczlspdOFArNBgQIgszEOpReFAnNJgQIgczlspdOFArNBgQIgszEOpReFAnNJgQIgczlspdOFArNBgQIgszEOpReFAnNJgQIgczlspdOFArNBgQIgszEOpReFAnNJgQIgczlspdOFArNBgQIgszEOpReFAnNJgQIgczlspdOFArNBgQIgszEOpReFAnNJgQIgczlspdOFArNBgQIgszEOpReFAnNJgQIgczlspdOFArNBgQIgszEOpReFAnNJgQIgczlspdOFArNBgQIgszEOpReFAnNJgQIgczlspdOFArNBgQIgszEOpReFAnNJgQIgczlspdOFArNBgQIgszEOpReFAnNJgQIgczlspdOFArNBgQIgszEOpReFAnNJgQIgczlspdOFArNBgQIgszEOpReFAnNJgQIgczlspdOFArNBgQIgszEOpReFAnNJgQIgczlspdOFArNBgQIgszEOpReFAnNJgQIgczlspdOFArNBgQIgszEOpReFAnNJgQIgczlspdOFArNBgQIgszEOpReFAnNJgQIgczlspdOFArNBgQIgszEOpReFAnNJgQIgczlspdOFArNBgf8P2EfbwT03JcEAAAAASUVORK5CYII=',
           'sprites' => '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
   RK5CYII=',
       );
   }
   ?>