<?php

use App\Services\ReferralService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    foreach (['referral_attachments', 'referral_clinical', 'referral_patientinfo', 'referral_information'] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('referral_information', function (Blueprint $table) {
        $table->string('LogID')->primary();
    });
    Schema::create('referral_patientinfo', function (Blueprint $table) {
        $table->string('LogID');
    });
    Schema::create('referral_clinical', function (Blueprint $table) {
        $table->string('LogID');
    });
    Schema::create('referral_attachments', function (Blueprint $table) {
        $table->id();
        $table->string('LogID');
        $table->string('disk');
        $table->string('path');
    });
});

test('it deletes only the selected referral transaction and its attachment file', function () {
    Storage::fake('local');
    Storage::disk('local')->put('referrals/target.pdf', 'target');

    DB::table('referral_information')->insert([
        ['LogID' => 'RHU-5795090226084151'],
        ['LogID' => 'RHU-KEEP'],
    ]);
    DB::table('referral_patientinfo')->insert([
        ['LogID' => 'RHU-5795090226084151'],
        ['LogID' => 'RHU-KEEP'],
    ]);
    DB::table('referral_clinical')->insert(['LogID' => 'RHU-5795090226084151']);
    DB::table('referral_attachments')->insert([
        'LogID' => 'RHU-5795090226084151',
        'disk' => 'local',
        'path' => 'referrals/target.pdf',
    ]);

    app(ReferralService::class)->deleteReferralTransaction('RHU-5795090226084151');

    expect(DB::table('referral_information')->where('LogID', 'RHU-5795090226084151')->exists())->toBeFalse()
        ->and(DB::table('referral_patientinfo')->where('LogID', 'RHU-5795090226084151')->exists())->toBeFalse()
        ->and(DB::table('referral_clinical')->where('LogID', 'RHU-5795090226084151')->exists())->toBeFalse()
        ->and(DB::table('referral_information')->where('LogID', 'RHU-KEEP')->exists())->toBeTrue()
        ->and(DB::table('referral_patientinfo')->where('LogID', 'RHU-KEEP')->exists())->toBeTrue();

    Storage::disk('local')->assertMissing('referrals/target.pdf');
});
