<?php namespace Clockwork\DataSource;

use Clockwork\Request\Request;
use Illuminate\Support\Facades\Config;

class FilesDataSource implements ExtraDataSourceInterface
{

    /**
     * @return string
     */
    public function getKey()
    {
        return 'files';
    }

    /**
     * Adds data to the request and returns it
     */
    public function resolve(Request $request)
    {
        $files = get_included_files();

        foreach($files as $key => $file) {
            $files[$key] =  str_replace(base_path(), '', $file);
        }

        return $files;
    }
}