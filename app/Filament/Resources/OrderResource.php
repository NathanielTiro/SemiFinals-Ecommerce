<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers\AddressRelationManager;
use App\Models\Order;
use App\Models\Product;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

     protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Group::make()
                ->columnSpanFull()
                ->schema([

                    // -----------------------------
                    // ORDER INFORMATION
                    // -----------------------------
                    Section::make('Order Information')
                        ->columns(2)
                        ->columnSpanFull()
                        ->schema([
                            Select::make('user_id')
                                ->label('Customer')
                                ->relationship('user', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),

                            Select::make('payment_method')
                                ->options([
                                    'stripe' => 'Stripe',
                                    'cod' => 'Cash on Delivery',
                                ])
                                ->required(),

                            Select::make('payment_status')
                                ->options([
                                    'pending' => 'Pending',
                                    'paid' => 'Paid',
                                    'failed' => 'Failed',
                                ])
                                ->default('pending')
                                ->required(),

                            ToggleButtons::make('status')
                                ->inline()
                                ->default('new')
                                ->required()
                                ->options([
                                    'new' => 'New',
                                    'processing' => 'Processing',
                                    'shipped' => 'Shipped',
                                    'delivered' => 'Delivered',
                                    'cancelled' => 'Cancelled',
                                ])
                                ->colors([
                                    'new' => 'info',
                                    'processing' => 'warning',
                                    'shipped' => 'success',
                                    'delivered' => 'success',
                                    'cancelled' => 'danger',
                                ])
                                ->icons([
                                    'new' => 'heroicon-m-sparkles',
                                    'processing' => 'heroicon-m-arrow-path',
                                    'shipped' => 'heroicon-m-truck',
                                    'delivered' => 'heroicon-m-check-badge',
                                    'cancelled' => 'heroicon-m-x-circle',
                                ]),

                            Select::make('currency')
                                ->options([
                                    'usd' => 'USD',
                                    'eur' => 'EUR',
                                    'inr' => 'INR',
                                    'gbp' => 'GBP',
                                ])
                                ->default('inr')
                                ->required(),

                            Select::make('shipping_method')
                                ->options([
                                    'fedex' => 'FedEx',
                                    'ups' => 'UPS',
                                    'dhl' => 'DHL',
                                    'usps' => 'USPS',
                                ]),

                            Textarea::make('notes')->columnSpanFull(),
                        ]),

                    // -----------------------------
                    // ORDER ITEMS
                    // -----------------------------
                    Section::make('Order Items')
                        ->columnSpanFull()
                        ->schema([
                            Repeater::make('items')
                                ->relationship('items')
                                ->columns(12)
                                ->schema([
                                    Select::make('product_id')
                                        ->label('Product')
                                        ->relationship('product', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->distinct()
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                        ->columnSpan(4)
                                        ->reactive()
                                        ->afterStateHydrated(function ($state, Get $get, Set $set) {
                                            $product = Product::find($state);
                                            if ($get('unit_amount') === null) {
                                                $set('unit_amount', $product?->price ?? 0);
                                            }
                                            if ($get('total_amount') === null) {
                                                $quantity = $get('quantity') ?? 1;
                                                $set('total_amount', ($product?->price ?? 0) * $quantity);
                                            }
                                        })
                                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                            $product = Product::find($state);
                                            $quantity = $get('quantity') ?? 1;
                                            $set('unit_amount', $product?->price ?? 0);
                                            $set('total_amount', ($product?->price ?? 0) * $quantity);
                                        }),

                                    TextInput::make('quantity')
                                        ->numeric()
                                        ->required()
                                        ->default(1)
                                        ->minValue(1)
                                        ->columnSpan(2)
                                        ->reactive()
                                        ->afterStateUpdated(fn ($state, Get $get, Set $set) =>
                                            $set('total_amount', $state * $get('unit_amount'))
                                        ),

                                    TextInput::make('unit_amount')
                                        ->numeric()
                                        ->disabled()
                                        ->dehydrated()
                                        ->columnSpan(3)
                                        ->default(fn ($get, $state) => $state ?? 0),

                                    TextInput::make('total_amount')
                                        ->numeric()
                                        ->dehydrated()
                                        ->columnSpan(3)
                                        ->default(fn ($get, $state) => $state ?? ($get('quantity') * $get('unit_amount'))),
                                ]),

                            Placeholder::make('grand_total_placeholder')
                                ->label('Grand Total')
                                ->content(function (Get $get) {
                                    $total = 0;
                                    $items = $get('items') ?? [];
                                    foreach ($items as $key => $item) {
                                        $total += $get("items.{$key}.total_amount") ?? 0;
                                    }
                                    return '₹ ' . number_format($total, 2);
                                }),

                            Hidden::make('grand_total')
                                ->dehydrated()
                                ->columnSpanFull()
                                ->default(0)
                                ->afterStateHydrated(function (Get $get, Set $set) {
                                    $items = $get('items') ?? [];
                                    $total = 0;
                                    foreach ($items as $item) {
                                        $total += $item['total_amount'] ?? 0;
                                    }
                                    $set('grand_total', $total);
                                }),
                        ])->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('grand_total')
                    ->numeric()
                    ->money('INR')
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('payment_status')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('currency')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('shipping_method')
                    ->searchable()
                    ->sortable(),

                SelectColumn::make('status')
                    ->options([
                        'new' => 'New',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ])
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                DeleteBulkAction::make(),
            ])
            ]);
    }

    public static function getRelations(): array
{
        return [
            AddressRelationManager::class,
       
    ];
}

public static function getNavigationBadge(): ?string
{
    return static::getModel()::count();
}

public static function getNavigationBadgeColor(): string|array|null
{
    return static::getModel()::count() > 10 ? 'success' : 'danger';
}


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
