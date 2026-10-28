<?php

namespace App\Http\Controllers;

use App\Models\ParapheurVisa;
use App\Services\SignaturePinService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParapheurController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $visas = ParapheurVisa::query()
            ->pending()
            ->forValidator((int) $user->id)
            ->with([
                'courrier:id,reference_interne,objet,type,tf,bureau_actuel_id,workflow_etape_id,assigned_to_user_id,created_at',
                'courrier.bureauActuel:id,nom',
                'courrier.workflowEtape:id,libelle,ui_color',
                'courrier.assignedTo:id,name',
            ])
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('parapheur.index', compact('visas'));
    }

    public function approve(Request $request, ParapheurVisa $visa): RedirectResponse
    {
        $this->ensureOwnsPendingVisa($request, $visa);
        $validated = $request->validate(['signature_pin' => ['required', 'digits:4']]);
        app(SignaturePinService::class)->approve($request->user(), $visa, $validated['signature_pin']);

        return redirect()->route('courriers.show', $visa->courrier_id)->with('status', __('parapheur.approved'));
    }

    public function reject(Request $request, ParapheurVisa $visa): RedirectResponse
    {
        $this->ensureOwnsPendingVisa($request, $visa);

        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:5000'],
        ]);

        $updated = ParapheurVisa::whereKey($visa->id)
            ->where('validator_id', $request->user()->id)->pending()->update([
                'status' => ParapheurVisa::STATUS_REJECTED,
                'comment' => trim($validated['comment']),
                'signed_at' => now(),
            ]);
        abort_unless($updated === 1, 404);

        return redirect()
            ->route('courriers.show', $visa->courrier_id)
            ->with('status', __('parapheur.rejected'));
    }

    private function ensureOwnsPendingVisa(Request $request, ParapheurVisa $visa): void
    {
        abort_unless(
            (int) $visa->validator_id === (int) $request->user()->id,
            403
        );
        abort_unless($visa->isPending(), 404);
    }
}
