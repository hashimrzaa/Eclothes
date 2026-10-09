<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;

class HomeController extends Controller
{
    /** Section order follows spec §15 "Recommended Homepage Layout". */
    public function __invoke()
    {
        $visible = Category::navTree()->pluck('slug');
        // Only products that can be ordered right now are promoted on the homepage.
        $pool = fn () => Product::general()->purchasable()->whereIn('department', $visible);
        $picks = fn (string $dept) => $visible->contains($dept)
            ? Product::general()->purchasable()->where('department', $dept)->orderByDesc('is_winner')->orderByDesc('sold_count')->limit(10)->get()
            : collect();

        $rails = [
            ['Trending Products', 'What shoppers are picking this week', 'deals-trending/discovery/trending-products', $pool()->where('is_winner', true)->orderByDesc('sold_count')->limit(10)->get()],
            ['Best Sellers', 'Our most ordered products', 'deals-trending/discovery/best-sellers', $pool()->orderByDesc('sold_count')->limit(10)->get()],
            ["Women's Picks", 'Suits, kurtis, bags and more', 'women', $picks('women')],
            ["Men's Picks", 'Everyday essentials and layers', 'men', $picks('men')],
            ["Kids' Picks", 'Comfortable outfits from 0 to 16 years', 'kids', $picks('kids')],
            ['Home & Kitchen', 'Organisers and gadgets for a tidy home', 'home-kitchen', $picks('home-kitchen')],
            ['New Arrivals', 'Just added to the store', 'deals-trending/discovery/new-arrivals', $pool()->latest()->limit(10)->get()],
            ['Bundle Deals', 'Packs and sets that save you more', 'deals-trending/deals/bundle-deals', $pool()->where('is_bundle', true)->orderByDesc('sold_count')->limit(10)->get()],
        ];

        return view('store.home', [
            // Five in-stock winners float in the hero's 3D deck.
            'deck' => $pool()->whereDoesntHave('category', fn ($c) => $c->where('path', 'like', '%innerwear%'))->orderByDesc('is_winner')->orderByDesc('sold_count')->limit(40)->get()->unique('department')->take(5)->values(),
            'deptPhotos' => Category::navTree()->where('kind', 'normal')->mapWithKeys(fn ($dept) => [
                $dept->slug => Product::general()->purchasable()->where('department', $dept->slug)->orderByDesc('sold_count')->limit(12)->get()
                    ->map(fn ($p) => $p->image_urls[0] ?? null)->filter()->first(),
            ]),
            'avgRating' => number_format((float) Review::where('status', 'approved')->avg('rating'), 1),
            'reviewCount' => Review::where('status', 'approved')->count(),
            'productCount' => (int) floor(Product::general()->count() / 10) * 10,
            'heroes' => Banner::where('placement', 'hero')->where('is_active', true)->orderBy('position')->get(),
            'departments' => Category::navTree(),
            'rails' => array_values(array_filter($rails, fn ($rail) => $rail[3]->isNotEmpty())),
            'reviews' => Review::where('status', 'approved')->where('rating', '>=', 4)
                ->whereHas('product', fn ($p) => $p->general())->with('product')->latest()->limit(6)->get(),
        ]);
    }
}
