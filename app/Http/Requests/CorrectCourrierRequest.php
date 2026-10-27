<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CorrectCourrierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('courrier'));
    }

    public function rules(): array
    {
        $rules = [
            'objet' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
            'version' => ['required', 'string', 'size:64'],
            'valeur_immeuble' => ['nullable', 'numeric', 'min:0'],
        ];
        foreach (['reference_externe', 'tf', 'numero_acte', 'numero_repertoire'] as $field) {
            $rules[$field] = ['nullable', 'string', 'max:100'];
        }
        foreach (['expediteur', 'destinataire', 'proprietaire', 'requerant', 'vendeur', 'etude_notariale'] as $field) {
            $rules[$field] = ['nullable', 'string', 'max:255'];
        }
        foreach (['date_courrier', 'date_reception', 'date_envoi'] as $field) {
            $rules[$field] = ['nullable', 'date'];
        }
        return $rules;
    }
}
