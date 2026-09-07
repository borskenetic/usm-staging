<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class FineSettingController extends Controller
{
    public function edit(): RedirectResponse
    {
        return redirect()->route('circulation.policy.edit');
    }

    public function update(): RedirectResponse
    {
        return redirect()->route('circulation.policy.edit');
    }
}
