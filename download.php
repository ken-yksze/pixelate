<?php
// checks if the file parameter is set in the URL query string (e.g., download.php?file=$_SESSION['pixelated_file'])
if (isset($_GET['file'])) {
    $file = $_GET['file'];

    if (file_exists($file)) {
        // Specifies that the content should be downloaded as an attachment and sets the filename to the basename of the file.
        header('Content-Disposition: attachment; filename="'.basename($file).'"');

        // eads the file and outputs its content to the browser. 
        readfile($file);
        exit;
    } else {
        echo "File not found.";
    }
} else {
    echo "No file specified.";
}
?>