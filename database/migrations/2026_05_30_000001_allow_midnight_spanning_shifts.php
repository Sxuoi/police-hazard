<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Phase 3 — Allow midnight-spanning shifts.
     *
     * Phase 1 enforced `shift_end > shift_start`, which blocks legitimate
     * overnight patrol windows (e.g. 18:00 → 06:00). The application layer
     * (LocationTimezoneResolver::shiftWindow) and the Phase 3 requirements
     * glossary both already handle midnight-spanning windows, so the DB
     * rule is too strict.
     *
     * Replace it with `shift_end <> shift_start`, which still rejects
     * zero-length shifts while allowing the end-of-day wraparound.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE shifts DROP CONSTRAINT IF EXISTS chk_shift_time');
        DB::statement('ALTER TABLE shifts ADD CONSTRAINT chk_shift_time CHECK (shift_end <> shift_start)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE shifts DROP CONSTRAINT IF EXISTS chk_shift_time');
        DB::statement('ALTER TABLE shifts ADD CONSTRAINT chk_shift_time CHECK (shift_end > shift_start)');
    }
};
