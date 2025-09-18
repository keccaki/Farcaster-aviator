// Enhanced Aviator Game Frame with Backend Integration
import { NextRequest, NextResponse } from 'next/server'
import { FrameRequest, getFrameMessage, getFrameHtmlResponse } from '@coinbase/onchainkit/frame'

export const config = {
  runtime: 'edge'
}

// Game state interface
interface GameState {
  status: 'waiting' | 'flying' | 'crashed'
  multiplier: number
  roundId: string
  timeRemaining?: number
  currentBets?: any[]
}

// Fetch game state from Laravel backend
async function fetchGameState(): Promise<GameState> {
  try {
    const response = await fetch(`${process.env.LARAVEL_API_URL}/farcaster/game-state`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      return {
        status: data.status || 'waiting',
        multiplier: data.multiplier || 1.0,
        roundId: data.round_id || Date.now().toString(),
        timeRemaining: data.time_remaining,
        currentBets: data.current_bets || []
      }
    }
  } catch (error) {
    console.log('Failed to fetch game state:', error)
  }
  
  // Fallback state
  return {
    status: 'waiting',
    multiplier: 1.0,
    roundId: Date.now().toString()
  }
}

// Generate dynamic SVG based on game state
function generateGameImage(gameState: GameState): string {
  const { status, multiplier } = gameState
  
  let statusText = 'Ready to Fly!'
  let statusColor = '#00ff88'
  let multiplierText = ''
  
  if (status === 'flying') {
    statusText = 'FLYING! 🚀'
    statusColor = '#ffaa00'
    multiplierText = `${multiplier.toFixed(2)}x`
  } else if (status === 'crashed') {
    statusText = 'CRASHED! 💥'
    statusColor = '#ff4444'
    multiplierText = `Crashed at ${multiplier.toFixed(2)}x`
  }
  
  const svg = `
    <svg width="1200" height="630" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" style="stop-color:#1a1a2e"/>
          <stop offset="100%" style="stop-color:#16213e"/>
        </linearGradient>
      </defs>
      <rect width="1200" height="630" fill="url(#bg)"/>
      
      <!-- Title -->
      <text x="600" y="150" text-anchor="middle" font-family="Arial, sans-serif" 
            font-size="48" font-weight="bold" fill="#ffffff">
        🚀 AVIATOR CRASH GAME
      </text>
      
      <!-- Status -->
      <text x="600" y="250" text-anchor="middle" font-family="Arial, sans-serif" 
            font-size="52" fill="${statusColor}">
        ${statusText}
      </text>
      
      <!-- Multiplier (if flying/crashed) -->
      ${multiplierText ? `
        <text x="600" y="350" text-anchor="middle" font-family="Arial, sans-serif" 
              font-size="72" font-weight="bold" fill="#ffaa00">
          ${multiplierText}
        </text>
      ` : ''}
      
      <!-- Instructions -->
      <text x="600" y="${multiplierText ? '450' : '350'}" text-anchor="middle" font-family="Arial, sans-serif" 
            font-size="24" fill="#cccccc">
        ${status === 'waiting' ? 'Place your bet and watch the multiplier climb!' : 
          status === 'flying' ? 'Cash out before the crash!' : 
          'Next round starting soon...'}
      </text>
      
      <!-- Round ID -->
      <text x="600" y="${multiplierText ? '500' : '400'}" text-anchor="middle" font-family="Arial, sans-serif" 
            font-size="16" fill="#888888">
        Round: ${gameState.roundId.slice(-8)}
      </text>
    </svg>
  `
  
  return `data:image/svg+xml;base64,${Buffer.from(svg).toString('base64')}`
}

// Handle POST requests (button interactions)
async function handlePOST(req: any, res: any) {
  try {
    const body: FrameRequest = await req.json()
    const { isValid, message } = await getFrameMessage(body)

    if (!isValid || !message) {
      return new NextResponse('Invalid frame message', { status: 400 })
    }

    const buttonIndex = message.button
    const userFid = message.interactor.fid
    
    // Fetch current game state
    const gameState = await fetchGameState()
    
    let redirectUrl = ''
    let newGameState = gameState
    
    // Handle different button actions
    switch (buttonIndex) {
      case 1: // Play Game / Place Bet
        if (gameState.status === 'waiting') {
          // Try to place a bet
          try {
            const betResponse = await fetch(`${process.env.LARAVEL_API_URL}/farcaster/bet`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              body: JSON.stringify({
                fid: userFid,
                amount: 10, // Default $10 bet
                roundId: gameState.roundId,
                autoCashOut: 2.0 // Auto cash out at 2x
              })
            })
            
            if (betResponse.ok) {
              const betData = await betResponse.json()
              // Update game state after placing bet
              newGameState = await fetchGameState()
            }
          } catch (error) {
            console.log('Failed to place bet:', error)
          }
        } else if (gameState.status === 'flying') {
          // Try to cash out
          try {
            await fetch(`${process.env.LARAVEL_API_URL}/farcaster/cashout`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              body: JSON.stringify({
                fid: userFid,
                betId: 'current', // Will need to track bet ID properly
                multiplier: gameState.multiplier
              })
            })
            newGameState = await fetchGameState()
          } catch (error) {
            console.log('Failed to cash out:', error)
          }
        }
        break
        
      case 2: // Alternative action
        redirectUrl = `${process.env.GAME_API_URL}`
        break
        
      case 3: // Stats or Info
        redirectUrl = `${process.env.NEXT_PUBLIC_FRAME_BASE_URL}/api/frames/aviator/stats`
        break
    }
    
    // Determine button configuration based on new game state
    const buttons = newGameState.status === 'waiting' ? [
      { label: '💰 Bet $10' },
      { label: '🎯 Play Full Game' },
      { label: '📊 Stats' }
    ] : newGameState.status === 'flying' ? [
      { label: '💸 Cash Out Now!' },
      { label: '🎮 Full Game' },
      { label: '📈 Leaderboard' }
    ] : [
      { label: '🚀 New Round' },
      { label: '🎮 Play More' },
      { label: '🏆 Results' }
    ]

    const frameHtml = getFrameHtmlResponse({
      buttons,
      image: {
        src: generateGameImage(newGameState)
      },
      postUrl: '/api/frames/aviator',
      ...(redirectUrl && { redirectUrl })
    })

    return new NextResponse(frameHtml)

  } catch (error) {
    console.error('Error in POST handler:', error)
    return new NextResponse('Frame error', { status: 500 })
  }
}

// Handle GET requests (initial frame load)
async function handleGET(req: any, res: any) {
  try {
    // Fetch current game state
    const gameState = await fetchGameState()
    
    // Determine buttons based on game state
    const buttons = gameState.status === 'waiting' ? [
      { label: '🎮 Play Game' },
      { label: '💰 Place Bet' }
    ] : gameState.status === 'flying' ? [
      { label: '💸 Cash Out!' },
      { label: '🎮 Full Game' }
    ] : [
      { label: '🚀 New Round' },
      { label: '🎮 Play Again' }
    ]

    const frameHtml = getFrameHtmlResponse({
      buttons,
      image: {
        src: generateGameImage(gameState)
      },
      postUrl: '/api/frames/aviator'
    })

    return new NextResponse(frameHtml)
    
  } catch (error) {
    console.error('Error in GET handler:', error)
    
    // Fallback frame
    const frameHtml = getFrameHtmlResponse({
      buttons: [
        { label: '🎮 Play Game' },
        { label: '💰 Place Bet' }
      ],
      image: {
        src: generateGameImage({
          status: 'waiting',
          multiplier: 1.0,
          roundId: 'demo'
        })
      },
      postUrl: '/api/frames/aviator'
    })

    return new NextResponse(frameHtml)
  }
}

// Main handler
export default async function handler(req: any, res: any) {
  if (req.method === 'POST') {
    return await handlePOST(req, res)
  } else {
    return await handleGET(req, res)
  }
}