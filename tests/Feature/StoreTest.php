<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function sellable(): Product
    {
        return Product::general()->purchasable()->where('stock_qty', '>=', 3)->firstOrFail();
    }

    private function cartPayload(Product $product, int $qty = 1): array
    {
        return ['product_id' => $product->id, 'qty' => $qty, 'colour' => $product->colours[0] ?? null, 'size' => $product->sizes[0] ?? null];
    }

    public function test_storefront_pages_load(): void
    {
        foreach (['/', '/shop', '/c/women', '/c/kids/age-groups/3-5-years', '/c/deals-trending/deals/under-rs-499', '/search?q=bag', '/cart', '/track-order', '/faqs', '/login'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get($this->sellable()->url)->assertOk()->assertSee('Add to Cart');
    }

    public function test_every_category_from_the_spec_has_a_page(): void
    {
        $this->assertSame(8, Category::where('depth', 0)->count());
        Category::where('is_restricted', false)->where('depth', '<', 2)->get()
            ->each(fn ($category) => $this->get($category->url)->assertOk());
    }

    public function test_private_section_is_age_gated_and_kept_out_of_the_general_catalogue(): void
    {
        $restricted = Product::where('is_restricted', true)->firstOrFail();

        $this->get('/c/women/private-intimate')->assertOk()->assertSee('Are you 18 years or older?')->assertDontSee($restricted->title);
        $this->get($restricted->url)->assertSee('Are you 18 years or older?');
        $this->get('/c/women')->assertDontSee($restricted->title);
        $this->get('/search?q='.urlencode($restricted->title))->assertDontSee($restricted->sku);

        $this->post('/age-confirm', ['answer' => 'yes', 'intended' => url('/c/women/private-intimate')])->assertRedirect('/c/women/private-intimate');
        $this->get('/c/women/private-intimate')->assertOk()->assertDontSee('Are you 18 years or older?');
    }

    public function test_product_with_unverified_supplier_stock_cannot_be_ordered(): void
    {
        $product = $this->sellable();
        $product->update(['stock_verified_at' => now()->subHours(config('store.stock_stale_hours') + 1)]);

        $this->assertSame('unconfirmed', $product->fresh()->availability);
        $this->get($product->url)->assertOk()->assertDontSee('Add to Cart')->assertSee('Availability being confirmed');
        $this->post('/cart', $this->cartPayload($product));
        $this->get('/cart')->assertSee('Your cart is empty');
    }

    public function test_guest_can_order_with_cash_on_delivery(): void
    {
        $product = $this->sellable();
        $stock = $product->stock_qty;

        $this->post('/cart', $this->cartPayload($product, 2))->assertRedirect();
        $this->post('/checkout', ['name' => 'Test Buyer', 'phone' => '+92 300 1112223', 'city' => 'Lahore', 'address' => 'House 1, Street 2, Model Town'])
            ->assertRedirect();

        $order = Order::latest('id')->first();
        $this->assertSame('pending', $order->status);
        $this->assertSame('03001112223', $order->phone);
        $this->assertSame($product->price * 2, $order->subtotal);
        $this->assertSame($stock - 2, $product->fresh()->stock_qty);

        $this->get('/order/'.$order->number)->assertOk()->assertSee('Your order is placed');
        $this->get('/track-order?number='.$order->number.'&phone=03001112223')->assertSee('Order status');
        $this->get('/track-order?number='.$order->number.'&phone=03009999999')->assertSee('could not find');

        // Cancelling returns the units to the virtual inventory.
        $order->transitionTo('cancelled');
        $this->assertSame($stock, $product->fresh()->stock_qty);
    }

    public function test_checkout_validates_phone_and_coupons(): void
    {
        $product = $this->sellable();
        $this->post('/cart', $this->cartPayload($product));

        $this->post('/checkout', ['name' => 'Test Buyer', 'phone' => '12345', 'city' => 'Lahore', 'address' => 'House 1, Street 2, Model Town'])
            ->assertSessionHasErrors('phone');
        $this->post('/cart/coupon', ['code' => 'NOPE'])->assertSessionHasErrors('code');
        $this->post('/cart/coupon', ['code' => 'EID25'])->assertSessionHasErrors('code'); // expired
        $this->assertSame(0, Order::where('name', 'Test Buyer')->count());
    }

    public function test_admin_is_limited_to_staff_and_to_each_roles_sections(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->actingAs(User::where('role', 'customer')->first())->get('/admin')->assertForbidden();

        $desk = User::where('role', 'orders')->first();
        $this->actingAs($desk)->get('/admin/orders')->assertOk();
        $this->actingAs($desk)->get('/admin/products')->assertForbidden();
        $this->actingAs($desk)->get('/admin/staff')->assertForbidden();

        $owner = User::where('role', 'owner')->first();
        foreach (['', '/orders', '/customers', '/returns', '/products', '/products/create', '/categories', '/suppliers', '/stock', '/pricing', '/coupons', '/reviews', '/banners', '/reports', '/staff'] as $path) {
            $this->actingAs($owner)->get('/admin'.$path)->assertOk();
        }
    }

    public function test_order_must_be_confirmed_by_phone_or_whatsapp_before_it_moves_on(): void
    {
        $owner = User::where('role', 'owner')->first();
        $order = Order::where('status', 'pending')->firstOrFail();
        $order->update(['confirmed_via' => null]);

        $this->actingAs($owner)->patch('/admin/orders/'.$order->id, ['status' => 'confirmed'])->assertSessionHasErrors('confirmed_via');
        $this->assertSame('pending', $order->fresh()->status);

        $this->actingAs($owner)->patch('/admin/orders/'.$order->id, ['status' => 'confirmed', 'confirmed_via' => 'whatsapp'])->assertSessionHasNoErrors();
        $this->assertSame('confirmed', $order->fresh()->status);

        // Skipping steps is rejected.
        $this->actingAs($owner)->patch('/admin/orders/'.$order->id, ['status' => 'delivered'])->assertSessionHasErrors('status');
    }
}
