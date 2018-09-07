<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\IzurviveLocation as Location;
use App\IzurviveLocationSpelling as Spelling;
use App\Helpers\Helper;

class RefreshIzurviveLocations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'izurvive:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refreshes the iZurvive DayZ map locations and spellings.';

    /**
     * Not my fault the "current maps" API endpoint linked in the issue
     * doesn't work properly. ¯\_(ツ)_/¯
     *
     * @var string
     */
    private $currentMap = 'https://maps.izurvive.com/maps/CH-Sat/1.9.3/citycoords.json';

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
        $locations = Helper::get($this->currentMap);

        foreach ($locations as $loc) {
            /**
             * Example format:
             * [
             *  "nameEN" : "airfield",
             *  "nameRU" : "аэродром",
             *  "lat" : "44.932979212515605",
             *  "lng" : "-75.26630500880349",
             *  "spellings" : ["airfield", "аэродром", "aepoApoM", "a3poApoM"]
             * ]
             */
            extract($loc);

            $location = Location::where('name_en', $nameEN)->first();

            if (empty($location)) {
                $location = Location::create([
                    'name_en' => $nameEN,
                    'name_ru' => $nameRU,
                    'latitude' => $lat,
                    'longitude' => $lng,
                ]);

                $location->save();

                $this->info(sprintf('[DayZ iZurvive] Added new location: %s (%s)', $nameEN, $nameRU));
            }

            $locationId = $location->id;

            if (!in_array($nameEN, $spellings)) {
                $spellings[] = $nameEN;
            }

            if (!in_array($nameRU, $spellings)) {
                $spellings[] = $nameRU;
            }

            // Delete all spellings and re-add them.
            Spelling::truncate();
            foreach ($spellings as $spelling) {
                $newSpelling = Spelling::create([
                    'location_id' => $locationId,
                    'spelling' => $spelling,
                ]);

                $newSpelling->save();

                $this->info(sprintf('[DayZ iZurvive] Added new spelling: "%s" for %s', $spelling, $nameEN));
            }
        }
    }
}
