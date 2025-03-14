<?php
session_start();

// Check if images have been displayed and delete them if necessary
if (isset($_SESSION['displayed']) && $_SESSION['displayed'] === true) {
    if (isset($_SESSION['uploaded_file']) && file_exists($_SESSION['uploaded_file'])) {
        unlink($_SESSION['uploaded_file']);
    }
    if (isset($_SESSION['pixelated_file']) && file_exists($_SESSION['pixelated_file'])) {
        unlink($_SESSION['pixelated_file']);
    }
    // Clear the session variables
    unset($_SESSION['uploaded_file']);
    unset($_SESSION['pixelated_file']);
    unset($_SESSION['displayed']);
}

if ($_POST['new_width'] < 1 || $_POST['new_width'] > 128) {
    echo 'New width should be >= 1 and <= 128';
    exit;
}

if ($_POST['num_color'] < 1) {
    echo 'Number of color should be >= 1';
    exit;
}

// Files will be uploaded to the uploads/ folder.
$target_dir = "uploads/";

// Combines the upload folder with the uploaded file’s name.
$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);

$uploadOk = 1;

// Converts the file extension(jpg/jpeg/png/gif) to lowercase for consistency.
$imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

// Check if image file is a actual image or fake image
if (isset($_POST["submit"])) {
    // check if the file is an actual image.
    $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);

    if ($check !== false) {
        $uploadOk = 1; //uploads continue
    } else {
        echo "File is not an image.";
        $uploadOk = 0; //upload is canceled
    }

    // Limit file size (max 2MB)
    if ($_FILES["fileToUpload"]["size"] > 2 * 1024 * 1024) {
        echo "Sorry, your file is too large.";
        $uploadOk = 0;
    }

    // Allow only certain file formats
    $allowedTypes = ["jpg", "jpeg", "png", "gif"];
    if (!in_array($imageFileType, $allowedTypes)) {
        echo "Sorry, only JPG, JPEG, PNG, & GIF files are allowed.";
        $uploadOk = 0;
    }

    // Move the uploaded file if there are no errors
    if ($uploadOk == 1) {
        // Moves the uploaded file from a temporary location to the uploads/ directory.
        // $_FILES["fileToUpload"]["tmp_name"] → The temporary file path where PHP stores the uploaded file.
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            // stores the path of the uploaded file in a session variable named uploaded_file
            // make it accessible in other scripts
            require_once 'pixelate.php';
            $pixelated_file = 'uploads/converted.png';
            image_pixelate($target_file, $pixelated_file, $_POST['new_width'], $_POST['num_color']);
            $_SESSION['uploaded_file'] = $target_file;
            $_SESSION['pixelated_file'] = $pixelated_file;
        } else {
            echo "Sorry, there was an error uploading your file.";
        }
    } else {
        echo "Your file was not uploaded.<br>";
    }
    header("Location: index.php");
    exit();
}
?>