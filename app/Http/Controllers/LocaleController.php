<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Set the application's current locale.
     */
    public function __invoke(string $locale)
    {
        if (in_array($locale, ['en', 'zh_TW'])) {
            session()->put('locale', $locale);
        }

        return back();
    }
}
