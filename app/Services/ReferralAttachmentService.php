<?php

namespace App\Services;

use App\Models\ReferralAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ReferralAttachmentService
{
    public const DISK = 'local';

    /**
     * @param  array<int, UploadedFile>  $files
     * @return array<int, array<string, mixed>>
     */
    public function store(string $logId, array $files, ?int $userId): array
    {
        if ($files === []) {
            return [];
        }

        $storedPaths = [];

        try {
            $attachments = DB::transaction(function () use ($logId, $files, $userId, &$storedPaths) {
                return collect($files)->map(function (UploadedFile $file) use ($logId, $userId, &$storedPaths) {
                    $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension());
                    $filename = Str::uuid().($extension !== '' ? '.'.$extension : '');
                    $directory = 'referral-attachments/'.hash('sha256', $logId);
                    $path = $file->storeAs($directory, $filename, self::DISK);

                    if ($path === false) {
                        throw new RuntimeException('The attachment could not be stored.');
                    }

                    $storedPaths[] = $path;

                    return ReferralAttachment::create([
                        'LogID' => $logId,
                        'disk' => self::DISK,
                        'path' => $path,
                        'original_name' => basename($file->getClientOriginalName()),
                        'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                        'size' => $file->getSize(),
                        'sha256' => hash_file('sha256', $file->getRealPath()),
                        'uploaded_by' => $userId,
                    ]);
                })->all();
            });
        } catch (Throwable $exception) {
            Storage::disk(self::DISK)->delete($storedPaths);
            throw $exception;
        }

        return array_map(fn (ReferralAttachment $attachment) => $this->metadata($attachment), $attachments);
    }

    /** @return array<string, mixed> */
    public function metadata(ReferralAttachment $attachment): array
    {
        return [
            'id' => $attachment->id,
            'name' => $attachment->original_name,
            'mime_type' => $attachment->mime_type,
            'size' => $attachment->size,
            'download_url' => route('referral.attachments.download', $attachment),
        ];
    }
}
