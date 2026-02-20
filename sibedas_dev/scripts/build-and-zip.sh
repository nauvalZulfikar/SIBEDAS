#!/bin/bash

# Build and Zip Script for Laravel Vite Project
echo "🚀 Starting build process..."

# Clean previous build
echo "🧹 Cleaning previous build..."
rm -rf public/build
rm -f build.zip

# Run npm build
echo "📦 Building assets with Vite..."
npm run build

# Check if build was successful
if [ $? -eq 0 ]; then
    echo "✅ Build completed successfully!"
    
    # Create zip file
    echo "📁 Creating build.zip..."
    cd public && zip -r ../build.zip build/
    cd ..
    
    echo "✅ build.zip created successfully!"
    echo "📊 Build folder size:"
    du -sh public/build
    echo "📊 Zip file size:"
    du -sh build.zip
    
    echo "🎉 Process completed! You can now upload build.zip to your server."
else
    echo "❌ Build failed! Please check the errors above."
    exit 1
fi 