import { useEffect, useState } from 'react'
import { sdk } from '@farcaster/miniapp-sdk'

export default function AviatorMiniApp() {
  const [gameState, setGameState] = useState({
    status: 'waiting',
    multiplier: 1.0,
    roundId: '',
    currentBets: [],
    isLoading: true
  })
  const [userBalance, setUserBalance] = useState(1000)
  const [betAmount, setBetAmount] = useState(10)

  // Initialize Mini App
  useEffect(() => {
    const initializeApp = async () => {
      try {
        // Call ready() to hide the splash screen
        await sdk.actions.ready()
        
        // Fetch initial game state
        await fetchGameState()
        
        console.log('Mini App initialized successfully!')
      } catch (error) {
        console.error('Failed to initialize Mini App:', error)
        // Still call ready() even if other initialization fails
        try {
          await sdk.actions.ready()
        } catch (readyError) {
          console.error('Failed to call ready():', readyError)
        }
      }
    }

    initializeApp()
  }, [])

  // Fetch current game state
  const fetchGameState = async () => {
    try {
      const response = await fetch('/api/game/state')
      const result = await response.json()
      
      if (result.success) {
        setGameState({
          ...result.data,
          isLoading: false
        })
      }
    } catch (error) {
      console.error('Failed to fetch game state:', error)
      setGameState(prev => ({ ...prev, isLoading: false }))
    }
  }

  // Place a bet
  const placeBet = async () => {
    try {
      const response = await fetch('/api/game/bet', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          fid: 12345, // Would get from SDK context
          amount: betAmount,
          roundId: gameState.roundId
        })
      })
      
      const result = await response.json()
      
      if (result.success) {
        setUserBalance(result.data.newBalance)
        await fetchGameState() // Refresh game state
        
        // Show success feedback
        console.log('Bet placed successfully:', result.data)
      }
    } catch (error) {
      console.error('Failed to place bet:', error)
    }
  }

  // Cash out
  const cashOut = async () => {
    try {
      const response = await fetch('/api/game/cashout', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          fid: 12345,
          betId: `bet_12345_${gameState.roundId}`,
          multiplier: gameState.multiplier
        })
      })
      
      const result = await response.json()
      
      if (result.success) {
        setUserBalance(result.data.newBalance)
        await fetchGameState()
        
        console.log('Cash out successful:', result.data)
      }
    } catch (error) {
      console.error('Failed to cash out:', error)
    }
  }

  if (gameState.isLoading) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-slate-900 to-slate-800 flex items-center justify-center">
        <div className="text-white text-xl">Loading game...</div>
      </div>
    )
  }

  const getStatusColor = () => {
    switch (gameState.status) {
      case 'flying': return 'text-yellow-400'
      case 'crashed': return 'text-red-400'
      default: return 'text-green-400'
    }
  }

  const getStatusText = () => {
    switch (gameState.status) {
      case 'flying': return '🚀 FLYING!'
      case 'crashed': return '💥 CRASHED!'
      default: return '✈️ Ready to Fly!'
    }
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-900 to-slate-800 text-white">
      {/* Header */}
      <div className="text-center py-8">
        <h1 className="text-4xl font-bold mb-2">🚀 AVIATOR CRASH GAME</h1>
        <p className="text-slate-300">by keccak</p>
      </div>

      {/* Game Status */}
      <div className="text-center mb-8">
        <div className={`text-6xl font-bold mb-4 ${getStatusColor()}`}>
          {getStatusText()}
        </div>
        
        {gameState.status === 'flying' && (
          <div className="text-5xl font-bold text-orange-400 mb-2">
            {gameState.multiplier.toFixed(2)}x
          </div>
        )}
        
        {gameState.status === 'crashed' && (
          <div className="text-3xl text-red-400 mb-2">
            Crashed at {gameState.multiplier.toFixed(2)}x
          </div>
        )}
      </div>

      {/* Instructions */}
      <div className="text-center mb-8">
        <p className="text-slate-300 text-lg">
          {gameState.status === 'waiting' && 'Place your bet and watch the multiplier climb!'}
          {gameState.status === 'flying' && 'Cash out before the crash!'}
          {gameState.status === 'crashed' && 'Next round starting soon...'}
        </p>
      </div>

      {/* Game Info */}
      <div className="bg-slate-800 mx-4 rounded-lg p-6 mb-8">
        <div className="grid grid-cols-2 gap-4 text-center">
          <div>
            <div className="text-slate-400">Your Balance</div>
            <div className="text-2xl font-bold text-green-400">${userBalance}</div>
          </div>
          <div>
            <div className="text-slate-400">Round ID</div>
            <div className="text-lg">{gameState.roundId.slice(-8)}</div>
          </div>
        </div>
      </div>

      {/* Betting Section */}
      <div className="px-4 mb-8">
        <div className="bg-slate-800 rounded-lg p-6">
          <div className="mb-4">
            <label className="block text-slate-400 mb-2">Bet Amount</label>
            <div className="flex items-center space-x-4">
              <input
                type="number"
                value={betAmount}
                onChange={(e) => setBetAmount(Math.max(1, parseInt(e.target.value) || 1))}
                className="flex-1 bg-slate-700 text-white px-4 py-3 rounded-lg text-lg"
                min="1"
                max={userBalance}
              />
              <span className="text-slate-400">$</span>
            </div>
          </div>

          {/* Action Buttons */}
          <div className="space-y-3">
            {gameState.status === 'waiting' && (
              <button
                onClick={placeBet}
                disabled={betAmount > userBalance}
                className="w-full bg-green-600 hover:bg-green-700 disabled:bg-slate-600 disabled:cursor-not-allowed text-white font-bold py-4 px-6 rounded-lg text-lg transition-colors"
              >
                🎮 Place Bet ${betAmount}
              </button>
            )}

            {gameState.status === 'flying' && (
              <button
                onClick={cashOut}
                className="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-4 px-6 rounded-lg text-lg animate-pulse transition-colors"
              >
                💸 Cash Out at {gameState.multiplier.toFixed(2)}x
              </button>
            )}

            {gameState.status === 'crashed' && (
              <button
                onClick={fetchGameState}
                className="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-6 rounded-lg text-lg transition-colors"
              >
                🚀 Wait for Next Round
              </button>
            )}
          </div>
        </div>
      </div>

      {/* Current Bets */}
      {gameState.currentBets && gameState.currentBets.length > 0 && (
        <div className="px-4 mb-8">
          <div className="bg-slate-800 rounded-lg p-6">
            <h3 className="text-lg font-bold mb-4">Active Bets</h3>
            <div className="space-y-2">
              {gameState.currentBets.map((bet: any, index: number) => (
                <div key={index} className="flex justify-between items-center bg-slate-700 px-4 py-2 rounded">
                  <span>{bet.username}</span>
                  <span className="font-bold">${bet.amount}</span>
                </div>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* Footer */}
      <div className="text-center py-8 text-slate-500">
        <p>Powered by Farcaster Mini Apps</p>
      </div>
    </div>
  )
}
