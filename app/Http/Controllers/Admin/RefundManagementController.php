<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppointmentRefundCase;
use App\Services\AppointmentLifecycleNotifier;
use App\Services\AppointmentEmailDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RefundManagementController extends Controller
{
    public function index(Request $request): View
    {
        $cases = AppointmentRefundCase::with(['appointment.patient', 'appointment.doctorProfile.user', 'payment'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->latest('requested_at')->paginate(25)->withQueryString();
        return view('admin.refunds.index', compact('cases'));
    }

    public function update(Request $request, AppointmentRefundCase $refundCase, AppointmentLifecycleNotifier $notifier, AppointmentEmailDispatcher $emails): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:processing,failed,rejected'],
            'provider_reference' => ['nullable', 'string', 'max:120'],
            'admin_note' => ['required', 'string', 'min:5', 'max:1000'],
        ]);
        if ($data['status'] === AppointmentRefundCase::PROCESSING && blank($data['provider_reference'])) {
            throw ValidationException::withMessages(['provider_reference' => 'Enter the Safepay refund reference after submitting the refund through Safepay.']);
        }
        DB::transaction(function () use ($request, $refundCase, $data): void {
            $locked = AppointmentRefundCase::whereKey($refundCase->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($locked->status, [AppointmentRefundCase::PENDING_REVIEW, AppointmentRefundCase::PROCESSING], true), 409);
            $locked->update([
                'status' => $data['status'], 'provider_reference' => $data['provider_reference'] ?? null,
                'admin_note' => $data['admin_note'], 'approved_by' => $request->user()->id,
            ]);
            $locked->appointment->events()->create([
                'actor_user_id' => $request->user()->id, 'event_type' => 'refund_'.$data['status'],
                'details' => ['refund_case_id' => $locked->id, 'amount_minor' => $locked->amount_minor, 'provider_reference' => $locked->provider_reference], 'created_at' => now(),
            ]);
        });
        $fresh = $refundCase->fresh('appointment');
        $notifier->notify($fresh->appointment, 'refund_'.$data['status'], 'Refund status updated', 'Your appointment refund is now marked '.$data['status'].'. '.($data['admin_note'] ?? ''), true);
        if ($data['status'] === AppointmentRefundCase::PROCESSING) $emails->dispatch($fresh->appointment, 'refund_processing', [$fresh->appointment->patient]);
        return back()->with('status', 'Refund case updated. A case is marked successful only by a verified Safepay refund webhook.');
    }
}
