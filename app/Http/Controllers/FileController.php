<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileController extends Controller
{
    public function index(Request $request)
    {
        $files = File::query()
            ->orderBy('folder')
            ->orderBy('filename')
            ->get();

        $filesByFolder = $files->groupBy(fn (File $f) => $f->folder ?: 'general')
            ->sortKeys();

        $folderCounts = $filesByFolder->map->count();

        $currentFolder = $request->query('folder');
        if ($currentFolder === '' || $currentFolder === null) {
            $filteredFiles = null;
        } else {
            $filteredFiles = $files->where('folder', $currentFolder)->values();
        }

        $presetLabels = config('repository.folder_presets', []);

        return view('files.index', compact(
            'files',
            'filesByFolder',
            'folderCounts',
            'currentFolder',
            'filteredFiles',
            'presetLabels'
        ));
    }

    public function upload(Request $request)
    {
        $presetKeys = implode(',', array_keys(config('repository.folder_presets', [])));

        $request->validate([
            'file' => 'required|file|max:20480',
            'folder_preset' => 'required|string|in:'.$presetKeys.',custom',
            'folder_custom' => 'nullable|required_if:folder_preset,custom|string|max:80',
        ]);

        $folder = $this->resolveUploadFolder(
            $request->input('folder_preset'),
            $request->input('folder_custom')
        );

        $upload = $request->file('file');
        $originalName = $upload->getClientOriginalName();
        $storedName = $this->uniqueFilename($folder, $originalName);

        Storage::disk('public')->makeDirectory('files/'.$folder);

        $relativePath = $upload->storeAs('files/'.$folder, $storedName, 'public');

        File::create([
            'folder' => $folder,
            'filename' => $originalName,
            'filepath' => $relativePath,
        ]);

        return redirect()
            ->route('files.index', ['folder' => $folder])
            ->with('success', 'File uploaded successfully.');
    }

    public function view($id)
    {
        $file = File::findOrFail($id);
        $path = $file->absolutePath();

        if (! is_file($path)) {
            return back()->with('error', 'File does not exist.');
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension === 'pdf') {
            return response()->file($path);
        }

        if (in_array($extension, ['doc', 'docx'], true)) {
            return response()->download($path);
        }

        return back()->with('error', 'Unsupported file type.');
    }

    public function download($id)
    {
        $file = File::findOrFail($id);
        $path = $file->absolutePath();

        if (! is_file($path)) {
            return back()->with('error', 'File does not exist: '.$path);
        }

        $extension = strtolower(pathinfo($file->filename, PATHINFO_EXTENSION));
        $forcePdf = ($extension !== 'pdf');
        $downloadName = $forcePdf
            ? pathinfo($file->filename, PATHINFO_FILENAME).'.pdf'
            : $file->filename;

        return response()->download($path, $downloadName, [
            'Content-Type' => mime_content_type($path) ?: 'application/octet-stream',
        ]);
    }

    public function delete($id)
    {
        $file = File::findOrFail($id);
        Storage::disk('public')->delete($file->publicDiskPath());
        $file->delete();

        return back()->with('success', 'File deleted.');
    }

    private function resolveUploadFolder(string $preset, ?string $custom): string
    {
        if ($preset === 'custom') {
            $slug = Str::slug((string) $custom);

            return $slug !== '' ? $slug : 'general';
        }

        return $preset;
    }

    private function uniqueFilename(string $folder, string $originalName): string
    {
        $base = pathinfo($originalName, PATHINFO_FILENAME);
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $ext = $ext !== '' ? '.'.$ext : '';

        $storedName = $base.$ext;
        $n = 0;

        while (Storage::disk('public')->exists('files/'.$folder.'/'.$storedName)) {
            $n++;
            $storedName = $base.'-'.$n.$ext;
        }

        return $storedName;
    }
}
