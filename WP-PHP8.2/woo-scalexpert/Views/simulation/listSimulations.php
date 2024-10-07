<?php
	/**
	 * Copyright © Scalexpert.
	 * This file is part of Scalexpert plugin for PrestaShop.
	 *
	 * @author    Société Générale
	 * @copyright Scalexpert
	 */
	
	
	if ( ! empty( $solutions ) ) {
		?>
        <ul class="sep-Simulations-solution-listSimulation">
			<?php
				foreach ( $solutions as $key => $simulation ) {
					$titleSolution = $simulation['designConfiguration']['visualTitle'];
					$md5Solution   = md5( ( $simulation['designConfiguration']['solutionCode'] ?? '' ) . strip_tags( $titleSolution ) . ( $simulation['duration'] ?? '' ) );
					if ( ! empty( $simulation ) ) {
						?>
                        <li class="sep-Simulations-solution-itemSimulation"
                            data-js="selectSolutionSimulation"
							<?php if ( $current === $key ) {
								echo 'data-current="true"';
							} ?>
                            data-id="<?php echo $md5Solution; ?>"
                            data-groupid="<?php echo $md5GroupSolution; ?>"
                        >
                            x<?php echo $simulation['duration']; ?>
                        </li>
						<?php
					}
				}
			?>
        </ul>
	<?php } ?>
