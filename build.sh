
#!/usr/bin/env bash
set -e

echo "Installing Node dependencies..."
npm ci

echo "Building Laravel Vite assets..."
npm run build

echo "Frontend build complete."
