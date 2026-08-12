<?php

namespace App\Http\Controllers;

use App\Models\ApiToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ApiTokenController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $tokens = ApiToken::where('organization_id', $user->organization_id)->orderByDesc('id')->get();
        return view('orgadmin.tokens', compact('tokens'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = Auth::user();
        $rawToken = 'wms_token_' . Str::random(32);

        ApiToken::create([
            'organization_id' => $user->organization_id,
            'name' => $request->input('name'),
            'token' => $rawToken,
            'status' => 'active',
        ]);

        return redirect()->back()->with('success', "API Token '{$request->input('name')}' created successfully. Token Key: {$rawToken}");
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $token = ApiToken::where('organization_id', $user->organization_id)->findOrFail($id);
        $token->delete();

        return redirect()->back()->with('success', 'API Token revoked successfully.');
    }
}
