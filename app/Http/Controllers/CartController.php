<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Product;
use App\Services\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CartController extends Controller
{
    public function __construct(private Cart $cart) {}

    public function index()
    {
        return view('store.cart', [
            'lines' => $this->cart->lines(),
            'totals' => $this->cart->totals(),
            'suggested' => Product::general()->purchasable()->where('price', '<=', 999)
                ->whereNotIn('id', $this->cart->lines()->pluck('product.id'))->orderByDesc('sold_count')->limit(5)->get(),
        ]);
    }

    public function add(Request $request)
    {
        $product = Product::listed()->findOrFail($request->integer('product_id'));
        $data = $request->validate([
            'qty' => ['nullable', 'integer', 'between:1,'.Cart::MAX_PER_LINE],
            'colour' => [Rule::requiredIf((bool) $product->colours), 'nullable', Rule::in($product->colours ?? [])],
            'size' => [Rule::requiredIf((bool) $product->sizes), 'nullable', Rule::in($product->sizes ?? [])],
        ], ['colour.required' => 'Please choose a colour.', 'size.required' => 'Please choose a size.']);

        if (! $product->isPurchasable()) {
            return back()->with('toast_error', 'Sorry, this product cannot be ordered right now.');
        }

        $this->cart->add($product, (int) ($data['qty'] ?? 1), $data['colour'] ?? null, $data['size'] ?? null);

        if ($request->boolean('buy_now')) {
            return redirect()->route('checkout');
        }

        return back()->with('toast', Str::limit($product->title, 40).' added to your cart.')->with('toast_action', ['View cart', route('cart')]);
    }

    /** "Frequently bought together" — adds several products with their first available options. */
    public function addMany(Request $request)
    {
        $ids = array_slice(array_map('intval', (array) $request->input('ids', [])), 0, 6);
        $products = Product::listed()->purchasable()->whereIn('id', $ids)->get();
        foreach ($products as $product) {
            $main = $product->id === $request->integer('product_id');
            $this->cart->add($product, 1,
                $main && $request->filled('colour') && in_array($request->input('colour'), $product->colours ?? [], true) ? $request->input('colour') : ($product->colours[0] ?? null),
                $main && $request->filled('size') && in_array($request->input('size'), $product->sizes ?? [], true) ? $request->input('size') : ($product->sizes[0] ?? null));
        }

        return redirect()->route('cart')->with('toast', $products->count().' items added to your cart. You can change sizes or colours from the product page.');
    }

    public function update(Request $request, string $key)
    {
        $this->cart->update($key, $request->integer('qty'));

        return back();
    }

    public function remove(string $key)
    {
        $this->cart->remove($key);

        return back()->with('toast', 'Item removed from your cart.');
    }

    public function applyCoupon(Request $request)
    {
        $code = Str::upper(trim((string) $request->input('code')));
        $coupon = Coupon::where('code', $code)->first();
        $problem = $coupon ? $coupon->problemFor($this->cart->subtotal()) : 'We could not find that coupon code.';
        if ($code === '' || $problem) {
            return back()->withErrors(['code' => $code === '' ? 'Enter a coupon code.' : $problem])->withInput();
        }
        session(['coupon' => $coupon->code]);

        return back()->with('toast', "Coupon {$coupon->code} applied — {$coupon->summary}.");
    }

    public function removeCoupon()
    {
        session()->forget('coupon');

        return back();
    }
}
