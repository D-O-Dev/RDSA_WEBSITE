<?php

namespace App\Support;

use BaconQrCode\Encoder\ByteMatrix;
use BaconQrCode\Renderer\Module\ModuleInterface;
use BaconQrCode\Renderer\Module\RoundnessModule;
use BaconQrCode\Renderer\Path\Close;
use BaconQrCode\Renderer\Path\EllipticArc;
use BaconQrCode\Renderer\Path\Line;
use BaconQrCode\Renderer\Path\Move;
use BaconQrCode\Renderer\Path\Path;

/**
 * Conserver les arrondis du générateur avec des courbes comprises par Dompdf.
 * Les arcs produits par RoundnessModule sont des quarts de cercle.
 */
class RoundedQrModule implements ModuleInterface
{
    public function createPath(ByteMatrix $matrix): Path
    {
        $path = new Path;
        $x = $y = $startX = $startY = 0.0;
        $k = 4 / 3 * tan(M_PI / 8);
        foreach ((new RoundnessModule(RoundnessModule::STRONG))->createPath($matrix) as $operation) {
            if ($operation instanceof Move) {
                $x = $startX = $operation->getX();
                $y = $startY = $operation->getY();
                $path = $path->move($x, $y);
            } elseif ($operation instanceof Line) {
                $x = $operation->getX();
                $y = $operation->getY();
                $path = $path->line($x, $y);
            } elseif ($operation instanceof EllipticArc) {
                $endX = $operation->getX();
                $endY = $operation->getY();
                $clockwise = $operation->isSweep();
                $centerX = $x;
                $centerY = $endY;
                if ((($endX - $x) * ($endY - $y) > 0) !== $clockwise) {
                    $centerX = $endX;
                    $centerY = $y;
                }
                $direction = $clockwise ? 1 : -1;
                $path = $path->curve(
                    $x - $direction * $k * ($y - $centerY),
                    $y + $direction * $k * ($x - $centerX),
                    $endX + $direction * $k * ($endY - $centerY),
                    $endY - $direction * $k * ($endX - $centerX),
                    $endX, $endY
                );
                $x = $endX;
                $y = $endY;
            } elseif ($operation instanceof Close) {
                $path = $path->close();
                $x = $startX;
                $y = $startY;
            }
        }
        return $path;
    }
}
