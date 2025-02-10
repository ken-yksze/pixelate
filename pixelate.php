<?php

    // https://stackoverflow.com/questions/5752514/how-to-convert-png-to-8-bit-png-using-php-gd-library

    function image_pixelate($source, $destination, $new_width, $num_color) {

        $source_image = imagecreatefromjpeg($source);
        
        // Get original dimensions
        list($width, $height) = getimagesize($source);

        $new_height = round($new_width/$width * $height);

        // Create a blank true color image with new dimensions
        $destination_image = imagecreatetruecolor($new_width, $new_height);

        // Resize the image
        imagecopyresampled($destination_image, $source_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

        // Reduce colors to a fixed palette
        imagetruecolortopalette($destination_image, false, $num_color);

        // Save as GIF
        imagegif($destination_image, $destination);

        imagedestroy($destination_image);
    }
?>