<?php
// Set the content-type
header("Content-type: image/png");

// Set the enviroment variable for GD
putenv('GDFONTPATH=' . realpath('.').'/fonts/');

if (isset($_GET['text'])) {  
   $text = ($_GET['text']);  
   if(get_magic_quotes_gpc()) $text = stripslashes($text) ;
   $text = javascript_to_html($text) ;

   if (isset($_GET['color'])) $color = ($_GET['color']);
   if (!$color) $color = "000000";

   if (isset($_GET['bgcolor'])) $bgcolor = ($_GET['bgcolor']);
   if (!$bgcolor) $bgcolor = "FFFFFF";

   if (isset($_GET['size'])) $size = (int)($_GET['size']);
   if ($size == 0) $size = 12;

   if (isset($_GET['maxwidth'])) $maxwidth = (int)($_GET['maxwidth']);
   if ($maxwidth < 100) $maxwidth = 0;

   if (isset($_GET['align'])) $align = ($_GET['align']);
   if (($align<>"right") && ($align<>"center")) $align = "left";

   //transparent bg
   if (isset($_GET['transbg'])) $transbg = ($_GET['transbg']);
   if ($transbg!="on") $transbg = "off";

   //font smoothing
   if (isset($_GET['aa'])) $aa = ($_GET['aa']);
   if ($aa!="off") $aa = "on";

   // Name of the font to be used (note the lack of the .ttf extension)
   if (isset($_GET['font'])) $font = ($_GET['font']);
   if (!$font) $font = "Vera";
   $font = $font.".ttf";

   //check for font
   if (!file_exists("fonts/".$font)) 
      $font = "Vera.ttf";

   $hash = md5($font.$size.$color.$bgcolor.$text.$maxwidth.$align.$transbg.$aa);

   $cached_image = "cache/dtr_".$hash.".png";

   if (file_exists($cached_image)) {
      // send the cached image to the output buffer (browser)
      readfile($cached_image);
   }
   else
   {
      // sample text for height
      $box = imagettfbbox($size,0,$font,'ABCDEFGHIJKLMNOPQRSTUVQXYZabcdefghijlkmnopqrstuvwxyz1234567890-=[]\{}|;:",./<>?`~)(*&^%$#@!'."'");
      $height = abs($box[5]-$box[1]);

      // sample text for descender calc
      $box = imagettfbbox($size,0,$font,'M');
      $desc = $height - abs($box[5]-$box[1]);

      // Get the image size
      $box = imagettfbbox($size,0,$font,$text);
      $width = abs($box[4]-$box[0]);     

      // 2 px buffer on left and right
      $x_pad=2;
      if ($maxwidth==0) $maxwidth=$width+$x_pad*2;

      // Check for multiple lines and break into arrays
      $output=false;
      $lines=0;
      while ($width > ($maxwidth-$x_pad*2)) {
         $lines = $lines + 1;
         $i = $width;
         $t = strlen($text);

         while ($i > ($maxwidth-$x_pad*2)) {
            $t = $t-1;
            $box = ImageTTFBBox ( $size, 0, $font, substr($text, 0, $t));
            $i = abs($box[0]) + abs($box[2]);
         }
         $t = strrpos(substr($text, 0, $t), " ");
         $output[$lines-1] = substr($text, 0, $t);

         $text = ltrim(substr($text, $t));
         $output[] = $text;

         $box = ImageTTFBBox ( $size, 0, $font, $output[$lines]);
         $width = abs($box[0]) + abs($box[2]);
      }
      
      // catch one line
      $lines++;
      if (!$output) $output[] = $text;

      // Create the image
      $im =  imagecreate ($maxwidth, $height*$lines);

      // Create some colors
      $color = hex_to_rgb($color);
      $bgcolor = hex_to_rgb($bgcolor);
      $color1 = imagecolorallocate($im, $bgcolor[0], $bgcolor[1], $bgcolor[2]);
      $color2 = imagecolorallocate($im, $color[0], $color[1], $color[2]);
      if ($transbg=="on") { imagecolortransparent($im, $color1); }
      if ($aa=="off") { $color2 = -$color2; }

      // Output all the line of text as placed in array

      $i=0;
      foreach ($output as $value) 
      {
         $box = ImageTTFBBox ( $size, 0, $font, $value);
         $w = abs($box[0]-$box[2]);
         $x = 0;
         if ($align == 'right')  $x = $maxwidth-$x_pad*2-$w;
         if ($align == 'center') $x = $maxwidth/2-$w/2-$x_pad;
         if ($align == 'left')   $x = $x_pad;
         imagettftext ($im, $size, 0, $x, $height*($i+1)-$desc+$x_pad,
                       $color2, $font, $value);
         $i++;
      }

      // Using imagepng() results in clearer text compared with imagejpeg()
      imagepng($im);
      // now save a copy of the new image to the cache directory
      imagepng($im, $cached_image);
	
      imagedestroy($im);
    }
}

// Convert Javascript Unicode characters into embedded HTML entities
// (e.g. '%u2018' => '&#8216;')
function javascript_to_html($text) 
{
   $matches = null ;
   preg_match_all('/%u([0-9A-F]{4})/i',$text,$matches) ;
   if(!empty($matches)) for($i=0;$i<sizeof($matches[0]);$i++)
      $text = str_replace($matches[0][$i],
              '&#'.hexdec($matches[1][$i]).';',$text) ;
   return $text ; 
}

// Convert HEX to 255,255,255
function hex_to_rgb($hex) 
{
   // expand short form ('fff') color
   if(strlen($hex) == 3) 
   {
      $hex = substr($hex,0,1) . substr($hex,0,1) .
             substr($hex,1,1) . substr($hex,1,1) .
             substr($hex,2,1) . substr($hex,2,1) ;
   }

   if(strlen($hex) != 6) $hex="ffffff"; //error handle

   // convert
   $rgb[] = hexdec(substr($hex,0,2)) ;
   $rgb[] = hexdec(substr($hex,2,2)) ;
   $rgb[] = hexdec(substr($hex,4,2)) ;
   return $rgb ; 
}
?> 