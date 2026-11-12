<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\DoctorProfile;
use App\Models\DoctorVerificationDocument;
use App\Models\User;
use App\Notifications\DoctorVerificationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'document_type' => ['required', 'in:license,qualification,additional'],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'mimetypes:application/pdf,image/jpeg,image/png', 'max:10240'],
        ]);
        $profile = $request->user()->doctorProfile()->firstOrFail();
        $file = $request->file('document');
        $path = $file->store('doctor-verification/'.$profile->id, 'local');
        abort_if(! $path, 500, 'The document could not be stored.');

        try {
            DB::transaction(function () use ($data, $file, $profile, $path, $request): void {
                $profile = DoctorProfile::whereKey($profile->id)->lockForUpdate()->firstOrFail();
                $previous = $profile->documents()->where('document_type', $data['document_type'])->where('is_current', true)->lockForUpdate()->get();
                $version = ((int) $profile->documents()->where('document_type', $data['document_type'])->max('version')) + 1;
                foreach ($previous as $document) {
                    $document->update(['is_current' => false, 'status' => 'replaced']);
                }
                $profile->documents()->create([
                    'document_type' => $data['document_type'],
                    'original_name' => basename($file->getClientOriginalName()),
                    'storage_path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size_bytes' => $file->getSize(),
                    'version' => $version,
                    'status' => 'pending',
                    'is_current' => true,
                ]);
                $from = $profile->status;
                if ($profile->status === DoctorProfile::STATUS_APPROVED || $profile->status === DoctorProfile::STATUS_UNDER_REVIEW) {
                    $profile->update([
                        'status' => DoctorProfile::STATUS_PENDING,
                        'reviewed_by' => null,
                        'reviewed_at' => null,
                        'rejection_reason' => null,
                    ]);
                    $request->user()->forceFill(['role' => User::ROLE_PENDING_DOCTOR])->save();
                }
                $profile->history()->create([
                    'actor_id' => $request->user()->id,
                    'event_type' => 'document_replaced',
                    'from_status' => $from,
                    'to_status' => $profile->fresh()->status,
                    'reason' => 'A verification document was uploaded or replaced.',
                    'details' => ['document_type' => $data['document_type'], 'version' => $version],
                    'created_at' => now(),
                ]);
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return back()->with('status', 'The document was uploaded securely. Your application status is shown on this page.');
    }

    public function download(Request $request, DoctorVerificationDocument $document): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $document->doctorProfile()->where('user_id', $user->id)->exists(), 404);
        abort_unless(Storage::disk('local')->exists($document->storage_path), 404);

        return Storage::disk('local')->download($document->storage_path, $document->original_name, [
            'Content-Type' => $document->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function resubmit(Request $request): RedirectResponse
    {
        $profile = $request->user()->doctorProfile()->firstOrFail();
        abort_unless($profile->status === DoctorProfile::STATUS_REJECTED, 409);

        $requiredDocs = $profile->currentDocuments()->whereIn('document_type', ['license', 'qualification'])->get()->keyBy('document_type');
        $missing = collect(['license', 'qualification'])->filter(fn ($type) => ! isset($requiredDocs[$type]) || ! in_array($requiredDocs[$type]->status, ['pending', 'approved'], true))->values();
        if ($missing->isNotEmpty()) {
            return back()->withErrors(['documents' => 'Replace the rejected required documents before resubmitting: '.$missing->implode(', ').'.']);
        }

        DB::transaction(function () use ($profile, $request): void {
            $from = $profile->status;
            $profile->update(['status' => DoctorProfile::STATUS_PENDING, 'submitted_at' => now(), 'reviewed_at' => null, 'reviewed_by' => null, 'rejection_reason' => null]);
            $request->user()->forceFill(['role' => User::ROLE_PENDING_DOCTOR])->save();
            $profile->history()->create([
                'actor_id' => $request->user()->id,
                'event_type' => 'application_resubmitted',
                'from_status' => $from,
                'to_status' => DoctorProfile::STATUS_PENDING,
                'reason' => 'Doctor resubmitted the application after updating requirements.',
                'created_at' => now(),
            ]);
        });
        $request->user()->notify(new DoctorVerificationNotification('Application resubmitted', 'Your updated application is pending review.', route('doctor.dashboard')));

        return back()->with('status', 'Your updated application was resubmitted for review.');
    }
}
