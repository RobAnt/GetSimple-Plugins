<?php
/*
Plugin Name: Dynamic Text Replacment
Description: Replaces text with GD generated imaged
Version: 0.1
Author: Rob Antonishen
Author URI: http://ffaat.poweredbyclear.com/
Usage is [DTR]Text to Render[/DTR]
full syntax is [DTR font=xxx; color=rrggbb; aa=[on]|off; 
bgcolor=rrggbb; transbg=on|[off]; align=[left]|center|right;
size=zzz; maxwidth=yyy;]Text to Render[/DTR]
If maxwidth set to 0 then it will dynamically calculate the width [default]
*/

# get correct id for plugin
$thisfile=basename(__FILE__, ".php");

# register plugin
register_plugin(
    $thisfile, 
    'Dynamic Text Replacement',     
    '0.1',         
    'Rob Antonishen',
    'http://ffaat.poweredbyclear.com', 
    'Replaces text with GD generated imaged',
    'plugins',
    'dtr_config'  
);

# activate filter
add_action('plugins-sidebar','createSideMenu',array($thisfile,'Dynamic Text Replacement'));
add_filter('content','dtr_display'); 

# global vars
$dtr_conf = dtr_loadconf();

/* frontend contant replacement */
function dtr_display($contents) {
  $preg_flags = 'ei';
  $output = preg_replace("'\[DTR\s*([^\]]*)]([^\/]*)\[/DTR]'$preg_flags", "dtr_main('\\2', trim('\\1'))",$contents);
  return $output;
}

/* returns the dtr page code */
function dtr_main($text, $params) {
  global $dtr_conf;
  if (strlen($text)==0) 
  {
    $output="";
  }
  else 
  {           
    $font = $dtr_conf['font'];
    $size = $dtr_conf['size'];
    $color = $dtr_conf['color'];
    $bgcolor = $dtr_conf['bgcolor'];
    $align = $dtr_conf['align'];
    $maxwidth = $dtr_conf['maxwidth'];
    if ($dtr_conf['aa']=="Y") $aa="on";
    else $aa="off";
    if ($dtr_conf['transbg']=="Y") $transbg="on";
    else $transbg="off";
    $paramlist = array_filter(explode(";", $params));
    //split up the parameters
    $param_array = array();
    foreach($paramlist as $parameter) 
    {
      $temp = explode("=",$parameter);
      $param_array[trim(strtolower($temp[0]))] = trim($temp[1]);
    }
     
    if (isset($param_array['font'])) $font=$param_array['font'];
    if (isset($param_array['size'])) $size=$param_array['size'];
    if (isset($param_array['color'])) $color=$param_array['color'];
    if (isset($param_array['bgcolor'])) $bgcolor=$param_array['bgcolor'];
    if (isset($param_array['align'])) $align=$param_array['align'];
    if (isset($param_array['maxwidth'])) $maxwidth=$param_array['maxwidth'];
    if (isset($param_array['aa'])) $aa=$param_array['aa'];
    if (isset($param_array['transbg'])) $transbg=$param_array['transbg'];
 
    // build the www path:
    $me = $_SERVER['PHP_SELF'];
    $Apathweb = explode("/", $me);
    $myFileName = array_pop($Apathweb);
    $pathweb = implode("/", $Apathweb);
    $myURL = "http://".$_SERVER['HTTP_HOST'].$pathweb."/";
    $imageURL = $myURL."plugins/dtr/dtr.php";
    $output = "<IMG SRC=\"$imageURL?";
    $output .= "font=$font&size=$size&color=$color";
    $output .= "&bgcolor=$bgcolor&align=$align";
    $output .= "&maxwidth=$maxwidth&aa=$aa&transbg=$transbg";
    $output .= "&text=$text\" ALIGN =\"middle\" ALT=\"$text\">";             }
  return $output;   
}

/* clear the simplecache directory */
function dtr_flushall()
{
  $cachedir = GSPLUGINPATH . 'dtr/cache/';
  if (is_dir($cachedir))
  {
    $dir_handle = @opendir($cachedir) or exit('Unable to open the folder ' . $cachedir . ', check the folder privileges.');
    $filenames = array();

    while ($filename = readdir($dir_handle))
    {
      $filenames[] = $filename;
    }

    if (count($filenames) != 0)
    {
      foreach ($filenames as $file)
      {
        if (!($file == '.' || $file == '..' || is_dir($cachedir.$file) || $file == '.htaccess'))
        {
          unlink($cachedir.$file) or exit('Unable to clean up the folder ' . $cachedir . ', check folder content privileges.');
        }
      }
    }
  }
}

/* backend management page */
function dtr_config() {
  global $dtr_conf;

  /********** post/get checks **********/  
  /* Save Settings */
  if (isset($_POST['submit_settings']))
  {
    if (isset($_POST['font']))
      $dtr_conf['font'] = $_POST['font'];
    if (isset($_POST['size']))
      $dtr_conf['size'] = $_POST['size'];      
    if (isset($_POST['color']))
      $dtr_conf['color'] = $_POST['color']; 
    if (isset($_POST['bgcolor']))
      $dtr_conf['bgcolor'] = $_POST['bgcolor'];
    if (isset($_POST['align']))
      $dtr_conf['align'] = $_POST['align'];
    if (isset($_POST['maxwidth']))
      $dtr_conf['maxwidth'] = $_POST['maxwidth'];
    if (isset($_POST['aa'])) $dtr_conf['aa'] = "Y";
    else $dtr_conf['aa'] = "N";          
    if (isset($_POST['transbg'])) $dtr_conf['transbg'] = "Y";
    else $dtr_conf['transbg'] = "N";
    	
    dtr_saveconf();
    echo '<div style="display: block;" class="updated">Updated settings.</div>';
  }
  
  // cache flush requested
  if ((isset($_GET['cache_flush'])) && ($_GET['cache_flush'] == "Y"))
  {
    dtr_flushall();
    echo '<div style="display: block;" class="updated">DTR Cache files deleted.</div>';
  }
 
  // load up the font files in a pick list
  $fontlist= array();
  if ($handle = opendir(realpath(GSPLUGINPATH . 'dtr/fonts/'))) 
  {
    while (false !== ($file = readdir($handle))) 
	{
      if (strpos(strtoupper($file),".TTF")) 
	  {
        $ffile = substr($file,0,strlen($file)-4);
        $fontlist[$ffile]=$ffile;
      }
    }
    closedir($handle);
  }
  natcasesort($fontlist);
  
  echo "<label>Dynamic Text Replacemnt Defaults</label><br/><br/>";
  echo '<form name="dtr_settings" action="load.php?id=dynamic%20text%20replacement" method="post">';
  
  echo '<p>Default Font:&nbsp;';
  echo '<select name="font">';
  foreach ($fontlist as $font)
  {
    if ($font == $dtr_conf['font'])
	  echo '<option value="' . $font . '" selected="selected">' . $font . "</option>";
	else
	  echo '<option value="' . $font . '">' . $font . "</option>";
  }
  echo '</select>';
  
  echo '<p>Default text size (Default is 12):&nbsp;';
  echo '<input name="size" type="text" size="10" value="'.$dtr_conf['size'] .'"></p>';
  
  echo '<p>Default text color (In hex.  Default is 000000):&nbsp;';
  echo '<input name="color" type="text" size="10" value="'.$dtr_conf['color'] .'"></p>';
  
  echo '<p>Default background color (In hex.  Default is FFFFFF):&nbsp;';
  echo '<input name="bgcolor" type="text" size="10" value="'.$dtr_conf['bgcolor'] .'"></p>';
  
  echo '<p>Default Alignment:&nbsp;';
  $alignlist = array("left", "center", "right");
  echo '<select name="align">';
  foreach ($alignlist as $align)
  {
    if ($font == $dtr_conf['align'])
	  echo '<option value="' . $align . '" selected="selected">' . $align . "</option>";
	else
	  echo '<option value="' . $align . '">' . $align . "</option>";
  }
  echo '</select>';
  
  echo '<p>Default maximum image width. If set to 0 (default) the width will be calculated and the text will be on one line:&nbsp;';
  echo '<input name="maxwidth" type="text" size="10" value="'.$dtr_conf['maxwidth'] .'"></p>';
  
  echo '<input name="aa" type="checkbox" value="Y"';
  if ($dtr_conf['aa']=="Y") echo ' checked';
  echo '>&nbsp; Antialiasing Text<br /><br />';  

  echo '<input name="transbg" type="checkbox" value="Y"';
  if ($dtr_conf['transbg']=="Y") echo ' checked';
  echo '>&nbsp; Use transparent background.<br /><br />';  
  
  echo "<input name='submit_settings' class='submit' type='submit' value='Save Settings'>\n";


  echo "<br /><br /><label>Dynamic Text Replacemnt Status</label>";
  /* delete cache */
  $fileglob = GSPLUGINPATH . 'dtr/cache/'.'dtr_*.png';
  $cachecount = count(glob($fileglob));
  echo "<br /><br /><p>".$cachecount." DTR Cached Image Files</p>";
  
  echo "<input name='flush' class='submit' type='button' value='Delete all DTR cache files' ";
  if($cachecount== 0)
  {
    echo " disabled ";
  }
  echo 'onclick="window.location.href=' . "'" . $_SERVER["REQUEST_URI"] . "&cache_flush=Y'" . '">';
    
  echo '</form>';
}

/* get config settings from file */
function dtr_loadconf()
{
  $vals=array();
  $configfile=GSDATAOTHERPATH."dtr.xml";
  if (!file_exists($configfile))
  {
    //default settings
    $xmlstr = "<?xml version='1.0'?><dtrsettings><font>Vera</font><size>12</size><color>000000</color><bgcolor>FFFFFF</bgcolor><align>left</align><maxwidth>0</maxwidth><aa>Y</aa><transbg>N</transbg></dtrsettings>";
    $fp = fopen($configfile, 'w') or exit('Unable to save ' . $configfile . ', check GetSimple privileges.');
    // save the contents of output buffer to the file
    fwrite($fp, $xmlstr);
    // close the file
    fclose($fp);
  }

  $fp = @fopen($configfile,"r");
  $xmlvals = getXML($configfile);
  fclose($fp);

  $node = $xmlvals->children();

  $vals['font'] = (string)$node->font;
  $vals['size'] = (int)$node->size;
  $vals['color'] = (string)$node->color;
  $vals['bgcolor'] = (string)$node->bgcolor;
  $vals['align'] = (string)$node->align;
  $vals['maxwidth'] = (int)$node->maxwidth;
  $vals['aa'] = (string)$node->aa;
  $vals['transbg'] = (string)$node->transbg;
  
  return($vals);
}

/* save config settings to file*/
function dtr_saveconf()
{
  global $dtr_conf;
  $configfile=GSDATAOTHERPATH."dtr.xml";

  $xmlstr = '<?xml version=\'1.0\'?><dtrsettings>';
  $xmlstr .= "<font>" . $dtr_conf['font'] . "</font>";
  $xmlstr .= "<size>" . $dtr_conf['size'] . "</size>";
  $xmlstr .= "<color>" . $dtr_conf['color'] . "</color>";
  $xmlstr .= "<bgcolor>" . $dtr_conf['bgcolor'] . "</bgcolor>";
  $xmlstr .= "<align>" . $dtr_conf['align'] . "</align>";
  $xmlstr .= "<maxwidth>" . $dtr_conf['maxwidth'] . "</maxwidth>";
  $xmlstr .= "<aa>" . $dtr_conf['aa'] . "</aa>";
  $xmlstr .= "<transbg>" . $dtr_conf['transbg'] . "</transbg>";  
  $xmlstr .= "</dtrsettings>";

  $fp = fopen($configfile, 'w') or exit('Unable to save ' . $configfile . ', check GetSimple privileges.');
  // save the contents of output buffer to the file
  fwrite($fp, $xmlstr);
  // close the file
  fclose($fp);
}

?>