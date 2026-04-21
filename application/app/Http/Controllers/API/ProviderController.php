<?php

namespace App\Http\Controllers\API;

use App\Enums\ProviderName;
use App\Http\Controllers\Controller;
use App\Services\MachineTranslation\MachineTranslationPickerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class ProviderController extends Controller
{
    public function __construct(
        private readonly MachineTranslationPickerService $picker
    ) {
    }

    #[OA\Get(
        path: '/providers',
        summary: 'List available machine translation providers',
        tags: ['Providers'],
        responses: [
            new OA\Response(response: 200, description: 'List of providers'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(): JsonResponse
    {
        $providers = collect(ProviderName::cases())
            ->filter(fn (ProviderName $provider) => Auth::hasPrivilege($provider->requiredPrivilege()))
            ->map(function (ProviderName $provider) {
                $service = $this->picker->pick($provider->value);

                return [
                    'name'                     => $provider->value,
                    'label'                    => $provider->label(),
                    'supports_file_translation' => $service->supportsFileTranslation(),
                ];
            })
            ->values();

        return response()->json(['data' => $providers]);
    }

    #[OA\Get(
        path: '/providers/{provider}/options',
        summary: 'Get provider-specific options for the UI form',
        tags: ['Providers'],
        parameters: [
            new OA\Parameter(name: 'provider', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Provider options'),
            new OA\Response(response: 404, description: 'Provider not found'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function options(string $provider): JsonResponse
    {
        $validProviders = array_column(ProviderName::cases(), 'value');

        if (! \in_array($provider, $validProviders)) {
            return response()->json(['message' => 'Provider not found.'], 404);
        }

        $providerEnum = ProviderName::from($provider);

        if (! Auth::hasPrivilege($providerEnum->requiredPrivilege())) {
            abort(403);
        }

        $service = $this->picker->pick($provider);

        return response()->json(['data' => $service->getOptions()]);
    }
}
