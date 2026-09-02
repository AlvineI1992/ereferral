<?php

use App\Models\ReferralAttachment;
use App\Services\ReferralAttachmentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

uses(Tests\TestCase::class);

beforeEach(function () {
    Schema::create('referral_attachments', function (Blueprint $table) {
        $table->id();
        $table->string('LogID');
        $table->string('disk');
        $table->string('path');
        $table->string('original_name');
        $table->string('mime_type');
        $table->unsignedBigInteger('size');
        $table->char('sha256', 64);
        $table->unsignedBigInteger('uploaded_by')->nullable();
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('referral_attachments');
});

it('stores referral attachments privately and returns safe metadata', function () {
    Storage::fake('local');

    $metadata = app(ReferralAttachmentService::class)->store(
        'REF-TEST-001',
        [UploadedFile::fake()->image('clinical-photo.jpg')],
        null
    );

    expect($metadata)->toHaveCount(1)
        ->and($metadata[0]['name'])->toBe('clinical-photo.jpg')
        ->and($metadata[0]['mime_type'])->toBe('image/jpeg')
        ->and($metadata[0])->not->toHaveKeys(['disk', 'path', 'sha256']);

    $attachment = ReferralAttachment::query()->firstOrFail();

    Storage::disk('local')->assertExists($attachment->path);
    expect($attachment->LogID)->toBe('REF-TEST-001')
        ->and($attachment->sha256)->toHaveLength(64);
});
