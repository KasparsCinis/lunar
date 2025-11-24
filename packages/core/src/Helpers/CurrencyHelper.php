<?php

namespace Lunar\Helpers;

use JsonSerializable;
use Lunar\Base\FieldType;
use Lunar\Exceptions\FieldTypeException;

class CurrencyHelper
{
    public  static function cleanup($price) {
        if ($price === null) {
            return null;
        }

        // Convert to string & trim
        $price = trim((string) $price);

        // Remove spaces
        $price = str_replace(' ', '', $price);

        // If it looks like EU format: 1.234,56 or 123,45
        if (preg_match('/^\d{1,3}(\.\d{3})*,\d+$/', $price) || preg_match('/^\d+,\d+$/', $price)) {
            // Remove thousand separators
            $price = str_replace('.', '', $price);
            // Replace decimal comma with dot
            $price = str_replace(',', '.', $price);
            return $price;
        }

        // If it looks like US format with commas as thousands: 1,234.56
        if (preg_match('/^\d{1,3}(,\d{3})*\.\d+$/', $price)) {
            // Remove thousand separators
            $price = str_replace(',', '', $price);
            return $price;
        }

        // If it contains a comma but is not matched above, assume comma = decimal separator
        if (strpos($price, ',') !== false) {
            $price = str_replace(',', '.', $price);
        }

        return $price;
    }
}
