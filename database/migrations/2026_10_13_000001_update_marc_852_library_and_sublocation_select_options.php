<?php

use App\Models\MarcField;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $libraryNames = [
            'Kundo E. Pahm Learning Resource Center',
            'College of Veterinary Medicine Library',
            'College of Medicine Library',
            'Institute of Middle East and Asian Studies Library',
            'Graduate School Library',
            'University Laboratory School Library',
            'College of Agriculture Library',
        ];

        $sublocations = [
            'Circulation',
            'Filipiniana',
            'Reference',
            'Periodicals',
            'Fiction',
            'Reserve',
        ];

        MarcField::query()
            ->where('tag', '852')
            ->where('subfield', 'b')
            ->update([
                'input_type' => 'select',
                'options' => $libraryNames,
            ]);

        MarcField::query()
            ->where('tag', '852')
            ->where('subfield', 'c')
            ->update([
                'input_type' => 'select',
                'options' => $sublocations,
            ]);
    }

    public function down(): void
    {
        MarcField::query()
            ->where('tag', '852')
            ->where('subfield', 'b')
            ->update([
                'input_type' => 'select',
                'options' => ['Basic Education', 'Academic Library'],
            ]);

        MarcField::query()
            ->where('tag', '852')
            ->where('subfield', 'c')
            ->update([
                'input_type' => 'text',
                'options' => null,
            ]);
    }
};
