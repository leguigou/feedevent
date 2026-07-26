<?php

namespace App\Http\Controllers;

use App\Models\ConnectorToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class ConnectorController extends Controller
{
    public function index(Request $request): View
    {
        return view('connector.index', [
            'tokens' => $request->user()
                ->connectorTokens()
                ->latest()
                ->get(),
        ]);
    }

    public function download(Request $request): BinaryFileResponse
    {
        $plainToken = Str::random(80);

        $request->user()->connectorTokens()->create([
            'name' => 'Extension Chrome du '.now()->format('d/m/Y H:i'),
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDays(180),
        ]);

        $archivePath = tempnam(sys_get_temp_dir(), 'feedevent-connector-');

        if ($archivePath === false) {
            abort(500, 'Impossible de préparer le téléchargement.');
        }

        $zip = new ZipArchive;

        if ($zip->open($archivePath, ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Impossible de créer l’archive du connecteur.');
        }

        $extensionPath = base_path('browser-extension');
        $manifest = json_decode(file_get_contents($extensionPath.'/manifest.json'), true, flags: JSON_THROW_ON_ERROR);
        $manifest['host_permissions'] = [$this->hostPermission($request)];

        $zip->addFromString(
            'feedevent-connector/manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        );

        foreach (['popup.html', 'popup.css', 'popup.js'] as $file) {
            $zip->addFile($extensionPath.'/'.$file, 'feedevent-connector/'.$file);
        }

        $configuration = [
            'apiUrl' => $request->getSchemeAndHttpHost().'/api/connector/events',
            'appUrl' => $request->getSchemeAndHttpHost(),
            'token' => $plainToken,
        ];

        $zip->addFromString(
            'feedevent-connector/config.js',
            'globalThis.FEEDEVENT_CONNECTOR_CONFIG = Object.freeze('
                .json_encode($configuration, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                .');',
        );
        $zip->close();

        return response()
            ->download($archivePath, 'feedevent-connecteur-chrome.zip', [
                'Content-Type' => 'application/zip',
                'Cache-Control' => 'no-store, private',
            ])
            ->deleteFileAfterSend(true);
    }

    public function revoke(Request $request, ConnectorToken $token): RedirectResponse
    {
        abort_unless($token->user_id === $request->user()->id, 404);

        $token->update(['revoked_at' => now()]);

        return back()->with('status', 'connector-revoked');
    }

    private function hostPermission(Request $request): string
    {
        $host = $request->getHost();

        return $request->getScheme().'://'.$host.'/*';
    }
}
