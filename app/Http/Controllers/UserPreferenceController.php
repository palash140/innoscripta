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

        // Map input values - include null values to clear preferences
        $input = collect($mapped)
            ->filter(fn ($_, $key) => array_key_exists($key, $validated)) // Only include keys that were actually sent
            ->mapWithKeys(fn ($newKey, $oldKey) => [$newKey => $validated[$oldKey]]) // Include null values
            ->all();

        if(empty($input)  && empty($request->user()->preference)) {
            return [
                'data' => []
            ];
        }

        if (!empty($input)) {
            $user = $request->user();
            $user->preference()->updateOrCreate([], $input);
        }

        return new UserPrefrenceResource($request->user()->fresh()->preference);
    }

}
