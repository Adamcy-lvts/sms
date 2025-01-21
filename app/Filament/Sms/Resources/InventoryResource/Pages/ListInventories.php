<?php

namespace App\Filament\Sms\Resources\InventoryResource\Pages;

use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Sms\Resources\InventoryResource;

class ListInventories extends ListRecords
{
    protected static string $resource = InventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Items')
                ->badge($this->getModel()::count()),

            'low_stock' => Tab::make('Low Stock')
                ->badge($this->getModel()::whereRaw('quantity <= reorder_level')->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->whereRaw('quantity <= reorder_level')
                ),

            'active' => Tab::make('Active')
                ->badge($this->getModel()::where('is_active', true)->count())
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->where('is_active', true)
                ),

            'inactive' => Tab::make('Inactive')
                ->badge($this->getModel()::where('is_active', false)->count())
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->where('is_active', false)
                ),
        ];
    }
}
