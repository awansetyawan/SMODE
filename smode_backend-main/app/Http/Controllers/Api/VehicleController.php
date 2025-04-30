<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Services\MqttManager; // Pastikan untuk mengimpor MqttManager

class VehicleController extends Controller
{
    // Mengambil daftar kendaraan milik pengguna yang terautentikasi
    public function list()
    {
        $user = auth()->user();

        $vehicles = Vehicle::select('id', 'merk', 'plate', 'image', 'mode_aman', 'mode_mesin')
            ->where('owner_id', $user->id)
            ->get();

        foreach ($vehicles as $vehicle) {
            $vehicle->image = url('storage/vehicles/' . $vehicle->image);
        }

        return response()->json($vehicles);
    }

    // Mengubah mode aman kendaraan
    public function modeaman(Request $request)
    {
        $mqtt = MqttManager::getClient(); // Dapatkan koneksi MQTT dari MqttManager

        // Validasi input
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()], 400);
        }

        $user = auth()->user();

        // Cek apakah kendaraan ada
        $isExist = Vehicle::where(['id' => $request->id, 'owner_id' => $user->id])->exists();

        if (!$isExist) {
            return response()->json(['message' => 'Vehicle not found'], 404);
        }

        DB::beginTransaction();
        try {
            $vehicle = Vehicle::where(['id' => $request->id, 'owner_id' => $user->id])->first();

            // Toggle mode aman
            $modeaman = $vehicle->mode_aman == 0 ? 'APK 1' : 'APK 0';

            // Publikasi ke topik MQTT
            $topic = 'vehicle/aman/' . $request->id;
            $mqtt->publish($topic, $modeaman, 1);
            
            
            if ($modeaman == 'APK 1'){
                $modeaman = 1;
            }elseif($modeaman == 'APK 0'){
                $modeaman = 0;
            }
            
            printf("print mode aman : %s\n", $modeaman);
            
            // Update mode aman di database
            Vehicle::where(['id' => $request->id, 'owner_id' => $user->id])->update(['mode_aman' => $modeaman]);

            DB::commit();
            return response(['message' => 'Mode Aman updated successfully']);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    // Mengubah mode mesin kendaraan
    public function modemesin(Request $request)
    {
        $mqtt = MqttManager::getClient(); // Dapatkan koneksi MQTT dari MqttManager

        // Validasi input
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()], 400);
        }

        $user = auth()->user();

        // Cek apakah kendaraan ada
        $isExist = Vehicle::where(['id' => $request->id, 'owner_id' => $user->id])->exists();

        if (!$isExist) {
            return response()->json(['message' => 'Vehicle not found'], 404);
        }

        DB::beginTransaction();
        try {
            $vehicle = Vehicle::where(['id' => $request->id, 'owner_id' => $user->id])->first();

            // Toggle mode mesin
            $modemesin = $vehicle->mode_mesin == 0 ? 1 : 0;

            // Publikasi ke topik MQTT
            $topic = 'vehicle/mesin/' . $request->id;
            $mqtt->publish($topic, $modemesin, 1);

            // Update mode mesin di database
            Vehicle::where(['id' => $request->id, 'owner_id' => $user->id])->update(['mode_mesin' => $modemesin]);

            DB::commit();
            return response(['message' => 'Mode Mesin updated successfully']);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
}