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
 * Jeu de données pour tester le Kanban, l'enregistrement et le parapheur.
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
            [
                'email' => 'directeur@test.com',
                'name' => 'Directeur GUTF',
                'role' => 'directeur',
                'bureau_code' => null,
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
                    'bureau_id' => $agent['bureau_code']
                        ? $bureaux[$agent['bureau_code']]
                        : null,
                ]
            );

            $user->syncRoles([$agent['role']]);

            if ($agent['bureau_code']) {
                $usersByBureau[$agent['bureau_code']] = $user;
            }
        }

        $creator = $usersByBureau['accueil'];

        // Répartition visuelle Kanban : 4 / 4 / 4 / 3 = 15 entrants
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

        $dossiers = [
            [
                'objet' => 'Demande de certificat de propriété',
                'tf' => 'TF-2026-1001',
                'proprietaire' => 'Kouassi Jean',
                'expediteur' => 'Étude Me N\'Guessan',
                'etude_notariale' => 'Me N\'Guessan & Associés',
                'numero_acte' => 'ACT-2401',
                'valeur_immeuble' => 45000000,
            ],
            [
                'objet' => 'Opposition sur titre foncier',
                'tf' => 'TF-2026-1002',
                'proprietaire' => 'Société FONCIER SA',
                'requerant' => 'Bamba Aïcha',
                'expediteur' => 'Cabinet Legalis',
            ],
            [
                'objet' => 'Mutation de droits fonciers',
                'tf' => 'TF-2026-1003',
                'vendeur' => 'Traoré Mamadou',
                'proprietaire' => 'Koné Fatou',
                'expediteur' => 'Notaire Central Abidjan',
                'numero_repertoire' => 'REP-7781',
                'valeur_immeuble' => 125000000,
            ],
            [
                'objet' => 'Demande d\'extrait du livre foncier',
                'tf' => 'TF-2026-1004',
                'proprietaire' => 'Yao Michel',
                'expediteur' => 'Banque Atlantique',
            ],
            [
                'objet' => 'Régularisation cadastrale',
                'tf' => 'TF-2026-1005',
                'proprietaire' => 'Commune de Cocody',
                'expediteur' => 'Direction du Cadastre',
            ],
            [
                'objet' => 'Recours gracieux – formalités',
                'tf' => 'TF-2026-1006',
                'requerant' => 'Diabaté Salif',
                'expediteur' => 'Cabinet Diabaté',
            ],
            [
                'objet' => 'Transmission dossier conservation',
                'tf' => 'TF-2026-1007',
                'proprietaire' => 'SCI Les Palmiers',
                'expediteur' => 'Conservation foncière',
            ],
            [
                'objet' => 'Demande de visa de conformité',
                'tf' => 'TF-2026-1008',
                'etude_notariale' => 'Étude Me Kouamé',
                'numero_acte' => 'ACT-2512',
                'expediteur' => 'Étude Me Kouamé',
            ],
            [
                'objet' => 'Saisie d\'acte notarié',
                'tf' => 'TF-2026-1009',
                'proprietaire' => 'Ouattara Awa',
                'vendeur' => 'Touré Ibrahim',
                'expediteur' => 'Me Ouattara',
                'valeur_immeuble' => 78000000,
            ],
            [
                'objet' => 'Requête d\'enregistrement fiscal',
                'tf' => 'TF-2026-1010',
                'proprietaire' => 'Groupe IMMO CI',
                'expediteur' => 'DGI – Pôle immobilier',
            ],
            [
                'objet' => 'Dossier de bornage contradictoire',
                'tf' => 'TF-2026-1011',
                'proprietaire' => 'N\'Dri Paul',
                'requerant' => 'Assi Luc',
                'expediteur' => 'Géomètre Assi',
            ],
            [
                'objet' => 'Rectification d\'erreur matérielle',
                'tf' => 'TF-2026-1012',
                'proprietaire' => 'Bakayoko Rokia',
                'expediteur' => 'Étude Me Bakayoko',
            ],
            [
                'objet' => 'Demande de duplication de titre',
                'tf' => 'TF-2026-1013',
                'proprietaire' => 'Société TERRA SARL',
                'expediteur' => 'TERRA SARL',
            ],
            [
                'objet' => 'Instruction de publicité foncière',
                'tf' => 'TF-2026-1014',
                'proprietaire' => 'Coulibaly Seydou',
                'expediteur' => 'Notaire de Yopougon',
                'numero_acte' => 'ACT-2603',
            ],
            [
                'objet' => 'Contrôle de formalités GUTF',
                'tf' => 'TF-2026-1015',
                'proprietaire' => 'État de Côte d\'Ivoire',
                'expediteur' => 'Secrétariat GUTF',
            ],
        ];

        $year = (int) now()->year;
        $seqBase = 0;

        $index = 0;
        foreach ($distribution as $ordre => $count) {
            $etape = $etapes[$ordre];

            for ($i = 0; $i < $count; $i++) {
                $dossier = $dossiers[$index] ?? ['objet' => "Courrier test Kanban #".($index + 1)];
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
                        'objet' => $dossier['objet'],
                        'description' => 'Dossier de démonstration généré pour tester l\'enregistrement et le circuit Kanban.',
                        'tf' => $dossier['tf'] ?? null,
                        'expediteur' => $dossier['expediteur'] ?? fake()->company(),
                        'destinataire' => 'GUTF / BACL',
                        'proprietaire' => $dossier['proprietaire'] ?? null,
                        'requerant' => $dossier['requerant'] ?? null,
                        'vendeur' => $dossier['vendeur'] ?? null,
                        'etude_notariale' => $dossier['etude_notariale'] ?? null,
                        'valeur_immeuble' => $dossier['valeur_immeuble'] ?? null,
                        'numero_acte' => $dossier['numero_acte'] ?? null,
                        'numero_repertoire' => $dossier['numero_repertoire'] ?? null,
                        'reference_externe' => 'EXT-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
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

        // Sortants + internes (registre multi-types)
        $extra = [
            [
                'type' => 'sortant',
                'prefix' => 'SOR',
                'seq' => 1,
                'objet' => 'Transmission avis de conformité au notaire',
                'destinataire' => 'Étude Me N\'Guessan',
                'tf' => 'TF-2026-1001',
            ],
            [
                'type' => 'sortant',
                'prefix' => 'SOR',
                'seq' => 2,
                'objet' => 'Réponse à opposition – notification',
                'destinataire' => 'Cabinet Legalis',
                'tf' => 'TF-2026-1002',
            ],
            [
                'type' => 'interne',
                'prefix' => 'INT',
                'seq' => 1,
                'objet' => 'Note de service – calendrier des visas',
                'destinataire' => 'Bureau Visas et Contrôles',
                'tf' => null,
            ],
        ];

        $etapeReception = $etapes[1];

        foreach ($extra as $row) {
            Courrier::query()->updateOrCreate(
                [
                    'reference_interne' => sprintf('GUTF-%s-%d-%06d', $row['prefix'], $year, $row['seq']),
                ],
                [
                    'type' => $row['type'],
                    'year' => $year,
                    'seq' => $row['seq'],
                    'objet' => $row['objet'],
                    'description' => 'Courrier de démonstration (registre multi-types).',
                    'tf' => $row['tf'],
                    'expediteur' => 'GUTF',
                    'destinataire' => $row['destinataire'],
                    'date_courrier' => now()->toDateString(),
                    'date_reception' => now()->toDateString(),
                    'date_envoi' => $row['type'] === 'sortant' ? now()->toDateString() : null,
                    'created_by_user_id' => $creator->id,
                    'assigned_to_user_id' => $creator->id,
                    'bureau_actuel_id' => $etapeReception->bureau_id,
                    'workflow_etape_id' => $etapeReception->id,
                    'is_late' => false,
                ]
            );
        }

        $this->command?->info('TestEnvironmentSeeder : 4 utilisateurs + 15 entrants + 2 sortants + 1 interne.');
        $this->command?->table(
            ['Email', 'Rôle / Bureau', 'Mot de passe'],
            [
                ['agent_accueil@test.com', 'agent_courrier / Accueil', 'password'],
                ['agent_visas@test.com', 'validateur / Visas', 'password'],
                ['agent_livre_foncier@test.com', 'agent_traitement / Livre Foncier', 'password'],
                ['directeur@test.com', 'directeur (tous bureaux)', 'password'],
                ['admin@test.com', 'super-admin', 'password'],
            ]
        );
    }
}
