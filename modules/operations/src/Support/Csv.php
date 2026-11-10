<?php

namespace Modules\Operations\Support;

final class Csv
{
    /**
     * @param  list<string>  $headers
     * @param  iterable<array<int, scalar|null>>  $rows
     */
    public static function build(array $headers, iterable $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headers, escape: '');

        foreach ($rows as $row) {
            fputcsv($handle, array_map(self::safe(...), $row), escape: '');
        }

        rewind($handle);

        return (string) stream_get_contents($handle);
    }

    /**
     * Stops spreadsheet formula injection from text typed by members.
     */
    private static function safe(string|int|float|bool|null $value): string|int|float|bool|null
    {
        return is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
