<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Collection;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/** Membuat PDF ID card (satu kartu per halaman, 80x110 mm) dari template + layout anchor. */
class CardPdf
{
    private const MM_PT = 72 / 25.4;

    public static function fontFile(): ?string
    {
        foreach (['Gotham-Bold.ttf', 'Montserrat-Bold.ttf'] as $name) {
            if (is_file($p = public_path("fonts/$name"))) {
                return $p;
            }
        }

        return null;
    }

    public static function render(Collection $participants): string
    {
        $options = new Options;
        $options->set('chroot', [base_path()]);
        $options->set('isRemoteEnabled', false);
        $options->set('fontDir', storage_path('app/dompdf-fonts'));
        $options->set('fontCache', storage_path('app/dompdf-fonts'));
        @mkdir(storage_path('app/dompdf-fonts'), 0775, true);

        $dompdf = new Dompdf($options);
        $metrics = $dompdf->getFontMetrics();

        $fontFile = self::fontFile();
        $family = 'Helvetica';
        if ($fontFile) {
            $family = 'cardfont'.md5($fontFile.filemtime($fontFile));
            $metrics->registerFont(['family' => $family, 'style' => 'normal', 'weight' => 'bold'], $fontFile);
            $metrics->registerFont(['family' => $family, 'style' => 'normal', 'weight' => 'normal'], $fontFile);
        }

        $fontPath = $metrics->getFont($family, 'bold');
        $layout = CardTemplate::layout();
        $W = CardTemplate::WIDTH_MM;
        $H = CardTemplate::HEIGHT_MM;
        $bg = str_replace('\\', '/', CardTemplate::imagePath());

        $html = '<html><head><meta charset="utf-8"><style>'
            ."@page{size:{$W}mm {$H}mm;margin:0}"
            ."body{margin:0;padding:0;font-family:'$family',Helvetica,sans-serif;font-weight:bold;color:#000}"
            ."@page{margin:0}"
            .".card{position:relative;width:{$W}mm;height:{$H}mm;overflow:hidden;page-break-after:always}"
            .".card.last{page-break-after:auto}"
            .".bg{position:absolute;left:0;top:0;width:{$W}mm;height:{$H}mm}"
            .".t{position:absolute;text-align:center;white-space:nowrap}"
            .'</style></head><body>';

        $count = $participants->count();
        foreach ($participants->values() as $i => $p) {
            $html .= '<div class="card'.($i === $count - 1 ? ' last' : '').'"><img class="bg" src="'.e($bg).'">';

            foreach (['code' => 'code', 'nama' => 'name', 'nip' => 'nip', 'kab_kota' => 'kab_kota'] as $key => $field) {
                if (! isset($layout[$key])) {
                    continue;
                }
                $a = $layout[$key];
                $text = (string) $p->$field;
                $boxW = $a['w'] * $W;
                $boxH = $a['h'] * $H;
                $size = $a['fs'] * $H * self::MM_PT;
                $wpt = $boxW * self::MM_PT;
                while ($size > 3 && $metrics->getTextWidth($text, $fontPath, $size) > $wpt) {
                    $size -= 0.25;
                }
                // Baris dibuat lebih tinggi dari kotak anchor (rata tengah vertikal) agar ekor huruf tidak terpotong
                $lineH = max($boxH, $size / self::MM_PT * 1.6);
                $top = $a['y'] * $H + $boxH / 2 - $lineH / 2;
                $html .= sprintf(
                    '<div class="t" style="left:%.3fmm;top:%.3fmm;width:%.3fmm;height:%.3fmm;line-height:%.3fmm;font-size:%.2fpt">%s</div>',
                    $a['x'] * $W, $top, $boxW, $lineH, $lineH, $size, e($text)
                );
            }

            if (isset($layout['qrcode'])) {
                $a = $layout['qrcode'];
                $svg = QrCode::format('svg')->size(400)->margin(0)->generate($p->code);
                $html .= sprintf(
                    '<img style="position:absolute;left:%.3fmm;top:%.3fmm;width:%.3fmm;height:%.3fmm" src="data:image/svg+xml;base64,%s">',
                    $a['x'] * $W, $a['y'] * $H, $a['w'] * $W, $a['h'] * $H, base64_encode((string) $svg)
                );
            }
            $html .= '</div>';
        }
        $html .= '</body></html>';

        $dompdf->setPaper([0, 0, $W * self::MM_PT, $H * self::MM_PT]);
        $dompdf->loadHtml($html);
        $dompdf->render();

        return $dompdf->output();
    }
}
