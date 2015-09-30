<?php
namespace Clockwork\Storage;

use Clockwork\Request\Request;
use Exception;

/**
 * Simple file based storage for requests
 */
class FileStorage extends Storage
{

    /**
     * Path where files are stored
     */
    private $path;

    /**
     * Return new storage, takes path where to store files as argument, throws exception if path is not writable
     */
    public function __construct()
    {
        $path = storage_path('clockwork');

        if (!file_exists($path)) {
            # directory doesn't exist, try to create one
            if (!mkdir($path, 0700, true))
                throw new Exception('Directory "' . $path . '" does not exist.');

            # create default .gitignore, to ignore stored json files
            file_put_contents($path . '/.gitignore', "*.json\n");
        }

        if (!is_writable($path))
            throw new Exception('Path "' . $path . '" is not writable.');

        $this->path = $path;
    }

    /**
     * @author LAHAXE Arnaud
     *
     * @param $id
     *
     * @return \Clockwork\Request\Request|null
     */
    public function get($id)
    {
        if (!is_readable($this->path . '/' . $id . '.json')) {
            return null;
        }

        $data = file_get_contents($this->path . '/' . $id . '.json');

        if (is_bool($data)) {
            return null;
        }

        $patchDir = $this->path . DIRECTORY_SEPARATOR . $id;

        $files = glob($patchDir . '/patch-*.json');
        foreach ($files as $file) {
            $patch = file_get_contents($file);
            $data  = $this->applyPatch($data, $patch);
        }

        file_put_contents($this->path . '/' . $id . '.json', $data);
        $this->deletePatches($id);

        return new Request(json_encode($data));
    }

    /**
     * Retrieve request specified by id argument, if second argument is specified, array of requests from id to last
     * will be returned
     */
    public function retrieve($id = null, $last = null)
    {
        if ($id && !$last) {
            if (!is_readable($this->path . '/' . $id . '.json'))
                return null;

            return new Request(
                json_decode(file_get_contents($this->path . '/' . $id . '.json'), true)
            );
        }

        $files = glob($this->path . '/*.json');

        $id   = ($id) ? $id . '.json' : first($files);
        $last = ($last) ? $last . '.json' : end($files);

        $requests = array();
        $add      = false;

        foreach ($files as $file) {
            if ($file == $id)
                $add = true;
            elseif ($file == $last)
                $add = false;

            if (!$add)
                continue;

            $requests[] = new Request(
                json_decode(file_get_contents($file), true)
            );
        }

        return $requests;
    }

    /**
     * Store request, requests are stored in JSON representation in files named <request id>.json in storage path
     */
    public function store(Request $request)
    {
        file_put_contents(
            $this->path . '/' . $request->id . '.json',
            @json_encode($this->applyFilter($request->toArray()))
        );
    }

    /**
     * Store request
     */
    public function storePatch(Request $request, $patch)
    {
        $patchDir = $this->path . DIRECTORY_SEPARATOR . $request->id;

        if (!is_dir($patchDir)) {
            mkdir($patchDir);
        }

        file_put_contents(
            $patchDir . '/patch-' . microtime(true) . '.json',
            @json_encode($patch)
        );
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function delete($id)
    {
        $baseJsonPath = $this->path . '/' . $id . '.json';
        if (is_file($baseJsonPath)) {
            unlink($baseJsonPath);
        }
    }

    /**
     * @author LAHAXE Arnaud
     *
     * @param $id
     *
     */
    public function deletePatches($id)
    {
        $patchDir = $this->path . DIRECTORY_SEPARATOR . $id;

        if (is_dir($patchDir)) {
            $files = glob($patchDir . '/*.json');
            foreach ($files as $file) {
                unlink($file);
            }
        }

        rmdir($patchDir);
    }
}
