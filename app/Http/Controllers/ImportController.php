<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\LegacyImportService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use ZipArchive;

class ImportController extends Controller
{
    public function __construct(private LegacyImportService $importer) {}

    public function index()
    {
        abort_unless(auth()->user()->can('manage settings'), 403);

        return view('import.index', [
            'legacyUsers' => User::whereNotNull('legacy_id')->count(),
            'lastImportedAt' => User::whereNotNull('legacy_id')->latest('updated_at')->value('updated_at'),
        ]);
    }

    public function run(Request $request)
    {
        abort_unless(auth()->user()->can('manage settings'), 403);

        $request->validate([
            'dump' => ['required', 'file', 'max:51200', 'mimes:sql,txt,zip'],
            'purge_demo' => ['nullable', 'boolean'],
        ]);

        @set_time_limit(600);
        @ini_set('memory_limit', '512M');

        try {
            $path = $this->resolveSqlPath($request->file('dump'));

            $summary = $this->importer->importFromFile(
                $path,
                ['purge_demo' => $request->boolean('purge_demo')],
                auth()->id()
            );

            ActivityLogger::log('legacy.import', null, 'Imported legacy data', $summary);

            return back()->with('import_summary', $summary)
                ->with('success', 'Legacy data imported successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Import failed: '.$e->getMessage());
        }
    }

    /**
     * Accept a raw .sql/.txt file or a .zip containing one, returning a path to the .sql.
     */
    private function resolveSqlPath(UploadedFile $file): string
    {
        $tmpDir = storage_path('app/imports');
        if (! is_dir($tmpDir)) {
            @mkdir($tmpDir, 0755, true);
        }

        $ext = strtolower($file->getClientOriginalExtension());

        if ($ext === 'zip') {
            $zip = new ZipArchive;
            if ($zip->open($file->getRealPath()) !== true) {
                throw new \RuntimeException('Could not open the ZIP archive.');
            }
            $sqlName = null;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (str_ends_with(strtolower($name), '.sql')) {
                    $sqlName = $name;
                    break;
                }
            }
            if (! $sqlName) {
                $zip->close();
                throw new \RuntimeException('No .sql file found inside the ZIP.');
            }
            $zip->extractTo($tmpDir, $sqlName);
            $zip->close();

            return $tmpDir.'/'.$sqlName;
        }

        $dest = $tmpDir.'/import_'.uniqid().'.sql';
        $file->move($tmpDir, basename($dest));

        return $dest;
    }
}
