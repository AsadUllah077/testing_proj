<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceTermCondition extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'title',
        'content',
        'is_default',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCompanyOrGlobal($query, ?int $companyId)
    {
        return $query->where(function ($subQuery) use ($companyId) {
            $subQuery->whereNull('company_id');

            if ($companyId) {
                $subQuery->orWhere('company_id', $companyId);
            }
        });
    }

    public static function defaultForCompany(?int $companyId): ?self
    {
        $companyTerm = null;

        if ($companyId) {
            $companyTerm = static::where('company_id', $companyId)
                ->active()
                ->orderByDesc('is_default')
                ->orderBy('sort_order')
                ->orderByDesc('id')
                ->first();
        }

        return $companyTerm ?: static::whereNull('company_id')
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->first();
    }

    public static function sanitizeContent(?string $content, bool $plainTextAsList = true): string
    {
        $content = trim((string) $content);

        if ($content === '') {
            return '';
        }

        if ($content === strip_tags($content)) {
            $items = array_filter(
                array_map('trim', preg_split('/\r\n|\r|\n/', $content)),
                fn ($line) => $line !== ''
            );

            if (empty($items)) {
                return '';
            }

            if (! $plainTextAsList) {
                return nl2br(htmlspecialchars(implode(PHP_EOL, $items), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
            }

            return '<ol><li>' . implode('</li><li>', array_map(
                fn ($line) => static::formatPlainTextTermLine($line),
                $items
            )) . '</li></ol>';
        }

        $content = strip_tags($content, '<p><br><strong><b><em><i><u><ol><ul><li><div>');
        $content = preg_replace('/<([a-z][a-z0-9]*)(?:\s[^>]*)?>/i', '<$1>', $content);

        if (trim(str_replace('&nbsp;', ' ', strip_tags($content))) === '') {
            return '';
        }

        return trim($content);
    }

    private static function formatPlainTextTermLine(string $line): string
    {
        $line = preg_replace('/^\s*(?:\d+[\.\)]|[-*])\s*/', '', trim($line));
        $line = htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $line = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $line);
        $line = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/', '<em>$1</em>', $line);

        return $line;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
