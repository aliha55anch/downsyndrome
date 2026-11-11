<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentPrescription;

/** Small dependency-free, text-based PDF renderer for protected prescription downloads. */
class PrescriptionPdf
{
    public function render(Appointment $appointment, AppointmentPrescription $prescription): string
    {
        $appointment->loadMissing(['patient', 'doctorProfile.user']);
        $lines = [
            config('app.name', 'Little Steps').' | Digital Prescription',
            'Appointment: LS-APT-'.$appointment->id.'    Issued: '.$prescription->published_at?->format('Y-m-d'),
            'Patient: '.$appointment->patient->name,
            'Doctor: '.$appointment->doctorProfile->professional_title.' '.$appointment->doctorProfile->user->name.' | '.$appointment->doctorProfile->specialty,
            'Consultation: '.$appointment->localStartsAt()->format('Y-m-d g:i A').' ('.$appointment->timezone.')',
            '', 'DIAGNOSIS', $prescription->diagnosis ?: 'Not provided', '', 'MEDICINES',
        ];
        foreach ($prescription->medicines ?? [] as $index => $medicine) {
            $lines[] = ($index + 1).'. '.($medicine['name'] ?? '').' | '.($medicine['dosage'] ?? '').' | '.($medicine['frequency'] ?? '').' | '.($medicine['duration'] ?? '');
        }
        if (empty($prescription->medicines)) $lines[] = 'None prescribed';
        foreach ([['INSTRUCTIONS', $prescription->instructions], ['ADDITIONAL NOTES', $prescription->notes], ['FOLLOW UP', $prescription->follow_up]] as [$heading, $value]) {
            $lines[] = ''; $lines[] = $heading; $lines[] = $value ?: 'None';
        }
        $content = "BT\n/F1 10 Tf\n50 790 Td\n";
        foreach (array_slice($lines, 0, 48) as $line) {
            $plain = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $line) ?: '';
            $wrapped = explode("\n", wordwrap($plain, 92, "\n", true));
            foreach ($wrapped as $segment) $content .= '('.$this->escape($segment).") Tj\n0 -16 Td\n";
        }
        $content .= "ET";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            '<< /Length '.strlen($content)." >>\nstream\n".$content."\nendstream",
        ];
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n"; $offsets = [0];
        foreach ($objects as $i => $object) { $offsets[] = strlen($pdf); $pdf .= ($i + 1)." 0 obj\n".$object."\nendobj\n"; }
        $xref = strlen($pdf); $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) $pdf .= sprintf('%010d 00000 n ', $offset)."\n";
        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF";
    }

    private function escape(string $text): string { return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text); }
}
