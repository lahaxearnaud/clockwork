<?php
namespace Clockwork\Storage;

use Clockwork\Request\Request;
use Rs\Json\Patch;
use Rs\Json\Patch\InvalidOperationException;
use Rs\Json\Patch\InvalidPatchDocumentJsonException;
use Rs\Json\Patch\InvalidTargetDocumentJsonException;

/**
 * Class Storage
 *
 * Base storage class, all storages have to extend this class
 *
 * @package Clockwork\Storage
 * @author  LAHAXE Arnaud
 */
abstract class Storage implements StorageInterface
{

    /**
     * Array of data to be filtered from stored requests
     */
    public $filter = array();

    /**
     * @author LAHAXE Arnaud
     *
     * @param null $id
     * @param null $last
     *
     * @return null|string
     */
    public function retrieveAsJson($id = null, $last = null)
    {
        $requests = $this->retrieve($id, $last);

        if (!$requests)
            return null;

        if (!is_array($requests)) {
            return $requests->toJson();
        }

        foreach ($requests as &$request) {
            $request = $request->toArray();
        }

        return json_encode($requests);
    }

    /**
     * @author LAHAXE Arnaud
     *
     * @param array $data
     *
     * @return array
     */
    protected function applyFilter(array $data)
    {
        $emptyRequest = new Request(array());

        foreach ($this->filter as $key) {
            if (isset($data[$key])) {
                $data[$key] = $emptyRequest->$key;
            }
        }

        return $data;
    }

    /**
     * @author LAHAXE Arnaud
     *
     * @param $data
     * @param $patch
     *
     * @return string
     */
    protected function applyPatch($data, $patch)
    {
        try {
            $patchPrepared = new Patch($data, $patch);
            $data = $patchPrepared->apply();
        } catch (InvalidPatchDocumentJsonException $e) {
            // Will be thrown when using invalid JSON in a patch document
        } catch (InvalidTargetDocumentJsonException $e) {
            // Will be thrown when using invalid JSON in a target document
        } catch (InvalidOperationException $e) {
            // Will be thrown when using an invalid JSON Pointer operation (i.e. missing property)
        } catch (Patch\FailedTestException $e) {

        }

        return $data;
    }
}
