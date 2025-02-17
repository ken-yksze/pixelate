<?php
    // https://stackoverflow.com/questions/5752514/how-to-convert-png-to-8-bit-png-using-php-gd-library

function image_pixelate($source, $destination, $new_width, $num_color) {
    $source_image = imagecreatefromstring(file_get_contents($source));
        
    // Get original dimensions
    list($width, $height) = getimagesize($source);

    $new_height = round($new_width / $width * $height);

    // Create a blank true color image with new dimensions
    $destination_image = imagecreatetruecolor($new_width, $new_height);

    // Preserve transparency (for PNGs)
    imagesavealpha($destination_image, true);
    $transparent = imagecolorallocatealpha($destination_image, 0, 0, 0, 127);
    imagefill($destination_image, 0, 0, $transparent);

    // Resize the image (downscale for pixelation)
    imagecopyresampled($destination_image, $source_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

    // Reduce colors to a fixed palette
    imagetruecolortopalette($destination_image, false, $num_color);

    // **Scale back up while keeping the pixel effect**
    $scale_factor = 30; // Adjust scale factor for desired size
    $scaled_width = $new_width * $scale_factor;
    $scaled_height = $new_height * $scale_factor;
    
    $final_image = imagecreatetruecolor($scaled_width, $scaled_height);

    // Preserve transparency in final image, prevent the black background
    imagesavealpha($final_image, true);
    $transparent_final = imagecolorallocatealpha($final_image, 0, 0, 0, 127);
    imagefill($final_image, 0, 0, $transparent_final);

    // Scale the pixelated image back up
    imagecopyresampled($final_image, $destination_image, 0, 0, 0, 0, $scaled_width, $scaled_height, $new_width, $new_height);

    // Save as PNG (supports transparency better)
    imagepng($final_image, $destination);

    // Clean up memory
    imagedestroy($destination_image);
    imagedestroy($final_image);
}

?>