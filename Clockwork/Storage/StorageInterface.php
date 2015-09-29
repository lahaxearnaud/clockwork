<?php  namespace Clockwork\Storage;

use Clockwork\Request\Request;

/**
 * Base storage class, all storages have to extend this class
 */
interface StorageInterface
{

    /**
     * Retrieve request specified by id argument, if second argument is specified, array of requests from id to last
     * will be returned
     *
     * @param null $id
     * @param null $last
     * @return mixed
     */
    public function retrieve($id = null, $last = null);

    /**
     * Store request
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request);

    /**
     * Store a json patch
     * @param Request $request
     * @param $patch
     * @return mixed
     */
    public function storePatch(Request $request, $patch);

    /**
     * @param $id
     * @return mixed
     */
    public function delete($id);
}
