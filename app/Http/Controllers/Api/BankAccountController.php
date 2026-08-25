<?php

namespace App\Http\Controllers\Api;

use App\Actions\BankAccountAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function store(Request $request, BankAccountAction $bankAccountAction): JsonResponse
    {
        $validated = $request->validate([
            'bank_code' => ['required', 'string'],
            'account_number' => ['required', 'string', 'size:10'],
        ]);

        $bankAccountAction->register($request->user(), $validated['bank_code'], $validated['account_number']);

        return response()->json([
            'message' => 'Bank account linked successfully.',
        ]);
    }
}
