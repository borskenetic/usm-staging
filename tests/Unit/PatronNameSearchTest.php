<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\Student;
use App\Support\PatronNameSearch;
use Tests\TestCase;

class PatronNameSearchTest extends TestCase
{
    public function test_comma_format_matches_lastname_then_firstname(): void
    {
        $query = Student::query();
        PatronNameSearch::apply($query, 'Aba, Fatma');

        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $this->assertStringContainsString('lastname', $sql);
        $this->assertStringContainsString('firstname', $sql);
        $this->assertContains('%Aba%', $bindings);
        $this->assertContains('%Fatma%', $bindings);
        $this->assertNotContains('%Aba, Fatma%', $bindings);
    }

    public function test_employee_search_keeps_extra_columns(): void
    {
        $query = Employee::query();
        PatronNameSearch::apply($query, 'Paleta, Leonard', ['employee_id', 'designation']);

        $bindings = $query->getBindings();

        $this->assertContains('%Paleta%', $bindings);
        $this->assertContains('%Leonard%', $bindings);
        $this->assertContains('%Paleta, Leonard%', $bindings);
    }
}
