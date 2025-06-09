<?php

/**
 * Crypto Price Ticker - view template
 * Displays a cryptocurrency price ticker that updates in real-time using Interactivity API
 */

wp_interactivity_state(
	'cryptoPriceTicker',
	array(
		'isLoading' => false,
	)
);
?>

<div
    id="crypto-price-ticker"
    class="crypto-ticker-wrapper crypto-price-interactive"
    data-wp-interactive='cryptoPriceTicker'
    <?php echo wp_interactivity_data_wp_context($context_data); ?>
    data-wp-watch="callbacks.watchTester"
    >

    <h3 data-wp-text="context.cryptoPriceTicker.name"></h3>
    <div class="crypto-ticker-price">
        <span data-wp-text="context.cryptoPriceTicker.symbol"></span>
        <strong data-wp-text="context.cryptoPriceTicker.price"></strong>
    </div>
    <p
        data-wp-bind--hidden="!state.isLoading"
        class="crypto-ticker-status">

        Updating...
    </p>
	<button data-wp-on--click="actions.forceUpdate">
		Update
	</button>
</div>
