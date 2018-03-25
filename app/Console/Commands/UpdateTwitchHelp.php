<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Carbon\Carbon;
use Vinelab\Rss\Rss;
use GuzzleHttp\Client as HttpClient;
use Symfony\Component\DomCrawler\Crawler;

use App\TwitchHelpArticle as Article;
use App\TwitchHelpCategory as Category;
class UpdateTwitchHelp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'twitch:help';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the Twitch help articles and categories/topics.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $client = new HttpClient;

        $settings = [
            'headers' => [
                'User-Agent' => env('DECAPI_USER_AGENT', null),
            ],
            'http_errors' => false,
        ];

        $base = 'https://help.twitch.tv/';
        $request = $client->request('GET', $base, $settings);

        $body = (string) $request->getBody();

        $dom = new Crawler($body);
        $topics = $dom->filter('.topic');

        $rss = new Rss;
        $topics->each(function(Crawler $topic, $i) use($base, $rss) {
            $catId = str_replace('topic topic', null, $topic->attr('class'));
            $catTitle = htmlspecialchars_decode($topic->filter('h5.articles')->text());

            $this->info('Found help category/topic: ' . $catTitle);

            $category = Category::firstOrNew(['id' => $catId]);
            $category->title = $catTitle;
            $category->save();

            $feedUrl = sprintf('%s/customer/en/portal/topics/%s.rss', $base, $catId);
            $feed = $rss->feed($feedUrl);

            foreach ($feed->articles() as $article) {
                $id = str_replace($base . 'customer/en/portal/articles/', null, $article->link);
                $title = htmlspecialchars_decode($article->title);

                $helpArticle = Article::firstOrNew(['id' => $id]);
                $helpArticle->title = $title;
                $helpArticle->category_id = $catId;
                $helpArticle->published = Carbon::parse($article->pubDate);
                $helpArticle->save();

                $this->info('Found help article: ' . $title);
            }
        });
    }
}
