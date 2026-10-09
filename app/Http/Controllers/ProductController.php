<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function show(Request $request, string $slug)
    {
        $product = Product::listed()->where('slug', $slug)->with(['category.parent.parent', 'supplier'])->firstOrFail();

        if ($product->is_restricted && ! session('age_ok')) {
            return view('store.age-gate', ['category' => $product->category->parent, 'intended' => $request->fullUrl()]);
        }

        // Related and "bought together" never mix the restricted section with the general catalogue.
        $scope = fn () => Product::listed()->where('is_restricted', $product->is_restricted)->whereKeyNot($product->id);

        $related = $scope()->where('category_id', $product->category_id)->orderByDesc('sold_count')->limit(10)->get();
        if ($related->count() < 5) {
            $related = $related->concat(
                $scope()->where('department', $product->department)->whereNotIn('id', $related->pluck('id'))
                    ->orderByDesc('sold_count')->limit(10 - $related->count())->get()
            );
        }

        $together = $product->isPurchasable()
            ? $scope()->purchasable()->where('department', $product->department)->where('category_id', '!=', $product->category_id)
                ->orderByRaw('ABS(CAST(price AS SIGNED) - ?)', [$product->price])->limit(2)->get()
            : collect();

        $recentIds = collect(session('recent', []))->reject(fn ($id) => $id === $product->id)->take(10);
        $recent = Product::listed()->where('is_restricted', false)->whereIn('id', $recentIds)->get()
            ->sortBy(fn ($p) => $recentIds->search($p->id))->values();
        if (! $product->is_restricted) {
            session(['recent' => $recentIds->prepend($product->id)->take(12)->values()->all()]);
        }

        return view('store.product', [
            'product' => $product,
            'trail' => $product->category->trail(),
            'reviews' => $product->reviews()->where('status', 'approved')->latest()->limit(12)->get(),
            'breakdown' => $product->reviews()->where('status', 'approved')->selectRaw('rating, COUNT(*) c')->groupBy('rating')->pluck('c', 'rating'),
            'related' => $related,
            'together' => $together,
            'recent' => $recent,
            'inWishlist' => in_array($product->id, session('wishlist', []), true),
        ]);
    }

    public function review(Request $request, Product $product)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'city' => ['nullable', 'string', 'max:60'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'body' => ['required', 'string', 'min:10', 'max:1000'],
        ], [], ['body' => 'review']);

        $product->reviews()->create($data + ['user_id' => $request->user()?->id, 'status' => 'pending']);

        return back()->with('toast', 'Thank you. Your review will appear once our team approves it.');
    }
}
