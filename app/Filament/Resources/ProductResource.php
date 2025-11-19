<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                    ->columns(12) // parent grid: 12 columns
                    ->schema([
                        // Left column: Product Info + Images
                        Group::make()
                            ->columnSpan(7) // left column slightly bigger
                            ->schema([
                                Section::make('Product Information')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('name')
                                            ->required()
                                            ->maxLength(255)
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                $set('slug', Str::slug($state));
                                            }),
                                        TextInput::make('slug')
                                            ->required()
                                            ->maxLength(255)
                                            ->disabled()
                                            ->dehydrated()
                                            ->unique(Product::class, 'slug', ignoreRecord: true),
                                        MarkdownEditor::make('description')
                                            ->fileAttachmentsDirectory('products')
                                            ->columnSpanFull(),
                                    ]),
                                Section::make('Images')
                                    ->schema([
                                        FileUpload::make('images')
                                            ->directory('products')
                                            ->multiple()
                                            ->maxFiles(5)
                                            ->image()
                                            ->reorderable(),
                                    ]),
                            ]),

                        // Right column: Price + Associations + Status (taller rectangle)
                        Group::make()
                            ->columnSpan(5) // right column wider
                            ->schema([
                                Section::make('Price & Associations')
                                    ->columns(1) // make sections full width
                                    ->schema([
                                        TextInput::make('price')
                                            ->required()
                                            ->numeric()
                                            ->prefix('INR')
                                            ->columnSpanFull(),
                                        Select::make('category_id')
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->relationship('category', 'name')
                                            ->columnSpanFull(),
                                        Select::make('brand_id')
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->relationship('brand', 'name')
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),

                                Section::make('Status')
                                    ->columns(1) // stack toggles vertically
                                    ->schema([
                                        Toggle::make('in_stock')
                                            ->required()
                                            ->default(true)
                                            ->columnSpanFull(),
                                        Toggle::make('is_active')
                                            ->required()
                                            ->columnSpanFull(),
                                        Toggle::make('is_featured')
                                            ->required()
                                            ->columnSpanFull(),
                                        Toggle::make('on_sale')
                                            ->required()
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),
                            ])->columns(2)
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('category.name')->sortable(),
                TextColumn::make('brand.name')->sortable(),
                TextColumn::make('price')->money('INR')->sortable(),
                IconColumn::make('is_featured')->boolean(),
                IconColumn::make('on_sale')->boolean(),
                IconColumn::make('in_stock')->boolean(),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')->relationship('category', 'name'),
                SelectFilter::make('brand')->relationship('brand', 'name'),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
