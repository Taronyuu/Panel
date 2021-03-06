<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
 * Some Modifications (c) 2015 Dylan Seidt <dylan.seidt@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
namespace Pterodactyl\Http\Controllers\Admin;

use Alert;
use Settings;
use Mail;
use Log;
use Pterodactyl\Models\User;
use Pterodactyl\Repositories\UserRepository;
use Pterodactyl\Models\Server;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\DisplayValidationException;

use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{

    /**
     * Controller Constructor
     */
    public function __construct()
    {
        //
    }

    public function getIndex(Request $request)
    {
        return view('admin.users.index', [
            'users' => User::paginate(20)
        ]);
    }

    public function getNew(Request $request)
    {
        return view('admin.users.new');
    }

    public function getView(Request $request, $id)
    {
        return view('admin.users.view', [
            'user' => User::findOrFail($id),
            'servers' => Server::select('servers.*', 'nodes.name as nodeName', 'locations.long as location')
                ->join('nodes', 'servers.node', '=', 'nodes.id')
                ->join('locations', 'nodes.location', '=', 'locations.id')
                ->where('owner', $id)
                ->get(),
        ]);
    }

    public function deleteUser(Request $request, $id)
    {
        try {
            $repo = new UserRepository;
            $repo->delete($id);
            Alert::success('Successfully deleted user from system.')->flash();
            return redirect()->route('admin.users');
        } catch(DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An exception was encountered while attempting to delete this user.')->flash();
        }
        return redirect()->route('admin.users.view', $id);
    }

    public function postNew(Request $request)
    {
        try {
            $user = new UserRepository;
            $userid = $user->create($request->input('email'), $request->input('password'));
            Alert::success('Account has been successfully created.')->flash();
            return redirect()->route('admin.users.view', $userid);
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.users.new')->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An error occured while attempting to add a new user.')->flash();
            return redirect()->route('admin.users.new');
        }
    }

    public function updateUser(Request $request, $user)
    {
        $data = [
            'email' => $request->input('email'),
            'root_admin' => $request->input('root_admin'),
            'password_confirmation' => $request->input('password_confirmation'),
        ];

        if ($request->input('password')) {
            $data['password'] = $request->input('password');
        }

        try {
            $repo = new UserRepository;
            $repo->update($user, $data);
            Alert::success('User account was successfully updated.')->flash();
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.users.view', $user)->withErrors(json_decode($ex->getMessage()));
        } catch (\Exception $e) {
            Log::error($e);
            Alert::danger('An error occured while attempting to update this user.')->flash();
        }
        return redirect()->route('admin.users.view', $user);
    }

    public function getJson(Request $request)
    {
        foreach(User::select('email')->get() as $user) {
            $resp[] = $user->email;
        }
        return $resp;
    }

}
