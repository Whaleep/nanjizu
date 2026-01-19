<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

trait HandlesJsonMedia
{
    /**
     * Define which attributes should be scanned for media cleanup.
     * Can be JSON fields (array/object) or simple strings (RichEditor HTML).
     * Override this in your model.
     */
    public function jsonMediaAttributes(): array
    {
        return [];
    }

    public static function bootHandlesJsonMedia()
    {
        static::deleting(function ($model) {
            Log::info("🗑️ Deleting media for {$model->getTable()} #{$model->id}", [
                'fields' => $model->jsonMediaAttributes()
            ]);

            foreach ($model->jsonMediaAttributes() as $attribute) {
                $model->deleteMediaInJson($model->{$attribute});
            }
        });

        static::updating(function ($model) {
            foreach ($model->jsonMediaAttributes() as $attribute) {
                $oldData = $model->getOriginal($attribute);
                $newData = $model->{$attribute};

                Log::debug("🔄 Updating media for {$attribute}", [
                    'old_paths_count' => count($model->extractPathsFromJson($oldData)),
                    'new_paths_count' => count($model->extractPathsFromJson($newData))
                ]);

                $model->pruneObsoleteJsonMedia($oldData, $newData);
            }
        });
    }

    /**
     * Delete all media paths found in the given data (array or JSON string).
     */
    public function deleteMediaInJson($data)
    {
        $paths = $this->extractPathsFromJson($data);
        Log::info("🗑️ Deleting " . count($paths) . " paths", ['paths' => $paths]);

        foreach ($paths as $path) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                Log::info("✅ Deleted: {$path}");
            } else {
                Log::warning("⚠️ File not found: {$path}");
            }
        }
    }

    /**
     * Compare old and new data, delete paths present in old but missing in new.
     */
    public function pruneObsoleteJsonMedia($oldData, $newData)
    {
        $oldPaths = $this->extractPathsFromJson($oldData);
        $newPaths = $this->extractPathsFromJson($newData);

        $obsoletePaths = array_diff($oldPaths, $newPaths);

        Log::info("🔄 Pruning " . count($obsoletePaths) . " obsolete paths", [
            'obsolete' => $obsoletePaths
        ]);

        foreach ($obsoletePaths as $path) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Recursively scan data for strings that look like valid file paths.
     * Supports arrays (JSON) and plain strings (HTML/RichText).
     */
    protected function extractPathsFromJson($data): array
    {
        if (is_string($data)) {
            // Try to decode as JSON first
            $decoded = json_decode($data, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $data = $decoded;
            } else {
                // If it's just a plain string (like RichEditor HTML), wrap it in an array to use walk_recursive
                $data = [$data];
            }
        }

        if (!is_array($data)) {
            return [];
        }

        $paths = [];
        array_walk_recursive($data, function ($value) use (&$paths) {
            if (!is_string($value)) {
                return;
            }

            // 1. Identify all occurrences of storage URLs or paths containing '/storage/'
            // This handles HTML (<img> src), Trix attributes (data-trix-attachment), etc.
            if (str_contains($value, '/storage/')) {
                // Matches strings like "/storage/products/image.jpg" or "http://site.test/storage/image.jpg"
                // It captures everything after /storage/ until a non-path character
                preg_match_all('/\/storage\/([a-zA-Z0-9_\-\.\/]+)/', $value, $matches);
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $match) {
                        $paths[] = ltrim($match, '/');
                    }
                }
            }

            // 2. Direct path check (e.g., FileUpload raw paths like "products/photo.jpg" or "photo.jpg")
            // To avoid collecting random strings as paths, we check for common storage prefixes 
            // OR if it's a root-level file with a common image/file extension
            $isKnownPrefix = str_starts_with($value, 'products/') ||
                str_starts_with($value, 'content/') ||
                str_starts_with($value, 'images/') ||
                str_starts_with($value, 'temp/');

            $isRootLevelFile = !str_contains($value, '/') &&
                preg_match('/\.(jpg|jpeg|png|gif|webp|pdf|docx|zip)$/i', $value);

            if ($isKnownPrefix || $isRootLevelFile) {
                // Final check: only add if it's not a full URL (already handled above)
                if (!str_starts_with($value, 'http')) {
                    $paths[] = ltrim($value, '/');
                }
            }
        });

        $uniquePaths = array_unique($paths);
        Log::debug("📁 Extracted paths", [
            'total' => count($paths),
            'unique' => count($uniquePaths),
            'paths' => array_slice($uniquePaths, 0, 5) // 只 log 前 5 個
        ]);

        return $uniquePaths;
    }
}
