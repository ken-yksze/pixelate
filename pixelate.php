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

    // Second pass: Apply selected colors
    for ($x = 0; $x < $new_width; $x++) {
        for ($y = 0; $y < $new_height; $y++) {
            $rgba = imagecolorat($destination_image, $x, $y);
            $alpha = ($rgba >> 24) & 0x7F;

            if ($alpha === 127) {
                imagesetpixel($destination_image, $x, $y, $transparent);
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

function generate_pixelate_instruction($source, $destination, $new_width)
{
    $circle_diameter = 3840 / $new_width;
    $source_image = imagecreatefromstring(file_get_contents($source));

    // Get original dimensions
    list($width, $height) = getimagesize($source);
    $new_height = round($new_width / $width * $height);

    $temp_image = imagecreatetruecolor($new_width, $new_height);
    imagecopyresampled($temp_image, $source_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    $used_hex = [];

    for ($x = 0; $x < $new_width; $x++) {
        for ($y = 0; $y < $new_height; $y++) {
            $rgba = imagecolorat($temp_image, $x, $y);
            $alpha = ($rgba >> 24) & 0x7F;

            if ($alpha === 127) {
                continue;
            }

            // Extract RGB components
            $r = ($rgba >> 16) & 0xFF;
            $g = ($rgba >> 8) & 0xFF;
            $b = $rgba & 0xFF;

            // Compute hexadecimal representation
            $hex = sprintf('%02x%02x%02x', $r, $g, $b);
            $used_hex[$hex] = true;
        }
    }

    // Create a blank true color image with new dimensions
    $destination_image = imagecreatetruecolor($new_width * $circle_diameter / 3 + ($new_width + 1) * $circle_diameter, max((count($used_hex) + 1) * $circle_diameter, ($new_height + 1) * $circle_diameter));

    // Preserve transparency (for PNGs)
    imagesavealpha($destination_image, true);
    $black = imagecolorallocatealpha($destination_image, 0, 0, 0, 0);
    imagefill($destination_image, 0, 0, $black);

    $colours_to_info = get_object_vars(json_decode(file_get_contents("colours_to_info.json")));

    // Second pass: Apply selected colors
    for ($x = 0; $x < $new_width; $x++) {
        for ($y = 0; $y < $new_height; $y++) {
            $rgba = imagecolorat($temp_image, $x, $y);
            $alpha = ($rgba >> 24) & 0x7F;

            if ($alpha === 127) {
                continue;
            }

            // Extract RGB components
            $r = ($rgba >> 16) & 0xFF;
            $g = ($rgba >> 8) & 0xFF;
            $b = $rgba & 0xFF;

            // Compute hexadecimal representation
            $hex = sprintf('%02x%02x%02x', $r, $g, $b);
            $used_hex[$hex] = true;

            // Determine circle center coordinates
            $circle_center_x = $new_width * $circle_diameter / 3 + ($x + 1) * $circle_diameter;
            $circle_center_y = ($y + 1) * $circle_diameter;

            // Allocate circle color
            $ellipse_color = imagecolorallocate($destination_image, $r, $g, $b);

            // Calculate relative luminance
            $luminance = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;

            // Determine text color based on luminance
            if ($luminance > 128) {
                // Light background, use dark text
                $text_color = imagecolorallocate($destination_image, 0, 0, 0); // Black
            } else {
                // Dark background, use light text
                $text_color = imagecolorallocate($destination_image, 255, 255, 255); // White
            }

            // Draw the circle
            imagefilledellipse($destination_image, $circle_center_x, $circle_center_y, $circle_diameter, $circle_diameter, $ellipse_color);

            // Define text properties
            $text = $colours_to_info[$hex]->id;
            $font_size = $circle_diameter / 3;
            $font_file = 'Arial.ttf'; // Ensure this path is correct and the font file is accessible

            // Calculate text bounding box
            $bbox = imagettfbbox($font_size, 0, $font_file, $text);

            // Determine text width and height
            $text_width = abs($bbox[4] - $bbox[0]);
            $text_height = abs($bbox[5] - $bbox[1]);

            // Compute text coordinates for centering
            $text_x = $circle_center_x - ($text_width / 2);
            $text_y = $circle_center_y + ($text_height / 2);

            // Render the text
            imagettftext($destination_image, $font_size, 0, $text_x, $text_y, $text_color, $font_file, $text);
        }
    }

    $i = 0;

    foreach ($used_hex as $hex => $_) {
        list($r, $g, $b) = sscanf($hex, "%02x%02x%02x");

        // Determine circle center coordinates
        $circle_center_x = $circle_diameter;
        $circle_center_y = ($i + 1) * $circle_diameter;
        $i++;

        // Allocate circle color
        $ellipse_color = imagecolorallocate($destination_image, $r, $g, $b);

        // Calculate relative luminance
        $luminance = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;

        // Determine text color based on luminance
        if ($luminance > 128) {
            // Light background, use dark text
            $text_color_id = imagecolorallocate($destination_image, 0, 0, 0); // Black
        } else {
            // Dark background, use light text
            $text_color_id = imagecolorallocate($destination_image, 255, 255, 255); // White
        }

        $text_color = imagecolorallocate($destination_image, 255, 255, 255);

        // Draw the circle
        imagefilledellipse($destination_image, $circle_center_x, $circle_center_y, $circle_diameter, $circle_diameter, $ellipse_color);

        // Define text properties
        $font_size = $circle_diameter / 3;
        $font_file = 'Arial.ttf'; // Ensure this path is correct and the font file is accessible

        // Calculate text bounding box
        $bbox = imagettfbbox($font_size, 0, $font_file, $text);

        // Determine text width and height
        $text_width = abs($bbox[4] - $bbox[0]);
        $text_height = abs($bbox[5] - $bbox[1]);

        // Compute text coordinates for centering
        $text_x = $circle_center_x - ($text_width / 2);
        $text_y = $circle_center_y + ($text_height / 2);

        // Render the text
        imagettftext($destination_image, $font_size, 0, $text_x, $text_y, $text_color_id, $font_file, $colours_to_info[$hex]->id);
        imagettftext($destination_image, $font_size, 0, $text_x + $circle_diameter, $text_y, $text_color, $font_file, $colours_to_info[$hex]->name);
        imagettftext($destination_image, $font_size, 0, $text_x + 7.5 * $circle_diameter, $text_y, $text_color, $font_file, "#" . $hex);
    }

    // Save as PNG (supports transparency better)
    imagepng($destination_image, $destination);

    // Clean up memory
    imagedestroy($source_image);
    imagedestroy($temp_image);
    imagedestroy($destination_image);
}

?>