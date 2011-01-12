<?php
/*
Plugin Name: External Comments
Description: Provides external comments support on pages (Disqus or IntenseDebate)
Version: 0.3
Author: Rob Antonishen
Author URI: http://ffaat.poweredbyclear.com/
*/

# get correct id for plugin
$thisfile=basename(__FILE__, ".php");

# register plugin
register_plugin(
    $thisfile, 
    'External Comments',     
    '0.3',         
    'Rob Antonishen',
    'http://ffaat.poweredbyclear.com', 
    'Provides external comments support',
    'plugins',
    'external_comments_config'  
);

# activate filter
add_action('plugins-sidebar','createSideMenu',array($thisfile,'External Comments'));
add_filter('content','external_comments_display'); 

# global vars
$external_comments_conf = external_comments_loadconf();

/* frontend content replacement */
function external_comments_display($contents) {
  $tmp_content = $contents;
  $location = stripos($tmp_content,"(% external_comments %)");
      
  if ($location !== FALSE)
  {
    $tmp_content = str_replace("(% external_comments %)","",$tmp_content);
    $start_content = substr($tmp_content, 0 ,$location);
    $end_content = substr($tmp_content, $location, strlen($tmp_content)-$location );
    
    $tmp_content = $start_content . return_external_comments() . $end_content;
  }
  // build page
  return $tmp_content;
}

/* echo the comment page code for use in templates */
function get_external_comments() {
  echo return_external_comments();
}

/* returns the disqus page code */
function return_external_comments() {
  global $external_comments_conf;
    
  if ($external_comments_conf['provider'] == "Disqus")
  {    
    $new_content = '<div id="disqus_thread"></div>';
    $new_content .= '<script type="text/javascript">';
    $new_content .= "var disqus_shortname = '" . $external_comments_conf['shortname'] . "';"; 
    $new_content .= "var disqus_developer = '" . $external_comments_conf['developer'] . "';"; 
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
  }
  elseif ($external_comments_conf['provider'] == "ID")
  {
    $new_content = '<script>';
    $new_content .= "var idcomments_acct = '" . $external_comments_conf['shortname'] . "';";
    $new_content .= "var idcomments_post_id = '" . return_page_slug() . "';";
    $new_content .= "var idcomments_post_url = '" . get_page_url(True) . "';";
    $new_content .= "</script>";
    $new_content .= '<span id="IDCommentsPostTitle" style="display:none"></span>';
    $new_content .= "<script type='text/javascript' src='http://www.intensedebate.com/js/genericCommentWrapperV2.js'></script>";
  }
  return $new_content;
}


/* backend management page */
function external_comments_config() {
  global $external_comments_conf;
  
  /* Save Settings */
  if (isset($_POST['submit_settings']))
  {
    if (isset($_POST['developer']))
    {
      $external_comments_conf['developer'] = 1;
    }
    else
    {
      $external_comments_conf['developer'] = 0;
    }
    if (isset($_POST['shortname']))
      $external_comments_conf['shortname'] = $_POST['shortname'];
    if (isset($_POST['provider']))
      $external_comments_conf['provider'] = $_POST['provider'];
    external_comments_saveconf();
    echo '<div style="display: block;" class="updated">Updated settings.</div>';
  }

  
  echo "<label>External Comments Configuration</label><br/><br/>";
  echo '<form name="settings" action="load.php?id=external_comments" method="post">';
  
  echo '<p>External Comment Service:<br />';
  echo '<input type="radio" name="provider" value="Disqus" ';
  if ($external_comments_conf['provider'] == "Disqus") echo "checked";
  echo '> Disqus<br />';
  echo '<input type="radio" name="provider" value="ID" ';
  if ($external_comments_conf['provider'] == "ID") echo "checked";
  echo '> Intense Debate<br /></p>';
  
  echo '<p>Your forum\'s ID - the unique identifier for your website from the comment provider:<br />';
  echo '<input name="shortname" type="text" size="90" value="'.$external_comments_conf['shortname'] .'"></p>';
  
  if ($external_comments_conf['provider'] == "Disqus")
  {
    echo '<input name="developer" type="checkbox" value="Y"';
    if ($external_comments_conf['developer'] == 1) echo ' checked';
    echo '>&nbsp; Developer Mode: testing the system on an inaccessible website, e.g. secured staging server or a local environment.<br /><br />';
  }
  
  echo "<input name='submit_settings' class='submit' type='submit' value='Save Settings'>\n";
  echo '</form>';
  echo '<p /><p><i>Enable comments on a single page by adding the tag <b>(% external_comments %)</b> in that page.<br />';
  echo 'Alternately insert <b>&lt?php get_external_comments(); ?&gt</b> in your page template to have comments for all pages.</i></p>';
}

/* get config settings from file */
function external_comments_loadconf()
{
  $vals=array();
  $configfile=GSDATAOTHERPATH."external_comments.xml";
  if (!file_exists($configfile))
  {
    //default settings
    $xmlstr = "<?xml version='1.0'?><settings><provider>Disqus</provider><developer>0</developer><shortname></shortname></settings>";
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

  $vals['provider'] = (string)$node->provider;
  $vals['developer'] = (int)$node->developer;
  $vals['shortname'] = (string)$node->shortname;
  
  return($vals);
}

/* save config settings to file*/
function external_comments_saveconf()
{
  global $external_comments_conf;
  $configfile=GSDATAOTHERPATH."external_comments.xml";

  $xmlstr = '<?xml version=\'1.0\'?><settings>';
  $xmlstr .= "<provider>" . $external_comments_conf['provider'] . "</provider>";
  $xmlstr .= "<developer>" . $external_comments_conf['developer'] . "</developer>";
  $xmlstr .= "<shortname>" . $external_comments_conf['shortname'] . "</shortname>";
  $xmlstr .= "</settings>";

  $fp = fopen($configfile, 'w') or exit('Unable to save ' . $configfile . ', check GetSimple privileges.');
  // save the contents of output buffer to the file
  fwrite($fp, $xmlstr);
  // close the file
  fclose($fp);
}

?>