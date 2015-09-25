<?php namespace Clockwork\DataSource;

use App\User;
use Clockwork\Request\Request;
use Illuminate\Support\Facades\Auth;

class AuthDataSource implements ExtraDataSourceInterface
{

    /**
     * @return string
     */
    public function getKey()
    {
        return 'auth';
    }

    /**
     * Adds data to the request and returns it
     */
    public function resolve(Request $request)
    {
        if (Auth::guest()) {
            return [];
        }

        /** @var User $user */
        $user = Auth::user();

        return $user->toArray();
    }
}