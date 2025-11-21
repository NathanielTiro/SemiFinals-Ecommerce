<?php

namespace App\Livewire;

use App\Helpers\CartManagement;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ProductsPage extends Component
{
    use WithPagination;

    #[Url]
    public $selected_categories = [];

    #[Url]
    public $selected_brands = [];

    public $featured = false;
    public $on_sale = false;
    public $price_range = 300000;

    #[Url]
    public $sort = 'latest';

    // Track the total cart count
    public $total_count = 0;

    // Initialize total_count on page load
    public function mount()
    {
        // Get cart from session, count items safely
        $this->total_count = is_array(session('cart')) ? count(session('cart')) : 0;
    }

    public function addToCart($product_id)
    {
        // Add item to cart and get updated total count
        $this->total_count = CartManagement::addItemToCart($product_id);

        // Optional: dispatch event if other components listen
        $this->dispatch('update-cart-count', ['total_count' => $this->total_count]);

        LivewireAlert::success()
            ->text('Product added to the cart successfully.')
            ->position('bottom-end')
            ->timer(3000)
            ->toast()
            ->show();
    }

    public function render()
    {
        $productQuery = Product::query()->where('is_active', 1);

        if (!empty($this->selected_categories)) {
            $productQuery->whereIn('category_id', $this->selected_categories);
        }

        if (!empty($this->selected_brands)) {
            $productQuery->whereIn('brand_id', $this->selected_brands);
        }

        if ($this->featured) {
            $productQuery->where('is_featured', 1);
        }

        if ($this->on_sale) {
            $productQuery->where('on_sale', 1);
        }

        if ($this->price_range) {
            $productQuery->whereBetween('price', [0, $this->price_range]);
        }

        if ($this->sort === 'latest') {
            $productQuery->latest();
        }

        if ($this->sort === 'price') {
            $productQuery->orderBy('price');
        }

        return view('livewire.products-page', [
            'products' => $productQuery->paginate(9),
            'brands' => Brand::where('is_active', 1)->get(['id', 'name', 'slug']),
            'categories' => Category::where('is_active', 1)->get(['id', 'name', 'slug']),
        ]);
    }
}
