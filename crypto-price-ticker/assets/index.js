import { store, getContext } from '@wordpress/interactivity';

const { state, actions } = store('cryptoPriceTicker', {

    state: {
        isLoading: false,
    },

    actions: {

        // Main function to fetch the price
        fetchPrice: async (context) => {

            state.isLoading = true; // loading indicator: ON

            try {
                const ajaxUrl = context.ajaxUrl; // Get AJAX URL from context
                const response = await fetch(ajaxUrl, { // Using fetch() here to reduce dependencies
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'get_crypto_price',
                        coinId: context.coinId, // Pass the coin ID from the context
                    }),
                });

                const result = await response.json();

                // Check and get data
                if (!result.success) {
                    throw new Error(result.data);
                }
                const data = result.data;

                // Update the context with the new data
                context.cryptoPriceTicker = {
                    ...context.cryptoPriceTicker, // merge existing properties
                    name: data.name,
                    symbol: data.symbol,
                    price: data.price,
                    error: data.error,
                };
            }

            // Error handling
            catch (error) {

                console.error('Error fetching crypto price:', error);

                // Update state to show error to the user
                context.cryptoPriceTicker = {
                    ...context.cryptoPriceTicker,
                    name: context.coinId + ': Error',
                    price: 'N/A',
                    error: true,
                };
            }

            finally {
                state.isLoading = false; // loading indicator: OFF
            }
        },

        // Function to force an update/refresh
        forceUpdate: () => {
            const forceUpdateContext = getContext();
            actions.fetchPrice(forceUpdateContext);
        },

    },

    callbacks: {

        // Automatic refresh function
        watchTester: () => {

            const contextRef = getContext();

            setInterval(() => {
                console.log('Refresh results: ', contextRef.cryptoPriceTicker);
                actions.fetchPrice(contextRef);
            }, 60000); // 60 seconds refresh rate

        },
    },
});

