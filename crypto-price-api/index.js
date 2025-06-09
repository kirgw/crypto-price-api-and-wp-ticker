const express = require('express');
const axios = require('axios');
const NodeCache = require('node-cache');
require('dotenv').config(); // .env file
const cors = require('cors');

// Init server and cache
const app = express();
const port = process.env.PORT || 3000; // use .env port or 3000 by default
const cache = new NodeCache({ stdTTL: 60 }); // 1 minute default cache
app.use(cors());

// CoinGecko API setup
const COINGECKO_API_URL =  process.env.COINGECKO_API_URL;

if (!process.env.COINGECKO_API_KEY) {
    console.error('FATAL ERROR: COINGECKO_API_KEY is not defined in .env file.');
    process.exit(1);
}

if (!COINGECKO_API_URL) {
    console.error('FATAL ERROR: COINGECKO_API_URL is not defined in .env file.');
    process.exit(1);
}

// Endpoint GET /price/:id
app.get('/price/:id', async (req, res) => {
    const { id } = req.params; // crypto id ('bitcoin')

    try {
        // Cached data check
        const cachedData = cache.get(id);
        if (cachedData) {
            console.log(`Returning from cache: ${id}`);
            return res.json(cachedData);
        }
        console.log(`No cache for: ${id}. Fetching from API...`);

        // Call API with least params
        const response = await axios.get(`${COINGECKO_API_URL}${id}`, {
            params: {
                localization: 'false',
                tickers: 'false',
                market_data: 'true',
                community_data: 'false',
                developer_data: 'false',
                sparkline: 'false'
            },
            headers: {
                'x-cg-pro-api-key': process.env.COINGECKO_API_KEY
            }
        });

        // Data save and check
        const coinData = response.data;

        if (!coinData.market_data || !coinData.market_data.current_price || !coinData.market_data.current_price.usd) {
            throw new Error('Invalid data from CoinGecko API');
        }

        const priceData = {
            name: coinData.name,
            symbol: coinData.symbol,
            price: coinData.market_data.current_price.usd,
        };

        // Add to cache
        cache.set(id, priceData);
        console.log(`Cache saved for: ${id}`);

        // Send JSON response
        res.json(priceData);

    }

    // Error handling
    catch (error) {
        console.error('Error fetching data:', error.message);

        // Has response?
        if (error.response) {
            if (error.response.status === 404) {
                // Checking just for not found
                return res.status(404).json({ error: `Crypto id '${id}' not found.` });
            }
        }

        // Others
        return res.status(500).json({ error: 'Failed to fetch data from the provider.' });
    }
});

// Catch other endpoints and pass 404
app.use((req, res, next) => {
  res.status(404).json({ error: `Not Found: ${req.method} ${req.originalUrl}` });
});

// Global error handler (optional, but ok)
app.use((err, req, res, next) => {
    console.error(err.stack);
    res.status(500).json({ error: 'Something went wrong!' });
});

// Server start
app.listen(port, () => {
    console.log(`Crypto Price API server running on http://localhost:${port}`);
});
