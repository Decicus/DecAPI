<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use PHPHtmlParser\Dom;
use Vinelab\Rss\Rss;

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
        $dom = new Dom;
        $base = 'https://help.twitch.tv';

        $dom->loadFromUrl($base);
        $topics = $dom->find('.topic');

        $rss = new Rss;
        foreach ($topics as $topic) {
            $catId = str_replace('topic topic', null, $topic->class);
            $catTitle = htmlspecialchars_decode($topic->find('h5.articles')->innerHtml);

            $this->info('Found help category/topic: ' . $catTitle);

            $category = Category::firstOrNew(['id' => $catId]);
            $category->title = $catTitle;
            $category->save();

            $feedUrl = sprintf('%s/customer/en/portal/topics/%s.rss', $base, $catId);
            $feed = $rss->feed($feedUrl);

            foreach ($feed->articles() as $article) {
                $id = str_replace($base . '/customer/en/portal/articles/', null, $article->link);
                $title = htmlspecialchars_decode($article->title);

                $helpArticle = Article::firstOrNew(['id' => $id]);
                $helpArticle->title = $title;
                $helpArticle->category_id = $catId;
                $helpArticle->save();

                $this->info('Found help article: ' . $title);
            }
        }
    }
}
