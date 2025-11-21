<?php

namespace App\Livewire;

use App\Helpers\CartManagement;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Product;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;


#[Title('Product Detail Page')]
class ProductDetailPage extends Component
{
    
    public $slug;
    public $quantity = 1;

    public function mount($slug)
    {
        $this->slug = $slug;
    }

    public function increaseQty()
    {
        $this->quantity++;
    }

    public function decreaseQty()
    {
        if ($this->quantity > 1) {
            $this->quantity--;
        }
    }

    public function addToCart()
    {
        $product = Product::where('slug', $this->slug)->firstOrFail();

        $total_count = CartManagement::addItemToCartWithQty($product->id, $this->quantity);

        $this->dispatch('update-cart-count', ['total_count' => $total_count]);

        // Now $this->alert() works because we included the trait
        LivewireAlert::flash('Success', 'Product added to cart!', [
            'position' => 'bottom-end',
            'timer' => 3000,
            'toast' => true,
            'text' => 'You can continue shopping or view your cart.'
        ]);
    }

    public function render()
    {
        return view('livewire.product-detail-page', [
            'product' => Product::where('slug', $this->slug)->firstOrFail(),
        ]);
    }
}
