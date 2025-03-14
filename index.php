<?php
session_start();

if (isset($_SESSION['displayed']) && $_SESSION['displayed'] === true) {
    session_unset(); // Clear all session variables
    session_destroy(); // Destroy the session
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Convert Image to Pixelated Image</title>
    <link rel="stylesheet" href="./style.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Jersey+25&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">
</head>

<body>
    <header id="header">
        <a href="./index.php"><img src="./images/Pixelate.png" alt="Pixelate"></a>
    </header>
    <main class="container">
        <!-- <h1>Convert Image to Pixelated Image</h1> -->
        <section class="intro">
            <h1>Pixel Art to LEGO Instructions</h1>
            <p>This application will convert a provided image into a set of instructions to recreate the image using
                LEGO™ bricks.</p>
        </section>
        <section>
            <h2>How does it work?</h2>
            <ol>
                <li>Step 1. Upload a picture</li>
                <li>Step 2. Custom size</li>
                <li>Step 3. Upload!</li>
            </ol>
        </section>
        <!-- submits data to upload.php; enctype for sending files.-->
        <form action="upload.php" method="post" enctype="multipart/form-data" class="form-container">
            <!-- Left Section -->
            <section class="left-section">
                <label for="fileToUpload" class="upload-label">Select image to upload</label>
                <input type="file" name="fileToUpload" id="fileToUpload" required>
            </section>
            <!-- Right Section -->
            <section class="right-section">
                <label for="new_width" class="width-label">What is your desired pixel width (1 - 128):</label>
                <input type="number" name="new_width" id="new_width" required>
                <label for="num_color" class="color-label">How many colors (at max) you want (at least 1):</label>
                <input type="number" name="num_color" id="num_color" required>
                <button type="submit" name="submit" id="submit-btn">Convert Image</button>
            </section>
        </form>

        <?php
        if (isset($_SESSION['uploaded_file']) && isset($_SESSION['pixelated_file'])) {
            echo '<h2>Resulting Images</h2>';
            echo '<img src="' . $_SESSION['uploaded_file'] . '" width="300">';
            echo '&nbsp;';
            echo '<img src="' . $_SESSION['pixelated_file'] . '" width="300">';
            echo '<br>';
            echo '<button onclick="window.location.href=\'download.php?file=' . $_SESSION['pixelated_file'] . '\'" id="download-btn">Download</button>';
            echo '<br>';
            echo '<h2 style="margin-top:24px">LEGO instruction</h2>';
            echo '<img src="' . $_SESSION['instruction_png'] . '" width="600">';
            echo '<br>';
            echo '<button onclick="window.location.href=\'download.php?file=' . $_SESSION['instruction_file'] . '\'" id="download-btn">Download Instruction PDF</button>';
            echo '<br>';
            $_SESSION['displayed'] = true;
        }
        ?>
    </main>
    <footer id="footer">
        <p>&copy; 2025 Pixelate - Convert Images to LEGO™ Instructions. All rights reserved.</p>
    </footer>
</body>

</html>