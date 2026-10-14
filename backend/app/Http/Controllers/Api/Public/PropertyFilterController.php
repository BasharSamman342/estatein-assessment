<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\PropertyFilterRequest;
use App\Models\Property;
use Illuminate\Http\JsonResponse;

class PropertyFilterController extends Controller
{
    /**
     * Options for the Location dropdown: the distinct locations of published properties in one language.
     */
    public function __invoke(PropertyFilterRequest $request): JsonResponse
    {
        $column = $request->locale()->column('location');

        // Distinct on the exact string (matching the case-sensitive location filter), sorted by the unicode collation.
        $locations = Property::query()
            ->published()
            ->selectRaw("distinct {$column} collate utf8mb4_bin as location")
            ->orderByRaw('location collate utf8mb4_unicode_ci')
            ->pluck('location');

        return response()->json(['data' => ['locations' => $locations]]);
    }
}
