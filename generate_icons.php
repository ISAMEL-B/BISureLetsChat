<?php
// generate_icons.php - Run this once to create PWA icons
// Access via: http://localhost/generate_icons.php

// Create icons directory
$iconDir = __DIR__ . '/assets/icons/';
if (!file_exists($iconDir)) {
    mkdir($iconDir, 0755, true);
    echo "✅ Created directory: assets/icons/<br>";
}

// Function to create a simple icon
function createIcon($size, $filename) {
    global $iconDir;
    
    $img = imagecreatetruecolor($size, $size);
    
    // Enable alpha blending
    imagealphablending($img, true);
    imagesavealpha($img, true);
    
    // Colors
    $bgColor = imagecolorallocate($img, 13, 110, 253);  // #0d6efd - Blue
    $white = imagecolorallocate($img, 255, 255, 255);
    $darkBlue = imagecolorallocate($img, 0, 86, 179);
    
    // Fill background
    imagefilledrectangle($img, 0, 0, $size, $size, $bgColor);
    
    // Draw a white circle
    $circleSize = $size * 0.4;
    imagefilledellipse($img, $size/2, $size/2, $circleSize, $circleSize, $white);
    
    // Draw a smaller blue circle
    $innerSize = $size * 0.3;
    imagefilledellipse($img, $size/2, $size/2, $innerSize, $innerSize, $darkBlue);
    
    // Add text "BC" (using built-in font)
    $fontSize = $size > 100 ? 5 : 4;
    $textX = $size/2 - ($size * 0.12);
    $textY = $size/2 - ($size * 0.08);
    imagestring($img, $fontSize, $textX, $textY, "BC", $white);
    
    // Save image
    $path = $iconDir . $filename;
    imagepng($img, $path, 9); // Maximum compression
    imagedestroy($img);
    
    echo "✅ Created: {$filename} ({$size}x{$size})<br>";
}

// Generate required sizes
$sizes = [72, 96, 128, 144, 152, 192, 384, 512];
foreach ($sizes as $size) {
    createIcon($size, "icon-{$size}x{$size}.png");
}

// Also create a default-profile.png in assets/images
$defaultProfileDir = __DIR__ . '/assets/images/';
if (!file_exists($defaultProfileDir)) {
    mkdir($defaultProfileDir, 0755, true);
    echo "✅ Created directory: assets/images/<br>";
}

// Create a simple default profile picture
$profileImg = imagecreatetruecolor(200, 200);
imagealphablending($profileImg, true);
imagesavealpha($profileImg, true);

$gray = imagecolorallocate($profileImg, 200, 200, 200);
$white = imagecolorallocate($profileImg, 255, 255, 255);
$darkGray = imagecolorallocate($profileImg, 150, 150, 150);

// Background
imagefilledrectangle($profileImg, 0, 0, 200, 200, $gray);

// Head (circle)
imagefilledellipse($profileImg, 100, 80, 80, 80, $white);

// Body (semi-circle)
imagefilledellipse($profileImg, 100, 200, 120, 120, $white);

// Save
imagepng($profileImg, $defaultProfileDir . 'default-profile.png');
imagedestroy($profileImg);
echo "✅ Created: default-profile.png<br>";

echo "<br>✅ All icons generated successfully!<br>";
echo "📁 Location: assets/icons/ and assets/images/<br>";
echo "<br>You can now <a href='/bisureletschat'>return to your site</a>";
?>