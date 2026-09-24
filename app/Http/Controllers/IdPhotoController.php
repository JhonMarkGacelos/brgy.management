<?php

namespace App\Http\Controllers;

use App\Models\DocumentRequest;
use App\Models\Resident;
use App\Services\CloudinaryService;
use Illuminate\Support\Facades\Auth;

/**
 * Serves uploaded ID photos and payment receipts to logged-in staff/admin only, by redirecting to a
 * short-lived Cloudinary link. The images themselves are private, so a copied link stops working.
 */
class IdPhotoController extends Controller
{
    /** kind => [model, url column, public_id column] */
    private const SOURCES = [
        'pwd'             => [Resident::class,        'pwd_id_url',          'pwd_id_public_id'],
        'solo_parent'     => [Resident::class,        'solo_parent_id_url',  'solo_parent_id_public_id'],
        'fourps'          => [Resident::class,        'fourps_id_url',       'fourps_id_public_id'],
        'senior'          => [Resident::class,        'senior_id_url',       'senior_id_public_id'],
        'id_photo'        => [DocumentRequest::class, 'id_photo_url',        'id_photo_public_id'],
        'payment_receipt' => [DocumentRequest::class, 'payment_receipt_url', 'payment_receipt_public_id'],
    ];

    public function show(string $kind, int $id)
    {
        abort_unless(in_array(Auth::user()?->role, ['admin', 'staff'], true), 403);
        abort_unless(isset(self::SOURCES[$kind]), 404);

        [$model, $urlColumn, $publicIdColumn] = self::SOURCES[$kind];
        $record = $model::findOrFail($id);
        abort_unless($record->$urlColumn && $record->$publicIdColumn, 404);

        $url = app(CloudinaryService::class)->privateUrl($record->$publicIdColumn, $record->$urlColumn);

        return redirect()->away($url)->header('Cache-Control', 'no-store, private');
    }

    /** Link used by the pages; null when nothing is on file. */
    public static function link(string $kind, $record): ?string
    {
        [, $urlColumn] = self::SOURCES[$kind];

        return $record && $record->$urlColumn ? route('id-photo.show', [$kind, $record->id]) : null;
    }
}
