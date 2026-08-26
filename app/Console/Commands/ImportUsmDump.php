<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ImportUsmDump extends Command
{
    protected $signature = 'usm:import-dump
        {--source=u537625773_usmdata : Source database that already has the USM SQL dump}
        {--fresh : Truncate Pantas library tables before importing}
        {--skip-attendance : Skip attendance_logs (large table)}';

    protected $description = 'Copy USM dump tables into Pantas library_* schema (demo_2)';

    /** @var array<int, array{0: string, 1: string, 2?: array<string, string>}> */
    private array $copies = [
        // [source, target, optional expression overrides keyed by target column]
        ['roles', 'library_roles'],
        ['programs', 'library_programs'],
        ['program_years', 'library_program_years'],
        ['program_courses', 'library_program_courses'],
        ['catalog_frameworks', 'library_catalog_frameworks', ['code' => "CONCAT('fw_', id)", 'is_default' => '0']],
        ['marc_fields', 'library_marc_fields', [
            'name' => 'COALESCE(`label`, `tag`)',
            'required' => '0',
            'select_options' => '`options`',
            'description' => 'NULL',
        ]],
        ['catalog_framework_fields', 'library_catalog_framework_fields', [
            'catalog_framework_id' => '`framework_id`',
        ]],
        ['years', 'years'],
        ['settings', 'settings'],
        ['settings', 'library_settings'],
        ['holidays', 'library_holidays', ['is_active' => '1', 'date' => '`holiday_date`']],
        ['fine_settings', 'library_fine_settings'],
        ['students', 'library_students'],
        ['employees', 'library_employees'],
        ['pending_students', 'library_pending_students'],
        ['pending_employees', 'library_pending_employees'],
        ['books', 'library_books', [
            'accession_no' => "NULLIF(TRIM(`accession_no`), '')",
            'barcode' => "NULLIF(TRIM(`barcode`), '')",
            'rfid' => "NULLIF(TRIM(`rfid`), '')",
        ]],
        ['book_marc_fields', 'library_book_marc_fields'],
        ['book_program', 'library_book_program'],
        ['book_logs', 'library_book_logs'],
        ['book_reservations', 'library_book_reservations'],
        ['ebooks', 'library_ebooks'],
        ['rooms', 'library_rooms', ['is_active' => '1']],
        ['room_reservations', 'library_room_reservations'],
        ['reservation_students', 'library_reservation_students'],
        ['reservation_logs', 'library_reservation_logs'],
        ['feedback', 'library_feedback'],
        ['files', 'library_files'],
        ['prospectuses', 'library_prospectuses', [
            'course_name' => '`course`',
            'course_code' => '`subject`',
            'year_number' => 'NULL',
            'sort_order' => '0',
            'program_id' => 'NULL',
        ]],
        ['student_edit_requests', 'library_student_edit_requests'],
        ['attendance_feedback', 'library_attendance_feedbacks'],
        ['zendy_logs', 'zendy_logs'],
        ['admin_activities', 'admin_activities'],
    ];

    /** Truncate order: children first */
    private array $truncateOrder = [
        'library_attendance_logs',
        'library_attendance_feedbacks',
        'library_book_logs',
        'library_book_marc_fields',
        'library_book_program',
        'library_book_reservations',
        'library_reservation_logs',
        'library_reservation_students',
        'library_room_reservations',
        'library_student_edit_requests',
        'library_employee_edit_requests',
        'library_ebooks',
        'library_books',
        'library_pending_students',
        'library_pending_employees',
        'library_students',
        'library_employees',
        'library_rooms',
        'library_files',
        'library_feedback',
        'library_prospectuses',
        'library_fine_settings',
        'library_holidays',
        'library_settings',
        'library_catalog_framework_fields',
        'library_marc_fields',
        'library_catalog_frameworks',
        'library_program_courses',
        'library_program_years',
        'library_programs',
        'library_roles',
        'zendy_logs',
        'admin_activities',
        'years',
    ];

    public function handle(): int
    {
        $source = (string) $this->option('source');
        $target = (string) config('database.connections.mysql.database');

        if ($source === $target) {
            $this->error('Source and target databases must differ.');

            return self::FAILURE;
        }

        $exists = DB::selectOne('SELECT SCHEMA_NAME AS n FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?', [$source]);
        if (! $exists) {
            $this->error("Source database [{$source}] was not found. Import the SQL dump first.");

            return self::FAILURE;
        }

        $books = DB::selectOne("SELECT COUNT(*) AS c FROM `{$source}`.`books`");
        if (! $books || (int) $books->c === 0) {
            $this->error("Source [{$source}] has no books yet. Wait for the SQL import to finish.");

            return self::FAILURE;
        }

        $this->info("Importing from [{$source}] → [{$target}] ({$books->c} books in source)");

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::statement("SET SESSION sql_mode = REPLACE(REPLACE(@@sql_mode,'NO_ZERO_DATE',''),'NO_ZERO_IN_DATE','')");

        try {
            if ($this->option('fresh')) {
                $this->warn('Truncating Pantas library tables…');
                foreach ($this->truncateOrder as $table) {
                    if ($this->tableExists($target, $table)) {
                        DB::table($table)->truncate();
                        $this->line("  truncated {$table}");
                    }
                }
                // Keep branding_settings; replace users from dump.
                if ($this->tableExists($target, 'users')) {
                    DB::table('users')->truncate();
                    $this->line('  truncated users');
                }
            }

            $this->importUsers($source);
            $this->importSharedCopies($source);

            if (! $this->option('skip-attendance')) {
                $this->importAttendanceLogs($source);
            } else {
                $this->warn('Skipped attendance_logs (--skip-attendance).');
            }

            $this->info('Done. Sample counts:');
            foreach (['users', 'library_students', 'library_employees', 'library_books', 'library_book_logs', 'library_attendance_logs'] as $table) {
                if ($this->tableExists($target, $table)) {
                    $this->line(sprintf('  %-28s %s', $table, DB::table($table)->count()));
                }
            }
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        return self::SUCCESS;
    }

    private function importUsers(string $source): void
    {
        $this->info('Importing users…');

        $count = $this->copyIntersectingColumns($source, 'users', 'users', [
            'is_active' => '1',
        ]);
        $this->line("  users: {$count}");
    }

    private function importSharedCopies(string $source): void
    {
        foreach ($this->copies as $copy) {
            [$from, $to, $overrides] = [$copy[0], $copy[1], $copy[2] ?? []];

            if (! $this->tableExists($source, $from) || ! $this->tableExists(config('database.connections.mysql.database'), $to)) {
                $this->warn("  skip {$from} → {$to} (missing table)");
                continue;
            }

            try {
                $count = $this->copyIntersectingColumns($source, $from, $to, $overrides);
                $this->line("  {$from} → {$to}: {$count}");
            } catch (Throwable $e) {
                $this->error("  FAILED {$from} → {$to}: ".$e->getMessage());
            }
        }
    }

    /**
     * @param  array<string, string>  $overrides  target_column => SQL expression
     */
    private function copyIntersectingColumns(string $source, string $from, string $to, array $overrides = []): int
    {
        $targetDb = (string) config('database.connections.mysql.database');
        $sourceCols = $this->columnNames($source, $from);
        $targetCols = $this->columnNames($targetDb, $to);

        $shared = array_values(array_intersect($targetCols, $sourceCols));
        // Prefer source order but only shared; then add override-only columns.
        foreach (array_keys($overrides) as $col) {
            if (in_array($col, $targetCols, true) && ! in_array($col, $shared, true)) {
                $shared[] = $col;
            }
        }

        if ($shared === []) {
            return 0;
        }

        $selectParts = [];
        foreach ($shared as $col) {
            if (isset($overrides[$col])) {
                $selectParts[] = $overrides[$col].' AS `'.$col.'`';
                continue;
            }

            $type = $this->columnType($source, $from, $col);
            if (in_array($type, ['date', 'datetime', 'timestamp'], true)) {
                $selectParts[] = "NULLIF(NULLIF(`{$from}`.`{$col}`, '0000-00-00'), '0000-00-00 00:00:00') AS `{$col}`";
            } else {
                $selectParts[] = "`{$from}`.`{$col}`";
            }
        }

        $colsSql = implode(', ', array_map(fn ($c) => '`'.$c.'`', $shared));
        $selectSql = implode(', ', $selectParts);

        DB::statement("DELETE FROM `{$to}`");
        DB::statement("INSERT INTO `{$to}` ({$colsSql}) SELECT {$selectSql} FROM `{$source}`.`{$from}`");

        return (int) DB::table($to)->count();
    }

    private function importAttendanceLogs(string $source): void
    {
        $this->info('Importing attendance_logs → library_attendance_logs…');

        DB::table('library_attendance_logs')->truncate();

        // Dump student_id is varchar of library student PK.
        $sql = "
            INSERT INTO `library_attendance_logs` (id, student_id, status, scanned_at, created_at, updated_at)
            SELECT
                a.id,
                CASE
                    WHEN a.student_id REGEXP '^[0-9]+$' AND EXISTS (
                        SELECT 1 FROM `library_students` s WHERE s.id = CAST(a.student_id AS UNSIGNED)
                    ) THEN CAST(a.student_id AS UNSIGNED)
                    ELSE NULL
                END AS student_id,
                a.status,
                a.scanned_at,
                a.created_at,
                a.updated_at
            FROM `{$source}`.`attendance_logs` a
            WHERE a.student_id REGEXP '^[0-9]+$'
              AND EXISTS (
                  SELECT 1 FROM `library_students` s WHERE s.id = CAST(a.student_id AS UNSIGNED)
              )
        ";

        DB::statement($sql);
        $this->line('  library_attendance_logs: '.DB::table('library_attendance_logs')->count());
    }

    private function tableExists(string $schema, string $table): bool
    {
        $row = DB::selectOne(
            'SELECT 1 AS ok FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? LIMIT 1',
            [$schema, $table]
        );

        return (bool) $row;
    }

    /** @return list<string> */
    private function columnNames(string $schema, string $table): array
    {
        $rows = DB::select(
            'SELECT COLUMN_NAME AS name FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
            [$schema, $table]
        );

        return array_map(fn ($r) => (string) $r->name, $rows);
    }

    private function columnType(string $schema, string $table, string $column): string
    {
        $row = DB::selectOne(
            'SELECT DATA_TYPE AS t FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$schema, $table, $column]
        );

        return strtolower((string) ($row->t ?? ''));
    }
}
