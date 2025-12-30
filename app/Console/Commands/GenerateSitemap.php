<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use App\Models\Post;
use App\Models\DeviceModel;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';
    protected $description = 'Generate the sitemap.';

    public function handle()
    {
        $sitemap = Sitemap::create();

        // 靜態頁面
        $sitemap->add(Url::create('/'));
        $sitemap->add(Url::create('/about'));
        $sitemap->add(Url::create('/repair'));
        $sitemap->add(Url::create('/second-hand'));
        $sitemap->add(Url::create('/stores'));

        // 動態頁面：文章
        Post::where('is_published', true)->each(function (Post $post) use ($sitemap) {
            $sitemap->add(Url::create("/posts/{$post->slug}"));
        });

        // 動態頁面：維修機型
        DeviceModel::all()->each(function (DeviceModel $model) use ($sitemap) {
            $sitemap->add(Url::create("/repair/{$model->id}"));
        });

        // 寫入檔案
        $sitemap->writeToFile(public_path('sitemap.xml'));

        $this->info('Sitemap generated successfully!');
    }
}

?>
