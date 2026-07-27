<?php

namespace Lunar\Admin\Support\Synthesizers;

use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Language;

class TranslatedTextSynth extends AbstractFieldSynth
{
    public static $key = 'lunar_translatedtext_field';

    protected static $targetClass = TranslatedText::class;

    public function dehydrate($target)
    {
        $languages = Language::orderBy('default', 'desc')->get();

        return [
            $languages->mapWithKeys(
                fn ($language) => [
                    $language->code => new Text(
                        (string) $target->getValueForLocale($language->code)
                    ),
                ]
            )->toArray(),
            [],
        ];
    }

    public function hydrate($value)
    {
        $instance = new static::$targetClass;
        $instance->setValue(collect($value));

        return $instance;
    }

    public function get(&$target, $key)
    {
        return $target->getValueForLocale($key)?->getValue() ?? '';
    }

    public function set(&$target, $key, $value)
    {
        $collectionValue = $target->getValue();
        $field = $target->getValueForLocale($key);

        if (! $field instanceof Text) {
            $field = new Text;
        }

        $field->setValue($value);

        // Drop any case-variant of this locale before writing the canonical key.
        foreach ($collectionValue->keys() as $existingKey) {
            if (strcasecmp((string) $existingKey, (string) $key) === 0) {
                $collectionValue->forget($existingKey);
            }
        }

        $collectionValue->put($key, $field);

        $target->setValue($collectionValue);
    }
}
