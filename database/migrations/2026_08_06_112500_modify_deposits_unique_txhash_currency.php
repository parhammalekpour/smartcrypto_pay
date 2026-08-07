<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected function getIndexNamesForColumn(string $tableName, string $columnName): array
    {
        $connection = DB::connection();
        $database = $connection->getDatabaseName();

        $rows = DB::select(
            'SELECT INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS columns FROM information_schema.statistics WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? GROUP BY INDEX_NAME',
            [$database, $tableName]
        );

        $names = [];
        foreach ($rows as $row) {
            if (!isset($row->INDEX_NAME, $row->columns)) {
                continue;
            }
            $cols = explode(',', $row->columns);
            if (count($cols) === 1 && $cols[0] === $columnName) {
                $names[] = $row->INDEX_NAME;
            }
        }

        return $names;
    }

    protected function hasIndexWithColumns(string $tableName, array $columns): bool
    {
        $connection = DB::connection();
        $database = $connection->getDatabaseName();

        $rows = DB::select(
            'SELECT GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS columns FROM information_schema.statistics WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? GROUP BY INDEX_NAME',
            [$database, $tableName]
        );

        foreach ($rows as $row) {
            if (!isset($row->columns)) {
                continue;
            }
            $cols = explode(',', $row->columns);
            if ($cols === $columns) {
                return true;
            }
        }

        return false;
    }

    public function up(): void
    {
        $indexNames = [];
        $compositeExists = false;

        try {
            $indexNames = $this->getIndexNamesForColumn('deposits', 'tx_hash');
            $compositeExists = $this->hasIndexWithColumns('deposits', ['tx_hash', 'currency']);
        } catch (\Throwable $e) {
            // ignore failed detection and attempt conventional drop later
        }

        if (!empty($indexNames)) {
            foreach ($indexNames as $indexName) {
                try {
                    DB::statement('ALTER TABLE `deposits` DROP INDEX `' . $indexName . '`');
                } catch (\Throwable $_) {
                    // ignore if remove fails
                }
            }
        }

        if (!$compositeExists) {
            Schema::table('deposits', function (Blueprint $table) {
                $table->unique(['tx_hash', 'currency']);
            });
        }
    }

    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE `deposits` DROP INDEX `deposits_tx_hash_currency_unique`');
        } catch (\Throwable $_) {
            // ignore if it doesn't exist
        }

        Schema::table('deposits', function (Blueprint $table) {
            $table->unique('tx_hash');
        });
    }
};
