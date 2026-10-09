<?php

namespace App\Services;

use App\Models\Student;
use GdImage;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class StudentIdCardImageService
{
    private const SCALE = 2;

    private const WIDTH = 325;

    private const HEIGHT = 204;

    private const TEXT_COLOR = [12, 22, 72];

    /** GD sizes text in points at 96 DPI; CSS sizes are pixels. */
    private const PX_TO_PT = 0.75;

    public function renderPng(Student $student, string $template = 'classic'): string
    {
        $canvas = $this->renderCanvas($student, $template);

        ob_start();
        imagepng($canvas, null, 6);
        imagedestroy($canvas);
        $binary = ob_get_clean();

        if ($binary === false || $binary === '') {
            throw new RuntimeException('Could not encode ID card PNG.');
        }

        return $binary;
    }

    public function renderJpeg(Student $student, string $template = 'classic', int $quality = 92): string
    {
        $canvas = $this->renderCanvas($student, $template);

        ob_start();
        imagejpeg($canvas, null, $quality);
        imagedestroy($canvas);
        $binary = ob_get_clean();

        if ($binary === false || $binary === '') {
            throw new RuntimeException('Could not encode ID card JPEG.');
        }

        return $binary;
    }

    private function renderCanvas(Student $student, string $template): GdImage
    {
        $layouts = config('libcontrol.id_card_layouts', []);
        $layout = $layouts[$template] ?? $layouts['classic'] ?? null;
        if (! is_array($layout) || ! isset($layout['photo'])) {
            throw new RuntimeException('ID card layout is not configured.');
        }

        $width = self::WIDTH * self::SCALE;
        $height = self::HEIGHT * self::SCALE;

        $canvas = imagecreatetruecolor($width, $height);
        if ($canvas === false) {
            throw new RuntimeException('Could not create ID card image.');
        }

        imagealphablending($canvas, true);
        imagesavealpha($canvas, false);

        $this->fillBackground($canvas, public_path($layout['background']), $width, $height);
        $this->drawPhoto($canvas, $student, $layout['photo'], $width, $height);
        $this->drawValues($canvas, $student, config('libcontrol.id_card_text'), $width, $height);

        return $canvas;
    }

    private function fillBackground(GdImage $canvas, string $path, int $width, int $height): void
    {
        if (! is_readable($path)) {
            throw new RuntimeException('ID card background image is missing.');
        }

        $background = $this->loadImageFromPath($path);
        if ($background === null) {
            throw new RuntimeException('Could not load ID card background.');
        }

        imagecopyresampled($canvas, $background, 0, 0, 0, 0, $width, $height, imagesx($background), imagesy($background));
        imagedestroy($background);
    }

    /**
     * @param  array<string, float>  $box  percentages of the card
     */
    private function drawPhoto(GdImage $canvas, Student $student, array $box, int $cardWidth, int $cardHeight): void
    {
        $left = (int) round($box['left'] / 100 * $cardWidth);
        $top = (int) round($box['top'] / 100 * $cardHeight);
        $width = (int) round($box['width'] / 100 * $cardWidth);
        $height = (int) round($box['height'] / 100 * $cardHeight);
        $radius = (int) round(($box['radius'] ?? 0) / 100 * $cardWidth);

        $slot = imagecreatetruecolor($width, $height);
        if ($slot === false) {
            return;
        }

        imagealphablending($slot, false);
        imagesavealpha($slot, true);
        $transparent = imagecolorallocatealpha($slot, 0, 0, 0, 127);
        imagefill($slot, 0, 0, $transparent);

        $photo = $this->loadStudentPhoto($student);
        if ($photo === null) {
            $fill = imagecolorallocate($slot, 248, 250, 252);
            imagefilledrectangle($slot, 0, 0, $width, $height, $fill);
            imagealphablending($slot, true);
            $fontPx = (float) config('libcontrol.id_card_text.font_size', 3.4) / 100 * $cardWidth;
            $this->drawCenteredLabel($slot, $student->initials(), 0, 0, $width, $height, (int) round($fontPx * self::PX_TO_PT));
        } else {
            $this->copyCover($slot, $photo, 0, 0, $width, $height);
            imagedestroy($photo);
        }

        if ($radius > 0) {
            $this->applyRoundedCorners($slot, $radius);
        }

        imagecopy($canvas, $slot, $left, $top, 0, 0, $width, $height);
        imagedestroy($slot);
    }

    /**
     * Mirrors resources/views/students/id-cards/layouts/_card.blade.php: text sits just above each dotted line.
     *
     * @param  array<string, mixed>  $text
     */
    private function drawValues(GdImage $canvas, Student $student, array $text, int $cardWidth, int $cardHeight): void
    {
        $font = $this->boldFontPath();
        [$r, $g, $b] = self::TEXT_COLOR;
        $color = imagecolorallocate($canvas, $r, $g, $b);
        $fontPx = (float) $text['font_size'] / 100 * $cardWidth;
        $fontPt = $fontPx * self::PX_TO_PT;

        $values = [
            'name' => $student->name,
            'father_name' => $student->father_name ?: '—',
            'date_of_birth' => $student->date_of_birth?->format('d-m-Y') ?? '—',
            'student_id' => $student->student_code,
        ];

        foreach ($text['rows'] as $key => $row) {
            $left = (int) round($row['left'] / 100 * $cardWidth);
            $maxWidth = (int) round(($text['line_end'] - $row['left']) / 100 * $cardWidth);
            $boxBottom = ($row['line'] - $text['gap_above_line']) / 100 * $cardHeight;
            $baselineY = (int) round($boxBottom - 0.2 * $fontPx);
            $value = $this->fitText((string) ($values[$key] ?? '—'), $font, $fontPt, $maxWidth);

            imagettftext($canvas, $fontPt, 0, $left, $baselineY, $color, $font, $value);
        }
    }

    private function fitText(string $text, string $font, float $size, int $maxWidth): string
    {
        $measure = function (string $value) use ($font, $size): int {
            $bbox = imagettfbbox($size, 0, $font, $value);

            return $bbox === false ? 0 : abs($bbox[2] - $bbox[0]);
        };

        if ($measure($text) <= $maxWidth) {
            return $text;
        }

        while (mb_strlen($text) > 1 && $measure(rtrim($text).'…') > $maxWidth) {
            $text = mb_substr($text, 0, -1);
        }

        return rtrim($text).'…';
    }

    private function drawCenteredLabel(GdImage $canvas, string $text, int $left, int $top, int $width, int $height, int $fontSize): void
    {
        $font = $this->boldFontPath();
        $color = imagecolorallocate($canvas, 12, 22, 72);
        $bbox = imagettfbbox($fontSize, 0, $font, $text);
        if ($bbox === false) {
            return;
        }

        $textWidth = abs($bbox[2] - $bbox[0]);
        $x = $left + (int) (($width - $textWidth) / 2);
        $y = $top + (int) (($height - ($bbox[1] - $bbox[7])) / 2) - $bbox[1];

        imagettftext($canvas, $fontSize, 0, $x, $y, $color, $font, $text);
    }

    private function applyRoundedCorners(GdImage $image, int $radius): void
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $radius = min($radius, (int) (min($width, $height) / 2));

        imagealphablending($image, false);
        imagesavealpha($image, true);

        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                if ($this->isOutsideRoundedRect($x, $y, $width, $height, $radius)) {
                    imagesetpixel($image, $x, $y, $transparent);
                }
            }
        }

        imagealphablending($image, true);
    }

    private function isOutsideRoundedRect(int $x, int $y, int $width, int $height, int $radius): bool
    {
        if ($x < $radius && $y < $radius) {
            return ($x - $radius) ** 2 + ($y - $radius) ** 2 > $radius ** 2;
        }

        if ($x >= $width - $radius && $y < $radius) {
            return ($x - ($width - $radius - 1)) ** 2 + ($y - $radius) ** 2 > $radius ** 2;
        }

        if ($x < $radius && $y >= $height - $radius) {
            return ($x - $radius) ** 2 + ($y - ($height - $radius - 1)) ** 2 > $radius ** 2;
        }

        if ($x >= $width - $radius && $y >= $height - $radius) {
            return ($x - ($width - $radius - 1)) ** 2 + ($y - ($height - $radius - 1)) ** 2 > $radius ** 2;
        }

        return false;
    }

    private function loadStudentPhoto(Student $student): ?GdImage
    {
        if (! $student->photo_path || ! Storage::disk('public')->exists($student->photo_path)) {
            return null;
        }

        return $this->loadImageFromPath(Storage::disk('public')->path($student->photo_path));
    }

    private function loadImageFromPath(string $path): ?GdImage
    {
        $info = @getimagesize($path);
        if ($info === false) {
            return null;
        }

        $image = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };

        return $image instanceof GdImage ? $image : null;
    }

    private function copyCover(GdImage $canvas, GdImage $source, int $destX, int $destY, int $destW, int $destH): void
    {
        $srcW = imagesx($source);
        $srcH = imagesy($source);
        $srcRatio = $srcW / $srcH;
        $destRatio = $destW / $destH;

        if ($srcRatio > $destRatio) {
            $cropH = $srcH;
            $cropW = (int) round($srcH * $destRatio);
            $cropX = (int) round(($srcW - $cropW) / 2);
            $cropY = 0;
        } else {
            $cropW = $srcW;
            $cropH = (int) round($srcW / $destRatio);
            $cropX = 0;
            $cropY = (int) round(($srcH - $cropH) / 2);
        }

        imagecopyresampled($canvas, $source, $destX, $destY, $cropX, $cropY, $destW, $destH, $cropW, $cropH);
    }

    private function boldFontPath(): string
    {
        $candidates = [
            resource_path('fonts/arial-bold.ttf'),
            'C:\\Windows\\Fonts\\arialbd.ttf',
            'C:\\Windows\\Fonts\\ARIALBD.TTF',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
        ];

        foreach ($candidates as $path) {
            if (is_readable($path)) {
                return $path;
            }
        }

        throw new RuntimeException('No bold TrueType font found for ID card rendering. Add resource/fonts/arial-bold.ttf.');
    }
}
