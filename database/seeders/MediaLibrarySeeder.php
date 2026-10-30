<?php

namespace Database\Seeders;

use App\Models\Edition;
use App\Models\MediaItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class MediaLibrarySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([2, 1, 3] as $number) {
            $directory = public_path('images/gallery/rdsa' . $number);
            if (!is_dir($directory)) continue;
            $priority = $number === 2 ? ['rdsa2-img-h42', 'rdsa2-img-h41', 'rdsa2-img-h16', 'rdsa2-img-v38', 'rdsa2-img-h2', 'rdsa2-img-v65', 'rdsa2-img-h36', 'rdsa2-img-v16', 'rdsa2-img-h1'] : [];
            $files = collect(File::files($directory))->filter(fn ($f) => in_array(strtolower($f->getExtension()), ['jpg', 'jpeg', 'png', 'webp']) && !str_contains($f->getFilename(), 'flyer'))
                ->sortBy(function ($file) use ($priority) {
                    $index = array_search($file->getBasename('.' . $file->getExtension()), $priority, true);
                    return $index === false ? '100-' . $file->getFilename() : sprintf('%03d', $index);
                }, SORT_NATURAL)->values();
            $titles = ['Unis dans l’adoration', 'Une rencontre, des souvenirs', 'Au cœur de la RDSA'];
            foreach ($files->take(9) as $index => $file) {
                $path = 'images/gallery/rdsa' . $number . '/' . $file->getFilename();
                MediaItem::firstOrCreate(['disk' => 'assets', 'file_path' => $path], [
                    'title' => $titles[$index % 3], 'album_name' => 'RDSA ' . $number,
                    'edition_id' => Edition::where('number', $number)->value('id'), 'kind' => 'photo',
                    'is_published' => true, 'sort_order' => ($number === 2 ? 0 : ($number === 1 ? 40 : 60)) + $index,
                ]);
            }
        }
    }
}
