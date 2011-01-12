<?php
/*
Plugin Name: Disqus
Description: Provides Discus comments support on pages
Version: 0.1
Author: Rob Antonishen
Author URI: http://ffaat.poweredbyclear.com/
*/

# get correct id for plugin
$thisfile=basename(__FILE__, ".php");

# register plugin
register_plugin(
    $thisfile, 
    'Disqus Comments',     
    '0.1',         
    'Rob Antonishen',
    'http://ffaat.poweredbyclear.com', 
    'Provides Disqus Commenting',
    'plugins',
    'disqus_config'  
);

# activate filter
add_action('plugins-sidebar','createSideMenu',array($thisfile,'Disqus'));
add_filter('content','disqus_display'); 

# global vars
$disqus_conf = disqus_loadconf();

/* frontend contant replacement */
function disqus_display($contents) {
  $tmp_content = $contents;
  $location = stripos($tmp_content,"(% disqus %)");
      
  if ($location !== FALSE)
  {
    global $disqus_conf;
    $tmp_content = str_replace("(% disqus %)","",$tmp_content);
    $start_content = substr($tmp_content, 0 ,$location);
    $end_content = substr($tmp_content, $location, strlen($tmp_content)-$location );
    
    $new_content = '<div id="disqus_thread"></div>';
    $new_content .= '<script type="text/javascript">';
    $new_content .= "var disqus_shortname = '" . $disqus_conf['shortname'] . "';"; 
    $new_content .= "var disqus_developer = '" . $disqus_conf['developer'] . "';"; 
    $new_content .= "var disqus_identifier = '" . return_page_slug() . "';";
    $new_content .= "var disqus_url = '" . get_page_url(True) . "';";
    $new_content .= "var disqus_title = '" . return_page_title() . "';";

$new_content .= <<<INLINECODE
    (function() {
        var dsq = document.createElement('script'); dsq.type = 'text/javascript'; dsq.async = true;
        dsq.src = 'http://' + disqus_shortname + '.disqus.com/embed.js';
        (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);
    })();
</script>
<noscript>Please enable JavaScript to view the <a href="http://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>
<a href="http://disqus.com" class="dsq-brlink">blog comments powered by <span class="logo-disqus">Disqus</span></a>
INLINECODE;

    // build page
    $tmp_content = $start_content . $new_content . $end_content;
  }
  
  return $tmp_content;
}

/* backend management page */
function disqus_config() {
  global $disqus_conf;
  
  /* Save Settings */
  if (isset($_POST['submit_settings']))
  {
    if (isset($_POST['developer']))
    {
      $disqus_conf['developer'] = 1;
    }
    else
    {
      $disqus_conf['developer'] = 0;
    }
    if (isset($_POST['shortname']))
      $disqus_conf['shortname'] = $_POST['shortname'];

    disqus_saveconf();
    echo '<div style="display: block;" class="updated">Updated settings.</div>';
  }

  
  echo "<label>Disqus Configuration</label><br/><br/>";
  echo '<form name="disqus_settings" action="load.php?id=disqus" method="post">';
  echo '<p>Your forum\'s shortname, which is the unique identifier for your website as registered on Disqus:<br />';
  echo '<input name="shortname" type="text" size="90" value="'.$disqus_conf['shortname'] .'"></p>';
  echo '<input name="developer" type="checkbox" value="Y"';
  if ($disqus_conf['developer'] == 1) echo ' checked';
  echo '>&nbsp; Developer Mode: testing the system on an inaccessible website, e.g. secured staging server or a local environment.<br /><br />';  
  echo "<input name='submit_settings' class='submit' type='submit' value='Save Settings'>\n";
  echo '</form>';
  echo '<p /><p><i>Enable comments on a single page by adding the tag <b>&lt% disqus %&gt</b> in that page, or in the body of your page template to have comments on all pages.</i></p>';
}

/* get config settings from file */
function disqus_loadconf()
{
  $vals=array();
  $configfile=GSDATAOTHERPATH."disqus.xml";
  if (!file_exists($configfile))
  {
    //default settings
    $xmlstr = "<?xml version='1.0'?><disqussettings><developer>0</developer><shortname></shortname></disqussettings>";
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

  $vals['developer'] = (int)$node->developer;
  $vals['shortname'] = (string)$node->shortname;

  return($vals);
}

/* save config settings to file*/
function disqus_saveconf()
{
  global $disqus_conf;
  $configfile=GSDATAOTHERPATH."disqus.xml";

  $xmlstr = '<?xml version=\'1.0\'?><disqussettings>';
  $xmlstr .= "<developer>" . $disqus_conf['developer'] . "</developer>";
  $xmlstr .= "<shortname>" . $disqus_conf['shortname'] . "</shortname>";
  $xmlstr .= "</disqussettings>";

  $fp = fopen($configfile, 'w') or exit('Unable to save ' . $configfile . ', check GetSimple privileges.');
  // save the contents of output buffer to the file
  fwrite($fp, $xmlstr);
  // close the file
  fclose($fp);
}

?>