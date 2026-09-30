<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:150']]);
        $query = Service::where('is_active', true);
        if ($q = trim($data['q'] ?? '')) {
            $query->where('name', 'like', '%'.$q.'%');
        }

        return view('catalog', ['services' => $query->orderBy('name')->paginate(9)->withQueryString(), 'units' => MasterDataController::UNITS]);
    }
}
