// Simple social frame
import { NextRequest, NextResponse } from 'next/server'
import { getFrameHtmlResponse } from '@coinbase/onchainkit/frame'

// Removed edge runtime for Vercel compatibility

export default async function handler(req: any, res: any) {
  const frameHtml = getFrameHtmlResponse({
    buttons: [
      { label: '🏆 Leaderboard' },
      { label: '🔙 Back to Game' }
    ],
    image: {
      src: `data:image/svg+xml;base64,${Buffer.from(`
        <svg width="1200" height="630" xmlns="http://www.w3.org/2000/svg">
          <rect width="1200" height="630" fill="#1a1a2e"/>
          <text x="600" y="300" text-anchor="middle" font-family="Arial" 
                font-size="48" fill="#ffffff">🏆 Social Features Coming Soon!</text>
        </svg>
      `).toString('base64')}`
    },
    postUrl: '/api/frames/aviator'
  })

  return new NextResponse(frameHtml)
}