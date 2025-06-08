<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserPrefrenceResource;
use Illuminate\Http\Request;

class UserPreferenceController extends Controller
{
    public function show(Request $request)
    {
        $preference = $request->user()?->preference;

        if(empty($preference)) {
            return [
                'data' => []
            ];
        };

        return new UserPrefrenceResource($preference);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'author_id'   => 'nullable|exists:news_authors,id',
            'source_id'   => 'nullable|exists:news_sources,id',
            'category_id' => 'nullable|exists:news_categories,id',
        ]);

        $mapped = [
            'author_id'   => 'news_author_id',
            'source_id'   => 'news_source_id',
            'category_id' => 'news_category_id',
        ];

        // Filter and map input values
        $input = collect($mapped)
            ->filter(fn ($_, $key) => filled($validated[$key] ?? null))
            ->mapWithKeys(fn ($newKey, $oldKey) => [$newKey => $validated[$oldKey]])
            ->all();

        if (!empty($input)) {
            $user = $request->user();

            $user->preference()->updateOrCreate([], $input);
        }

        return new UserPrefrenceResource($request->user()->preference);
    }

}
