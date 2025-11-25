<?php

namespace App\Enums;

/**
 * Distribution methods for gyvatukas circulation cost allocation.
 * 
 * @see \App\Services\GyvatukasCalculator::distributeCirculationCost()
 */
enum DistributionMethod: string
{
    /**
     * Equal distribution: Divide cost equally among all apartments (C/N)
     */
    case EQUAL = 'equal';

    /**
     * Area-based distribution: Divide cost proportionally by apartment area (C × A_i / Σ A_j)
     */
    case AREA = 'area';
}
