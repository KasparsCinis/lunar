<?php

namespace Lunar\FieldTypes;

use Illuminate\Support\Collection;
use JsonSerializable;
use Lunar\Base\FieldType;
use Lunar\Exceptions\FieldTypeException;
use Lunar\Models\Language;

class TranslatedText implements FieldType, JsonSerializable
{
    /**
     * @var Collection
     */
    protected $value;

    /**
     * Create a new instance of TranslatedText field type.
     *
     * @param  Collection  $value
     */
    public function __construct($value = null)
    {
        if ($value) {
            $this->setValue($value);
        } else {
            $this->value = new Collection;
        }
    }

    /**
     * Serialize the class.
     *
     * @return Collection
     */
    public function jsonSerialize(): mixed
    {
        return $this->value;
    }

    /**
     * Return the value of this field.
     *
     * @return Collection
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get a locale value, matching language codes case-insensitively.
     */
    public function getValueForLocale(string $locale): mixed
    {
        $matchedKey = $this->findLocaleKey($this->value ?? collect(), $locale);

        if ($matchedKey === null) {
            return null;
        }

        return $this->value->get($matchedKey);
    }

    /**
     * Set the value of this field.
     *
     * @param  Collection  $value
     */
    public function setValue($value)
    {
        if (is_array($value)) {
            $value = collect($value);
        }

        if (! $value instanceof Collection) {
            throw new FieldTypeException(self::class.' value must be a collection.');
        }

        foreach ($value as $key => $item) {
            if (is_string($item) || is_numeric($item) || is_bool($item)) {
                $item = new Text($item);
                $value[$key] = $item;
            }
            if ($item && (get_class($item) !== Text::class)) {
                throw new FieldTypeException(self::class.' only supports '.Text::class.' field types.');
            }
        }

        $this->value = $this->normalizeLocaleKeys($value);
    }

    /**
     * Remap translation keys onto configured language codes (e.g. LV → lv).
     */
    protected function normalizeLocaleKeys(Collection $value): Collection
    {
        try {
            $codes = Language::query()->pluck('code');
        } catch (\Throwable) {
            return $value;
        }

        if ($codes->isEmpty()) {
            return $value;
        }

        $normalized = collect();

        foreach ($codes as $code) {
            $matchedKey = $this->findLocaleKey($value, (string) $code);

            if ($matchedKey !== null) {
                $normalized->put($code, $value->get($matchedKey));
            }
        }

        return $normalized;
    }

    /**
     * Prefer an exact locale key, otherwise a case-insensitive match.
     */
    protected function findLocaleKey(Collection $value, string $locale): int|string|null
    {
        $exact = $value->keys()->first(
            fn ($key) => (string) $key === $locale
        );

        if ($exact !== null) {
            return $exact;
        }

        return $value->keys()->first(
            fn ($key) => strcasecmp((string) $key, $locale) === 0
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getConfig(): array
    {
        return [
            'options' => [
                'richtext' => 'nullable',
                'options' => [
                    'nullable',
                    function ($attribute, $value, $fail) {
                        if (! json_decode($value, true)) {
                            $fail('Must be valid json');
                        }
                    },
                ],
            ],
        ];
    }
}
