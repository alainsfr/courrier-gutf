<?php

// Uses a disposable MySQL database only. Run with APP_ENV=testing,
// DB_CONNECTION=mysql and DB_DATABASE=courrier_concurrency_test.
use App\Http\Controllers\CourrierController;
use App\Http\Controllers\ParapheurController;
use App\Models\Bureau;
use App\Models\Courrier;
use App\Models\ParapheurVisa;
use App\Models\User;
use App\Services\SignaturePinService;
use Database\Seeders\BureauSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowEtapesSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

if (getenv('APP_ENV') !== 'testing' || getenv('DB_CONNECTION') !== 'mysql'
    || getenv('DB_DATABASE') !== 'courrier_concurrency_test') {
    fwrite(STDERR, "Refusing to run outside the named disposable MySQL test database.\n");
    exit(1);
}
putenv('APP_CONFIG_CACHE='.sys_get_temp_dir().'/courrier-concurrency-config-absent.php');
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (DB::getDriverName() !== 'mysql' || DB::connection()->getDatabaseName() !== 'courrier_concurrency_test') {
    throw new RuntimeException('Unexpected database configuration.');
}

if (($argv[1] ?? '') === 'worker') {
    [$mode, $userId, $key, $startAt] = array_slice($argv, 2);
    $actor = User::findOrFail($userId);
    Auth::login($actor);
    while (microtime(true) < (float) $startAt) {
        usleep(10000);
    }
    try {
        if ($mode === 'register') {
            $request = Request::create('/courriers', 'POST', [
                'submission_key' => $key, 'type' => 'entrant', 'objet' => 'Concurrent test', 'bureau_actuel_id' => $actor->bureau_id,
            ]);
            $request->setUserResolver(fn () => $actor);
            $app->make(CourrierController::class)->store($request);
        } elseif ($mode === 'wrong-pin' || $mode === 'approve') {
            $app->make(SignaturePinService::class)->approve($actor, ParapheurVisa::findOrFail($key), $mode === 'approve' ? '1234' : '9999');
        } elseif ($mode === 'reject') {
            $request = Request::create('/', 'POST', ['comment' => 'Concurrent rejection']);
            $request->setUserResolver(fn () => $actor);
            $app->make(ParapheurController::class)->reject($request, ParapheurVisa::findOrFail($key));
        } elseif ($mode === 'submit') {
            $request = Request::create('/', 'POST', ['validator_id' => User::where('email', 'director@example.test')->value('id')]);
            $request->setUserResolver(fn () => $actor);
            $app->make(CourrierController::class)->submitToParapheur($request, Courrier::findOrFail($key));
        }
        echo 'ok';
    } catch (ValidationException) {
        echo 'validation';
    } catch (HttpException $e) {
        echo (string) $e->getStatusCode();
    }
    exit;
}

function check(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

function parallel(array $jobs): array
{
    $processes = [];
    $start = (string) (microtime(true) + 4);
    foreach ($jobs as [$mode, $userId, $key]) {
        $process = proc_open([PHP_BINARY, __FILE__, 'worker', $mode, (string) $userId, (string) $key, $start],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        check(is_resource($process), 'Worker did not start.');
        fclose($pipes[0]);
        $processes[] = [$process, $pipes];
    }
    $results = [];
    foreach ($processes as [$process, $pipes]) {
        $output = trim(stream_get_contents($pipes[1]));
        $error = trim(stream_get_contents($pipes[2]));
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        check($exit === 0 && $error === '', 'Concurrency worker failed: '.$error);
        $results[] = $output;
    }

    return $results;
}

Artisan::call('migrate:fresh', ['--force' => true]);
Artisan::call('db:seed', ['--class' => BureauSeeder::class, '--force' => true]);
Artisan::call('db:seed', ['--class' => WorkflowEtapesSeeder::class, '--force' => true]);
Artisan::call('db:seed', ['--class' => RolesAndPermissionsSeeder::class, '--force' => true]);
$office = Bureau::where('code', 'accueil')->value('id');
$actors = [];
for ($i = 1; $i <= 8; $i++) {
    $user = User::forceCreate(['name' => 'Concurrent Test '.$i, 'email' => "actor{$i}@example.test", 'password' => Hash::make('TestOnlyPassword!2026'),
        'bureau_id' => $office, 'is_active' => true, 'email_verified_at' => now()]);
    $user->assignRole('agent_courrier');
    $actors[] = $user;
}
check(parallel(array_map(fn ($user) => ['register', $user->id, (string) Str::uuid()], $actors)) === array_fill(0, 8, 'ok'), 'Distinct registrations failed.');
check(Courrier::count() === 8 && Courrier::distinct()->count('reference_interne') === 8, 'References collided.');
$token = (string) Str::uuid();
check(parallel(array_fill(0, 4, ['register', $actors[0]->id, $token])) === array_fill(0, 4, 'ok'), 'Repeated form failed.');
check(Courrier::count() === 9 && DB::table('courrier_histories')->where('action', 'created')->count() === 9, 'Repeated form created duplicates.');
check(Courrier::orderBy('seq')->pluck('seq')->all() === range(1, 9), 'Sequence is inconsistent.');
echo "PASS: parallel references and repeated submissions\n";

$director = User::forceCreate(['name' => 'Director Test', 'email' => 'director@example.test', 'password' => Hash::make('TestOnlyPassword!2026'),
    'bureau_id' => $office, 'is_active' => true, 'email_verified_at' => now(), 'signature_pin' => Hash::make('1234')]);
$director->assignRole('directeur');
$visa = ParapheurVisa::create(['courrier_id' => Courrier::first()->id, 'validator_id' => $director->id, 'status' => 'pending']);
check(parallel(array_fill(0, 6, ['wrong-pin', $director->id, $visa->id])) === array_fill(0, 6, 'validation'), 'PIN attempt result is inconsistent.');
check($director->fresh()->signature_pin_attempts === 5 && $director->fresh()->signature_pin_locked_until->isFuture(), 'PIN lock lost an increment.');
check(parallel([['approve', $director->id, $visa->id]]) === ['validation'], 'Locked PIN was accepted.');
$director->forceFill(['signature_pin_locked_until' => now()->subSecond()])->save();
check(parallel([['approve', $director->id, $visa->id]]) === ['ok'], 'Expired lock did not recover.');
check($visa->fresh()->status === 'approved', 'Approval was not saved.');
echo "PASS: parallel PIN attempts and lock expiry\n";

$visa = ParapheurVisa::create(['courrier_id' => Courrier::first()->id, 'validator_id' => $director->id, 'status' => 'pending']);
$results = parallel([['approve', $director->id, $visa->id], ['reject', $director->id, $visa->id]]);
sort($results);
check($results === ['404', 'ok'], 'Approval and rejection both succeeded.');
echo "PASS: mutually exclusive visa decisions\n";

$courrier = Courrier::orderByDesc('id')->first();
$results = parallel(array_fill(0, 4, ['submit', $actors[0]->id, $courrier->id]));
sort($results);
check($results === ['ok', 'validation', 'validation', 'validation'], 'Concurrent submissions did not produce one pending visa.');
check($courrier->visas()->pending()->count() === 1, 'Duplicate pending visas exist.');
echo "PASS: one pending visa per dossier\n";
