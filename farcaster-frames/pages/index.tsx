import { useEffect, useState } from 'react'

const GameScreen = () => {
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
        // For now, simulate the SDK ready call
        // In production, this would be replaced with actual SDK
        console.log('Mini App initializing...')
        
        // Simulate calling sdk.actions.ready()
        if (typeof window !== 'undefined' && (window as any).farcasterSDK) {
          await (window as any).farcasterSDK.actions.ready()
        }
        
        // Fetch initial game state
        await fetchGameState()
        
        console.log('Mini App initialized successfully!')
      } catch (error) {
        console.error('Failed to initialize Mini App:', error)
        // Still fetch game state even if SDK fails
        await fetchGameState()
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
    <div className="min-h-screen bg-gradient-to-br from-purple-900 via-blue-900 to-indigo-900 text-white">
      {/* Header */}
      <div className="text-center py-6">
        <h1 className="text-7xl font-bold mb-3">🚀 AVIATOR</h1>
        <h2 className="text-4xl font-semibold mb-2 text-yellow-300">CRASH GAME</h2>
        <p className="text-2xl text-green-300 font-medium">Ready to Fly!</p>
        <p className="text-lg text-gray-200 mt-2">Click Play to start betting!</p>
      </div>

      {/* Game Status */}
      <div className="text-center mb-6">
        <div className={`text-8xl font-bold mb-4 ${getStatusColor()}`}>
          {getStatusText()}
        </div>
        
        {gameState.status === 'flying' && (
          <div className="text-9xl font-bold text-orange-400 mb-4 animate-pulse">
            {gameState.multiplier.toFixed(2)}x
          </div>
        )}
        
        {gameState.status === 'crashed' && (
          <div className="text-6xl text-red-400 mb-4">
            Crashed at {gameState.multiplier.toFixed(2)}x
          </div>
        )}
      </div>

      {/* Instructions */}
      <div className="text-center mb-6">
        <p className="text-gray-200 text-2xl font-medium">
          {gameState.status === 'waiting' && 'Place your bet and watch the multiplier climb!'}
          {gameState.status === 'flying' && 'Cash out before the crash!'}
          {gameState.status === 'crashed' && 'Next round starting soon...'}
        </p>
      </div>

      {/* Game Info */}
      <div className="bg-white/10 backdrop-blur-lg mx-4 rounded-2xl p-6 mb-6 border border-white/20">
        <div className="grid grid-cols-2 gap-6 text-center">
          <div>
            <div className="text-gray-300 text-xl mb-2">Your Balance</div>
            <div className="text-4xl font-bold text-green-400">${userBalance}</div>
          </div>
          <div>
            <div className="text-gray-300 text-xl mb-2">Round ID</div>
            <div className="text-2xl font-mono">{gameState.roundId.slice(-8)}</div>
          </div>
        </div>
      </div>

      {/* Betting Section */}
      <div className="px-4 mb-6">
        <div className="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
          <div className="mb-6">
            <label className="block text-gray-300 text-2xl mb-3 font-medium">Bet Amount</label>
            <div className="flex items-center space-x-4">
              <input
                type="number"
                value={betAmount}
                onChange={(e) => setBetAmount(Math.max(1, parseInt(e.target.value) || 1))}
                className="flex-1 bg-white/20 text-white px-6 py-4 rounded-xl text-2xl font-bold text-center border border-white/30"
                min="1"
                max={userBalance}
              />
              <span className="text-gray-300 text-2xl font-bold">$</span>
            </div>
          </div>

          {/* Action Buttons */}
          <div className="space-y-4">
            {gameState.status === 'waiting' && (
              <button
                onClick={placeBet}
                disabled={betAmount > userBalance}
                className="w-full bg-green-600 hover:bg-green-700 disabled:bg-gray-600 disabled:cursor-not-allowed text-white font-bold py-6 px-8 rounded-xl text-3xl transition-all duration-300 transform hover:scale-105 disabled:scale-100 shadow-lg"
              >
                🎮 PLACE BET ${betAmount}
              </button>
            )}

            {gameState.status === 'flying' && (
              <button
                onClick={cashOut}
                className="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-6 px-8 rounded-xl text-3xl animate-pulse transition-all duration-300 transform hover:scale-105 shadow-lg"
              >
                💸 CASH OUT {gameState.multiplier.toFixed(2)}x
              </button>
            )}

            {gameState.status === 'crashed' && (
              <button
                onClick={fetchGameState}
                className="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-6 px-8 rounded-xl text-3xl transition-all duration-300 transform hover:scale-105 shadow-lg"
              >
                🚀 NEXT ROUND
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
      <div className="text-center py-6 text-gray-400">
        <p className="text-lg">Powered by Farcaster Mini Apps</p>
      </div>
    </div>
  )
}

export default function AviatorMiniApp() {
  return <GameScreen />
}
