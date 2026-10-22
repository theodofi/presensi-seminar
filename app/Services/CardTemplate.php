<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

/**
 * Template ID card: gambar latar + posisi anchor (<<NAMA>>, <<NIP>>, <<KAB/KOTA>>, <<CODE>>, <<QRCODE>>).
 * Anchor dideteksi dari teks hitam pekat pada gambar, urut dari atas ke bawah.
 * Posisi disimpan sebagai pecahan (0-1) dari lebar/tinggi kartu.
 */
class CardTemplate
{
    public const ANCHORS = ['code' => '<<CODE>>', 'qrcode' => '<<QRCODE>>', 'nama' => '<<NAMA>>', 'nip' => '<<NIP>>', 'kab_kota' => '<<KAB/KOTA>>'];

    public const WIDTH_MM = 80;

    public const HEIGHT_MM = 110;

    public static function imagePath(): string
    {
        return public_path('card-template.png');
    }

    public static function layoutPath(): string
    {
        return storage_path('app/card-layout.json');
    }

    public static function exists(): bool
    {
        return File::exists(self::imagePath());
    }

    public static function layout(): array
    {
        if (File::exists(self::layoutPath())) {
            return json_decode(File::get(self::layoutPath()), true) ?: [];
        }

        return [];
    }

    public static function saveLayout(array $layout): void
    {
        File::put(self::layoutPath(), json_encode($layout, JSON_PRETTY_PRINT));
    }

    /** Proses gambar upload: deteksi anchor, hapus teks anchor dari gambar, simpan template + layout. */
    public static function process(string $file): array
    {
        $im = @imagecreatefromstring(file_get_contents($file));
        if (! $im) {
            throw new \RuntimeException('File gambar tidak valid.');
        }
        imagepalettetotruecolor($im);
        $w = imagesx($im);
        $h = imagesy($im);

        $blobs = self::detect($im, $w, $h);
        $layout = [];
        $keys = array_keys(self::ANCHORS);
        foreach ($blobs as $i => $b) {
            if (! isset($keys[$i])) {
                break;
            }
            $layout[$keys[$i]] = self::toBox($im, $b, $w, $h, $keys[$i]);
        }
        // NAMA, NIP, KAB/KOTA berada dalam satu panel: samakan lebar kotaknya
        $textKeys = array_values(array_filter(['nama', 'nip', 'kab_kota'], fn ($k) => isset($layout[$k])));
        if ($textKeys) {
            $maxW = max(array_map(fn ($k) => $layout[$k]['w'], $textKeys));
            foreach ($textKeys as $k) {
                $center = $layout[$k]['x'] + $layout[$k]['w'] / 2;
                $layout[$k]['w'] = $maxW;
                $layout[$k]['x'] = $center - $maxW / 2;
            }
        }
        foreach ($blobs as $i => $b) {
            if (isset($keys[$i])) {
                self::erase($im, $b);
            }
        }

        // Anchor yang tidak terdeteksi diberi posisi bawaan agar tetap bisa diatur manual
        $defaults = [
            'code' => [0.70, 0.02, 0.28, 0.04, 0.028],
            'qrcode' => [0.31, 0.38, 0.38, 0.28, 0],
            'nama' => [0.10, 0.70, 0.80, 0.05, 0.034],
            'nip' => [0.10, 0.79, 0.80, 0.03, 0.022],
            'kab_kota' => [0.10, 0.83, 0.80, 0.03, 0.022],
        ];
        foreach ($defaults as $k => [$x, $y, $bw, $bh, $fs]) {
            $layout[$k] ??= ['x' => $x, 'y' => $y, 'w' => $bw, 'h' => $bh, 'fs' => $fs];
        }

        imagepng($im, self::imagePath());
        self::saveLayout($layout);

        return ['detected' => min(count($blobs), count($keys)), 'size' => [$w, $h]];
    }

    private static function dark($im, int $x, int $y): bool
    {
        $c = imagecolorat($im, $x, $y);

        return (($c >> 16) & 255) < 55 && (($c >> 8) & 255) < 55 && ($c & 255) < 55;
    }

    private static function detect($im, int $w, int $h): array
    {
        $cell = 4;
        $gw = (int) ceil($w / $cell);
        $gh = (int) ceil($h / $cell);
        $grid = array_fill(0, $gh, array_fill(0, $gw, 0));
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                if (self::dark($im, $x, $y)) {
                    $grid[intdiv($y, $cell)][intdiv($x, $cell)]++;
                }
            }
        }

        // Gabungkan sel yang berdekatan (jarak antar huruf/kata) menjadi satu blob
        $seen = [];
        $blobs = [];
        $gap = 2;
        for ($gy = 0; $gy < $gh; $gy++) {
            for ($gx = 0; $gx < $gw; $gx++) {
                if (! $grid[$gy][$gx] || isset($seen["$gx,$gy"])) {
                    continue;
                }
                $stack = [[$gx, $gy]];
                $seen["$gx,$gy"] = true;
                $b = ['x1' => $gx, 'y1' => $gy, 'x2' => $gx, 'y2' => $gy, 'n' => 0];
                while ($stack) {
                    [$cx, $cy] = array_pop($stack);
                    $b['x1'] = min($b['x1'], $cx);
                    $b['x2'] = max($b['x2'], $cx);
                    $b['y1'] = min($b['y1'], $cy);
                    $b['y2'] = max($b['y2'], $cy);
                    $b['n'] += $grid[$cy][$cx];
                    for ($dy = -$gap; $dy <= $gap; $dy++) {
                        for ($dx = -$gap; $dx <= $gap; $dx++) {
                            $nx = $cx + $dx;
                            $ny = $cy + $dy;
                            if ($nx < 0 || $ny < 0 || $nx >= $gw || $ny >= $gh || ! $grid[$ny][$nx] || isset($seen["$nx,$ny"])) {
                                continue;
                            }
                            $seen["$nx,$ny"] = true;
                            $stack[] = [$nx, $ny];
                        }
                    }
                }
                $bw = ($b['x2'] - $b['x1'] + 1) * $cell;
                $bh = ($b['y2'] - $b['y1'] + 1) * $cell;
                if ($bw >= 40 && $bh >= 8 && $bw > $bh * 2 && $b['n'] >= 80) {
                    $blobs[] = [
                        'x1' => $b['x1'] * $cell, 'y1' => $b['y1'] * $cell,
                        'x2' => min($w - 1, ($b['x2'] + 1) * $cell - 1), 'y2' => min($h - 1, ($b['y2'] + 1) * $cell - 1), 'n' => $b['n'],
                    ];
                }
            }
        }

        usort($blobs, fn ($a, $b) => $b['n'] <=> $a['n']);
        $blobs = array_slice($blobs, 0, 5);
        usort($blobs, fn ($a, $b) => $a['y1'] <=> $b['y1']);

        return $blobs;
    }

    private static function close($im, int $x, int $y, array $ref): bool
    {
        $c = imagecolorat($im, $x, $y);
        $d = abs((($c >> 16) & 255) - $ref[0]) + abs((($c >> 8) & 255) - $ref[1]) + abs(($c & 255) - $ref[2]);

        return $d < 60;
    }

    private static function toBox($im, array $b, int $w, int $h, string $key): array
    {
        $cy = intdiv($b['y1'] + $b['y2'], 2);
        $cx = intdiv($b['x1'] + $b['x2'], 2);
        $rx = max(0, $b['x1'] - 8);
        $c = imagecolorat($im, $rx, $cy);
        $ref = [($c >> 16) & 255, ($c >> 8) & 255, $c & 255];

        $l = $b['x1'];
        while ($l > 0 && self::close($im, $l - 1, $cy, $ref)) {
            $l--;
        }
        $r = $b['x2'];
        while ($r < $w - 1 && self::close($im, $r + 1, $cy, $ref)) {
            $r++;
        }
        [$ty1, $ty2] = self::capRows($im, $b);
        $textH = $ty2 - $ty1 + 1;

        if ($key === 'qrcode') {
            $t = $b['y1'];
            while ($t > 0 && self::close($im, $cx, $t - 1, $ref)) {
                $t--;
            }
            $bt = $b['y2'];
            while ($bt < $h - 1 && self::close($im, $cx, $bt + 1, $ref)) {
                $bt++;
            }
            // QR persegi, rata tengah, hampir memenuhi kotak putih (sisakan sedikit agar tidak keluar dari sudut membulat)
            $side = (int) floor(min($r - $l, $bt - $t) * 0.96);
            $x = intdiv($l + $r - $side, 2);
            $y = intdiv($t + $bt - $side, 2);

            return ['x' => $x / $w, 'y' => $y / $h, 'w' => $side / $w, 'h' => $side / $h, 'fs' => 0];
        }

        // Teks: rata tengah pada titik tengah anchor, lebar maksimal sebatas area datar di sekitarnya
        $half = min($cx - $l, $r - $cx);
        $half = max($half - 6, ($b['x2'] - $b['x1']) / 2 + 4);

        return [
            'x' => ($cx - $half) / $w, 'y' => ($ty1 - 2) / $h,
            'w' => 2 * $half / $w, 'h' => ($textH + 4) / $h,
            'fs' => round($textH / 0.72 / $h, 4),
        ];
    }

    /** Tinggi huruf kapital: baris yang kepadatan pikselnya cukup (mengabaikan ujung "/" atau "<"). */
    private static function capRows($im, array $b): array
    {
        $rows = [];
        for ($y = $b['y1']; $y <= $b['y2']; $y++) {
            $n = 0;
            for ($x = $b['x1']; $x <= $b['x2']; $x++) {
                $n += self::dark($im, $x, $y) ? 1 : 0;
            }
            $rows[$y] = $n;
        }
        $peak = max($rows) ?: 1;
        $ys = array_keys(array_filter($rows, fn ($n) => $n >= $peak * 0.3));

        return $ys ? [min($ys), max($ys)] : [$b['y1'], $b['y2']];
    }

    private static function erase($im, array $b): void
    {
        $x1 = max(0, $b['x1'] - 3);
        $x2 = min(imagesx($im) - 1, $b['x2'] + 3);
        $y1 = max(0, $b['y1'] - 3);
        $y2 = min(imagesy($im) - 1, $b['y2'] + 3);
        $lx = max(0, $x1 - 4);
        $rx = min(imagesx($im) - 1, $x2 + 4);
        for ($y = $y1; $y <= $y2; $y++) {
            $a = imagecolorat($im, $lx, $y);
            $c = imagecolorat($im, $rx, $y);
            for ($x = $x1; $x <= $x2; $x++) {
                $t = ($x - $x1) / max(1, $x2 - $x1);
                $col = imagecolorallocate($im,
                    (int) ((($a >> 16) & 255) * (1 - $t) + (($c >> 16) & 255) * $t),
                    (int) ((($a >> 8) & 255) * (1 - $t) + (($c >> 8) & 255) * $t),
                    (int) (($a & 255) * (1 - $t) + ($c & 255) * $t));
                imagesetpixel($im, $x, $y, $col);
            }
        }
    }
}
