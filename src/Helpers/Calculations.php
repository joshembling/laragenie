<?php

namespace JoshEmbling\Laragenie\Helpers;

trait Calculations
{
    public function calculateCost(int $tokens)
    {
        $rate = 0.03;

        $cost = round(($tokens / 1000) * $rate, 2);

        $this->newLine();
        $this->warn('Cost of this response: $' . $cost);
        $this->newLine();
    }
}
