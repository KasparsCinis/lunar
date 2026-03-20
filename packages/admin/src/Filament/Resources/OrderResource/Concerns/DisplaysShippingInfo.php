<?php

namespace Lunar\Admin\Filament\Resources\OrderResource\Concerns;

use Filament\Infolists;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\Collection;

trait DisplaysShippingInfo
{
    public static function getShippingInfolist(): Infolists\Components\Section
    {
        return self::callStaticLunarHook('extendShippingInfolist', static::getDefaultShippingInfolist());
    }

    public static function getDefaultShippingInfolist(): Infolists\Components\Section
    {
        return Infolists\Components\Section::make()
            ->schema([
                Infolists\Components\RepeatableEntry::make('shippingLines')
                    ->hiddenLabel()
                    ->contained(false)
                    ->columns(2)
                    ->columnSpan(12)
                    ->schema([
                        Infolists\Components\TextEntry::make('description')
                            ->icon('heroicon-s-truck')
                            ->html()
                            ->iconPosition(IconPosition::Before)
                            ->hiddenLabel(),
                        Infolists\Components\TextEntry::make('sub_total')
                            ->hiddenLabel()
                            ->alignEnd()
                            ->formatStateUsing(fn ($state) => $state->formatted),
                        Infolists\Components\TextEntry::make('notes')
                            ->hidden(
                                fn ($state) => ! $state
                            )
                            ->placeholder(
                                __('lunarpanel::order.infolist.notes.placeholder')
                            ),
                    ]),
                Infolists\Components\TextEntry::make('venipak_collection_point')
                    ->label(__('lunarpanel::order.infolist.venipak_collection_point.label'))
                    ->icon('heroicon-o-map-pin')
                    ->iconPosition(IconPosition::Before)
                    ->columnSpanFull()
                    ->visible(
                        fn ($record) => strtoupper((string) $record->shippingAddress?->shipping_option) === 'VENIPACK'
                    )
                    ->getStateUsing(function ($record) {
                        $place = data_get($record->shippingAddress?->meta, 'venipak_parcel_place');

                        if ($place === null || $place === '') {
                            return null;
                        }

                        if (is_array($place)) {
                            return Collection::make($place)->filter()->implode(', ');
                        }

                        if (is_object($place)) {
                            $decoded = json_decode(json_encode($place), true);

                            return is_array($decoded)
                                ? Collection::make($decoded)->filter()->implode(', ')
                                : (string) $place;
                        }

                        return $place;
                    })
                    ->placeholder(
                        __('lunarpanel::order.infolist.venipak_collection_point.placeholder')
                    ),
            ]);
    }
}
