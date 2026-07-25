<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $guarded = [];

    protected $casts = ['is_encrypted' => 'boolean'];

    public static function get(string $key, $default = null)
    {
        $row = static::query()->where('key', $key)->first();
        if (! $row) {
            return $default;
        }
        $value = $row->value;
        if ($row->is_encrypted && $value) {
            try {
                $value = Crypt::decryptString($value);
            } catch (\Throwable $e) {
                return $default;
            }
        }

        return $value;
    }

    public static function put(string $key, $value, string $group = 'general', bool $encrypt = false): void
    {
        if ($encrypt && $value !== null && $value !== '') {
            $value = Crypt::encryptString($value);
        }
        static::query()->updateOrCreate(['key' => $key], [
            'value' => $value, 'group' => $group, 'is_encrypted' => $encrypt,
        ]);
    }
}
