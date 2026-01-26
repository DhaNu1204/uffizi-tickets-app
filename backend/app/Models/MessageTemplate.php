<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'channel',
        'subject',
        'content',
        'language',
        'language_name',
        'language_flag',
        'template_type',
        'is_default',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Template types
     */
    public const TYPE_TICKET_ONLY = 'ticket_only';
    public const TYPE_TICKET_WITH_AUDIO = 'ticket_with_audio';

    /**
     * Supported languages with flags (10+ languages)
     */
    public const LANGUAGES = [
        'en' => ['name' => 'English', 'flag' => '🇬🇧'],
        'it' => ['name' => 'Italian', 'flag' => '🇮🇹'],
        'es' => ['name' => 'Spanish', 'flag' => '🇪🇸'],
        'de' => ['name' => 'German', 'flag' => '🇩🇪'],
        'fr' => ['name' => 'French', 'flag' => '🇫🇷'],
        'ja' => ['name' => 'Japanese', 'flag' => '🇯🇵'],
        'el' => ['name' => 'Greek', 'flag' => '🇬🇷'],
        'tr' => ['name' => 'Turkish', 'flag' => '🇹🇷'],
        'ko' => ['name' => 'Korean', 'flag' => '🇰🇷'],
        'pt' => ['name' => 'Portuguese', 'flag' => '🇵🇹'],
        'ru' => ['name' => 'Russian', 'flag' => '🇷🇺'],
        'ar' => ['name' => 'Arabic', 'flag' => '🇸🇦'],
        'zh' => ['name' => 'Chinese', 'flag' => '🇨🇳'],
        'nl' => ['name' => 'Dutch', 'flag' => '🇳🇱'],
        'pl' => ['name' => 'Polish', 'flag' => '🇵🇱'],
    ];

    /**
     * Available channels
     */
    public const CHANNELS = ['whatsapp', 'sms', 'email'];

    /**
     * Template variable placeholders
     */
    public const VARIABLES = [
        'customer_name',
        'customer_email',
        'tour_date',
        'tour_time',
        'product_name',
        'pax',
        'reference_number',
        'audio_guide_url',
        'audio_guide_username',
        'audio_guide_password',
    ];

    /**
     * Get messages using this template
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'template_id');
    }

    /**
     * Get default template for a channel and language
     */
    public static function getDefault(string $channel, string $language = 'en'): ?self
    {
        return static::where('channel', $channel)
            ->where('language', $language)
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get template for a specific language and type
     */
    public static function getByLanguageAndType(string $language, string $templateType, string $channel = 'email'): ?self
    {
        return static::where('language', $language)
            ->where('template_type', $templateType)
            ->where('channel', $channel)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get all templates for a channel
     */
    public static function getByChannel(string $channel, ?string $language = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = static::where('channel', $channel)
            ->where('is_active', true);

        if ($language) {
            $query->where('language', $language);
        }

        return $query->orderBy('is_default', 'desc')
            ->orderBy('language')
            ->get();
    }

    /**
     * Get all available languages with templates
     */
    public static function getAvailableLanguages(): array
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('language_name')
            ->get()
            ->unique('language')
            ->map(function ($template) {
                return [
                    'code' => $template->language,
                    'name' => $template->language_name ?? self::LANGUAGES[$template->language]['name'] ?? $template->language,
                    'flag' => $template->language_flag ?? self::LANGUAGES[$template->language]['flag'] ?? '',
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Get all templates grouped by language and type
     */
    public static function getAllGrouped(): array
    {
        $templates = static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('language')
            ->get();

        $grouped = [];
        foreach ($templates as $template) {
            $lang = $template->language;
            $type = $template->template_type ?? 'ticket_only';

            if (!isset($grouped[$lang])) {
                $grouped[$lang] = [
                    'code' => $lang,
                    'name' => $template->language_name ?? self::LANGUAGES[$lang]['name'] ?? $lang,
                    'flag' => $template->language_flag ?? self::LANGUAGES[$lang]['flag'] ?? '',
                    'templates' => [],
                ];
            }

            $grouped[$lang]['templates'][$type] = [
                'id' => $template->id,
                'subject' => $template->subject,
                'content' => $template->content,
            ];
        }

        return array_values($grouped);
    }

    /**
     * Scope to filter by template type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('template_type', $type);
    }

    /**
     * Scope to order by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('language_name');
    }

    /**
     * Render template with variables
     */
    public function render(array $variables): string
    {
        $content = $this->content;

        foreach ($variables as $key => $value) {
            $content = str_replace('{' . $key . '}', $value ?? '', $content);
        }

        return $content;
    }

    /**
     * Render subject with variables (for email)
     */
    public function renderSubject(array $variables): ?string
    {
        if (!$this->subject) {
            return null;
        }

        $subject = $this->subject;

        foreach ($variables as $key => $value) {
            $subject = str_replace('{' . $key . '}', $value ?? '', $subject);
        }

        return $subject;
    }

    /**
     * Scope for active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for default templates
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
