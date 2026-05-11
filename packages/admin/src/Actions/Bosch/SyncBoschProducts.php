<?php

namespace Lunar\Admin\Actions\Bosch;

use Illuminate\Support\Facades\Http;
use Lunar\Helpers\CurrencyHelper;
use Lunar\Models\Currency;
use Lunar\Models\ProductVariant;
use SimpleXMLElement;
use Throwable;

class SyncBoschProducts
{
    /**
     * @return array{updated: int, in_feed: int}
     */
    public function __invoke(): array
    {
        $url = config('lunar.panel.bosch.api_url');
        $requestFrom = config('lunar.panel.bosch.request_from');
        $passcode = config('lunar.panel.bosch.passcode');

        if (empty($url) || $requestFrom === null || $requestFrom === '' || $passcode === null || $passcode === '') {
            throw new \RuntimeException('Bosch sync is not configured. Set BOSCH_API_URL, BOSCH_API_REQUESTFROM, and BOSCH_API_PASSCODE in your environment.');
        }

        $response = Http::timeout(300)
            ->accept('application/xml, text/xml, */*')
            ->asForm()
            ->withoutVerifying()
            ->post($url, [
                'RequestFrom' => $requestFrom,
                'RequestType' => 'Products',
                'Passcode' => $passcode,
                'RequestParameters' => 'PT',
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Bosch API request failed (HTTP '.$response->status().').');
        }

        $body = $response->body();
        if ($body === '') {
            throw new \RuntimeException('Bosch API returned an empty response.');
        }

        libxml_use_internal_errors(true);
        try {
            $xml = new SimpleXMLElement($body);
        } catch (Throwable $e) {
            throw new \RuntimeException('Could not parse Bosch XML response: '.$e->getMessage());
        }

        $products = $xml->xpath('//Product') ?: $xml->xpath('//product') ?: [];
        $updated = 0;

        $currency = Currency::getDefault();
        if (! $currency) {
            throw new \RuntimeException('No default currency is configured.');
        }

        foreach ($products as $product) {
            $article = isset($product->Article) ? trim((string) $product->Article) : '';
            if ($article === '') {
                continue;
            }

            $variant = ProductVariant::query()->where('sku', $article)->first();
            if (! $variant) {
                continue;
            }

            $balance = isset($product->AvailableBalance) ? trim((string) $product->AvailableBalance) : '';
            if ($balance !== '') {
                if (preg_match('/^\d+\+$/', $balance)) {
                    $variant->stock = 999;
                } elseif (is_numeric($balance)) {
                    $variant->stock = (int) round((float) $balance);
                }
            }

            $retailPrice = isset($product->RetailPrice) ? trim((string) $product->RetailPrice) : '';
            if ($retailPrice !== '' && is_numeric($retailPrice)) {
                $this->updateRetailPrice($variant, $currency, $retailPrice);
            }

            $variant->stock_zero_delay = null;
            $variant->saveOrFail();
            $updated++;
        }

        return [
            'updated' => $updated,
            'in_feed' => count($products),
        ];
    }

    private function updateRetailPrice(ProductVariant $variant, Currency $currency, string $rawPrice): void
    {
        $cleaned = CurrencyHelper::cleanup($rawPrice);
        if ($cleaned === null || $cleaned === '' || ! is_numeric($cleaned)) {
            return;
        }

        $minor = (int) bcmul((string) $cleaned, (string) $currency->factor, 0);
        $priceModel = $variant->prices()
            ->where('currency_id', $currency->id)
            ->where('min_quantity', 1)
            ->whereNull('customer_group_id')
            ->first();

        if ($priceModel) {
            $priceModel->update(['price' => $minor]);
        } else {
            $variant->prices()->create([
                'currency_id' => $currency->id,
                'min_quantity' => 1,
                'price' => $minor,
            ]);
        }
    }
}
