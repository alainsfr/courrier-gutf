<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;

class LanguageController extends Controller
{
    /**
     * Bascule la langue d'interface (FR / EN) pour la session courante.
     */
    public function switch(string $locale): RedirectResponse
    {
        if (! in_array($locale, ['fr', 'en'], true)) {
            abort(400, 'Locale non supportée.');
        }

        session(['app_locale' => $locale]);

        // Compatibilité avec l’existant (profil / guests)
        session(['locale' => $locale]);

        App::setLocale($locale);

        if ($user = auth()->user()) {
            $user->forceFill(['locale' => $locale])->save();
        }

        return back();
    }
}
