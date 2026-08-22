<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CleanStagingMedia extends Command
{
    protected $signature = 'media:clean-staging';

    protected $description = 'Hapus file sementara di folder staging/ yang berusia lebih dari 24 jam.';

    public function handle(): int
    {
        $this->info('Memulai pembersihan file sementara staging...');

        $hasR2Config = ! empty(config('filesystems.disks.r2.key')) && ! empty(config('filesystems.disks.r2.secret'));
        $useR2 = config('filesystems.default') === 'r2' && $hasR2Config && ! app()->runningUnitTests();
        $disk = $useR2 ? 'r2' : 'public';

        $thresholdTimestamp = Carbon::now()->subHours(24)->getTimestamp();
        $deletedCount = 0;

        try {
            $files = Storage::disk($disk)->allFiles('staging');

            foreach ($files as $file) {
                $lastModified = Storage::disk($disk)->lastModified($file);

                if ($lastModified < $thresholdTimestamp) {
                    Storage::disk($disk)->delete($file);
                    $deletedCount++;
                }
            }
        } catch (Throwable $e) {
            $this->warn('Gagal membaca direktori staging: ' . $e->getMessage());
        }

        $this->info("Pembersihan staging selesai. Total file dihapus: {$deletedCount}");

        return Command::SUCCESS;
    }
}
