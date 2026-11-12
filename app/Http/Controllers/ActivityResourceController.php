<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityResourceController extends Controller
{
    public function download(Activity $activity, string $type): StreamedResponse
    {
        abort_unless($activity->isPublished(), 404);
        abort_unless(in_array($type, ['pptx', 'pdf'], true), 404);

        $path = $type === 'pptx' ? $activity->pptx_path : $activity->pdf_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path);
    }
}
