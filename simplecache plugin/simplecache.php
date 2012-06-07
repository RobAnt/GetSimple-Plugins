<?php
/*
Plugin Name: SimpleCache
Description: a simple cache
Version: 0.9
Author: Rob Antonishen
Author URI: http://ffaat.poweredbyclear.com/
*/

// get correct id for plugin
$thisfile=basename(__FILE__, '.php');

// register plugin
register_plugin(
  $thisfile,
  'SimpleCache',
  '0.9',
  'Rob Antonishen',
  'http://ffaat.poweredbyclear.com/',
  'A simple cache for Get Simple',
  'pages',
  'simplecache_manage'
);

// activate filter
add_action('changedata-save','simplecache_flushpage');
add_action('index-pretemplate','simplecache_pagestart');
add_action('index-posttemplate','simplecache_pageend');
add_action('pages-sidebar','createSideMenu',array($thisfile,'SimpleCache'));

// global vars
$simplecache_conf = simplecache_loadconf();

/***********************************************************************************
*
* Hook functions
*
***********************************************************************************/

/* start of page */
function simplecache_pagestart() {
  global $simplecache_conf;

  //caching enabled?
  if ($simplecache_conf['enabled']=='Y') {
    // check for pages to not cache
    if(!in_array(return_page_slug(), array_merge(array('404'), $simplecache_conf['nocache']))) {
      $simplecache_dir = GSDATAOTHERPATH . 'simplecache_cache/';
      if (is_dir($simplecache_dir)) {
        $simplecache_file = $simplecache_dir .  md5(return_page_slug()) . '.cache';
        if (file_exists($simplecache_file)) {
          $cachetime = filemtime($simplecache_file);
          if (($simplecache_conf['ttl'] == 0) || ((time() - $simplecache_conf['ttl']*60) < $cachetime)) {
            // use cached file
            include($simplecache_file);
            // and stop running scripts
            exit;
          }
        }
      }
      if(!ob_start('ob_gzhandler')) ob_start();
    }
  }
}

/* end of page */
function simplecache_pageend() {
  global $simplecache_conf;

  //caching enabled?
  if ($simplecache_conf['enabled']=='Y') {
    // check for pages to not cache
    if (!in_array(return_page_slug(), array_merge(array('404'), $simplecache_conf['nocache']))) {
      $simplecache_dir = GSDATAOTHERPATH . 'simplecache_cache/';
      if (is_dir($simplecache_dir)===FALSE) {
        mkdir($simplecache_dir, 0755) or exit('Unable to create .../data/other/simplecache_cache folder, check GetSimple privileges.');
      }

      if (is_dir($simplecache_dir)) {
        $simplecache_file = $simplecache_dir .  md5(return_page_slug()) . '.cache';
        // open the cache file for writing
        $fp = fopen($simplecache_file, 'w') or exit('Unable to save ' . $simplecache_file . ', check GetSimple privileges.');

        echo "\n<!-- Page cached by SimpleCache on " . date('F d Y H:i:s', time()) . ' -->';

        // save the contents of output buffer to the file
        fwrite($fp, ob_get_contents());
        // close the file
        fclose($fp);
      }
      // Send the output to the browser
      ob_end_flush();
    }
  }
}

/***********************************************************************************
*
* Helper functions
*
***********************************************************************************/

/* clear the simplecache directory */
function simplecache_flushall() {
  $simplecache_dir = GSDATAOTHERPATH . 'simplecache_cache/';
  if (is_dir($simplecache_dir)) {
    $dir_handle = @opendir($simplecache_dir) or exit('Unable to open the folder .../data/other/simplecache_cache, check the folder privileges.');
    $filenames = array();

    while ($filename = readdir($dir_handle)) {
      $filenames[] = $filename;
    }

    if (count($filenames) != 0) {
      foreach ($filenames as $file) {
        if (!($file == '.' || $file == '..' || is_dir($simplecache_dir.$file) || $file == '.htaccess')) {
          unlink($simplecache_dir.$file) or exit('Unable to clean up the folder .../data/other/simplecache_cache, check folder content privileges.');
        }
      }
    }
  }
}

/* flush one page from cache when edited - pass in md5 hash of page slug to specify a page otherwise flushes the edited page in the back end */
function simplecache_flushpage($page = NULL) {
  $simplecache_dir = GSDATAOTHERPATH . 'simplecache_cache/';
  if ($page === null) $page = md5($_POST['post-id']);
  if (is_dir($simplecache_dir)) {
    $simplecache_file = $simplecache_dir . $page . '.cache';
    if (file_exists($simplecache_file)) {
      unlink($simplecache_file) or exit('Unable to clean up the folder .../data/other/simplecache_cache, check folder content privileges.');
    }
  }
}

/* return sorted list of page information and slugs and the slug hash */
function simplecache_pagelist() {
  $path=GSDATAPAGESPATH;
  $data='';
  $filenames = getFiles($path);
  $count="0";
  $pagesArray = array();
   
  if (count($filenames) != 0) {
    foreach ($filenames as $file) {
      if (isFile($file, $path, 'xml')) {
        $data = getXML($path .$file);
        $pagesArray[$count]['title'] = (string)html_entity_decode($data->title, ENT_QUOTES, 'UTF-8');
        $pagesArray[$count]['parent'] = (string)$data->parent;
        
        $pagesArray[$count]['sort'] = (string)$data->title;
        $parent = (string)$data->parent;
        while ($parent != '') {
          $parentdata = getXML($path . $parent .'.xml');
          $parentTitle = (string)$parentdata->title;
          $pagesArray[$count]['sort'] = $parentTitle .'^'. $pagesArray[$count]['sort'];
          $parent = (string)$parentdata->parent;
        }
        $pagesArray[$count]['slug'] = (string)$data->url;
        $pagesArray[$count]['hash'] = md5((string)$data->url);
        $parentTitle = '';
        $count++;
      }
    }
  }
  
  return(subval_sort($pagesArray, 'sort'));
}

/* save config settings to file */
function simplecache_saveconf() {
  global $simplecache_conf;

  $configfile=GSDATAOTHERPATH . 'simplecache.xml';

  $xml_root = new SimpleXMLElement('<simplecachesettings></simplecachesettings>');
  $xml_root->addchild('enabled', $simplecache_conf['enabled']);
  $xml_root->addchild('ttl', $simplecache_conf['ttl']);
  $nocache = $xml_root->addchild('nocache');
  foreach ($simplecache_conf['nocache'] as $slug) {
    $nocache->addchild('slug', $slug);
  }
    
  if ($xml_root->asXML($configfile) === FALSE) {
	exit('Unable to save ' . $configfile . ', check GetSimple privileges.');
  }
}

/* get config settings from file */
function simplecache_loadconf() {
  $vals=array();
  $configfile=GSDATAOTHERPATH . 'simplecache.xml';
  
  /*create new file if none exists */
  if (!file_exists($configfile)) {
    //default settings
    $xml_root = new SimpleXMLElement('<simplecachesettings><enabled>Y</enabled><ttl>60</ttl><nocache></nocache></simplecachesettings>');
    if ($xml_root->asXML($configfile) === FALSE) {
	  exit('Unable to save ' . $configfile . ', check GetSimple privileges.');
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
  
    $vals['enabled'] = (string)$node->enabled;
    $vals['ttl'] = (int)$node->ttl;
    
    $sluglist = $node->nocache->children();
    $vals['nocache'] = array();
    foreach($sluglist->slug as $slug) {
      $vals['nocache'][] = (string)$slug;
    }    
  }
  
  return($vals);
}

/***********************************************************************************
*
* Backend management page
*
***********************************************************************************/
function simplecache_manage() {
  global $simplecache_conf;

  $simplecache_dir = GSDATAOTHERPATH . 'simplecache_cache/';
  if (is_dir($simplecache_dir)) {
    $message = '';

    /********** Display backend page ***********/
    // display cache page data
    $pagearray = simplecache_pagelist();

    /********** post/get checks **********/
    if ((isset($_GET['cache_flush'])) && ($_GET['cache_flush'] == 'Y')) {
      // cache flush requested
      simplecache_flushall();
      $message = 'All cache files deleted.';
    } elseif (isset($_GET['cache_flushpage'])) {
      // single cache file delete requested
      simplecache_flushpage($_GET['cache_flushpage']);
      $message = 'Cache file deleted.';
    }

    // update of per page setings
    if (isset($_POST['submit_perpage'])) {
      $nocache=array();
      foreach ($pagearray as $pagedata) {
        if (!in_array($pagedata['slug'], $_POST['cache'])) {
          $nocache[] = $pagedata['slug'];
          if (!in_array($pagedata['slug'], $simplecache_conf['nocache'])) {
            simplecache_flushpage(md5($pagedata['slug']));
          }
        }
      }
      $simplecache_conf['nocache'] = $nocache;
      simplecache_saveconf();
      $message = 'Updated per-page caching.';
    }

    // update of general settings
    if (isset($_POST['submit_settings'])) {
      if (isset($_POST['enabled'])) {
        $simplecache_conf['enabled'] = 'Y';
      } else {
        $simplecache_conf['enabled'] = 'N';
      }

      if ((isset($_POST['ttl'])) && is_numeric($_POST['ttl'])) {
        $simplecache_conf['ttl'] = min(1400, max(0, (int)$_POST['ttl']));
      }

      simplecache_saveconf();
      $message = 'Updated settings.';
    }

    //disabled cache warning
    if ($simplecache_conf['enabled']=='N') {
      echo '<div class="error" style="display: block;">SimpleCache page caching is currently disabled!</div>';
    }

    if ($message!='') {
      echo '<div style="display: block;" class="updated">' . $message . '</div>';
    }
    
    /* ------------------------------ */
    /*  Display Status of Cache Pages */
     
    echo '<h3 class="floated">SimpleCache Status</h3><br/><br/>';

    $pcount = 0;
    $dir_handle = @opendir($simplecache_dir) or exit('Unable to open the folder .../data/other/simplecache_cache, check the folder privileges.');
    $filenames = array();

    while ($filename = readdir($dir_handle)) {
      $filenames[] = $filename;
    }

    //set up form to disable specific page caching
    echo '<form name="status" class="manyinputs" action="load.php?id=simplecache" method="post">';
    echo '<table class="edittable highlight">';
    echo '<tr id="tr-header" >';
    echo '<th align="center" width="30px">Cache</th>';
    echo '<th width="50%" >Page</th>';
    echo '<th><span>Cache File Date</span></th>';
    echo '<th width="10px"></th>';
    echo "</tr>\n";

    //display each page and its cache status
    if (count($pagearray) != 0) {
      foreach ($pagearray as $pagedata) {
        echo '<tr>';
        echo '<td align="center" width="30px"><input type="checkbox" name="cache[]" value = "' . $pagedata['slug'] . '"';
        if (!in_array($pagedata['slug'], $simplecache_conf['nocache'])) {
          echo ' checked';
        }
        echo '></td>';
        echo '<td width="65%" >';
        if ($pagedata['parent'] != '') {
          echo '<span>' . str_repeat('&nbsp;', 5 * (substr_count($pagedata['sort'],'^') - 1)) . '&ndash&nbsp;&nbsp;</span>'; //indent
        }
        echo $pagedata['title'] . '</td>';

        $simplecache_file = $simplecache_dir . $pagedata['hash'] . '.cache';
        if (file_exists($simplecache_file)) {
          echo '<td><span>' . date("d M Y H:i:s", filemtime($simplecache_file)) . '</span></td>';
          echo '<td class="delete" width="10px"><b><a href="' . $_SERVER['REQUEST_URI'] . '&cache_flushpage=' . $pagedata['hash'] . '" title="Delete Page Cache">X</a></b></td>';
          $pcount++;
        } else {
          echo '<td><span>Page not cached.</span></td><td width="10px"></td>';
        }
        echo "</tr>\n";
      }
    }
    closedir($dir_handle);
    echo "</table>\n";
    echo "<input name='submit_perpage' class='submit' type='submit' value='Update per-page caching'>\n";

    //delete all cached pages button
    echo '<input name="flush" class="submit" style="float: right;" type="button" value="Delete all cache files" ';
    if($pcount == 0) {
      echo ' disabled ';
    }
    echo 'onclick="window.location.href=' . "'" . $_SERVER['REQUEST_URI'] . "&cache_flush=Y'" . '">';
    echo "</form>\n";

    // general plugin settings
    echo '<br/><br/><h3 class="floated">SimpleCache Settings</h3><br/><br/>';
    echo '<form name="settings" class="manyinputs" action="load.php?id=simplecache" method="post">';
    echo '<input type="checkbox" name="enabled" value="Y"';
    if ($simplecache_conf['enabled'] == 'Y') {
      echo ' checked';
    }
    echo '>&nbsp; SimpleCache page caching enabled.<br /><br />';
    echo '<input name="ttl" type="text" size="4" value="'.$simplecache_conf['ttl'] .'">&nbsp;&nbsp;&nbsp;Life of cached pages (minutes).  Enter 0 for no time based expiry.<br /><br />';
    echo "<input name='submit_settings' class='submit' type='submit' value='Save Settings'>\n";
    echo '</form>';
  } else {
    echo 'Folder .../data/other/simplecache_cache doesn\'t exist!';
  }
}
?>