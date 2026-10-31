<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Insurer;
use App\Models\Policy;
use Illuminate\Http\Request;

class PolicyController extends Controller
{
    public function index()
    {
        $policies = Policy::with(['client', 'insurer'])->latest()->paginate(10)->withQueryString();
        $clients = Client::all();
        $insurers = Insurer::where('is_active', true)->get();

        return view('pages.policies.index', compact('policies', 'clients', 'insurers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'policy_number' => 'required|string|max:255|unique:policies',
            'insurer_id' => 'required|exists:insurers,id',
            'cover_type' => 'nullable|string|max:255',
            'sum_assured' => 'nullable|numeric|min:0',
            'annual_premium' => 'nullable|numeric|min:0',
            'renewal_date' => 'nullable|date',
            'status' => 'nullable|in:Active,Inactive,Cancelled',
        ]);

        if (! isset($validated['status'])) {
            $validated['status'] = 'Active';
        }

        Policy::create($validated);

        return redirect()->route('policies.index')->with('success', 'Policy issued successfully!');
    }
}
