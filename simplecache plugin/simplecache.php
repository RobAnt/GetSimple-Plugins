<?php
/*
Plugin Name: SimpleCache
Description: a simple cache
Version: 0.1
Author: Rob Antonishen
Author URI: http://ffaat.poweredbyclear.com/
*/

# get correct id for plugin
$thisfile=basename(__FILE__, ".php");

# register plugin
register_plugin(
	$thisfile, 
	'SimpleCache', 	
	'0.1', 		
	'Rob Antonishen',
	'http://ffaat.poweredbyclear.com/', 
	'A simple cache for Get Simple',
	'plugin',
	''  
);

# activate filter
add_action('changedata-save','simplecache_flush'); 
add_action('index-pretemplate','simplecache_pagestart'); 
add_action('index-posttemplate','simplecache_pageend'); 

# functions
/* clear the simplecache directory */
function simplecache_flush() 
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

/* start of page */
function simplecache_pagestart() 
{
  $simplecache_dir = GSDATAOTHERPATH.'simplecache_cache/';
  if (is_dir($simplecache_dir))
  {
    $simplecache_file = $simplecache_dir .  md5($_SERVER['REQUEST_URI']) . ".cache";
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

/* end of page */
function simplecache_pageend() 
{
  $simplecache_dir = GSDATAOTHERPATH.'simplecache_cache/';
  if (is_dir($simplecache_dir)==false)
  {
    mkdir($simplecache_dir, 0755) or exit('Unable to create .../data/other/simplecache_cache folder, check GetSimple privileges.');
  }
  if (is_dir($simplecache_dir))
  {
    $simplecache_file = $simplecache_dir .  md5($_SERVER['REQUEST_URI']) . ".cache";
    // open the cache file "cache/home.html" for writing
    $fp = fopen($simplecache_file, 'w') or exit('Unable to save ' . $simplecache_file . ', check GetSimple privileges.');
    // save the contents of output buffer to the file
    fwrite($fp, ob_get_contents());
    // close the file
    fclose($fp);
  }
  // Send the output to the browser
  ob_end_flush();
}

?>