<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for Wordpress / Woocommerce
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */


namespace wooScalexpert\Helper\API;

class SimulationFormatter
{

    /**
     * @param $simulateResponse
     * @param $designSolution
     * @param $groupSolutions
     * @param $sortBySolutionCode
     *
     * @return array
     */
    public static function normalizeSimulations($simulateResponse, $designSolution = array(), $groupSolutions = FALSE, $sortBySolutionCode = FALSE): array
    {
        $simulationsFullData = array();
        $solutionSimulations = $simulateResponse['contentsDecoded']['solutionSimulations'];
        if (empty($solutionSimulations)) {
            return $simulationsFullData;
        }

        foreach ($solutionSimulations as $k => $solutionSimulation) {
            $solutionCode = $solutionSimulation['solutionCode'];
            if (!empty($designSolution)) {
                $solutionSimulations[$k]['designConfiguration'] = (!empty($designSolution[$solutionCode])) ? $designSolution[$solutionCode] : '';
            }

            // Add context data
            $solutionSimulations[$k]['isLongFinancingSolution'] = static::isLongFinancingSolution($solutionCode);
            $solutionSimulations[$k]['hasFeesSolution'] = static::hasFeesFinancingSolution($solutionCode);
            $solutionSimulations[$k]['hasFeesOnFirstInstallment'] =
                $solutionSimulations[$k]['hasFeesSolution']
                && 0 < $solutionSimulations[$k]['simulations'][0]['feesAmount'];


            foreach ($solutionSimulation['simulations'] as $j => $simulation) {
                // Refactor percentage data
                $solutionSimulations[$k]['simulations'][$j]['effectiveAnnualPercentageRateFormatted'] = $simulation['effectiveAnnualPercentageRate'] . '%';
                $solutionSimulations[$k]['simulations'][$j]['nominalPercentageRateFormatted'] = $simulation['nominalPercentageRate'] . '%';

                // Refactor price data
                $solutionSimulations[$k]['simulations'][$j]['totalCostFormatted'] = wc_price($simulation['totalCost']);
                $solutionSimulations[$k]['simulations'][$j]['dueTotalAmountFormatted'] = wc_price($simulation['dueTotalAmount']);
                $solutionSimulations[$k]['simulations'][$j]['feesAmountFormatted'] = wc_price($simulation['feesAmount']);
                foreach ($simulation['installments'] as $i => $installment) {
                    // Refactor price data
                    $solutionSimulations[$k]['simulations'][$j]['installments'][$i]['amountFormatted'] = wc_price($installment['amount']);
                }
            }

        }

        if ($groupSolutions) {
            // Group financing solutions by having fees or not
            // No chances that this will be used
            foreach ($solutionSimulations as $solutionSimulation) {
                foreach ($solutionSimulation['simulations'] as $simulation) {
                    $simulation['designConfiguration'] = $solutionSimulation['designConfiguration'];
                    $simulation['isLongFinancingSolution'] = $solutionSimulation['isLongFinancingSolution'];
                    $simulation['hasFeesOnFirstInstallment'] =
                        $solutionSimulation['hasFeesSolution']
                        && 0 < $simulation['feesAmount'];

                    if ($sortBySolutionCode) {
                        $simulationsFullData[$solutionSimulation['solutionCode']][] = $simulation;
                    } else {
                        $simulationsFullData['all'][] = $simulation;
                    }

                }
            }

            // Sort by duration
            if ($sortBySolutionCode) {
                foreach ($simulationsFullData as $simulationsFullDatum) {
                    static::sortSolutionsByDuration($simulationsFullDatum);
                }
            } else {
                static::sortSolutionsByDuration($simulationsFullData['all']);
            }
        } else {

            foreach ($solutionSimulations as $key => $solutionSimulation) {
                $solutionCode = $solutionSimulation['solutionCode'];
                if (!empty($designSolution[$solutionCode])) {
                    $nbDurations = count($solutionSimulation['simulations']);

                    for ($i = 0; $i < $nbDurations; $i++) {
                        $simulationsFullData[$solutionCode . '.' . $i] = $solutionSimulation['simulations'][$i];
                        $simulationsFullData[$solutionCode . '.' . $i]['designConfiguration'] = $designSolution[$solutionCode];
                        $simulationsFullData[$solutionCode . '.' . $i]['isLongFinancingSolution'] = $solutionSimulation['isLongFinancingSolution'];
                        $simulationsFullData[$solutionCode . '.' . $i]['hasFeesOnFirstInstallment'] = $solutionSimulation['hasFeesOnFirstInstallment'];
                    }
                }
            }
        }

        return $simulationsFullData;
    }


    /**
     * @param $solutionCode
     *
     * @return bool
     */
    public static function isFrenchLongFinancingSolution($solutionCode)
    {
        return in_array($solutionCode, [
            'SCFRLT-TXPS',
            'SCFRLT-TXNO',
            'SCFRLT-TXTS',
        ], TRUE);
    }

    /**
     * @param $solutionCode
     *
     * @return bool
     */
    public static function isDeutschLongFinancingSolution($solutionCode)
    {
        return in_array($solutionCode, [
            'SCDELT-DXTS',
            'SCDELT-DXCO',
        ], TRUE);
    }

    /**
     * @param string $solutionCode
     *
     * @return bool
     */
    public static function isLongFinancingSolution(string $solutionCode): bool
    {
        return
            static::isFrenchLongFinancingSolution($solutionCode)
            || static::isDeutschLongFinancingSolution($solutionCode);
    }

    /**
     * @param string $solutionCode
     *
     * @return bool
     */
    public static function hasFeesFinancingSolution(string $solutionCode): bool
    {
        return in_array($solutionCode, [
            'SCFRSP-3XPS',
            'SCFRSP-4XPS',
            'SCFRLT-TXPS',
        ], TRUE);
    }

    /**
     * @param $solutions
     *
     * @return void
     */
    private static function sortSolutionsByDuration(&$solutions)
    {
        uasort($solutions, function ($a, $b) {
            return $a['duration'] > $b['duration'];
        });
    }


    /**
     * @param $eligibleSimulations
     * @param $eligibleSolutions
     * @param false $isPayment
     * @param bool $isCart
     * @return array
     *
     */
    public function buildDesignData($eligibleSimulations, $eligibleSolutions, bool $isPayment = FALSE, bool $isCart = FALSE): array
    {

        $solutionCodes = [];
        $designSolutions = [];
        $solutionsFullData = [];

        foreach ($eligibleSolutions['contentsDecoded']['solutions'] as $solution) {
            $solutionsFullData[$solution['solutionCode']] = $solution;
        }

        foreach ($eligibleSimulations as $eligibleSimulation) {
            $solutionCode = $eligibleSimulation['solutionCode'];

            if (isset($solutionsFullData[$solutionCode])) {
                // Built available solution codes array
                $solutionCodes[] = $solutionCode;
                // Prepare design config variables for front
                $designSolutions[$solutionCode] = $solutionsFullData[$solutionCode]['communicationKit'];
                $designBO = get_option('sg_scalexpert_design_' . $solutionCode);
                if ($isPayment) {
                    $designSolutions[$solutionCode]['displayLogo'] = (!empty($designBO['showlogo_cart'])) ? "TRUE" : FALSE;
                    if (!empty($solutionsFullData[$solutionCode]['title_payment'])) {
                        $designSolutions[$solutionCode]['visualTitle'] = $solutionsFullData[$solutionCode]['title_payment'];
                    }
                    if (!empty($designBO['payment_title'])) {
                        $designSolutions[$solutionCode]['visualTitle'] = '<div class="scalexpert_title">' . $designBO['payment_title'] . '</div>';
                    }
                } elseif ($isCart) {
                    $designSolutions[ $solutionCode ][ 'displayLogo' ] = ( !empty( $designBO[ 'showlogo_cart_cart' ] ) ) ? "TRUE" : FALSE;
                    if ( !empty( $solutionsFullData[ $solutionCode ][ 'title_cart' ] ) ) {
                        $designSolutions[ $solutionCode ][ 'visualTitle' ] = $solutionsFullData[ $solutionCode ][ 'title_cart' ];
                    }
                    if ( !empty( $designBO[ 'cart_title' ] ) ) {
                        $designSolutions[ $solutionCode ][ 'visualTitle' ] = '<div class="scalexpert_title">' . $designBO[ 'cart_title' ] . '</div>';;
                    }
                } else {
                    $designSolutions[$solutionCode]['displayLogo'] = (!empty($designBO['showlogo'])) ? "TRUE" : FALSE;
                    if (!empty($solutionsFullData[$solutionCode]['title'])) {
                        $designSolutions[$solutionCode]['visualTitle'] = $solutionsFullData[$solutionCode]['title'];
                    }
                    if (!empty($designBO['bloc_title'])) {
                        $designSolutions[$solutionCode]['visualTitle'] = '<div class="scalexpert_title">' . $designBO['bloc_title'] . '</div>';;
                    }
                }
                $designSolutions[$solutionCode]['custom'] = $solutionsFullData[$solutionCode];
            }
        }

        return [
            'solutionCodes' => $solutionCodes,
            'designSolutions' => $designSolutions
        ];
    }


}
