<?php

namespace Bazar\Cart;

use Bazar\Contracts\Models\Cart;
use Bazar\Proxies\Cart as CartProxy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class CookieDriver extends Driver
{
    /**
     * Resolve the cart instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Bazar\Contracts\Models\Cart
     */
    protected function resolve(Request $request): Cart
    {
        $user = $request->user();

        $cart = CartProxy::query()
                    ->firstOrCreate(['id' => $request->cookie('cart_id')])
                    ->setRelation('user', $user)
                    ->loadMissing(['shipping', 'products', 'products.media', 'products.variants']);

        if ($user && $cart->user_id !== $user->id) {
            CartProxy::query()->where('user_id', $user->id)->delete();

            $cart->user()->associate($user)->save();
        }

        Cookie::queue('cart_id', $cart->id, $this->config['expiration'] ?? 4320);

        return $cart;
    }
}
