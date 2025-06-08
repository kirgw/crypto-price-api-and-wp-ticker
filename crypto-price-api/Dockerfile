# Builder stage: full Node image for build tools
FROM node:22 AS builder

WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .

# Runtime stage: lighter Alpine image
FROM node:22-alpine

WORKDIR /app
COPY package*.json ./

# Install production-only dependencies
RUN npm ci --omit=dev

# Copy app from builder stage
COPY --from=builder /app/index.js ./index.js

# Expose application port
EXPOSE 3000

# Run application
CMD [ "node", "index.js" ]
