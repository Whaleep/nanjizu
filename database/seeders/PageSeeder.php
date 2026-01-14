<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Page;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    // php artisan db:seed --class=PageSeeder

    public function run(): void
    {
        Page::updateOrCreate(
            ['id' => 1],
            [
                'title' => '首頁',
                'slug' => 'home',
                'content' => json_decode('[
                    {
                        "type":"hero_carousel",
                        "data":{
                            "slides":[
                                {
                                    "image":"/images/hero-bg.jpg",
                                    "heading":"\u60a8\u7684\u624b\u6a5f\u6025\u8a3a\u5ba4",
                                    "subheading":"iPhone \/ Android \/ iPad \u5c08\u696d\u7dad\u4fee",
                                    "button_text":"\u67e5\u8a62\u7dad\u4fee\u50f9\u683c",
                                    "button_url":"\/repair"
                                },
                                {
                                    "image":"/images/hero-shop.png",
                                    "heading":"\u7cbe\u9078\u914d\u4ef6 \u9650\u6642\u512a\u60e0",
                                    "subheading":"\u4fdd\u8b77\u8cbc\u3001\u624b\u6a5f\u6bbc\u3001\u5145\u96fb\u7dda\uff0c\u901a\u901a\u90fd\u6709",
                                    "button_text":"\u524d\u5f80\u5546\u5e97",
                                    "button_url":"\/shop"
                                }
                            ],
                            "autoplay_delay":5000
                        }
                    },
                    {
                        "type":"icon_links",
                        "data":{
                            "columns":4,
                            "items":[
                                {"label":"\u624b\u6a5f\u7dad\u4fee","url":"\/repair","icon":"\ud83d\udee0","color":"blue"},
                                {"label":"\u7dda\u4e0a\u5546\u5e97","url":"shop","icon":"\ud83d\uded2","color":"green"},
                                {"label":"\u9001\u4fee\u6d41\u7a0b","url":"\/process","icon":"\ud83d\udce6","color":"purple"},
                                {"label":"\u9580\u5e02\u64da\u9ede","url":"\/stores","icon":"\ud83d\udccd","color":"orange"}
                            ]
                        }
                    },
                    {
                        "type":"product_grid",
                        "data":{"heading":"\u6700\u65b0\u4e0a\u67b6","limit":"4","category_id":null,"tag_id":null,"show_cart":true}
                    },
                    {
                        "type":"post_grid",
                        "data":{"heading":"\u6700\u65b0\u6d88\u606f","type":"news","limit":3,"bg_color":"white"}
                    },
                    {
                        "type":"feature_wall",
                        "data":{
                            "heading":null,
                            "subheading":null,
                            "items":[
                                {
                                    "image":"/images/repair-process.png",
                                    "title":"\u5404\u5f0f\u624b\u6a5f\u3001\u5e73\u677f\u7dad\u4fee",
                                    "description":null,
                                    "url":null,
                                    "cols":"2",
                                    "rows":1
                                },
                                {
                                    "image":"/images/hero-shop.png",
                                    "title":"3C\u914d\u4ef6",
                                    "description":null,
                                    "url":null,
                                    "cols":1,
                                    "rows":1
                                },
                                {
                                    "image":"/images/hero-bg.jpg",
                                    "title":"\u65b0\u820a\u6a5f\u8cb7\u8ce3",
                                    "description":null,
                                    "url":null,
                                    "cols":1,
                                    "rows":1
                                },
                                {
                                    "image":"/images/repair-process.png",
                                    "title":"\u8cc7\u6599\u6551\u63f4",
                                    "description":null,
                                    "url":null,
                                    "cols":1,
                                    "rows":1
                                },
                                {
                                    "image":"/images/hero-bg.jpg",
                                    "title":"\u4e3b\u677f\u7dad\u4fee",
                                    "description":null,
                                    "url":null,
                                    "cols":1,
                                    "rows":1
                                }
                            ]
                        }
                    }
                ]', true),
                'is_published' => true,
                'seo_title' => null,
                'seo_description' => null,
            ]
        );
    }
}
