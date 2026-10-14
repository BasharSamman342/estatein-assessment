<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Uploaded images live on the public disk; the database only stores their relative path.
 */
class ImageStorage
{
    public const DISK = 'public';

    /**
     * Store an upload under $directory. The file name is random and the extension
     * comes from the detected content type, never from the client's file name.
     */
    public function store(UploadedFile $file, string $directory): string
    {
        return $file->store($directory, self::DISK);
    }

    public function url(?string $path): ?string
    {
        return $path === null ? null : $this->disk()->url($path);
    }

    /**
     * Delete files once the surrounding transaction commits, or right away when there is none,
     * so a rolled-back change never loses the file its row still points to.
     *
     * @param  string|array<int, string|null>|null  $paths
     */
    public function deleteAfterCommit(string|array|null $paths): void
    {
        $paths = array_values(array_filter((array) $paths));

        if ($paths !== []) {
            DB::afterCommit(fn () => $this->disk()->delete($paths));
        }
    }

    /**
     * Delete files right away. Use for files that no committed row points to.
     *
     * @param  string|array<int, string>  $paths
     */
    public function delete(string|array $paths): void
    {
        $this->disk()->delete($paths);
    }

    /**
     * Fill and save a model whose image lives in a single path column.
     *
     * The new file is written first. If saving fails it is removed again; the file it
     * replaces is only deleted after the save has committed.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function saveWithImage(Model $model, array $attributes, ?UploadedFile $file, string $column, string $directory): Model
    {
        $previousPath = $model->exists ? $model->getRawOriginal($column) : null;
        $newPath = $file ? $this->store($file, $directory) : null;

        try {
            DB::transaction(function () use ($model, $attributes, $column, $newPath, $previousPath) {
                $model->fill($attributes);

                if ($newPath !== null) {
                    $model->setAttribute($column, $newPath);
                }

                $model->save();

                if ($newPath !== null) {
                    $this->deleteAfterCommit($previousPath);
                }
            });
        } catch (Throwable $e) {
            if ($newPath !== null) {
                $this->delete($newPath);
            }

            throw $e;
        }

        return $model;
    }

    private function disk(): Filesystem
    {
        return Storage::disk(self::DISK);
    }
}
