<?php
/*
Plugin Name: simpletumblr
Description: Provides a embeddable tumblr stream

Version: 0.2
Author: Rob Antonishen
Author URI: http://ffaat.poweredbyclear.com/
*/

# get correct id for plugin
$thisfile=basename(__FILE__, '.php');

# register plugin
register_plugin(
    $thisfile, 
    'simpletumblr',     
    '0.2',         
    'Rob Antonishen',
    'http://ffaat.poweredbyclear.com', 
    'Embeds a tumblr stream.',
    'plugins',
    'simpletumblr_config'  
);

# global vars
$simpletumblr_conf = simpletumblr_loadconf();

# activate filter
add_filter('content','simpletumblr_display'); 

add_action('plugins-sidebar','createSideMenu',array($thisfile,'SimpleTumblr Settings'));
add_action('theme-header','simpletumblr_header',array());

/* Inject css code into header as necessary */
function simpletumblr_header() {
  global $simpletumblr_conf, $data_index, $SITEURL;

  if (strpos($data_index->content, '(% simpletumblr %)') !== false) {
    echo '<link rel="stylesheet" type="text/css" href="' . $SITEURL . 'plugins/simpletumblr/simpletumblr.css" media="all" />';
  }
}

/* frontend content replacement */
function simpletumblr_display($contents) {
  $tmp_content = $contents;
  $location = stripos($tmp_content, '(% simpletumblr %)');
      
  if ($location !== FALSE) {
    $tmp_content = str_replace('(% simpletumblr %)','',$tmp_content);
    $start_content = substr($tmp_content, 0 ,$location);
    $end_content = substr($tmp_content, $location, strlen($tmp_content)-$location );
    
    $tmp_content = $start_content . return_simpletumblr() . $end_content;
  }
  // build page
  return $tmp_content;
}

/* returns the appropriate page code */
function return_simpletumblr($name='', $count='', $imagesize='', $type='') {
  global $simpletumblr_conf, $SITEURL;

  if ($name=='') {
    $name=$simpletumblr_conf['name'];
  }
  if ($count=='') {
    $count=$simpletumblr_conf['count'];
  }
  if ($imagesize=='') {
    $imagesize=$simpletumblr_conf['imagesize'];
  }
  if ($type=='') {
    $type=$simpletumblr_conf['type'];
  }
  
  /* change type all to empty field */
  if ($type=='all') {
    $type = '';
  }
  
  /* check for a different page start */
  if (isset($_GET['start'])) {
    $startnum = stripslashes(urldecode($_GET['start']));
  } else {
    $startnum = 0;
  }
    
  $new_content = "\n<!-- START: simpletumblr plugin embed code -->\n";  

  $new_content .= '<div id="simpletumblr-div">&nbsp;</div>' . "\n";
  $new_content .= '<script type="text/javascript">' . "\n";
  $new_content .= '<!-- ' . "\n";
  $new_content .= 'simpletumblr_name = "' . $name . '"; ' . "\n";
  $new_content .= 'simpletumblr_count = ' . $count . '; ' . "\n";
  $new_content .= 'simpletumblr_divname= "simpletumblr-div"; ' . "\n";
  $new_content .= 'simpletumblr_imagesize = ' . $imagesize . '; ' . "\n";
  $new_content .= 'simpletumblr_shortdate = true; ' . "\n";
  $new_content .= 'simpletumblr_waittime = 2000; ' . "\n";
  $new_content .= 'simpletumblr_type = "' . $type . '"; ' . "\n";
  $new_content .= 'simpletumblr_start= ' . $startnum. '; ' . "\n";
  $new_content .= '//-->' . "\n";
  $new_content .= '</script>' . "\n";
  $new_content .= '<script type="text/javascript" src="' . $SITEURL . 'plugins/simpletumblr/simpletumblr.js"></script>' . "\n";
  $new_content .= '<noscript>Please enable JavaScript to view the tumblr posts<br /></noscript>' . "\n";

  $new_content .= "\n<!-- END: simpletumblr plugin embed code -->\n";  

  return $new_content;
}

/* backend management page */
function simpletumblr_config() {
  global $simpletumblr_conf;
  
  if (isset($_POST) && sizeof($_POST)>0) {
    /* Save Settings */
    if (isset($_POST['name'])) {
      $simpletumblr_conf['name'] = $_POST['name'];
    }
    if (isset($_POST['count'])) {
      $simpletumblr_conf['count'] = $_POST['count'];
    }
    if (isset($_POST['imagesize'])) {
      $simpletumblr_conf['imagesize'] = $_POST['imagesize'];
    }
    if (isset($_POST['type'])) {
      $simpletumblr_conf['type'] = $_POST['type'];
    }
    simpletumblr_saveconf();
    echo '<div style="display: block;" class="updated">' . i18n_r('SETTINGS_UPDATED') . '.</div>';
  }

  echo '<h3 class="floated">SimpleTumblr Plugin Settings</h3><br/><br/>';
  echo '<form name="settings" action="load.php?id=simpletumblr" method="post">';
  
  echo '<label>Tumblr user name:</label><p>';
  echo '<input name="name" type="text" size="90" value="' . $simpletumblr_conf['name'] .'"></p>';      

  echo '<label>Post count to display:</label><p>';
  echo '<input name="count" type="text" size="90" value="' . $simpletumblr_conf['count'] .'"></p>';      
  
  echo '<label>Image Size:</label><p>';
  echo '<select name="imagesize">';
  echo '<option value="75"';
  if ($simpletumblr_conf['imagesize'] == '75') {
    echo ' selected="selected" ';
  }
  echo '>75 px</option>';
  echo '<option value="100"';
  if ($simpletumblr_conf['imagesize'] == '100') {
    echo ' selected="selected" ';
  }
  echo '>100 px</option>';
  echo '<option value="250"';
  if ($simpletumblr_conf['imagesize'] == '250') {
    echo ' selected="selected" ';
  }
  echo '>250 px</option>';
  echo '<option value="400"';
  if ($simpletumblr_conf['imagesize'] == '400') {
    echo ' selected="selected" ';
  }
  echo '>400 px</option>';
  echo '<option value="500"';
  if ($simpletumblr_conf['imagesize'] == '500') {
    echo ' selected="selected" ';
  }
  echo '>500 px</option>';
  echo '</select></p>';
  
  echo '<label>Type of posts to display:</label><p>';
  echo '<select name="type">';
  echo '<option value="all"';
  if ($simpletumblr_conf['type'] == 'all') {
    echo ' selected="selected" ';
  }
  echo '>All</option>';
  echo '<option value="text"';
  if ($simpletumblr_conf['type'] == 'text') {
    echo ' selected="selected" ';
  }
  echo '>Regular</option>';
  echo '<option value="quote"';
  if ($simpletumblr_conf['type'] == 'quote') {
    echo ' selected="selected" ';
  }
  echo '>Quotes</option>';
  echo '<option value="photo"';
  if ($simpletumblr_conf['type'] == 'photo') {
    echo ' selected="selected" ';
  }
  echo '>Photos</option>';
  echo '<option value="link"';
  if ($simpletumblr_conf['type'] == 'link') {
    echo ' selected="selected" ';
  }
  echo '>Links</option>';
  echo '<option value="chat"';
  if ($simpletumblr_conf['type'] == 'chat') {
    echo ' selected="selected" ';
  }
  echo '>Chat</option>';
  echo '<option value="video"';
  if ($simpletumblr_conf['type'] == 'video') {
    echo ' selected="selected" ';
  }
  echo '>Videos</option>';
  echo '<option value="audio"';
  if ($simpletumblr_conf['type'] == 'audio') {
    echo ' selected="selected" ';
  }
  echo '>Audio</option>';
  echo '</select></p>';

      
  echo "<input name='submit_settings' class='submit' type='submit' value='" . i18n_r('BTN_SAVESETTINGS') . "'><br />";
  echo '</form>';
  echo '<br /><p><i>Insert (% simpletumblr %) as the page content where you wish the tumblr stream to appear.</i></p>';
}

/* get config settings from file */
function simpletumblr_loadconf() {
  $vals=array();
  $configfile=GSDATAOTHERPATH . 'simpletumblr.xml';
  if (!file_exists($configfile)) {
    //default settings
    $xml_root = new SimpleXMLElement('<settings><name>cartocopia</name><count>10</count><imagesize>400</imagesize><type>all</type></settings>');
    if ($xml_root->asXML($configfile) === FALSE) {
	  exit('Error saving ' . $configfile . ', check folder privlidges.');
    }
    if (defined('GSCHMOD')) {
	  chmod($configfile, GSCHMOD);
    } else {
      chmod($configfile, 0755);
    }
  }

  $xml_root = simplexml_load_file($configfile);
  
  if ($xml_root !== FALSE) {
    $node = $xml_root->children();
  
    $vals['name'] = (string)$node->name;
    $vals['count'] = min(50, max(1, (int)$node->count));
    if (in_array((int)$node->imagesize, array(75, 100, 250, 400, 500))) {
      $vals['imagesize'] = (int)$node->imagesize;
    } else {
      $vals['imagesize'] = 400;
    }
    if (in_array((string)$node->type, array('all', 'text', 'quote', 'photo', 'link', 'chat', 'video', 'audio'))) {
      $vals['type'] = (string)$node->type;
    } else {
      $vals['type'] = 'all';
    }
  }
  return($vals);
}

/* save config settings to file*/
function simpletumblr_saveconf() {
  global $simpletumblr_conf;
  $configfile=GSDATAOTHERPATH . 'simpletumblr.xml';

  $xml_root = new SimpleXMLElement('<settings></settings>');
  $xml_root->addchild('name', $simpletumblr_conf['name']);
  $xml_root->addchild('count', $simpletumblr_conf['count']);
  $xml_root->addchild('imagesize', $simpletumblr_conf['imagesize']);
  $xml_root->addchild('type', $simpletumblr_conf['type']);
  
  if ($xml_root->asXML($configfile) === FALSE) {
	exit('Error saving ' . $configfile . ', check folder privlidges.');
  }
}
?>