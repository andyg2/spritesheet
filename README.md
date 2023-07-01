# Sprite Creator

The Sprite Creator is a PHP script that generates a sprite image and corresponding CSS stylesheet from a folder of source images. It allows you to create a single image sprite that combines multiple images into one, reducing the number of HTTP requests and improving page load times. The generated CSS stylesheet provides the necessary background positions for each image within the sprite.

## Requirements

- PHP 5.6 or later

## Usage

1. Place the source images in the specified folder (`$p['sourceImagesFolder']`).
2. Set the desired sprite width and height (`$p['spriteWidth']` and `$p['spriteHeight']`).
3. Specify the output directory for the sprite image and CSS file (`$p['writeSpriteFilesPublic']`).
4. Define the CSS class name (`$p['cssClass']`) for the sprite image.
5. Run the `create_sprites()` function and pass the parameters array (`$p`).

```php
$p = [];
$p['cssClass'] = 'right-sprite';                  // CSS Class name
$p['spriteWidth'] = 32;                           // Cropped Sprite width (cover crop)
$p['spriteHeight'] = 32;                          // Cropped Sprite height (cover crop)
$p['writeSpriteFilesPublic'] = 'example-output';  // Relative output directory (no slashes)
$p['sourceImagesFolder'] = 'example-input';       // Relative images input directory (no slashes) (recursive)
$p['outputUrlSlugs'] = 'assets/css/sprites';      // Output url path (no slashes) (relative)

echo create_sprites($p);

```

```cli
php .\right-sprite.php
```

The function will generate a sprite image and corresponding CSS file in the specified output directory. The CSS file will contain the necessary CSS rules for displaying the individual images within the sprite.
Functions

### create_sprites($p)

This function generates the sprite image and CSS file.

Parameters:

```text
cssClass (optional): The CSS class name for the sprite image. Default: right-sprite.
spriteWidth (optional): The width of each individual image in the sprite. Default: 200.
spriteHeight (optional): The height of each individual image in the sprite. Default: 200.
writeSpriteFilesPublic (required): The output directory for the sprite image and CSS file.
sourceImagesFolder (required): The folder containing the source images.
outputUrlSlugs (required): The output URL path.
```

Generates CSS and Sprite Image (see example-output directory)

```
example-output/right-sprite.css
example-output/right-sprite.jpg
```

Returns:

```text
The URL of the generated sprite image file.
```

### CSS Generation

```css

      .right-sprite { 
        background: url("right-sprite.png");
        background-size: 544px 512px;
      }
      
      .right-sprite.spr-example-1-png { 
        background-position: -0px -0px; 
      }
      
      .right-sprite.spr-example-2-png { 
        background-position: -32px -0px; 
      }

```

#### Classnames are based on the input file

```php
preg_replace(['/[^a-zA-Z0-9]+/', '/-+/'], ['-', '-'], strtolower($filename));
```

### Caching

As a large directory opf fullsize images might take a while to process, the script creates a sprite sized thumbnail for faster reprocessing - these are placed in the [script directory]/cropped/file-name-200x200.png

Made for this SO question: <https://stackoverflow.com/questions/19229123/css-sprites-for-dynamic-images-using-php>
