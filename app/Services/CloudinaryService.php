<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;

class CloudinaryService
{
    private Cloudinary $cloudinary;

    /** How long a link to a private ID image stays valid. */
    public const PRIVATE_URL_TTL = 300;

    public function __construct()
    {
        $this->cloudinary = new Cloudinary(
            Configuration::instance([
                'cloud' => [
                    // Read via config (not env()) so it keeps working when config is cached on deploy.
                    'cloud_name' => config('services.cloudinary.cloud_name'),
                    'api_key'    => config('services.cloudinary.api_key'),
                    'api_secret' => config('services.cloudinary.api_secret'),
                ],
                'url' => ['secure' => true],
            ])
        );
    }

    /**
     * Upload an image. ID photos and payment receipts are private ("authenticated") by default and can only
     * be viewed through privateUrl(); pass $private = false for images that must be public (signature, GCash QR).
     */
    public function uploadIdPhoto(\Illuminate\Http\UploadedFile $file, string $folder = "ID's", bool $private = true): array
    {
        $result = $this->cloudinary->uploadApi()->upload($file->getRealPath(), [
            'folder'         => $folder,
            'resource_type'  => 'image',
            'type'           => $private ? 'authenticated' : 'upload',
            'quality'        => 'auto',
            'fetch_format'   => 'auto',
        ]);

        return [
            'url'       => $result['secure_url'],
            'public_id' => $result['public_id'],
        ];
    }

    public static function isPrivateUrl(?string $url): bool
    {
        return $url !== null && str_contains($url, '/authenticated/');
    }

    /**
     * A short-lived link to view an uploaded image. Private images get a signed download link that expires;
     * images uploaded before they were made private are still public, so their stored URL is returned.
     */
    public function privateUrl(string $publicId, string $storedUrl, int $ttl = self::PRIVATE_URL_TTL): string
    {
        if (!self::isPrivateUrl($storedUrl)) {
            return $storedUrl;
        }

        $format = strtolower(pathinfo(parse_url($storedUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION)) ?: 'jpg';

        return $this->cloudinary->uploadApi()->privateDownloadUrl($publicId, $format, [
            'type'       => 'authenticated',
            'expires_at' => time() + $ttl,
        ]);
    }

    /** Switch an already-uploaded public image to private. Returns its new (private) URL. */
    public function makePrivate(string $publicId): string
    {
        $result = $this->cloudinary->uploadApi()->rename($publicId, $publicId, [
            'type'       => 'upload',
            'to_type'    => 'authenticated',
            'invalidate' => true,
        ]);

        return $result['secure_url'];
    }

    public function delete(string $publicId): void
    {
        // The image may be private (current uploads) or public (older uploads); "not found" is not an error.
        $this->cloudinary->uploadApi()->destroy($publicId, ['type' => 'authenticated', 'invalidate' => true]);
        $this->cloudinary->uploadApi()->destroy($publicId, ['invalidate' => true]);
    }
}
