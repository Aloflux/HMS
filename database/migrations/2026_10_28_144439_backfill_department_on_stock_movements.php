<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Movements were saved without a department. Take it from the store each movement touched.
     */
    public function up(): void
    {
        DB::table('stock_movements')
            ->whereNull('department_id')
            ->update([
                'department_id' => DB::table('stock_locations')
                    ->select('department_id')
                    ->whereColumn('stock_locations.id', 'stock_movements.stock_location_id'),
            ]);
    }

    public function down(): void
    {
        //
    }
};
