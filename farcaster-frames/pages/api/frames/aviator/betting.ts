// Simple betting frame
import { NextRequest, NextResponse } from 'next/server'
import { getFrameHtmlResponse } from '@coinbase/onchainkit/frame'

export const config = {
  runtime: 'edge'
}

export default async function handler(req: any, res: any) {
  const frameHtml = getFrameHtmlResponse({
    buttons: [
      { label: '💰 Bet $10' },
      { label: '💰 Bet $25' },
      { label: '🔙 Back to Game' }
    ],
    image: {
      src: `data:image/svg+xml;base64,${Buffer.from(`
        <svg width="1200" height="630" xmlns="http://www.w3.org/2000/svg">
          <rect width="1200" height="630" fill="#1a1a2e"/>
          <text x="600" y="250" text-anchor="middle" font-family="Arial" 
                font-size="48" fill="#ffffff">💰 Place Your Bet</text>
          <text x="600" y="350" text-anchor="middle" font-family="Arial" 
                font-size="24" fill="#cccccc">Choose your bet amount and watch the multiplier fly!</text>
        </svg>
      `).toString('base64')}`
    },
    postUrl: '/api/frames/aviator'
  })

  return new NextResponse(frameHtml)
}