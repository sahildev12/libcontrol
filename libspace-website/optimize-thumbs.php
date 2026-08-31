<?php
$srcDir = __DIR__ . '/assets/screenshots';
$thumbDir = $srcDir . '/thumbs';
$thumbWidth = 640;

if (!is_dir($thumbDir)) {
    mkdir($thumbDir, 0755, true);
}

$files = glob($srcDir . '/*.png');
foreach ($files as $src) {
    $name = basename($src);
    $info = getimagesize($src);
    if (!$info) {
        echo "Skip (invalid): $name\n";
        continue;
    }
    [$w, $h] = $info;
    echo "$name: {$w}x{$h}\n";

    $srcImg = imagecreatefrompng($src);
    if (!$srcImg) {
        echo "  Failed to load\n";
        continue;
    }

    $thumbH = (int) round($h * ($thumbWidth / $w));
    $thumb = imagecreatetruecolor($thumbWidth, $thumbH);
    imagealphablending($thumb, false);
    imagesavealpha($thumb, true);
    imagecopyresampled($thumb, $srcImg, 0, 0, 0, 0, $thumbWidth, $thumbH, $w, $h);

    $dest = $thumbDir . '/' . $name;
    imagepng($thumb, $dest, 6);
    imagedestroy($srcImg);
    imagedestroy($thumb);

    $size = filesize($dest);
    echo "  -> thumbs/$name ({$thumbWidth}x{$thumbH}, " . round($size / 1024) . " KB)\n";
}

echo "Done.\n";
