<?php
/*
Plugin Name: SimpleCache
Description: a simple cache
Version: 0.3
Author: Rob Antonishen
Author URI: http://ffaat.poweredbyclear.com/
*/

// get correct id for plugin
$thisfile=basename(__FILE__, ".php");

// register plugin
register_plugin(
	$thisfile, 
	'SimpleCache', 	
	'0.3', 		
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
add_action('pages-sidebar','createSideMenu',array($thisfile,'SimpleCache Status'));

// functions

/* clear the simplecache directory */
function simplecache_flushall() 
{
  $simplecache_dir = GSDATAOTHERPATH.'simplecache_cache/';
  if (is_dir($simplecache_dir))
  {
    $dir_handle = @opendir($simplecache_dir) or exit('Unable to open the folder .../data/other/simplecache_cache, check the folder privileges.');
    $filenames = array();

    while ($filename = readdir($dir_handle))
    {
      $filenames[] = $filename;
    }

    if (count($filenames) != 0)
    {
      foreach ($filenames as $file) 
      {
        if (!($file == '.' || $file == '..' || is_dir($simplecache_dir.$file) || $file == '.htaccess'))
        {
          unlink($simplecache_dir.$file) or exit('Unable to clean up the folder .../data/other/simplecache_cache, check folder content privileges.');
        }
      }
    }
  }
}

/* flush one page from cache when edited - pass in md5 hash of page slug*/
function simplecache_flushpage($page=NULL) 
{
  $simplecache_dir = GSDATAOTHERPATH.'simplecache_cache/';
  if ($page === null) $page = md5($_POST['post-id']);
  if (is_dir($simplecache_dir))
  {
    $simplecache_file = $simplecache_dir . $page . ".cache";
    if (file_exists($simplecache_file)) 
    {
      unlink($simplecache_file) or exit('Unable to clean up the folder .../data/other/simplecache_cache, check folder content privileges.');
    }
  }
}

/* start of page */
function simplecache_pagestart() 
{
  // check for pages to not cache
  if(!in_array(return_page_slug(), array("404")))
  {  
    $simplecache_dir = GSDATAOTHERPATH.'simplecache_cache/';
    if (is_dir($simplecache_dir))
    {
      $simplecache_file = $simplecache_dir .  md5(return_page_slug()) . ".cache";
      if (file_exists($simplecache_file)) 
      {
        // use cached file
        include($simplecache_file); 
        // and stop running scripts
        exit;
      }
    }

    if(!ob_start("ob_gzhandler")) ob_start();
  }
}

/* end of page */
function simplecache_pageend() 
{
  // check for pages to not cache
  if(!in_array(return_page_slug(), array("404")))
  {
    $simplecache_dir = GSDATAOTHERPATH.'simplecache_cache/';
    if (is_dir($simplecache_dir)==false)
    {
      mkdir($simplecache_dir, 0755) or exit('Unable to create .../data/other/simplecache_cache folder, check GetSimple privileges.');
    }
  
    if (is_dir($simplecache_dir))
    {
      $simplecache_file = $simplecache_dir .  md5(return_page_slug()) . ".cache";
      // open the cache file for writing
      $fp = fopen($simplecache_file, 'w') or exit('Unable to save ' . $simplecache_file . ', check GetSimple privileges.');
      // save the contents of output buffer to the file
      fwrite($fp, ob_get_contents());
      // close the file
      fclose($fp);
    }
    // Send the output to the browser
    ob_end_flush();
  }
}

/* get list of page titles and slugs and the slug hash */
function simplecache_pagelist() {
  $path=GSDATAPAGESPATH;
  $data=''; 
  
  $count=0;
  $pagelist= array(); 
  if (is_dir($path))
  {
    if ($dh = opendir($path))
    {
      while (($file = readdir($dh)) !== false)
      {
        if($file!="." AND $file!=".." AND $file!=".htaccess")
        {
          $pathtofile="$path$file";            
          $da = @fopen($pathtofile,"r");
          $data=getXML($pathtofile);  
          
          $node=$data->children();
          $pagelist[$count]['slug'] = (string)$node->url;
          $pagelist[$count]['menu'] = (string)$node->menu;
          $pagelist[$count]['title'] = (string)$node->title;
          $pagelist[$count]['hash'] = md5((string)$node->url);
          
          $count++;

          @fclose($da);
        }
      }  
      closedir($dh);
    }
  }
  
  $pagelist= subval_sort($pagelist,'title');
  
  return($pagelist); 
}

function simplecache_manage()
{
  echo '<label>SimpleCache</label><br/><br/>';

  $simplecache_dir = GSDATAOTHERPATH.'simplecache_cache/';
  if (is_dir($simplecache_dir))
  {
    // cache flush requested
    if ((isset($_GET['cache_flush'])) && ($_GET['cache_flush'] == "Y"))
    {
      simplecache_flushall();
    }
 
    // single cache file delete requested
    if (isset($_GET['cache_flushpage']))
    {
      simplecache_flushpage($_GET['cache_flushpage']);
    }
        
        
    $pagearray = simplecache_pagelist();
    
    $pcount = 0;
    $dir_handle = @opendir($simplecache_dir) or exit('Unable to open the folder .../data/other/simplecache_cache, check the folder privileges.');
    $filenames = array();
    
    while ($filename = readdir($dir_handle))
    {
      $filenames[] = $filename;
    }

    echo '<table class="edittable highlight paginate">';   
    echo '<tr id="tr-header" >';
    echo '<th width="50%" >Page</th>';
    echo '<th><span>Cache File Date</span></th>';
    echo '<th width="10px"></th>';
    echo "</tr>\n";       
    
    //display each page and its cache status
    if (count($pagearray) != 0)
    {
      foreach ($pagearray as $pagedata) 
      {
        echo '<tr>';
        echo '<td width="50%" >' . $pagedata['title'] . '</td>';

        $simplecache_file = $simplecache_dir . $pagedata['hash'] . ".cache";
        if (file_exists($simplecache_file)) 
        {
          echo '<td>' . date("F d Y H:i:s", filemtime($simplecache_file)) . '</td>';
          echo '<td class="delete" width="10px"><b><a href="' . $_SERVER["REQUEST_URI"] . '&cache_flushpage=' . $pagedata['hash'] . '" title="Delete Page Cache">X</a></b></td>';
          $pcount++;     
        }
        else
        {
          echo '<td>Not Cached.</td><td width="10px"></td>';
        }

        echo "</tr>\n";   
          
      }
    }
    closedir($dir_handle); 
    echo "</table>\n";

    if($pcount != 0)
    {
      echo '<br/><a href="'.$_SERVER["REQUEST_URI"].'&cache_flush=Y">Delete all cache files</a>';
    }
    
  } 
  else 
  {
    echo "Folder .../data/other/simplecache_cache doesn't exist!";
  }
}
?>