<?php

namespace App\Services;

use App\Models\Participation;
use App\Models\SiteContent;
use App\Models\Ticket;
use App\Support\ImageSource;
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
        $source = SiteContent::current()['ticket_logo_path'];
        $path = str_starts_with($source, 'storage/rdsa/visuals/') ? storage_path('app/public/'.substr($source, 8)) : public_path($source);
        $valid = ImageSource::valid($source) && ! str_starts_with($source, 'https://');
        $logo = $valid && is_file($path) ? 'data:'.mime_content_type($path).';base64,'.base64_encode(file_get_contents($path)) : null;

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
