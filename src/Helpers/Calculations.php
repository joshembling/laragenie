<?php

namespace JoshEmbling\Laragenie\Helpers;

use JoshEmbling\Laragenie\Helpers;

trait Calculations
{
    use Helpers\Formatting;

    public function calculateCost(int $tokens)
    {
        $rate = 0.03;

        $cost = round(($tokens / 1000) * $rate, 2);

        return $cost;
    }

    public function costResponse(float $cost)
    {
        $this->newLine();
        $this->textWarning('Cost of this response: $'.$cost);
    }
}
