<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CleanRetentionMedia extends Command
{
    protected $signature = 'media:clean-retention';

    protected $description = 'Hapus file foto presensi di folder permanent/ yang berusia lebih dari 60 hari.';

    public function handle(): int
    {
        $this->info('Memulai pembersihan foto presensi di atas 60 hari...');

        $hasR2Config = ! empty(config('filesystems.disks.r2.key')) && ! empty(config('filesystems.disks.r2.secret'));
        $useR2 = config('filesystems.default') === 'r2' && $hasR2Config && ! app()->runningUnitTests();
        $disk = $useR2 ? 'r2' : 'public';

        $thresholdTimestamp = Carbon::now()->subDays(60)->getTimestamp();
        $deletedCount = 0;

        try {
            $files = Storage::disk($disk)->allFiles('permanent');

            foreach ($files as $file) {
                $lastModified = Storage::disk($disk)->lastModified($file);

                if ($lastModified < $thresholdTimestamp) {
                    Storage::disk($disk)->delete($file);
                    $deletedCount++;
                }
            }
        } catch (Throwable) {
            $this->warn('Gagal membaca direktori permanent.');
        }

        $this->info("Pembersihan retensi selesai. Total file dihapus: {$deletedCount}");

        return Command::SUCCESS;
    }
}
