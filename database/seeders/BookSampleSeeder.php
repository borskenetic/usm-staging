<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Program;
use Illuminate\Database\Seeder;

class BookSampleSeeder extends Seeder
{
    public function run(): void
    {
        $library = 'Kundo E. Pahm Learning Resource Center';
        $section = 'Circulation';

        /**
         * Each entry is one physical copy. Duplicate titles share bibliographic fields.
         *
         * @var list<array<string, mixed>>
         */
        $copies = [
            // —— Gen Ed / IT ——
            [
                'accession_no' => 'GG-2024-0001',
                'rfid' => 'RFID-GG-00001',
                'barcode' => 'BC-GG-00001',
                'title_statement' => 'Introduction to Computer Science',
                'main_author' => 'Garcia, Maria L.',
                'publisher' => 'Oxford University Press',
                'pub_year' => '2022',
                'isbn' => '9780198765432',
                'call_number' => 'QA76 .G37 2022',
                'content_type' => 'Printed',
                'curriculum' => 'gen ed',
                'program_codes' => ['BSCS', 'BSIT'],
                'course' => 'Introduction to Computing',
                'year' => '1st Year',
            ],
            [
                'accession_no' => 'GG-2024-0002',
                'rfid' => 'RFID-GG-00002',
                'barcode' => 'BC-GG-00002',
                'title_statement' => 'Introduction to Computer Science',
                'main_author' => 'Garcia, Maria L.',
                'publisher' => 'Oxford University Press',
                'pub_year' => '2022',
                'isbn' => '9780198765432',
                'call_number' => 'QA76 .G37 2022 c.2',
                'content_type' => 'Printed',
                'curriculum' => 'gen ed',
                'program_codes' => ['BSCS', 'BSIT'],
                'course' => 'Introduction to Computing',
                'year' => '1st Year',
            ],
            [
                'accession_no' => 'GG-2024-0003',
                'rfid' => 'RFID-GG-00003',
                'barcode' => 'BC-GG-00003',
                'title_statement' => 'Data Structures and Algorithms in Java',
                'main_author' => 'Lafore, Robert',
                'publisher' => 'Sams Publishing',
                'pub_year' => '2021',
                'isbn' => '9780134670945',
                'call_number' => 'QA76.73 .J38 L34 2021',
                'content_type' => 'Printed',
                'curriculum' => 'gen ed',
                'program_codes' => ['BSCS'],
                'course' => 'Data Structures',
                'year' => '2nd Year',
            ],
            // —— Prof Ed ——
            [
                'accession_no' => 'GG-2024-0004',
                'rfid' => 'RFID-GG-00004',
                'barcode' => 'BC-GG-00004',
                'title_statement' => 'Teaching in the Elementary Grades',
                'main_author' => 'Aquino, Gaudencio V.',
                'publisher' => 'Rex Book Store',
                'pub_year' => '2020',
                'call_number' => 'LB1555 .A68 2020',
                'content_type' => 'Printed',
                'curriculum' => 'prof ed',
                'program_codes' => ['BEED', 'BSED'],
                'course' => 'Principles of Teaching',
                'year' => '2nd Year',
            ],
            [
                'accession_no' => 'GG-2024-0005',
                'rfid' => 'RFID-GG-00005',
                'barcode' => 'BC-GG-00005',
                'title_statement' => 'Curriculum Development for Teachers',
                'main_author' => 'Ornstein, Allan C.',
                'publisher' => 'Cengage Learning',
                'pub_year' => '2019',
                'call_number' => 'LB2806 .O78 2019',
                'content_type' => 'Printed',
                'curriculum' => 'prof ed',
                'program_codes' => ['BSED'],
                'course' => 'Curriculum Planning',
                'year' => '2nd Year',
            ],
            [
                'accession_no' => 'GG-2024-0006',
                'rfid' => 'RFID-GG-00006',
                'barcode' => 'BC-GG-00006',
                'title_statement' => 'Curriculum Development for Teachers',
                'main_author' => 'Ornstein, Allan C.',
                'publisher' => 'Cengage Learning',
                'pub_year' => '2019',
                'call_number' => 'LB2806 .O78 2019 c.2',
                'content_type' => 'Printed',
                'curriculum' => 'prof ed',
                'program_codes' => ['BSED'],
                'course' => 'Curriculum Planning',
                'year' => '2nd Year',
            ],
            // —— Filipiniana ——
            [
                'accession_no' => 'GG-2024-0007',
                'rfid' => 'RFID-GG-00007',
                'barcode' => 'BC-GG-00007',
                'title_statement' => 'History of the Filipino People',
                'main_author' => 'Agoncillo, Teodoro A.',
                'publisher' => 'Garotech Publishing',
                'pub_year' => '2018',
                'call_number' => 'DS676 .A35 2018',
                'content_type' => 'Printed',
                'curriculum' => 'filipiniana',
                'program_codes' => ['BEED', 'BSED', 'BSBA', 'BSCS', 'BSIT'],
                'course' => 'Readings in Philippine History',
                'year' => '1st Year',
            ],
            [
                'accession_no' => 'GG-2024-0008',
                'rfid' => 'RFID-GG-00008',
                'barcode' => 'BC-GG-00008',
                'title_statement' => 'Noli Me Tangere',
                'main_author' => 'Rizal, José',
                'publisher' => 'National Historical Commission',
                'pub_year' => '2017',
                'call_number' => 'PQ8891 .R5 N65 2017',
                'content_type' => 'Printed',
                'curriculum' => 'filipiniana',
                'program_codes' => ['BEED', 'BSED'],
                'course' => 'Readings in Philippine History',
                'year' => '1st Year',
            ],
            [
                'accession_no' => 'GG-2024-0009',
                'rfid' => 'RFID-GG-00009',
                'barcode' => 'BC-GG-00009',
                'title_statement' => 'Noli Me Tangere',
                'main_author' => 'Rizal, José',
                'publisher' => 'National Historical Commission',
                'pub_year' => '2017',
                'call_number' => 'PQ8891 .R5 N65 2017 c.2',
                'content_type' => 'Printed',
                'curriculum' => 'filipiniana',
                'program_codes' => ['BEED', 'BSED'],
                'course' => 'Readings in Philippine History',
                'year' => '1st Year',
            ],
            // —— General reference ——
            [
                'accession_no' => 'GG-2024-0010',
                'rfid' => 'RFID-GG-00010',
                'barcode' => 'BC-GG-00010',
                'title_statement' => 'Merriam-Webster\'s Collegiate Dictionary',
                'main_author' => 'Merriam-Webster',
                'publisher' => 'Merriam-Webster, Inc.',
                'pub_year' => '2023',
                'call_number' => 'REF PE1628 .M47 2023',
                'content_type' => 'Printed',
                'curriculum' => 'general reference',
                'library_name' => 'Kundo E. Pahm Learning Resource Center',
                'section' => 'Reference',
                'program_codes' => ['BSCS', 'BSIT', 'BEED', 'BSED', 'BSBA', 'BSN'],
                'course' => 'Purposive Communication',
                'year' => '1st Year',
            ],
            [
                'accession_no' => 'GG-2024-0011',
                'rfid' => 'RFID-GG-00011',
                'barcode' => 'BC-GG-00011',
                'title_statement' => 'World Almanac and Book of Facts',
                'main_author' => 'World Almanac Editors',
                'publisher' => 'World Almanac Books',
                'pub_year' => '2024',
                'call_number' => 'REF AY67 .W67 2024',
                'content_type' => 'Printed',
                'curriculum' => 'general reference',
                'library_name' => 'Kundo E. Pahm Learning Resource Center',
                'section' => 'Reference',
                'program_codes' => ['BSCS', 'BSIT', 'BEED', 'BSED', 'BSBA', 'BSN'],
                'course' => 'The Contemporary World',
                'year' => '2nd Year',
            ],
            // —— Nursing / Business ——
            [
                'accession_no' => 'GG-2024-0012',
                'rfid' => 'RFID-GG-00012',
                'barcode' => 'BC-GG-00012',
                'title_statement' => 'Fundamentals of Nursing',
                'main_author' => 'Kozier, Barbara',
                'publisher' => 'Pearson',
                'pub_year' => '2021',
                'isbn' => '9780133974364',
                'call_number' => 'RT41 .K69 2021',
                'content_type' => 'Printed',
                'curriculum' => 'prof ed',
                'program_codes' => ['BSN'],
                'course' => 'Fundamentals of Nursing',
                'year' => '1st Year',
            ],
            [
                'accession_no' => 'GG-2024-0013',
                'rfid' => 'RFID-GG-00013',
                'barcode' => 'BC-GG-00013',
                'title_statement' => 'Principles of Marketing',
                'main_author' => 'Kotler, Philip',
                'publisher' => 'Pearson',
                'pub_year' => '2020',
                'call_number' => 'HF5415 .K67 2020',
                'content_type' => 'Printed',
                'curriculum' => 'gen ed',
                'program_codes' => ['BSBA'],
                'course' => 'Marketing Management',
                'year' => '3rd Year',
            ],
            [
                'accession_no' => 'GG-2024-0014',
                'rfid' => 'RFID-GG-00014',
                'barcode' => 'BC-GG-00014',
                'title_statement' => 'Principles of Marketing',
                'main_author' => 'Kotler, Philip',
                'publisher' => 'Pearson',
                'pub_year' => '2020',
                'call_number' => 'HF5415 .K67 2020 c.2',
                'content_type' => 'Printed',
                'curriculum' => 'gen ed',
                'program_codes' => ['BSBA'],
                'course' => 'Marketing Management',
                'year' => '3rd Year',
            ],
            [
                'accession_no' => 'GG-2024-0015',
                'rfid' => 'RFID-GG-00015',
                'barcode' => 'BC-GG-00015',
                'title_statement' => 'Database System Concepts',
                'main_author' => 'Silberschatz, Abraham',
                'publisher' => 'McGraw-Hill',
                'pub_year' => '2023',
                'isbn' => '9780078025907',
                'call_number' => 'QA76.9 .D3 S563 2023',
                'content_type' => 'Printed',
                'curriculum' => 'gen ed',
                'program_codes' => ['BSCS', 'BSIT'],
                'course' => 'Database Systems',
                'year' => '3rd Year',
            ],
            [
                'accession_no' => 'GG-2024-0016',
                'rfid' => 'RFID-GG-00016',
                'barcode' => 'BC-GG-00016',
                'title_statement' => 'Research Methods in Education',
                'main_author' => 'Cohen, Louis',
                'publisher' => 'Routledge',
                'pub_year' => '2018',
                'call_number' => 'LB1028 .C577 2018',
                'content_type' => 'Printed',
                'curriculum' => 'prof ed',
                'program_codes' => ['BEED', 'BSED'],
                'course' => 'Research in Education',
                'year' => '4th Year',
            ],
            [
                'accession_no' => 'GG-2024-0017',
                'rfid' => 'RFID-GG-00017',
                'barcode' => 'BC-GG-00017',
                'title_statement' => 'Philippine Constitution',
                'main_author' => 'Philippine Government',
                'publisher' => 'National Government',
                'pub_year' => '1987',
                'call_number' => 'KPM1744 .P45 1987',
                'content_type' => 'Printed',
                'curriculum' => 'filipiniana',
                'program_codes' => ['BEED', 'BSED', 'BSBA', 'BSCS', 'BSIT'],
                'course' => 'Ethics',
                'year' => '2nd Year',
            ],
            [
                'accession_no' => 'GG-2024-0018',
                'rfid' => 'RFID-GG-00018',
                'barcode' => 'BC-GG-00018',
                'title_statement' => 'Encyclopedia of Philippine Art',
                'main_author' => 'CCP Encyclopedia Staff',
                'publisher' => 'Cultural Center of the Philippines',
                'pub_year' => '2015',
                'call_number' => 'REF NX572 .P5 E53 2015',
                'content_type' => 'Printed',
                'curriculum' => 'general reference',
                'library_name' => 'Kundo E. Pahm Learning Resource Center',
                'section' => 'Reference',
                'program_codes' => ['BSCS', 'BSIT', 'BEED', 'BSED', 'BSBA', 'BSN'],
                'course' => 'Art Appreciation',
                'year' => '2nd Year',
            ],
        ];

        $created = 0;

        foreach ($copies as $row) {
            $programCodes = $row['program_codes'] ?? [];
            unset($row['program_codes']);

            $book = Book::updateOrCreate(
                ['accession_no' => $row['accession_no']],
                array_merge([
                    'availability' => 'Available',
                    'library_name' => $library,
                    'section' => $section,
                    'cataloging_source_e' => 'rda',
                ], $row)
            );

            if ($programCodes !== []) {
                $ids = Program::whereIn('program_code', $programCodes)->pluck('id');
                $book->programs()->sync($ids);
            } else {
                $book->programs()->detach();
            }

            $created++;
        }

        $created += $this->seedCatalogTitles($library);

        $titleCount = Book::query()
            ->where('accession_no', 'like', 'GG-2024-%')
            ->distinct()
            ->count('title_statement');

        $withPrograms = Book::whereHas('programs')->count();
        $withCourse = Book::whereNotNull('course')->where('course', '!=', '')->count();

        $this->command?->info("{$created} book copies seeded ({$titleCount} unique titles).");
        $this->command?->info("{$withPrograms} copies linked to programs; {$withCourse} copies have a course set.");
    }

    private function seedCatalogTitles(string $library): int
    {
        $catalog = [
            ['Calculus: Early Transcendentals', 'Stewart, James', 'Circulation', '2018', 'QA303 .S74 2018'],
            ['Organic Chemistry', 'McMurry, John', 'Circulation', '2016', 'QD251.3 .M36 2016'],
            ['University Physics', 'Young, Hugh D.', 'Circulation', '2020', 'QC21.3 .Y68 2020'],
            ['Microbiology: An Introduction', 'Tortora, Gerard J.', 'Circulation', '2019', 'QR41.2 .T67 2019'],
            ['Human Anatomy and Physiology', 'Marieb, Elaine N.', 'Circulation', '2019', 'QP34.5 .M265 2019'],
            ['Principles of Economics', 'Mankiw, N. Gregory', 'Circulation', '2021', 'HB172 .M36 2021'],
            ['Financial Accounting', 'Weygandt, Jerry J.', 'Circulation', '2018', 'HF5636 .W49 2018'],
            ['Business Law', 'Clarkson, Kenneth W.', 'Circulation', '2017', 'KF889 .C55 2017'],
            ['Organizational Behavior', 'Robbins, Stephen P.', 'Circulation', '2019', 'HD58.7 .R62 2019'],
            ['Statistics for the Behavioral Sciences', 'Gravetter, Frederick J.', 'Circulation', '2017', 'BF39 .G72 2017'],
            ['Noli Me Tangere', 'Rizal, Jose', 'Filipiniana', '2006', 'PQ8897 .R5 N6 2006'],
            ['El Filibusterismo', 'Rizal, Jose', 'Filipiniana', '2006', 'PQ8897 .R5 E4 2006'],
            ['Florante at Laura', 'Balagtas, Francisco', 'Filipiniana', '2010', 'PL6053 .B3 F5 2010'],
            ['The Philippines: A Past Revisited', 'Constantino, Renato', 'Filipiniana', '1975', 'DS668 .C66 1975'],
            ['History of the Filipino People', 'Agoncillo, Teodoro A.', 'Filipiniana', '1990', 'DS668 .A35 1990'],
            ['Banaag at Sikat', 'Santos, Lope K.', 'Filipiniana', '2008', 'PL6053 .S26 B3 2008'],
            ['Mga Ibong Mandaragit', 'Hernandez, Amado V.', 'Filipiniana', '2005', 'PL6053 .H47 I2 2005'],
            ['Philippine History', 'Zaide, Gregorio F.', 'Filipiniana', '1999', 'DS668 .Z35 1999'],
            ['To Kill a Mockingbird', 'Lee, Harper', 'Fiction', '2006', 'PS3562 .E353 T6 2006'],
            ['The Little Prince', 'Saint-Exupery, Antoine de', 'Fiction', '2000', 'PQ2637 .A274 P413 2000'],
            ['Nineteen Eighty-Four', 'Orwell, George', 'Fiction', '2003', 'PR6029 .R8 N49 2003'],
            ['Pride and Prejudice', 'Austen, Jane', 'Fiction', '2008', 'PR4034 .P7 2008'],
            ['The Alchemist', 'Coelho, Paulo', 'Fiction', '2014', 'PQ9698.13 .O3546 A413 2014'],
            ['One Hundred Years of Solitude', 'Garcia Marquez, Gabriel', 'Fiction', '2006', 'PQ8180.17 .A73 C513 2006'],
            ['The Hobbit', 'Tolkien, J. R. R.', 'Fiction', '2012', 'PR6039 .O32 H63 2012'],
            ['Harry Potter and the Sorcerer\'s Stone', 'Rowling, J. K.', 'Fiction', '1998', 'PR6068 .O93 H37 1998'],
            ['Oxford English Dictionary', 'Oxford University Press', 'Reference', '2010', 'REF PE1625 .O87 2010'],
            ['Merriam-Webster Collegiate Dictionary', 'Merriam-Webster', 'Reference', '2020', 'REF PE1628 .M36 2020'],
            ['Atlas of the World', 'National Geographic', 'Reference', '2019', 'REF G1021 .N38 2019'],
            ['Encyclopedia Britannica Yearbook', 'Encyclopaedia Britannica', 'Reference', '2022', 'REF AE5 .E363 2022'],
            ['Philippine Statistical Yearbook', 'Philippine Statistics Authority', 'Reference', '2023', 'REF HA1821 .P45 2023'],
            ['Journal of Philippine Librarianship', 'University of the Philippines', 'Periodicals', '2022', 'PER Z671 .J68 2022'],
            ['Philippine Journal of Science', 'Department of Science and Technology', 'Periodicals', '2021', 'PER Q1 .P45 2021'],
            ['Time Magazine Bound Volume', 'Time Inc.', 'Periodicals', '2019', 'PER AP2 .T47 2019'],
            ['National Geographic Collection', 'National Geographic Society', 'Periodicals', '2018', 'PER G1 .N27 2018'],
            ['Educational Research Review', 'Elsevier', 'Periodicals', '2020', 'PER LB1028 .E38 2020'],
            ['Reserve Readings in Biology', 'USM Faculty', 'Reserve', '2024', 'RES QH308 .U86 2024'],
            ['Reserve Readings in Calculus', 'USM Faculty', 'Reserve', '2024', 'RES QA303 .U86 2024'],
            ['Reserve Readings in Philippine Government', 'USM Faculty', 'Reserve', '2024', 'RES JQ1416 .U86 2024'],
            ['Reserve Readings in Nursing', 'USM Faculty', 'Reserve', '2024', 'RES RT41 .U86 2024'],
            ['Introduction to Psychology', 'Myers, David G.', 'Circulation', '2018', 'BF121 .M94 2018'],
            ['Sociology: A Brief Introduction', 'Schaefer, Richard T.', 'Circulation', '2019', 'HM586 .S33 2019'],
            ['Environmental Science', 'Miller, G. Tyler', 'Circulation', '2016', 'GE105 .M55 2016'],
            ['Agricultural Extension', 'Swanson, Burton E.', 'Circulation', '2014', 'S544 .S93 2014'],
            ['Veterinary Anatomy', 'Dyce, K. M.', 'Circulation', '2018', 'SF761 .D93 2018'],
            ['Plant Physiology', 'Taiz, Lincoln', 'Circulation', '2015', 'QK711.2 .T35 2015'],
            ['The Nature and Properties of Soils', 'Brady, Nyle C.', 'Circulation', '2017', 'S591 .B73 2017'],
            ['Public Administration', 'Denhardt, Robert B.', 'Circulation', '2015', 'JF1351 .D46 2015'],
            ['Information Literacy', 'American Library Association', 'Circulation', '2016', 'ZA3075 .A64 2016'],
            ['PHP and MySQL Web Development', 'Welling, Luke', 'Circulation', '2017', 'QA76.73 .P224 W45 2017'],
        ];

        $extraCopyTitles = [
            'Calculus: Early Transcendentals',
            'Human Anatomy and Physiology',
            'Noli Me Tangere',
            'To Kill a Mockingbird',
            'Oxford English Dictionary',
            'Reserve Readings in Biology',
            'Introduction to Psychology',
        ];

        $sequence = 101;
        $created = 0;

        foreach ($catalog as $row) {
            [$title, $author, $section, $year, $callNumber] = $row;
            $copyCount = in_array($title, $extraCopyTitles, true) ? 2 : 1;
            if ($title === 'Noli Me Tangere') {
                $copyCount = 3;
            }

            for ($copy = 1; $copy <= $copyCount; $copy++) {
                $accession = sprintf('GG-2024-%04d', $sequence);
                $sequence++;

                Book::updateOrCreate(
                    ['accession_no' => $accession],
                    [
                        'title_statement' => $title,
                        'main_author' => $author,
                        'publisher' => 'University Press',
                        'pub_year' => $year,
                        'call_number' => $copy === 1 ? $callNumber : $callNumber.' c.'.$copy,
                        'content_type' => 'Printed',
                        'cataloging_source_e' => 'rda',
                        'library_name' => $library,
                        'section' => $section,
                        'availability' => 'Available',
                        'barcode' => 'BC-'.$accession,
                        'rfid' => 'RFID-'.$accession,
                        'curriculum' => match ($section) {
                            'Filipiniana' => 'filipiniana',
                            'Reference' => 'general reference',
                            default => 'gen ed',
                        },
                    ]
                );
                $created++;
            }
        }

        return $created;
    }
}
