<?php

namespace App\Http\Controllers;

use App\Models\Courrier;
use App\Models\CourrierComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CourrierCommentController extends Controller
{
    public function store(Request $request, Courrier $courrier): RedirectResponse
    {
        $this->authorize('view', $courrier);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        CourrierComment::query()->create([
            'courrier_id' => $courrier->id,
            'user_id' => (int) $request->user()->id,
            'body' => trim($validated['body']),
        ]);

        return redirect()
            ->route('courriers.show', $courrier)
            ->with('status', __('courrier.comment_created'));
    }
}
