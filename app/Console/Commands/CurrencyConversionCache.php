<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

use Cache;

class CurrencyConversionCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'currency:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches currency conversion rates and caches them for up to 48 hours';

    /**
     * Base "API" URL
     *
     * @var string
     */
    protected $baseUrl = 'https://cdn.jsdelivr.net/gh/fawazahmed0/currency-api@1/latest';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $currencies = config('currency.currencies');
        $currencyRates = [];
        foreach ($currencies as $code)
        {
            $code = strtolower($code);
            $currencyRates[$code] = [
                'date' => date('Y-m-d'),
                'rates' => [],
            ];

            $currencyUrl = sprintf('%s/currencies/%s.json', $this->baseUrl, $code);
            $currencyResponse = Http::get($currencyUrl);

            if ($currencyResponse->failed()) {
                $this->error(sprintf('Failed to fetch currency rates for %s', $code));
                continue;
            }

            $ratesData = $currencyResponse->json();
            if (isset($ratesData['date'])) {
                $currencyRates[$code]['date'] = $ratesData['date'];
            }

            $currencyRates[$code]['rates'] = $ratesData[$code] ?? [];
            $this->info(sprintf('Fetched %d rates for %s', count($currencyRates[$code]['rates']), $code));
        }

        $cacheKey = config('currency.cacheKey') ?? 'currency_rates';
        $cacheHours = config('currency.cacheHours') ?? 48;

        Cache::put($cacheKey, $currencyRates, now()->addHours($cacheHours));

        return Command::SUCCESS;
    }
}
