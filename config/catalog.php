<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Curriculum (collection area)
    |--------------------------------------------------------------------------
    |
    | Used on cataloging forms (Programs tab). Keys are stored on books.curriculum.
    |
    */

    'curriculum_options' => [
        'prof ed' => 'Prof Ed',
        'gen ed' => 'Gen Ed',
        'filipiniana' => 'Filipiniana',
        'general reference' => 'General Reference',
    ],

    /*
    | Per-copy identifier for circulation (scan/type at checkout).
    | Lookup order: first match wins. Accession no. is the usual primary ID when RFID is not used.
    */
    'copy_identifier_fields' => [
        'accession_no' => 'Accession no.',
        'barcode' => 'Barcode',
        'rfid' => 'RFID',
    ],

    /*
    | Per-copy fields when cataloging multiple copies of one title.
    */
    'copy_unique_columns' => ['accession_no', 'rfid'],

    'copy_unique_marc' => [
        ['book_column' => 'accession_no', 'tag' => '949', 'subfield' => null, 'label' => 'Accession no.'],
        ['book_column' => 'rfid', 'tag' => '999', 'subfield' => 'r', 'label' => 'RFID'],
    ],

    /*
    | MARC select fields where catalogers may add new dropdown options (saved on marc_fields.options).
    */
    'extensible_select_marc' => [
        ['tag' => '336', 'subfield' => 'a', 'title' => 'Content type', 'book_column' => 'content_type'],
        ['tag' => '337', 'subfield' => 'a', 'title' => 'Media type', 'book_column' => 'media_type'],
        ['tag' => '338', 'subfield' => 'a', 'title' => 'Carrier type', 'book_column' => 'carrier_type'],
        ['tag' => '852', 'subfield' => 'b', 'title' => 'Library Name', 'book_column' => 'library_name'],
        ['tag' => '852', 'subfield' => 'c', 'title' => 'Sublocation / shelving (local)', 'book_column' => 'section'],
    ],

];
