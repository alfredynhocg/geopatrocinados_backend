<?php

namespace App\Shared\Kernel\Support;

use Illuminate\Support\Facades\DB;

final class SqlCompat
{
    public static function isPgsql(): bool
    {
        return DB::connection()->getDriverName() === 'pgsql';
    }

    public static function dateFormat(string $column, string $mysqlFormat): string
    {
        $map = ['%Y-%m' => 'YYYY-MM', '%Y' => 'YYYY'];

        return self::isPgsql()
            ? "TO_CHAR({$column}, '{$map[$mysqlFormat]}')"
            : "DATE_FORMAT({$column}, '{$mysqlFormat}')";
    }

    public static function castUnsignedInt(string $column): string
    {
        return self::isPgsql()
            ? "CAST({$column} AS INTEGER)"
            : "CAST({$column} AS UNSIGNED)";
    }

    public static function year(string $column): string
    {
        return self::isPgsql() ? "CAST(EXTRACT(YEAR FROM {$column}) AS INTEGER)" : "YEAR({$column})";
    }

    public static function hour(string $column): string
    {
        return self::isPgsql() ? "CAST(EXTRACT(HOUR FROM {$column}) AS INTEGER)" : "HOUR({$column})";
    }
}
