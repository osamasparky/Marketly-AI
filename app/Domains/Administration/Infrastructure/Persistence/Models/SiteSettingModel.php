<?php

namespace App\Domains\Administration\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSettingModel extends Model
{
    protected $table = 'site_settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
    ];

    /**
     * Get all settings as key-value associative array with cast types.
     */
    public static function getAllFormatted(): array
    {
        $records = static::all();
        $formatted = [];

        foreach ($records as $record) {
            $value = $record->value;
            if ($record->type === 'json' && !empty($value)) {
                $value = json_decode($value, true) ?? $value;
            } elseif ($record->type === 'boolean') {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            } elseif ($record->type === 'number') {
                $value = is_numeric($value) ? (float) $value : $value;
            }
            $formatted[$record->key] = $value;
        }

        return $formatted;
    }
}
