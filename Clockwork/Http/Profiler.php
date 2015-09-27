<?php namespace Clockwork\Http;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Laravel\Lumen\Application;
use Monolog\Logger;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;

class Profiler extends Controller
{
    /**
     * Legacy controller used for chrome extension
     *
     * @param null $id
     * @param null $last
     * @return mixed
     */
    public function getData($id = null, $last = null)
    {
        return App::make('clockwork.support')->getData($id, $last);
    }

    /**
     * @author   LAHAXE Arnaud
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @apiGroup Profiler
     * @apiName  index
     * @api      {post} /api/__profiler/profiles List of profiles
     *
     * @apiSuccess (200) {Array} profiles List of profiles.
     */
    public function index()
    {
        $this->clean();
        return response()->json($this->getDataFromJson());
    }

    /**
     * @author   LAHAXE Arnaud
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @apiGroup Profiler
     * @apiName  last
     * @api      {post} /api/__profiler/profiles/last Last profile
     *
     * @apiSuccess (200) {String} id Profile id.
     * @apiSuccess (200) {String} method Http method.
     * @apiSuccess (200) {Number} responseStatus Http status.
     * @apiSuccess (200) {Number} datetime Start request datetime.
     * @apiSuccess (200) {Number} timestamp Start request timestamp.
     * @apiSuccess (200) {String} uri Request uri.
     * @apiSuccess (200) {Number} duration Request duration.
     * @apiSuccess (200) {Number} databaseDuration Database queries duration.
     * @apiSuccess (200) {Number} nbSqlQueries Number of sql queries.
     */
    public function last()
    {
        $profils = $this->getDataFromJson();
        $last    = null;

        foreach ($profils as $profil) {
            if (is_null($last)) {
                $last = $profil;
            } elseif ($profil['timestamp'] > $last['timestamp']) {
                $last = $profil;
            }
        }

        return response()->json($last);
    }

    /**
     * @author LAHAXE Arnaud
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function show($id)
    {
        $filename = storage_path('clockwork/' . $id . '.json');
        if (!is_file($filename)) {
            return response()->json([], 404);
        }

        $profile = json_decode(file_get_contents($filename));

        if (is_bool($profile)) {
            return response()->json([], 503);
        }

        $profile->id           = $id;
        $profile->timestamp    = Carbon::createFromTimestamp($profile->time)->timestamp;
        $profile->datetime     = Carbon::createFromTimestamp($profile->time)->toIso8601String();
        $profile->duration     = floor($profile->responseDuration);
        $profile->nbSqlQueries = count($profile->databaseQueries);

        $start = $profile->timelineData->total->start;

        foreach ($profile->timelineData as $key => $item) {
            $profile->timelineData->{$key}->start    = floor(($item->start - $start) * 1000);
            $profile->timelineData->{$key}->end      = floor(($item->end - $start) * 1000);
            $profile->timelineData->{$key}->duration = floor($item->duration);
        }

        foreach ($profile->log as $key => $item) {
            $profile->log[$key]->time = floor(($profile->log[$key]->time - $start) * 1000);
            // log from monolog use number instread of string
            if (is_numeric($profile->log[$key]->level)) {
                $profile->log[$key]->level = Logger::getLevelName($profile->log[$key]->level);
            }

            $profile->log[$key]->level = strtoupper($profile->log[$key]->level);
        }

        return response()->json($profile);
    }

    /**
     * @author LAHAXE Arnaud
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @apiGroup Profiler
     * @apiName  stats
     * @api      {post} /api/__profiler/profiles/stats Session stats
     *
     * @apiSuccess (200) {Number} nbProfile Number of profiles.
     * @apiSuccess (200) {Number} nbSqlQueries Number of sql queries.
     * @apiSuccess (200) {Number} duration Average duration.
     * @apiSuccess (200) {Number} nbError Number of errors.
     * @apiSuccess (200) {Number} databaseDuration Average database duration.
     * @apiSuccess (200) {Number} nbLogs Average number of log.
     */
    public function stats()
    {
        $profiles = $this->getDataFromJson();

        $result = [
            'nbProfile'        => count($profiles),
            'nbSqlQueries'     => 0,
            'duration'         => 0,
            'nbError'          => 0,
            'databaseDuration' => 0,
            'nbLogs'           => 0
        ];

        if ($result['nbProfile'] === 0) {
            return response()->json($result);
        }

        foreach ($profiles as $profile) {
            $result['nbSqlQueries'] += $profile['nbSqlQueries'];
            $result['duration'] += $profile['duration'];
            $result['databaseDuration'] += $profile['databaseDuration'];
            $result['nbLogs'] += $profile['nbLogs'];
            if ($profile['responseStatus'] >= 400 && $profile['responseStatus'] != 401) {
                $result['nbError']++;
            }
        }

        $result['nbSqlQueries']     = $result['nbSqlQueries'] / $result['nbProfile'];
        $result['duration']         = $result['duration'] / $result['nbProfile'];
        $result['databaseDuration'] = $result['databaseDuration'] / $result['nbProfile'];
        $result['nbLogs']           = $result['nbLogs'] / $result['nbProfile'];

        return response()->json($result);
    }

    /**
     * @author LAHAXE Arnaud
     *
     *
     * @return array
     */
    protected function getDataFromJson()
    {
        $results = [];
        $finder  = Finder::create();
        $finder->name('*.json')->date('since 3 hours ago')->depth('== 0')->size('<= 100K')->sortByModifiedTime();
        /** @var File $file */
        foreach ($finder->in(storage_path('clockwork')) as $file) {
            $tmp = json_decode($file->getContents());
            if (is_bool($tmp)) {
                continue;
            }

            $results[] = [
                'id'               => $file->getBasename('.json'),
                'method'           => $tmp->method,
                'responseStatus'   => $tmp->responseStatus,
                'datetime'         => Carbon::createFromTimestamp($tmp->time)->toIso8601String(),
                'timestamp'        => Carbon::createFromTimestamp($tmp->time)->timestamp,
                'uri'              => $tmp->uri,
                'duration'         => floor($tmp->responseDuration),
                'databaseDuration' => floor($tmp->databaseDuration),
                'nbSqlQueries'     => count($tmp->databaseQueries),
                'nbLogs'           => count($tmp->log),
            ];

            unset($tmp);
        }

        return array_reverse($results);
    }

    protected function clean()
    {
        $finder  = Finder::create();
        $finder->name('*.json')->date('until 3 hours ago');
        /** @var File $file */
        foreach ($finder->in(storage_path('clockwork')) as $file) {
            unlink($file->getRealPath());
        }
    }
}