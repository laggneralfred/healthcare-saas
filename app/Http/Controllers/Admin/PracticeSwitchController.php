<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PracticeContext;
use Illuminate\Http\Request;

class PracticeSwitchController extends Controller
{
    public function switch(Request $request)
    {
        $id = (int) $request->input('practice_id');

        if ($id > 0) {
            PracticeContext::setCurrentPracticeId($id);
        }

        return redirect()->back();
    }
}
