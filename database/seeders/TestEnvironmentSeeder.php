<?php

namespace Database\Seeders;

use App\Models\Bureau;
use App\Models\Courrier;
use App\Models\User;
use App\Models\WorkflowEtape;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Jeu de données pour tester le Kanban (accès strict par bureau).
 */
class TestEnvironmentSeeder extends Seeder
{
    public function run(): void
    {
        // Prérequis référentiels
        $this->call([
            RolesAndPermissionsSeeder::class,
            BureauSeeder::class,
            WorkflowEtapesSeeder::class,
        ]);

        $bureaux = Bureau::query()->pluck('id', 'code');
        $etapes = WorkflowEtape::query()->orderBy('ordre')->get()->keyBy('ordre');

        foreach (['accueil', 'visas_controles', 'livre_foncier'] as $code) {
            if (! isset($bureaux[$code])) {
                throw new RuntimeException("Bureau « {$code} » manquant. Exécutez BureauSeeder d'abord.");
            }
        }

        foreach ([1, 2, 3, 4] as $ordre) {
            if (! isset($etapes[$ordre])) {
                throw new RuntimeException("Étape ordre {$ordre} manquante. Exécutez WorkflowEtapesSeeder d'abord.");
            }
        }

        $agents = [
            [
                'email' => 'agent_accueil@test.com',
                'name' => 'Agent Accueil',
                'role' => 'agent_courrier',
                'bureau_code' => 'accueil',
            ],
            [
                'email' => 'agent_visas@test.com',
                'name' => 'Agent Visas',
                'role' => 'validateur',
                'bureau_code' => 'visas_controles',
            ],
            [
                'email' => 'agent_livre_foncier@test.com',
                'name' => 'Agent Livre Foncier',
                'role' => 'agent_traitement',
                'bureau_code' => 'livre_foncier',
            ],
        ];

        $usersByBureau = [];

        foreach ($agents as $agent) {
            $user = User::query()->updateOrCreate(
                ['email' => $agent['email']],
                [
                    'name' => $agent['name'],
                    'password' => Hash::make('password'),
                    'locale' => 'fr',
                    'email_verified_at' => now(),
                    'is_active' => true,
                    'bureau_id' => $bureaux[$agent['bureau_code']],
                ]
            );

            $user->syncRoles([$agent['role']]);
            $usersByBureau[$agent['bureau_code']] = $user;
        }

        $creator = $usersByBureau['accueil'];

        // Répartition visuelle Kanban : 4 / 4 / 4 / 3 = 15
        $distribution = [
            1 => 4, // Réception (Accueil)
            2 => 4, // Analyse (Visas)
            3 => 4, // Traitement (Livre Foncier)
            4 => 3, // Validation (Visas)
        ];

        $assigneeByOrdre = [
            1 => $usersByBureau['accueil']->id,
            2 => $usersByBureau['visas_controles']->id,
            3 => $usersByBureau['livre_foncier']->id,
            4 => $usersByBureau['visas_controles']->id,
        ];

        $sujets = [
            'Demande de certificat de propriété',
            'Opposition sur titre foncier',
            'Mutation de droits fonciers',
            'Demande d\'extrait du livre foncier',
            'Régularisation cadastrale',
            'Recours gracieux – formalités',
            'Transmission dossier conservation',
            'Demande de visa de conformité',
            'Saisie d\'acte notarié',
            'Requête d\'enregistrement fiscal',
            'Dossier de bornage contradictoire',
            'Rectification d\'erreur matérielle',
            'Demande de duplication de titre',
            'Instruction de publicité foncière',
            'Contrôle de formalités GUTF',
        ];

        $year = (int) now()->year;
        $seqBase = (int) (Courrier::query()->where('type', 'entrant')->where('year', $year)->max('seq') ?? 0);

        $index = 0;
        foreach ($distribution as $ordre => $count) {
            $etape = $etapes[$ordre];

            for ($i = 0; $i < $count; $i++) {
                $index++;
                $seq = $seqBase + $index;

                Courrier::query()->updateOrCreate(
                    [
                        'reference_interne' => sprintf('GUTF-ENT-%d-%06d', $year, $seq),
                    ],
                    [
                        'type' => 'entrant',
                        'year' => $year,
                        'seq' => $seq,
                        'objet' => $sujets[$index - 1] ?? "Courrier test Kanban #{$index}",
                        'description' => 'Donnée de test générée par TestEnvironmentSeeder pour le tableau Kanban.',
                        'expediteur' => fake()->company(),
                        'destinataire' => 'GUTF / BACL',
                        'date_courrier' => now()->subDays(rand(1, 20))->toDateString(),
                        'date_reception' => now()->subDays(rand(0, 10))->toDateString(),
                        'created_by_user_id' => $creator->id,
                        'assigned_to_user_id' => $assigneeByOrdre[$ordre],
                        'bureau_actuel_id' => $etape->bureau_id,
                        'workflow_etape_id' => $etape->id,
                        'is_late' => false,
                    ]
                );
            }
        }

        $this->command?->info('TestEnvironmentSeeder : 3 agents + 15 courriers (étapes 1–4).');
        $this->command?->table(
            ['Email', 'Bureau', 'Mot de passe'],
            [
                ['agent_accueil@test.com', 'Accueil', 'password'],
                ['agent_visas@test.com', 'Visas et Contrôles', 'password'],
                ['agent_livre_foncier@test.com', 'Livre Foncier', 'password'],
            ]
        );
    }
}
