<?php
$p = $r = [];
$p['cssClass'] = 'right-sprite';                                            // CSS Class name
$p['spriteWidth'] = 200;                                                    // Cropped Sprite width (cover crop)
$p['spriteHeight'] = 200;                                                   // Cropped Sprite height (cover crop)
$p['writeSpriteFilesPublic'] = '../../liquidhub/web/assets/css/sprites';    // Relative output directory (no slashes)
$p['sourceImagesFolder'] = '../../liquidhub/web/assets/images/item-images'; // Relative images input directory (no slashes) (recursive)
$p['outputUrlSlugs'] = 'assets/css/sprites';                                // Output url path (no slashes) (relative)

echo create_sprites($p, $r);




function scanDirectoryForImages($directory) {
  // Create a recursive iterator for the directory
  $iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($directory)
  );

  // Array to store image file paths
  $imageFiles = [];

  foreach ($iterator as $file) {
    // Check if the file is an image
    if ($file->isFile() && isImageFile($file->getFilename())) {
      $imageFiles[] = $file->getPathname();
    }
  }
  return $imageFiles;
}


/**
 * The function checks if a given filename has a supported image file extension.
 * 
 * @param string $filename The name of the file that needs to be checked if it is an image file or not.
 * 
 * @return boolean indicating whether the given filename has an image file extension 
 * ['jpg', 'jpeg', 'png', 'gif']
 */
function isImageFile($filename) {
  // List of supported image file extensions
  $imageExtensions = ['jpg', 'jpeg', 'png', 'gif'];
  $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
  return in_array($extension, $imageExtensions);
}

/**
 * The function creates a directory if it doesn't exist and returns the absolute path to the directory.
 * 
 * @param string $dirName The name of the directory that needs to be created or checked for existence.
 * 
 * @return string absolute path to the directory, and if the directory doesn't exist, it creates it and
 * then returns the absolute path to the newly created directory.
 */
function getCreateDir($dirName) {
  // Get the absolute path to the directory
  $dirPath = realpath($dirName);

  // If the directory doesn't exist, create it
  if (!$dirPath) {
    $dirPath = __DIR__ . '/' . $dirName;
    mkdir($dirPath, 0644, true);
  }

  return $dirPath;
}



/**
 * The function creates a sprite image and corresponding CSS file from a folder of source images.
 * 
 * @param array $p An array of parameters for the function:
 * 
 * @return string the URL of the generated sprite image file.
 */
function create_sprites($p) {
  $cssClass = isset($p['cssClass']) ? trim($p['cssClass'], '.') : 'right-sprite';
  $spriteWidth = isset($p['spriteWidth']) ? $p['spriteWidth'] : 200;
  $spriteHeight = isset($p['spriteHeight']) ? $p['spriteHeight'] : 200;
  $writeSpriteFilesPublic = isset($p['writeSpriteFilesPublic']) ? trim($p['writeSpriteFilesPublic'], '/') . '/' : null;
  $sourceImagesFolder = isset($p['sourceImagesFolder']) ? trim($p['sourceImagesFolder'], '/') . '/' : null;
  $outputUrlSlugs = isset($p['outputUrlSlugs']) ? '/' . trim($p['outputUrlSlugs'], '/') . '/' : null;

  if ($sourceImagesFolder && $writeSpriteFilesPublic && $outputUrlSlugs) {
    // Get full path and create is erquires
    $inputDir = getCreateDir($sourceImagesFolder);
    $writeSpriteFilesPublicDir = getCreateDir($writeSpriteFilesPublic);
    $croppedDir = getCreateDir('cropped');

    if (is_dir($inputDir) && is_dir($writeSpriteFilesPublicDir)) {
      $sourceImageFiles = scanDirectoryForImages($inputDir); // ['/full/path/to/file1.png', '/full/path/to/file2.jpg', ...]
      $imageCount = count($sourceImageFiles);

      list($width, $height) = getSpriteSquare($imageCount, $spriteWidth, $spriteHeight);

      // Create a blank image for the sprites
      $spriteImage = imagecreatetruecolor($width, $height);

      // Set a background color for the image (optional)
      $backgroundColor = imagecolorallocate($spriteImage, 255, 255, 255);
      imagefill($spriteImage, 0, 0, $backgroundColor);

      // Variables to keep track of placement
      $offsetLeft = 0;
      $offsetTop = 0;

      $cssPath = $writeSpriteFilesPublicDir . '/' . $cssClass . '.css';
      $cssUrl = get_url($cssPath, $outputUrlSlugs);
      $cssContent = '.' . $cssClass . ' { ' . "\n\t" . 'background: url("' . $cssClass . '.jpg' . '");' . "\n" . '}';
      foreach ($sourceImageFiles as $file) {
        $filename = basename($file);
        // Load the sprite image

        // Look for small version.
        $small_file = $croppedDir . '/' . $filename . '-' . $spriteWidth . 'x' . $spriteHeight . '.jpg';

        if (file_exists($small_file)) {
          echo '-';
          $resizedSpriteImage = imagecreatefromstring(file_get_contents($small_file));
        } else {
          echo '+';
          $sourceImage = imagecreatefromstring(file_get_contents($file));

          // Resize or crop the sprite image if necessary
          $resizedSpriteImage = imagecreatetruecolor($spriteWidth, $spriteHeight);
          imagecopyresampled($resizedSpriteImage, $sourceImage, 0, 0, 0, 0, $spriteWidth, $spriteHeight, imagesx($sourceImage), imagesy($sourceImage));
          imagejpeg($resizedSpriteImage, $small_file, 60);
          imagedestroy($sourceImage);
        }

        // Place the sprite image on the main sprite image
        imagecopy($spriteImage, $resizedSpriteImage, $offsetLeft, $offsetTop, 0, 0, $spriteWidth, $spriteHeight);

        // Generate the CSS class name using the file basename
        $className = '' . $cssClass . ' spr-' . preg_replace(['/[^a-zA-Z0-9]+/', '/-+/'], ['-', '-'], strtolower($filename));

        // Append the CSS rule to the stylesheet content
        $cssContent .= "\n" . '.' . $className . ' { ' . "\n\t" . 'background-position: -' . $offsetLeft . 'px -' . $offsetTop . 'px; ' . "\n" . '}';

        // Calculate the position for the next sprite image
        $offsetLeft += $spriteWidth;
        if ($offsetLeft >= $width) {
          $offsetLeft = 0;
          $offsetTop += $spriteHeight;
        }
        // Free up memory
        imagedestroy($resizedSpriteImage);
      }

      // Save or output the final sprite image
      imagejpeg($spriteImage, $writeSpriteFilesPublicDir . '/' . $cssClass . '.jpg', 60);
      imagedestroy($spriteImage);

      // Save the CSS stylesheet
      file_put_contents($cssPath, $cssContent);
    }
    print_r([
      'cssClass' => $cssClass,
      'spriteWidth' => $spriteWidth,
      'spriteHeight' => $spriteHeight,
      'writeSpriteFilesPublic' => $writeSpriteFilesPublic,
      'sourceImagesFolder' => $sourceImagesFolder,
      'outputUrlSlugs' => $outputUrlSlugs,
    ]);
    return ($cssUrl);
  } else {
    // usage
  }
}



/**
 * The function calculates the optimal dimensions for a sprite sheet based on the number of images and
 * the dimensions of each image.
 * 
 * @param int $imageCount The number of images to be included in the sprite.
 * @param int $spriteWidth The width of each individual image in the sprite.
 * @param int $spriteHeight The height of each individual image in the sprite.
 * 
 * @return array with two values: the total width and height of the sprite sheet.
 */
function getSpriteSquare($imageCount, $spriteWidth, $spriteHeight) {
  $imageCount += 1;
  // Calculate the minimum square size required
  $squareSize = ceil(sqrt($imageCount));

  // Calculate the total width and height of the square
  $totalWidth = $squareSize * $spriteWidth;
  $totalHeight = ceil($imageCount / $squareSize) * $spriteHeight;

  // Calculate the wasted space for the square
  $wastedSpaceSquare = $totalWidth * $totalHeight - ($imageCount * $spriteWidth * $spriteHeight);

  // Calculate the total width and height of the rectangle
  $totalWidthRect = ceil($imageCount / $squareSize) * $spriteWidth;
  $totalHeightRect = $squareSize * $spriteHeight;

  // Calculate the wasted space for the rectangle
  $wastedSpaceRect = $totalWidthRect * $totalHeightRect - ($imageCount * $spriteWidth * $spriteHeight);

  // Choose the shape with the least wasted space
  if ($wastedSpaceSquare <= $wastedSpaceRect) {
    return [$totalWidth, $totalHeight];
  } else {
    return [$totalWidthRect, $totalHeightRect];
  }
}


/**
 * The function returns a URL based on a given path and URL path.
 * 
 * @param string $path The file path of the resource on the server.
 * @param string $url_path The URL path is a string that represents the base path of the URL. For example, if
 * the URL of the website is "https://www.example.com/mywebsite/", then the URL path would be
 * "/mywebsite/".
 * 
 * @return string URL that corresponds to the given file path and URL path.
 */
function get_url($path, $url_path) {
  $path = str_replace('\\', '/', $path); // Convert backslashes to forward slashes (for Windows paths)
  $path = trim($path, '/');
  $url_path = trim($url_path, '/');

  $url = substr($path, strpos($path, $url_path));
  return $url;
}
