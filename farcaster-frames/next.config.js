/** @type {import('next').NextConfig} */
const nextConfig = {
  reactStrictMode: true,
  swcMinify: true,
  images: {
    domains: ['your-domain.com'],
  },
  async headers() {
    return [
      {
        source: '/api/frames/:path*',
        headers: [
          {
            key: 'Access-Control-Allow-Origin',
            value: '*',
          },
          {
            key: 'Access-Control-Allow-Methods',
            value: 'GET, POST, PUT, DELETE, OPTIONS',
          },
          {
            key: 'Access-Control-Allow-Headers',
            value: 'Content-Type, Authorization',
          },
        ],
      },
    ];
  },
  env: {
    LARAVEL_API_URL: process.env.LARAVEL_API_URL || 'http://localhost:8000',
    FARCASTER_HUB_URL: process.env.FARCASTER_HUB_URL || 'https://nemes.farcaster.xyz:2283',
    REDIS_URL: process.env.REDIS_URL || 'redis://localhost:6379',
  }
}

module.exports = nextConfig

