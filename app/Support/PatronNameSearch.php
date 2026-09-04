<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class PatronNameSearch
{
    /**
     * Apply patron search, including "Lastname, Firstname" and "Firstname Lastname".
     *
     * @param  list<string>  $extraColumns
     */
    public static function apply(Builder $query, string $search, array $extraColumns = []): void
    {
        $search = trim($search);

        if ($search === '') {
            return;
        }

        $query->where(function (Builder $q) use ($search, $extraColumns) {
            if (str_contains($search, ',')) {
                [$lastname, $firstname] = array_pad(
                    array_map('trim', explode(',', $search, 2)),
                    2,
                    ''
                );

                $q->where(function (Builder $nameQuery) use ($lastname, $firstname) {
                    if ($lastname !== '') {
                        $nameQuery->where('lastname', 'like', "%{$lastname}%");
                    }

                    if ($firstname !== '') {
                        $nameQuery->where('firstname', 'like', "%{$firstname}%");
                    }
                });
            } else {
                $parts = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [];

                if (count($parts) >= 2) {
                    $firstname = $parts[0];
                    $lastname = $parts[array_key_last($parts)];

                    $q->where(function (Builder $nameQuery) use ($search, $firstname, $lastname) {
                        $nameQuery->where(function (Builder $ordered) use ($firstname, $lastname) {
                            $ordered->where('firstname', 'like', "%{$firstname}%")
                                ->where('lastname', 'like', "%{$lastname}%");
                        })->orWhere(function (Builder $reversed) use ($firstname, $lastname) {
                            $reversed->where('lastname', 'like', "%{$firstname}%")
                                ->where('firstname', 'like', "%{$lastname}%");
                        })->orWhere('firstname', 'like', "%{$search}%")
                            ->orWhere('lastname', 'like', "%{$search}%");
                    });
                } else {
                    $q->where(function (Builder $nameQuery) use ($search) {
                        $nameQuery->where('firstname', 'like', "%{$search}%")
                            ->orWhere('lastname', 'like', "%{$search}%");
                    });
                }
            }

            foreach ($extraColumns as $column) {
                $q->orWhere($column, 'like', "%{$search}%");
            }
        });
    }
}
