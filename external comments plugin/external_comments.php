<?php
/*
Plugin Name: External Comments
Description: Provides external comments support on pages supporting:
- Disqus 
- IntenseDebate
- Livefyre
- Facebook connect

*Can optionally override the page information so this call can be used
in external plugins like the News manager.
*Supports i18n language files

Version: 0.8
Author: Rob Antonishen
Author URI: http://ffaat.poweredbyclear.com/
*/

# get correct id for plugin
$thisfile=basename(__FILE__, ".php");

#load internationalzation
i18n_merge('external_comments') || i18n_merge('external_comments','en_US');

# register plugin
register_plugin(
    $thisfile, 
    'External Comments',     
    '0.8',         
    'Rob Antonishen',
    'http://ffaat.poweredbyclear.com', 
    'Provides external comments support',
    'plugins',
    'external_comments_config'  
);

# global vars
$external_comments_conf = external_comments_loadconf();

# activate filter
add_filter('content','external_comments_display'); 

add_action('plugins-sidebar','createSideMenu',array($thisfile,'External Comments'));
add_action('theme-header','external_comments_header',array());

/* Inject meta code for facebook admin into header as necessary */
function external_comments_header(){
  global $external_comments_conf, $data_index;

  if (strpos($data_index->content, '(% external_comments %)') !== FALSE && $external_comments_conf['provider'] == "facebook")
  {
    echo '<meta property="fb:admins" content="' . $external_comments_conf['shortname'] . '" />'."\n";
  }
}

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
function get_external_comments($PostID="", $PageURL="", $PageTitle="") {
  echo return_external_comments($PostID, $PageURL, $PageTitle);
}

/* returns the appropriate page code */
function return_external_comments($PostID="", $PageURL="", $PageTitle="") {
  global $external_comments_conf;

  if ($PostID=="")
  {
    $PostID=return_page_slug();
  }
  if ($PageURL=="")
  {
    $PageURL=get_page_url(True);
  }
  if ($PageTitle=="")
  {
    $PageTitle=return_page_title();
  }
    
  $new_content = "\n<!-- START: external_coments plugin embed code -->\n";  
    
  switch ($external_comments_conf['provider']) 
  {
    case "Disqus":    
      $new_content .= '<div id="disqus_thread"></div>';
      $new_content .= '<script type="text/javascript">';
      $new_content .= "var disqus_shortname = '" . $external_comments_conf['shortname'] . "';"; 
      $new_content .= "var disqus_developer = '" . $external_comments_conf['developer'] . "';"; 
      $new_content .= "var disqus_identifier = '" . $PostID . "';";
      $new_content .= "var disqus_url = '" . $PageURL . "';";
      $new_content .= "var disqus_title = '" . $PageTitle . "';";
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
      break;
    
    case "ID":
      $new_content .= '<script>';
      $new_content .= "var idcomments_acct = '" . $external_comments_conf['shortname'] . "';";
      $new_content .= "var idcomments_post_id = '" . $PostID . "';";
      $new_content .= "var idcomments_post_url = '" . $PageURL . "';";
      $new_content .= "</script>";
      $new_content .= '<span id="IDCommentsPostTitle" style="display:none"></span>';
      $new_content .= "<script type='text/javascript' src='http://www.intensedebate.com/js/genericCommentWrapperV2.js'></script>";
      break;

    case "livefyre":
      $new_content .= "<script type='text/javascript' src='http://livefyre.com/wjs/javascripts/livefyre.js'></script>";
      $new_content .= "<script type='text/javascript'>var fyre = LF({site_id: " . $external_comments_conf['shortname'];
      $new_content .= ", article_id: '" . $PostID . "', version: '1.0' }); </script>";
      break;
      
    case "facebook":
      $new_content .= '<div id="fb-root"></div>';
      $new_content .= '<script src="http://connect.facebook.net/en_US/all.js#appId=APP_ID&amp;xfbml=1"></script>';
      $new_content .= '<fb:comments href="' . $PageURL . '" num_posts="" width=""></fb:comments>';
      break;      
  }

  $new_content .= "\n<!-- END: external_coments plugin embed code -->\n";  

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
    echo '<div style="display: block;" class="updated">' . i18n_r("external_comments/MSG_UPDATED") . '.</div>';
  }
  
  echo "<label>" . i18n_r("external_comments/PLUGINTITLE") . "</label><br/><br/>";
  echo '<form name="settings" action="load.php?id=external_comments" method="post">';
  
  echo '<p>' . i18n_r("external_comments/LBL_SERVICE") . ':<br />';
  echo '<input type="radio" name="provider" value="Disqus" ';
  if ($external_comments_conf['provider'] == "Disqus") echo "checked";
  echo '> Disqus<br />';
  echo '<input type="radio" name="provider" value="ID" ';
  if ($external_comments_conf['provider'] == "ID") echo "checked";
  echo '> Intense Debate<br />';
  echo '<input type="radio" name="provider" value="livefyre" ';
  if ($external_comments_conf['provider'] == "livefyre") echo "checked";
  echo '> Livefyre<br />';
  echo '<input type="radio" name="provider" value="facebook" ';
  if ($external_comments_conf['provider'] == "facebook") echo "checked";
  echo '> Facebook Comments<br /></p>';

  echo "<input name='submit_settings' class='submit' type='submit' value='" . i18n_r("external_comments/BTN_SERVICE") . "'><br /><br /><br />";
    
  switch ($external_comments_conf['provider']) {
    case "Disqus":
      echo '<p>' . i18n_r("external_comments/LBL_DISQUSID") . ':';
      echo '<input name="shortname" type="text" size="90" value="'.$external_comments_conf['shortname'] .'"></p>';      
      echo '<p><input name="developer" type="checkbox" value="Y"';
      if ($external_comments_conf['developer'] == 1) echo ' checked';
      echo '>&nbsp; ' . i18n_r("external_comments/LBL_DISQUSDEV") . '</p><br />';
      break;
    case "ID":
      echo '<p>' . i18n_r("external_comments/LBL_IDID") . ':';
      echo '<input name="shortname" type="text" size="90" value="'.$external_comments_conf['shortname'] .'"></p>';      
      break;
    case "livefyre":
      echo '<p>' . i18n_r("external_comments/LBL_LIVEFYREID") . ':';
      echo '<input name="shortname" type="text" size="90" value="'.$external_comments_conf['shortname'] .'"></p>';      
      break;
    case "facebook":
      echo '<p>' . i18n_r("external_comments/LBL_FACEBOOKID") . ':';
      echo '<input name="shortname" type="text" size="90" value="'.$external_comments_conf['shortname'] .'"></p>';      
      break;
  }
      
  echo "<input name='submit_settings' class='submit' type='submit' value='" . i18n_r("external_comments/BTN_SAVE") . "'><br />";
  echo '</form>';
  echo '<p /><p><i>' . i18n_r("external_comments/PLUGINHELP") . '</i></p>';
}

/* get config settings from file */
function external_comments_loadconf()
{
  $vals=array();
  $configfile=GSDATAOTHERPATH."external_comments.xml";
  if (!file_exists($configfile))
  {
    //default settings
    $xml_root = new SimpleXMLElement('<settings><provider>Disqus</provider><developer>0</developer><shortname></shortname></settings>');
    if ($xml_root->asXML($configfile) === FALSE)
    {
	  exit(i18n_r("external_comments/MSG_SAVEERROR") . ' ' . $configfile . ', ' . i18n_r("external_comments/MSG_CHECKPRIV"));
    }
    if (defined('GSCHMOD')) 
    {
	  chmod($file, GSCHMOD);
    } 
    else 
    {
      chmod($file, 0755);
    }
  }

  $xml_root = simplexml_load_file($configfile);
  
  if ($xml_root !== FALSE)
  {
    $node = $xml_root->children();
  
    $vals['provider'] = (string)$node->provider;
    $vals['developer'] = (int)$node->developer;
    $vals['shortname'] = (string)$node->shortname;
  }
  return($vals);
}

/* save config settings to file*/
function external_comments_saveconf()
{
  global $external_comments_conf;
  $configfile=GSDATAOTHERPATH."external_comments.xml";

  $xml_root = new SimpleXMLElement('<settings></settings>');
  $xml_root->addchild('provider', $external_comments_conf['provider']);
  $xml_root->addchild('developer', $external_comments_conf['developer']);
  $xml_root->addchild('shortname', $external_comments_conf['shortname']);
  
  if ($xml_root->asXML($configfile) === FALSE)
  {
	exit(i18n_r("external_comments/MSG_SAVEERROR") . ' ' . $configfile . ', ' . i18n_r("external_comments/MSG_CHECKPRIV"));
  }
}

?>