<?php

use App\Http\Controllers\AttendanceController;
use App\Models\ShiftSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $setting = ShiftSetting::where('setting_key', AttendanceController::SETTING_KEY)->first();
        if ($setting && is_array($setting->setting_value)) {
            $val = $setting->setting_value;
            $val['studio_name'] = $val['studio_name'] ?? 'AIKu Studio';
            $val['studio_address'] = AttendanceController::STUDIO_DEFAULT_ADDRESS;
            $val['studio_lat'] = AttendanceController::STUDIO_DEFAULT_LAT;
            $val['studio_lng'] = AttendanceController::STUDIO_DEFAULT_LNG;
            $val['geofence_radius_m'] = $val['geofence_radius_m'] ?? 150;
            $val['updated_at'] = now()->toIso8601String();

            $setting->update(['setting_value' => $val]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep coordinates
    }
};
