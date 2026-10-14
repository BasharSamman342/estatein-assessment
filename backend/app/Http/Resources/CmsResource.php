<?php

namespace App\Http\Resources;

use App\Services\ImageStorage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shape shared by the public and admin APIs. Both return every language;
 * only admin responses include the publish status.
 */
abstract class CmsResource extends JsonResource
{
    /**
     * The section's own fields.
     *
     * @return array<string, mixed>
     */
    abstract protected function fields(Request $request): array;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            ...$this->fields($request),
            'sort_order' => $this->sort_order,
            'is_published' => $this->when(self::isAdminRequest($request), fn () => $this->is_published),
        ];
    }

    /**
     * Absolute URL (built from APP_URL) for a stored image, or null. Paths never leave the API.
     */
    protected function imageUrl(?string $path): ?string
    {
        return app(ImageStorage::class)->url($path);
    }

    public static function isAdminRequest(Request $request): bool
    {
        return $request->routeIs('admin.*');
    }
}
