<?php

namespace App\Services;

use App\Models\Courrier;
use Illuminate\Support\Facades\DB;
use LogicException;

class CourrierReferenceService
{
    public function next(string $type, int $year): array
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Allocate the reference in the courrier creation transaction.');
        }

        // The unique row also serializes the first registration of a year/type.
        DB::table('courrier_sequences')->upsert(
            [['type' => $type, 'year' => $year, 'last_seq' => 0]],
            ['type', 'year'],
            ['type'],
        );
        $query = DB::table('courrier_sequences')->where('type', $type)->where('year', $year);
        $counter = (clone $query)->lockForUpdate()->first();
        $lastExisting = Courrier::where('type', $type)->where('year', $year)->max('seq') ?? 0;
        $seq = max((int) $counter->last_seq, (int) $lastExisting) + 1;
        $query->update(['last_seq' => $seq]);

        $prefix = match ($type) {
            'sortant' => 'SOR', 'interne' => 'INT', default => 'ENT',
        };

        return ['seq' => $seq, 'reference_interne' => sprintf('GUTF-%s-%d-%06d', $prefix, $year, $seq)];
    }
}
