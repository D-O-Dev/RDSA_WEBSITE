<?php

namespace App\Services;

use App\Models\Ticket;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Eye\ModuleEye;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use App\Support\RoundedQrModule;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TicketQrService
{
    public function dataUri(Ticket $ticket): string
    {
        $module = new RoundedQrModule;
        $style = new RendererStyle(640, 4, $module, new ModuleEye($module),
            Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(16, 45, 105)));
        $writer = new Writer(new ImageRenderer($style, new SvgImageBackEnd));
        $svg = $writer->writeString(app(TicketService::class)->qrPayload($ticket), 'UTF-8', ErrorCorrectionLevel::M());
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
