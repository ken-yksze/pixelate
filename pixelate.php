<?php
// https://stackoverflow.com/questions/5752514/how-to-convert-png-to-8-bit-png-using-php-gd-library

function image_pixelate($source, $destination, $new_width, $num_color)
{
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

    $target_colors = json_decode(file_get_contents("converted_colours.json"));
    $num_color = min($num_color, count($target_colors));

    // First pass: Find most frequently needed colors
    $color_usage = array_fill(0, count($target_colors), 0);

    for ($x = 0; $x < $new_width; $x++) {
        for ($y = 0; $y < $new_height; $y++) {
            $rgba = imagecolorat($destination_image, $x, $y);
            $alpha = ($rgba >> 24) & 0x7F;
            if ($alpha === 127)
                continue;

            $r = ($rgba >> 16) & 0xFF;
            $g = ($rgba >> 8) & 0xFF;
            $b = $rgba & 0xFF;

            // Find closest color index
            $min_dist = PHP_INT_MAX;
            $c_index = 0;
            foreach ($target_colors as $i => [$tr, $tg, $tb]) {
                $dist = ($r - $tr) ** 2 + ($g - $tg) ** 2 + ($b - $tb) ** 2;
                if ($dist < $min_dist) {
                    $min_dist = $dist;
                    $c_index = $i;
                    if ($dist === 0)
                        break;
                }
            }
            $color_usage[$c_index]++;
        }
    }

    // Select top N most used colors
    arsort($color_usage);
    $selected_colors = [];

    foreach (array_slice(array_keys($color_usage), 0, $num_color) as $index) {
        $selected_colors[] = $target_colors[$index];
    }

    // Preallocate selected colors
    $prealloc_colors = [];

    foreach ($selected_colors as [$tr, $tg, $tb]) {
        $prealloc_colors[] = imagecolorallocatealpha($destination_image, $tr, $tg, $tb, 0);
    }

    $transparent_color = imagecolorallocatealpha($destination_image, 0, 0, 0, 127);

    // Second pass: Apply selected colors
    for ($x = 0; $x < $new_width; $x++) {
        for ($y = 0; $y < $new_height; $y++) {
            $rgba = imagecolorat($destination_image, $x, $y);
            $alpha = ($rgba >> 24) & 0x7F;

            if ($alpha === 127) {
                imagesetpixel($destination_image, $x, $y, $transparent_color);
                continue;
            }

            $r = ($rgba >> 16) & 0xFF;
            $g = ($rgba >> 8) & 0xFF;
            $b = $rgba & 0xFF;

            // Find closest in selected colors
            $min_dist = PHP_INT_MAX;
            $c_index = 0;

            foreach ($selected_colors as $i => [$tr, $tg, $tb]) {
                $dist = ($r - $tr) ** 2 + ($g - $tg) ** 2 + ($b - $tb) ** 2;
                if ($dist < $min_dist) {
                    $min_dist = $dist;
                    $c_index = $i;
                    if ($dist === 0)
                        break;
                }
            }

            imagesetpixel($destination_image, $x, $y, $prealloc_colors[$c_index]);
        }
    }

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
    imagedestroy($source_image);
    imagedestroy($destination_image);
    imagedestroy($final_image);
}

?>