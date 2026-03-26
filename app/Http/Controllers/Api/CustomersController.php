<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomersRequest;
use App\Http\Requests\ExcelUploadRequest;
use App\Http\Resources\CustomersResource;
use App\Imports\CustomersImport;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class CustomersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Customer::query()->orderBy('id', 'desc');
        if ($request->has('search') &&!empty($request->get('search'))) {
            $query = $query->where('nomor_pelanggan', 'LIKE', '%'.$request->get('search').'%')
                ->orWhere('nama', 'LIKE', '%'.$request->get('search').'%')
                ->orWhere('kota_pelayanan', 'LIKE', '%'.$request->get('search').'%');
        }
        return CustomersResource::collection($query->paginate(config('app.paginate_per_page', 50)));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CustomersRequest $request)
    {
        try{
            $customer = Customer::create($request->validated());
            return response()->json(['message' => 'Successfully created', new CustomersResource($customer)]);
        }catch(\Exception $e){
            return response()->json([
                'message' => 'Failed to create customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try{
            $customer = Customer::find($id);
            if($customer){
                return new CustomersResource($customer);
            } else {
                return response()->json(['message' => 'Customer not found'], 404);
            }
        }catch(\Exception $e){
            return response()->json([
                'message' => 'Failed to retrieve customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CustomersRequest $request, string $id)
    {
        try{
            $customer = Customer::find($id);
            if($customer){
                $customer->update($request->validated());
                return response()->json(['message' => 'Successfully updated', new CustomersResource($customer)]);
            } else {
                return response()->json(['message' => 'Customer not found'], 404);
            }
        }catch(\Exception $e) {
            return response()->json([
                'message' => 'Failed to update customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try{
            $customer = Customer::find($id);
            if($customer){
                $customer->delete();
                return response()->json(['message' => 'Successfully deleted']);
            }else {
                return response()->json(['message' => 'Customer not found'], 404);
            }
        }catch(\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function upload(ExcelUploadRequest $request){
        try{
            if(!$request->hasFile('file')){
                return response()->json([
                   'error' => 'No file provided'
                ], 400);
            }

            $file = $request->file('file');
            Excel::import(new CustomersImport, $file);

            return response()->json([
                'message' => 'File uploaded successfully',
            ]);
        }catch(\Exception $e){
            Log::info($e->getMessage());
            return response()->json([
                'error' => 'Failed to upload file',
               'message' => $e->getMessage()
            ], 500);
        }
    }
}
