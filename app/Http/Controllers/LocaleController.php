<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate(['locale' => ['required', Rule::in(['ar', 'en'])]]);

        if ($request->user()) {
            $request->user()->update(['locale' => $data['locale']]);
        } else {
            $request->session()->put('locale', $data['locale']);
        }

        return back();
    }
}
