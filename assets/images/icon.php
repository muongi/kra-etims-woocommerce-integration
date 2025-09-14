<?php
/**
 * Generate plugin icon
 * 
 * This file generates a simple icon for the KRA eTims WooCommerce plugin
 */

// Set content type
header('Content-Type: image/jpeg');

// Create image
$width = 256;
$height = 256;
$image = imagecreatetruecolor($width, $height);

// Set colors
$bg_color = imagecolorallocate($image, 0, 51, 102); // Dark blue
$text_color = imagecolorallocate($image, 255, 255, 255); // White
$accent_color = imagecolorallocate($image, 0, 153, 204); // Light blue
$highlight_color = imagecolorallocate($image, 255, 204, 0); // Yellow

// Fill background
imagefill($image, 0, 0, $bg_color);

// Draw a rounded rectangle
imagefilledrectangle($image, 30, 30, $width - 30, $height - 30, $accent_color);

// Draw KRA text
$font_size = 60;
$text = "KRA";
$text_box = imagettfbbox($font_size, 0, 'Arial', $text);
$text_width = $text_box[2] - $text_box[0];
$text_height = $text_box[1] - $text_box[7];
$x = ($width - $text_width) / 2 - 60;
$y = ($height - $text_height) / 2 + 20;
imagettftext($image, $font_size, 0, $x, $y, $text_color, 'Arial', $text);

// Draw eTims text
$font_size = 60;
$text = "eTims";
$text_box = imagettfbbox($font_size, 0, 'Arial', $text);
$text_width = $text_box[2] - $text_box[0];
$text_height = $text_box[1] - $text_box[7];
$x = ($width - $text_width) / 2 + 40;
$y = ($height - $text_height) / 2 + 20;
imagettftext($image, $font_size, 0, $x, $y, $highlight_color, 'Arial', $text);

// Draw WooCommerce text
$font_size = 24;
$text = "WooCommerce";
$text_box = imagettfbbox($font_size, 0, 'Arial', $text);
$text_width = $text_box[2] - $text_box[0];
$text_height = $text_box[1] - $text_box[7];
$x = ($width - $text_width) / 2;
$y = ($height - $text_height) / 2 + 70;
imagettftext($image, $font_size, 0, $x, $y, $text_color, 'Arial', $text);

// Output image
imagejpeg($image);

// Free memory
imagedestroy($image);
