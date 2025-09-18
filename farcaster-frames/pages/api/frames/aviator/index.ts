// Simple Aviator Game Frame API
import { NextRequest, NextResponse } from 'next/server'
import { FrameRequest, getFrameMessage, getFrameHtmlResponse } from '@coinbase/onchainkit/frame'

export const config = {
  runtime: 'edge'
}

// Simple game state for demo
let gameState = {
  status: 'waiting',
  multiplier: 1.0,
  roundId: Date.now().toString()
}

export default async function handler(req: any, res: any) {
  if (req.method === 'POST') {
    return await handlePOST(req, res);
  } else {
    return await handleGET(req, res);
  }
}

async function handlePOST(req: any, res: any) {
  try {
    const body: FrameRequest = await req.json()
    const { isValid, message } = await getFrameMessage(body)

    if (!isValid || !message) {
      return new NextResponse('Invalid frame message', { status: 400 })
    }

    const buttonIndex = message.button
    
    // Simulate game progression
    if (buttonIndex === 1) {
      gameState.status = 'flying'
      gameState.multiplier = Math.random() * 5 + 1
    } else if (buttonIndex === 2) {
      gameState.status = 'crashed'
    }

    const frameHtml = generateGameFrame()
    return new NextResponse(frameHtml)
    
  } catch (error) {
    console.error('Frame error:', error)
    return new NextResponse('Frame error', { status: 500 })
  }
}

async function handleGET(req: any, res: any) {
  const frameHtml = generateGameFrame()
  return new NextResponse(frameHtml)
}

function generateGameFrame(): string {
  const statusEmoji = gameState.status === 'waiting' ? '✈️' : 
                     gameState.status === 'flying' ? '🚀' : '💥'
  
  const multiplierText = gameState.status === 'flying' ? 
    `${gameState.multiplier.toFixed(2)}x` : 
    gameState.status === 'crashed' ? 
    `Crashed at ${gameState.multiplier.toFixed(2)}x` : 
    'Ready to Fly!'

  return getFrameHtmlResponse({
    buttons: [
      { label: '🎮 Play Game' },
      { label: '💰 Place Bet' }
    ],
    image: {
      src: `data:image/svg+xml;base64,${Buffer.from(`
        <svg width="1200" height="630" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" style="stop-color:#1a1a2e"/>
              <stop offset="100%" style="stop-color:#16213e"/>
            </linearGradient>
          </defs>
          <rect width="1200" height="630" fill="url(#bg)"/>
          
          <!-- Title -->
          <text x="600" y="100" text-anchor="middle" font-family="Arial, sans-serif" 
                font-size="48" font-weight="bold" fill="#ffffff">
            🚀 AVIATOR CRASH GAME
          </text>
          
          <!-- Status -->
          <text x="600" y="200" text-anchor="middle" font-family="Arial, sans-serif" 
                font-size="72" fill="#00ff88">
            ${statusEmoji}
          </text>
          
          <!-- Multiplier -->
          <text x="600" y="320" text-anchor="middle" font-family="Arial, sans-serif" 
                font-size="64" font-weight="bold" fill="#ffaa00">
            ${multiplierText}
          </text>
          
          <!-- Instructions -->
          <text x="600" y="420" text-anchor="middle" font-family="Arial, sans-serif" 
                font-size="24" fill="#cccccc">
            ${gameState.status === 'waiting' ? 'Click Play to start a new round!' :
              gameState.status === 'flying' ? 'Multiplier is rising... cash out now?' :
              'Game crashed! Try again?'}
          </text>
          
          <!-- Footer -->
          <text x="600" y="580" text-anchor="middle" font-family="Arial, sans-serif" 
                font-size="18" fill="#888888">
            Powered by Farcaster Frames
          </text>
        </svg>
      `).toString('base64')}`
    },
    postUrl: '/api/frames/aviator'
  })
}