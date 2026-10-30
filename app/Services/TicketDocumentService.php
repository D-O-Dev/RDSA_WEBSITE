<?php

namespace App\Services;

use App\Models\Participation;
use App\Models\Ticket;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\File;

class TicketDocumentService
{
    public function data(Ticket $ticket): array
    {
        $ticket->loadMissing(['participation.participant', 'participation.edition']);
        $usable = $ticket->status === Ticket::STATUS_VALID
            && $ticket->participation->status === Participation::STATUS_CONFIRMED;
        $qrDataUri = $usable ? app(TicketQrService::class)->dataUri($ticket) : null;
        $path = public_path('images/logo/logo-ticket.jpg');
        $logo = is_file($path) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($path)) : null;
        return compact('ticket', 'usable', 'qrDataUri', 'logo');
    }

    public function pdf(Ticket $ticket): string
    {
        $directory = storage_path('framework/dompdf');
        File::ensureDirectoryExists($directory);
        $options = new Options([
            'defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => false,
            'isPhpEnabled' => false, 'isJavascriptEnabled' => false,
            'tempDir' => $directory, 'fontCache' => $directory,
            'chroot' => [public_path('images'), public_path('fonts'), $directory, base_path('vendor/dompdf/dompdf/lib/fonts')],
        ]);
        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('tickets.pdf', $this->data($ticket))->render(), 'UTF-8');
        $pdf->setPaper('A5');
        $pdf->render();
        return $pdf->output();
    }
}
