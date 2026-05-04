<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\UpdateSettingsRequest;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Policies\SettingPolicy;
use Illuminate\Support\Facades\DB;

class InstitutionSettingsController extends Controller
{
    public function show(): JsonResponse
    {
        $this->authorize('view', Setting::class);

        $settings = $this->getBaseQuery()
            ->where('key', 'LIKE', 'azure_openai_%')
            ->pluck('value', 'key');

        $data = [
            'endpoint'          => $settings->get('azure_openai_endpoint'),
            'tenant_id'         => $settings->get('azure_openai_tenant_id'),
            'application_id'    => $settings->get('azure_openai_application_id'),
            'deployment'        => $settings->get('azure_openai_deployment'),
            'has_api_key'       => $settings->get('azure_openai_api_key') !== null,
            'has_client_secret' => $settings->get('azure_openai_client_secret') !== null,
            'show_confirmation' => (bool) $settings->get('azure_openai_show_confirmation'),
        ];

        return response()->json(['data' => $data]);
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $this->authorize('update', Setting::class);

        DB::transaction(function () use ($request) {
            $settings = $this->getBaseQuery()
                ->where('key', 'LIKE', 'azure_openai_%')
                ->get()
                ->keyBy('key');

            foreach ($request->validated() as $field => $value) {
                $setting = $settings->get("azure_openai_{$field}");

                if ($setting == null) {
                    $setting = new Setting();
                    $setting->institution_id = Auth::user()->institutionId;
                    $setting->key = "azure_openai_{$field}";
                }

                $setting->value = $value;
                $setting->save();
            }
        });

        return $this->show();
    }

    public static function getBaseQuery(): Builder
    {
        return Setting::getModel()->withGlobalScope('policy', SettingPolicy::scope());
    }
}
