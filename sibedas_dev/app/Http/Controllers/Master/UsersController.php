<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\UsersRequest;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Traits\GlobalApiResponse;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;

class UsersController extends Controller
{
    use GlobalApiResponse;
    public function allUsers(Request $request){
        $users = User::all();
        return $this->resSuccess($users);
    }
    public function index(Request $request){
        $menuId = $request->query('menu_id') ?? $request->input('menu_id');
        $permissions = $this->permissions[$menuId]?? []; // Avoid undefined index error
        $creator = $permissions['allow_create'] ?? 0;
        $updater = $permissions['allow_update'] ?? 0;
        $destroyer = $permissions['allow_destroy'] ?? 0;

        $users = User::paginate();
        return view('master.users.index', compact('users', 'creator', 'updater', 'destroyer','menuId'));
    }
    public function create(Request $request){
        $menuId = $request->query('menu_id') ?? $request->input('menu_id');
        $roles = Role::all();
        return view('master.users.create', compact('roles', 'menuId'));
    }
    public function store(UsersRequest $request){
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', 'max:255'],
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'role_id' => 'required|exists:roles,id'
        ]);


        DB::beginTransaction();
        try{
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'position' => $request->position
            ]);

            $user->roles()->attach($request->role_id);

            DB::commit();
            return response()->json(['message' => 'Successfully created'],201);
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()],500);
        };
    }
    public function show($id){
        $user = User::find($id);
        return view('master.users.show', compact('user'));
    }
    public function edit(Request $request, $id){
        $menuId = $request->query('menu_id') ?? $request->input('menu_id');
        $user = User::find($id);
        $roles = Role::all();
        return view('master.users.edit', compact('user', 'roles', 'menuId'));
    }
    public function update(Request $request, $id){
        $user = User::find($id);
        $validatedData  = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($id)],
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'role_id' => ['required', 'exists:roles,id'],
        ]);
        try{
            DB::beginTransaction();
            $updateData = [
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'firstname' => $validatedData['firstname'],
                'lastname' => $validatedData['lastname'],
                'position' => $validatedData['position'],
            ];
            $user->update($updateData);
            $user->roles()->sync([$request->role_id]);
            DB::commit();
            return response()->json(['message' => 'Successfully updated'],200);
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()],500);
        }
    }
    public function destroy($id){
        $user = User::find($id);
        $user->delete();
        return redirect()->route('users.index')->with('success','Successfully deleted');
    }
}
