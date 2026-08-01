<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SiteBranding\PublishSiteBrandingRequest;
use App\Http\Requests\SiteBranding\SaveSiteBrandingRequest;
use App\Models\SiteBrandingVersion;
use App\Services\SiteBrandingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class SiteSettingController extends Controller
{
    public function __construct(
        private readonly SiteBrandingService $branding,
    ) {}

    public function index(): JsonResponse
    {
        return response()
            ->json($this->branding->active())
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    public function versions(): JsonResponse
    {
        $versions = SiteBrandingVersion::query()
            ->with(['creator:id,name', 'publisher:id,name'])
            ->latest('id')
            ->get()
            ->map(fn (SiteBrandingVersion $version) => $this->branding->presentVersion($version));

        return response()->json(['data' => $versions]);
    }

    public function store(SaveSiteBrandingRequest $request): JsonResponse
    {
        $version = $this->branding->saveVersion(
            $request->validated(),
            $this->uploadedFiles($request),
            $request->user(),
        );

        return response()->json([
            'message' => $this->successMessage($version),
            'settings' => $this->branding->active(),
            'version' => $this->branding->presentVersion($version),
        ], 201);
    }

    /**
     * Backward-compatible endpoint for the previous settings screen.
     * Requests without action/label are treated as immediate publications.
     */
    public function update(SaveSiteBrandingRequest $request): JsonResponse
    {
        $version = $this->branding->saveVersion(
            $request->validated(),
            $this->uploadedFiles($request),
            $request->user(),
        );

        return response()->json([
            'message' => $this->successMessage($version),
            'settings' => $this->branding->active(),
            'version' => $this->branding->presentVersion($version),
        ]);
    }

    public function updateVersion(
        SaveSiteBrandingRequest $request,
        SiteBrandingVersion $brandingVersion,
    ): JsonResponse {
        $version = $this->branding->saveVersion(
            $request->validated(),
            $this->uploadedFiles($request),
            $request->user(),
            $brandingVersion,
        );

        return response()->json([
            'message' => $this->successMessage($version),
            'settings' => $this->branding->active(),
            'version' => $this->branding->presentVersion($version),
        ]);
    }

    public function publish(
        PublishSiteBrandingRequest $request,
        SiteBrandingVersion $brandingVersion,
    ): JsonResponse {
        $effectiveFrom = $request->filled('effective_from')
            ? $request->date('effective_from')
            : $brandingVersion->effective_from;

        $version = $this->branding->publish(
            $brandingVersion,
            $request->user(),
            $effectiveFrom,
        );

        return response()->json([
            'message' => $this->successMessage($version),
            'settings' => $this->branding->active(),
            'version' => $this->branding->presentVersion($version),
        ]);
    }

    public function rollback(
        Request $request,
        SiteBrandingVersion $brandingVersion,
    ): JsonResponse {
        abort_unless($request->user()?->role === 'Superadmin', 403);

        $version = $this->branding->rollback(
            $brandingVersion,
            $request->user(),
        );

        return response()->json([
            'message' => 'Identitas situs berhasil dikembalikan melalui versi rollback baru.',
            'settings' => $this->branding->active(),
            'version' => $this->branding->presentVersion($version),
        ]);
    }

    public function destroy(
        Request $request,
        SiteBrandingVersion $brandingVersion,
    ): JsonResponse {
        abort_unless($request->user()?->role === 'Superadmin', 403);

        $this->branding->deleteVersion($brandingVersion, $request->user());

        return response()->json([
            'message' => 'Draft identitas berhasil dihapus.',
        ]);
    }

    /** @return array<string, UploadedFile|null> */
    private function uploadedFiles(SaveSiteBrandingRequest $request): array
    {
        return [
            'app_logo' => $request->file('app_logo'),
            'instansi_logo' => $request->file('instansi_logo'),
            'favicon' => $request->file('favicon'),
        ];
    }

    private function successMessage(SiteBrandingVersion $version): string
    {
        return match ($version->status) {
            SiteBrandingVersion::STATUS_PUBLISHED => 'Identitas situs berhasil dipublikasikan.',
            SiteBrandingVersion::STATUS_SCHEDULED => 'Publikasi identitas berhasil dijadwalkan.',
            default => 'Draft identitas situs berhasil disimpan.',
        };
    }
}
