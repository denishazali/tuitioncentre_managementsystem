<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PublicFeedbackController extends Controller
{
    /**
     * Store feedback submitted through the public page.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:30',
                'regex:/^[0-9+\-\s()]+$/',
            ],
            'message' => [
                'required',
                'string',
                'min:10',
                'max:2000',
            ],

            // Hidden anti-spam field. Real users leave it empty.
            'website' => [
                'nullable',
                'prohibited',
            ],
        ]);

        Feedback::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'message' => $validated['message'],
            'status' => 'new',
        ]);

        return to_route('public.feedback')
            ->with(
                'success',
                'Thank you. Your feedback has been submitted for review.'
            );
    }
}