<?php

namespace App\Http\Controllers;

use App\Services\CardTemplate;
use Illuminate\Http\Request;

class CardTemplateController extends Controller
{
    public function edit()
    {
        return view('card-template', [
            'exists' => CardTemplate::exists(),
            'layout' => CardTemplate::layout(),
            'anchors' => CardTemplate::ANCHORS,
            'version' => CardTemplate::exists() ? filemtime(CardTemplate::imagePath()) : 0,
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate(['image' => 'required|image|mimes:png,jpg,jpeg|max:10240']);

        try {
            $r = CardTemplate::process($request->file('image')->getRealPath());
        } catch (\Throwable $e) {
            return back()->withErrors(['image' => $e->getMessage()]);
        }

        $n = count(CardTemplate::ANCHORS);

        return back()->with('ok', "Template disimpan. Anchor terdeteksi: {$r['detected']} dari $n."
            .($r['detected'] < $n ? ' Sisanya diberi posisi bawaan, silakan atur manual.' : ''));
    }

    public function update(Request $request)
    {
        $rules = [];
        foreach (array_keys(CardTemplate::ANCHORS) as $k) {
            foreach (['x', 'y', 'w', 'h', 'fs'] as $f) {
                $rules["$k.$f"] = 'required|numeric|min:0|max:100';
            }
        }
        $data = $request->validate($rules);

        $layout = [];
        foreach ($data as $k => $v) {
            $layout[$k] = array_map(fn ($n) => $n / 100, array_map('floatval', $v));
        }
        CardTemplate::saveLayout($layout);

        return back()->with('ok', 'Posisi anchor disimpan.');
    }
}
