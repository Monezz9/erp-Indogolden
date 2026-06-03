<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_name',
        'store_address',
        'store_phone',
        'footer_text',
        'paper_size',
        'show_logo',
        'logo_path',
        'show_qris',
        'qris_image_path',
        'show_discount',
        'show_tax',
    ];

    protected $casts = [
        'show_logo' => 'boolean',
        'show_qris' => 'boolean',
        'show_discount' => 'boolean',
        'show_tax' => 'boolean',
    ];

    public static function current(): self
    {
        return self::query()->first() ?? new self([
            'store_name' => config('app.name', 'INDOGOLDEN ERP'),
            'footer_text' => 'Terima kasih',
            'paper_size' => '80mm',
            'show_logo' => false,
            'logo_path' => 'images/logo-indogolden.png',
            'show_qris' => false,
            'show_discount' => true,
            'show_tax' => true,
        ]);
    }
}
