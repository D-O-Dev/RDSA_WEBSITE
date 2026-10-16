<?php

use App\Http\Controllers\ParticipationController;
use App\Models\Edition;
use App\Models\WaveNumber;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/', function () {
    $edition = Edition::query()
        ->where('is_active', true)
        ->orderByDesc('starts_at')
        ->first();

    $previousEditions = Edition::query()
        ->where('is_active', false)
        ->orderByDesc('starts_at')
        ->get()
        ->map(fn ($item) => [
            'id' => $item->id,
            'name' => $item->name,
            'date' => $item->starts_at
                ? $item->starts_at->locale('fr')->translatedFormat('d F Y')
                : '',
        ])
        ->values();

    $waveNumbers = WaveNumber::query()
        ->where('is_active', true)
        ->get()
        ->map(fn ($wave) => [
            'id' => $wave->id,
            'label' => $wave->label,
            'number' => $wave->phone,
            'active' => true,
        ])
        ->values();

    $submissionToken = (string) Str::uuid();

    session([
        'participation_submission_token' => $submissionToken,
    ]);

    $participationConfig = [
        'edition' => [
            'id' => $edition?->id,
            'name' => $edition?->name ?? 'Aucune édition disponible',
            'date' => $edition?->starts_at
                ? $edition->starts_at->locale('fr')->translatedFormat('l d F Y')
                : 'Date à confirmer',
        ],

        'previousEditions' => $previousEditions,

        'pricing' => [
            'basePrice' => $edition?->base_price ?? 0,
            'allowAdditionalContribution' => true,
        ],

        'waveNumbers' => $waveNumbers,

        'payment' => [
            'maxProofSize' => 5 * 1024 * 1024,
            'allowedProofTypes' => [
                'image/jpeg',
                'image/png',
                'image/webp',
            ],
        ],
    ];

    return view('home', compact(
        'participationConfig',
        'submissionToken'
    ));
})->name('home');

Route::post('/participations', [
    ParticipationController::class,
    'store',
])->middleware('throttle:10,1')->name('participations.store');

Route::get('/participations/confirmation/{reference}', [
    ParticipationController::class,
    'confirmation',
])->name('participations.confirmation');