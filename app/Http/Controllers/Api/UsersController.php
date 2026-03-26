<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\UsersRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\GlobalApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UsersController extends Controller
{
    use GlobalApiResponse;
    public function login(LoginRequest $request){
        $user = User::where('email', $request->email)->first();

        if(!$user || !Hash::check($request->password, $user->password)){
            return response(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken($_ENV['APP_KEY'])->plainTextToken;

        return response(['user' => $user, 'token' => $token], 200);
    }
    public function index(Request $request){
        $query = User::query();
        if($request->has('search') && !empty($request->get("search"))){
            $query->where('name', 'LIKE', '%'.$request->get('search').'%')
            ->orWhere('email', 'LIKE', '%'.$request->get('search').'%');
        }
        return UserResource::collection($query->paginate(config('app.paginate_per_page', 50)));
    }
    public function logout(Request $request){
        \Laravel\Sanctum\PersonalAccessToken::where('tokenable_id', $request->user()->id)
            ->where('tokenable_type', get_class($request->user()))
            ->delete();
        return response()->json(['message' => 'logged out successfully']);
    }
    public function store(UsersRequest $request){
        $validate_data = $request->validated();

        DB::beginTransaction();
        try{
            $user = User::create([
                'name' => $validate_data['name'],
                'email' => $validate_data['email'],
                'password' => Hash::make($validate_data['password']),
                'firstname' => $validate_data['firstname'],
                'lastname' => $validate_data['lastname'],
                'position' => $validate_data['position'],
            ]);

            $user->roles()->attach((int) $validate_data['role_id']);

            DB::commit();
            return response()->json(['message' => 'Successfully created'],201);
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()],500);
        };
    }
    public function update(UsersRequest $request, $id){
        try{
            $validate_data = $request->validated();
            $user = User::findOrFail($id);

            DB::beginTransaction();
            $user->update([
                'name' => $validate_data['name'],
                'email' => $validate_data['email'],
                'firstname' => $validate_data['firstname'],
                'lastname' => $validate_data['lastname'],
                'position' => $validate_data['position']
            ]);

            $user->roles()->sync($request->role_id);

            DB::commit();
            return response()->json(['message' => 'Successfully updated'], 200);
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()],500);
        }
    }

    public function destroy($id){
        try{
            $user = User::findOrFail($id);
            DB::beginTransaction();
            $user->delete(); 
            DB::commit();
            return response()->json(['message' => 'Successfully deleted'], 200);
        }catch(\Exception $e){
            Log::error('Failed to delete user: '. $e->getMessage());
            return response()->json(['message' => 'Failed to delete user'],500);
        }
    }
}
