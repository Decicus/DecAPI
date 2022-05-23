<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Helpers\Helper;
use GuzzleHttp\Client as HttpClient;
use Symfony\Component\DomCrawler\Crawler;

class ArmaController extends Controller
{
    /**
     * Generic HTTP client
     *
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var string
     */
    private $reforgerNewsBaseUrl = 'https://reforger.armaplatform.com/news';

    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Grab latest news from the Arma Platform Reforger page.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return Response
     */
    public function reforgerNews(Request $request)
    {
        $postIndex = (int) $request->input('index', 0);
        $settings = [
            'http_errors' => false,
        ];

        $httpRequest = $this->httpClient->request('GET', $this->reforgerNewsBaseUrl, $settings);
        $body = (string) $httpRequest->getBody();

        $dom = new Crawler($body);
        $json = trim($dom->filter('#__NEXT_DATA__')->text('[]', false));

        $data = json_decode($json, true);

        $posts = $data['props']['pageProps']['posts'];

        $post = $posts[$postIndex] ?? $posts[0];

        $url = sprintf('%s/%s', $this->reforgerNewsBaseUrl, $post['slug']);
        return Helper::text(sprintf('%s - %s', $post['title'], $url));
    }
}
