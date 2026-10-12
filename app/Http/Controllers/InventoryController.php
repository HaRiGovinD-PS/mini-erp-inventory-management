<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Product;

class InventoryController extends Controller
{
    public function index(): View
    {
        $products = Product::query()
            ->orderBy('name')
            ->paginate(15);

        return view('inventory.index', compact('products'));
    }
}
