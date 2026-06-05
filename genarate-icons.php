<?php
/**
 * Generate PWA Icons
 * Run this once to create proper app icons
 */

// Create a simple colored square with text as icon
function createIcon($size, $filename) {
    $image = imagecreatetruecolor($size, $size);
    
    // Enable alpha blending
    imagealphablending($image, true);
    imagesavealpha($image, true);
    
    // Background color (#0d6efd - blue)
    $bgColor = imagecolorallocate($image, 13, 110, 253);
    imagefilledrectangle($image, 0, 0, $size, $size, $bgColor);
    
    // Add white text "BC" (Bisure Chat)
    $textColor = imagecolorallocate($image, 255, 255, 255);
    $fontSize = $size * 0.4;
    
    // Use built-in font
    $text = "BC";
    $bbox = imagettfbbox($fontSize, 0, __DIR__ . '/arial.ttf', $text);
    
    if ($bbox) {
        $x = ($size - $bbox[2]) / 2;
        $y = ($size + $bbox[7]) / 2;
        imagettftext($image, $fontSize, 0, $x, $y, $textColor, __DIR__ . '/arial.ttf', $text);
    } else {
        // Fallback to built-in font if TTF not available
        $x = $size / 2 - 20;
        $y = $size / 2 - 10;
        imagestring($image, 5, $x, $y, $text, $textColor);
    }
    
    // Save the image
    $dir = __DIR__ . '/assets/icons/';
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    
    imagepng($image, $dir . $filename);
    imagedestroy($image);
    
    echo "✅ Created: {$filename} ({$size}x{$size})\n";
}

// Generate required sizes
$sizes = [192, 512];
foreach ($sizes as $size) {
    createIcon($size, "icon-{$size}x{$size}.png");
}

echo "\n✅ All icons generated!\n";
echo "📁 Location: /assets/icons/\n";
?>