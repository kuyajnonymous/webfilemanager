<?php
// Get the current directory from the URL (if exists) or set it to the current directory
$directory = isset($_GET['dir']) ? $_GET['dir'] : './';

// Make sure the directory exists and is a directory
if (!is_dir($directory)) {
    die("Invalid directory.");
}

// Get the parent directory
$parentDirectory = dirname($directory);

// Define an array of extensions to hide (web-related files)
$excludedExtensions = ['.php', '.html', '.htm', '.js', '.asp', '.cgi', '.pl', '.css'];

// Exclude system and web-related files (e.g., hidden files like .git, .htaccess)
$excludedFiles = ['.git', '.htaccess', '.DS_Store'];

// Search functionality
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

// Function to recursively scan directories
function searchDirectory($dir, $searchQuery, $excludedExtensions, $excludedFiles) {
    $result = [];
    $files = scandir($dir);

    // Loop through the files and directories
    foreach ($files as $file) {
        // Skip . and .. directories
        if ($file == '.' || $file == '..') continue;

        $filePath = $dir . '/' . $file;

        // Exclude system and web-related files
        if (in_array($file, $excludedFiles)) continue;

        // Check if it's a directory
        if (is_dir($filePath)) {
            // Recurse into subdirectories
            $result = array_merge($result, searchDirectory($filePath, $searchQuery, $excludedExtensions, $excludedFiles));
        } else {
            // Check if the file matches the search query
            if (stripos($file, $searchQuery) !== false) {
                // Filter out files with web-related extensions
                foreach ($excludedExtensions as $ext) {
                    if (stripos($file, $ext) !== false) {
                        continue 2; // Skip this file if it matches excluded extension
                    }
                }
                // Add file to result
                $result[] = $filePath;
            }
        }
    }

    return $result;
}

// If there's a search query, search the directory and subdirectories
if ($searchQuery) {
    $searchResults = searchDirectory($directory, $searchQuery, $excludedExtensions, $excludedFiles);
} else {
    // Scan the directory and get all files and folders for normal listing
    $searchResults = [];
    $files = scandir($directory);
    $files = array_diff($files, array('.', '..'));
    $files = array_filter($files, function($file) use ($excludedExtensions, $excludedFiles) {
        foreach ($excludedExtensions as $ext) {
            if (stripos($file, $ext) !== false) return false;
        }
        return !in_array($file, $excludedFiles);
    });
    $searchResults = array_map(function($file) use ($directory) {
        return $directory . '/' . $file;
    }, $files);
}

 // Upload functionality
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fileUpload'])) {
    $uploadDir = isset($_POST['folder']) ? $_POST['folder'] : $directory;
    $uploadFile = $uploadDir . '/' . basename($_FILES['fileUpload']['name']);
    
    // Check if the upload directory is valid and writable
    if (is_dir($uploadDir) && is_writable($uploadDir)) {
        if (move_uploaded_file($_FILES['fileUpload']['tmp_name'], $uploadFile)) {
            echo "<p>File successfully uploaded to $uploadDir.</p>";
        } else {
            echo "<p>Error uploading file.</p>";
        }
    } else {
        echo "<p>Upload directory is not writable or invalid.</p>";
    }
} 

// HTML Output
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web File Manager</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        header {
            background-color: #333;
            color: white;
            padding: 10px;
            text-align: center;
        }

        footer {
            background-color: #333;
            color: white;
            padding: 10px;
            text-align: center;
            position: absolute;
            bottom: 0;
            width: 100%;
        }

        .container {
            width: 80%;
            margin: 0 auto;
        }

        form input[type="text"] {
            padding: 10px;
            width: 80%;
            margin-bottom: 10px;
        }

        form input[type="submit"] {
            padding: 10px;
        }

        a {
            text-decoration: none;
            color: #333;
            font-weight: bold;
        }

        a:hover {
            color: #007BFF;
        }
            h1 {
        font-size: 2em;
        color: white; /* Set the color of the header text to white */
        text-align: center;
        margin-top: 20px;
    }

    h1 a {
        color: white; /* Set the color of the link inside the header to white */
        text-decoration: none; /* Remove underline */
    }

    h1 a:hover {
        color: #ccc; /* Slightly lighter color on hover */
    }
    </style>
</head>
<body>

<header>
   
    <h1> <a href="index.php">Web File Manager</a></h1>
</header>

<div class="container">
    <!-- Search Form --><br><br>
    <form method="get" action="">
        <input type="text" name="search" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Search files..." />
        <button type="submit">Search</button>
    </form>

    <!-- Upload Form 
    <h2>Upload File</h2>
    <form method="POST" enctype="multipart/form-data">
        <label for="fileUpload">Choose file to upload:</label>
        <input type="file" name="fileUpload" id="fileUpload" required><br><br>

        <label for="folder">Choose destination folder:</label>
        <select name="folder" id="folder">
            <option value="<?= htmlspecialchars($directory) ?>" selected>Current Folder (<?= htmlspecialchars($directory) ?>)</option>
            <?php
            // List directories for the folder selection
            $dirs = array_filter(glob($directory . '/*'), 'is_dir');
            foreach ($dirs as $dir) {
                echo "<option value='" . htmlspecialchars($dir) . "'>" . basename($dir) . "</option>";
            }
            ?>
        </select><br><br>

        <button type="submit">Upload File</button>
    </form> -->

    <?php
    // If there are search results
    if ($searchQuery && !empty($searchResults)) {
        echo "<h2>Search Results for: " . htmlspecialchars($searchQuery) . "</h2>";
        foreach ($searchResults as $filePath) {
            $fileName = basename($filePath);
            echo "<a href='$filePath'>$fileName</a><br>";
        }
    } elseif ($searchQuery) {
        // No results found for the search query
        echo "<p>No results found for: " . htmlspecialchars($searchQuery) . "</p>";
    } else {
        // If no search query, list files in the current directory
        echo "<h2>Directory Listing: " . htmlspecialchars($directory) . "</h2>";

        // If not at the root directory, show a Back button
        if ($directory !== './') {
            echo "<a href='?dir=" . urlencode($parentDirectory) . "'>Back</a><br><br>";
        }

        // Loop through the files and display them
        foreach ($searchResults as $filePath) {
            $fileName = basename($filePath);

            // Check if it's a directory
            if (is_dir($filePath)) {
                echo "<a href='?dir=" . urlencode($filePath) . "'>📁 $fileName</a><br>";
            } else {
                echo "<a href='$filePath'>$fileName</a><br>";
            }
        }
    }
    ?>
</div>

<footer>
    <p>&copy; <?= date("Y") ?>Web File Manager</p>
</footer>

</body>
</html>
