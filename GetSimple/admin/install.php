<?php 
/****************************************************
*
* @File: 	install.php
* @Package:	GetSimple
* @Action:	Installs the website if it has never been setup before. 	
*
*****************************************************/

// Setup inclusions
$load['plugin'] = true;

// Relative
$relative = '../';

// Include common.php
include('inc/common.php');

// Load user.xml
$file = GSDATAOTHERPATH.'user.xml';
if (file_exists($file)) {
	$data = getXML($file);
	$USR = stripslashes($data->USR);
	$PASSWD = $data->PWD;
	$EMAIL = $data->EMAIL;
}

// If there is a password set, we assume this site is already setup
if (isset($PASSWD) && $PASSWD != '') { header('Location: index.php'); exit; }

$php_modules = get_loaded_extensions();

// attempt to fix permissions issues
$dirsArray = array(
	GSDATAPATH, 
	GSDATAOTHERPATH, 
	GSDATAOTHERPATH.'logs/', 
	GSDATAPAGESPATH, 
	GSDATAUPLOADPATH, 
	GSTHUMBNAILPATH, 
	GSBACKUPSPATH, 
	GSBACKUPSPATH.'other/', 
	GSBACKUPSPATH.'pages/',
	GSBACKUPSPATH.'zip/'
);

foreach ($dirsArray as $dir) 
{
	$tmpfile = 'inc/tmp/tmp-404.xml';
	
	if (file_exists($dir)) 
	{
		chmod($dir, 0755);
		$result_755 = copy($tmpfile, $dir .'tmp.tmp');
		
		if (!$result_755) 
		{
			chmod($dir, 0777);
			$result_777 = copy($tmpfile, $dir .'tmp.tmp');
			
			if (!$result_777) 
			{
				$kill = $i18n['CHMOD_ERROR'];
			}
		}
	} 
	else 
	{
		mkdir($dir, 0755);
		$result_755 = copy($tmpfile, $dir .'tmp.tmp');
		if (!$result_755) 
		{
			chmod($dir, 0777);
			$result_777 = copy($tmpfile, $dir .'tmp.tmp');
			
			if (!$result_777) 
			{
				$kill = $i18n['CHMOD_ERROR'];
			}
		}
	}
	
	if (file_exists($dir .'tmp.tmp')) 
	{
		unlink($dir .'tmp.tmp');
	}
}


// get available language files
$lang_handle = @opendir(GSLANGPATH) or die("Unable to open ".GSLANGPATH);

if ($LANG == '') { $LANG = 'en_US'; }

while ($lfile = readdir($lang_handle)) 
{
	if( is_file(GSLANGPATH . $lfile) && $lfile != "." && $lfile != ".." ) 
	{
		$lang_array[] = basename($lfile, ".php");
	}
}

if (count($lang_array) != 0) 
{
	sort($lang_array);
	$count="0"; $sel = ''; $langs = '';
	
	foreach ($lang_array as $larray) 
	{
		if ($LANG == $larray) { $sel="selected";}
		
		$langs .= '<option '.@$sel.' value="'.$larray.'" >'.$larray.'</option>';
		$sel = '';
		$count++;
	}
} 
else 
{
	$langs = '<option value="" selected="selected" >-- '.$i18n['NONE'].' --</option>';
}

# salt value generation
$api_file = GSDATAOTHERPATH.'authorization.xml';

if (! file_exists($api_file)) {
	if (defined('GSUSECUSTOMSALT')) {
		$saltval = md5(GSUSECUSTOMSALT);
	} else {
		if (in_arrayi('curl', $php_modules)) {
			$apikey = generate_salt();
			if ($apikey->status == '6' && $apikey->api_key != '') {
				$saltval = $apikey->api_key;
			}
		}
	}
	$xml = @new SimpleXMLExtended('<item></item>');
	$note = $xml->addChild('apikey');
	$note->addCData($saltval);
	XMLsave($xml, $api_file);
}

# get salt value
$data = @getXML($api_file);
$APIKEY = @$data->apikey;

?>

<?php get_template('header', $site_full_name.' &raquo; '. $i18n['INSTALLATION']); ?>
	
	<h1><?php echo $site_full_name; ?> <span>&raquo;</span> <?php echo $i18n['INSTALLATION']; ?></h1>
</div>
</div>
<div class="wrapper">
	
<?php
	if (@$kill != '') {
		echo '<div class="error">'. $kill .'</div>';
	}	
?>

	<div id="maincontent">
	<div class="main" >
	<h3><?php echo $site_full_name .' '. $i18n['INSTALLATION']; ?></h3>

			<table class="highlight healthcheck">
			<?php
			if (in_arrayi('curl', $php_modules)) {
				$curl_URL = $api_url .'?k='.$APIKEY.'&v='.$site_version_no;
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_TIMEOUT, 2);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_URL, $curl_URL);
				$data = curl_exec($ch);
				curl_close($ch);
				if ($data !== false)	{
					$apikey = json_decode($data);
					$verstatus = $apikey->status;
				}	else {
					$apikey = null;
					$verstatus = null;
				}
			} else {
				$verstatus = '10';
			}
			
			if ($verstatus == '0') {
				$ver = '<span class="ERRmsg" >'. $i18n['UPG_NEEDED'].' <b>'.$apikey->latest .'</b><br /><a href="http://get-simple.info/download">'. $i18n['DOWNLOAD'].'</a></span>';
			} elseif ($verstatus == '1') {
				$ver = '<span class="OKmsg" ><b>'.$site_version_no.'</b> - '. $i18n['LATEST_VERSION'].'</span>';
			} elseif ($verstatus == '2') {
				$ver = '<span class="WARNmsg" ><b>'.$site_version_no.'</b> - Beta / Bleeding Edge</span>';
			} else {
				$ver = '<span class="WARNmsg" >'. $i18n['CANNOT_CHECK'].' <b>'.$site_version_no.'</b><br /><a href="http://get-simple.info/download">'. $i18n['DOWNLOAD'].'</a></span>';
			}
			?>
			<tr><td style="width:345px;" ><?php echo $site_full_name; ?> <?php echo $i18n['VERSION'];?></td><td><?php echo $ver; ?></td></tr>
			<tr><td>
			<?php
				if (version_compare(phpversion(), "5.1.3", "<")) {
					echo 'PHP '.$i18n['VERSION'].'</td><td><span class="ERRmsg" ><b>'. phpversion().'</b> - PHP 5.1.3 '.$i18n['OR_GREATER_REQ'].' - '.$i18n['ERROR'].'</span></td></tr>';
				} else {
					echo 'PHP '.$i18n['VERSION'].'</td><td><span class="OKmsg" ><b>'. phpversion().'</b> - '.$i18n['OK'].'</span></td></tr>';
				}
				
				if (@$kill == '') {
					echo '<tr><td>Folder Permissions</td><td><span class="OKmsg" >'.$i18n['OK'].' - '.$i18n['WRITABLE'].'</span></td></tr>';
				}	else {
					echo '<tr><td>Folder Permissions</td><td><span class="ERRmsg" >'.$i18n['ERROR'].' - '.$i18n['NOT_WRITABLE'].'</span></td></tr>';
				}
				
				if  (in_arrayi('curl', $php_modules) ) {
					echo '<tr><td>cURL Module</td><td><span class="OKmsg" >'.$i18n['INSTALLED'].' - '.$i18n['OK'].'</span></td></tr>';
				} else{
					echo '<tr><td>cURL Module</td><td><span class="WARNmsg" >'.$i18n['NOT_INSTALLED'].' - '.$i18n['WARNING'].'</span></td></tr>';
				}
				
				if  (in_arrayi('gd', $php_modules) ) {
					echo '<tr><td>GD Library</td><td><span class="OKmsg" >'.$i18n['INSTALLED'].' - '.$i18n['OK'].'</span></td></tr>';
				} else{
					echo '<tr><td>GD Library</td><td><span class="WARNmsg" >'.$i18n['NOT_INSTALLED'].' - '.$i18n['WARNING'].'</span></td></tr>';
				}

				if (! in_arrayi('SimpleXML', $php_modules) ) {
					echo '<tr><td>SimpleXML Module</td><td><span class="ERRmsg" >'.$i18n['NOT_INSTALLED'].' - '.$i18n['ERROR'].'</span></td></tr>';
				} else {
					echo '<tr><td>SimpleXML Module</td><td><span class="OKmsg" >'.$i18n['INSTALLED'].' - '.$i18n['OK'].'</span></td></tr>';
				}
				
				if (! function_exists('date_default_timezone_set') ) {
					echo '<tr><td>Default Timezone Function</td><td><span class="WARNmsg" >'.$i18n['NOT_INSTALLED'].' - '.$i18n['WARNING'].'</span></td></tr>';
				} else {
					echo '<tr><td>Default Timezone Function</td><td><span class="OKmsg" >'.$i18n['INSTALLED'].' - '.$i18n['OK'].'</span></td></tr>';
				}

				if ( function_exists('apache_get_modules') ) {
					if(! in_arrayi('mod_rewrite',apache_get_modules())) {
						echo '<tr><td>Apache Mod Rewrite</td><td><span class="WARNmsg" >'.$i18n['NOT_INSTALLED'].' - '.$i18n['WARNING'].'</span></td></tr>';
					} else {
						echo '<tr><td>Apache Mod Rewrite</td><td><span class="OKmsg" >'.$i18n['INSTALLED'].' - '.$i18n['OK'].'</span></td></tr>';
					}
				} else {
					echo '<tr><td>Apache Mod Rewrite</td><td><span class="OKmsg" >'.$i18n['INSTALLED'].' - '.$i18n['OK'].'</span></td></tr>';
				}

			?>
			</table>
			<form action="setup.php" method="post" accept-charset="utf-8" >
				<p><b><?php echo $i18n['SELECT_LANGUAGE'];?></b>:<br />
				<select name="lang" class="text">
					<?php echo $langs; ?>
				</select>
				</p>
				<p><input class="submit" type="submit" name="continue" value="<?php echo $i18n['CONTINUE_SETUP'];?> &raquo;" /></p>
			</form>
			
			<small class="hint"><a href="http://get-simple.info/download/languages"><?php echo $i18n['DOWNLOAD_LANG'];?></a></small>
			
	</div>
</div>

<div class="clear"></div>
<?php get_template('footer'); ?>