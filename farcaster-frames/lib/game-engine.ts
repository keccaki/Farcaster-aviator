// Aviator Game Engine - Direct port from PHP Gamesetting.php
import { GameState, PlayerBet, GameMetrics } from './types'

export class AviatorGameEngine {
  private gameState: GameState | null = null
  private activeBets: Map<number, PlayerBet> = new Map()
  private gameHistory: number[] = []

  /**
   * Direct port of calculateHouseEdgeMultiplier from Gamesetting.php
   * Maintains exact same algorithm for fairness and consistency
   */
  calculateCrashMultiplier(totalAmount: number, totalBets: number): number {
    // Realistic crash game algorithm with house edge protection
    const baseMultipliers = [
      1.00, 1.01, 1.02, 1.03, 1.04, 1.05, 1.06, 1.07, 1.08, 1.09,
      1.10, 1.11, 1.12, 1.13, 1.14, 1.15, 1.16, 1.17, 1.18, 1.19,
      1.20, 1.22, 1.25, 1.30, 1.35, 1.40, 1.45, 1.50, 1.55, 1.60,
      1.65, 1.70, 1.75, 1.80, 1.85, 1.90, 1.95, 2.00, 2.10, 2.20,
      2.30, 2.40, 2.50, 2.75, 3.00, 3.50, 4.00, 5.00, 7.00, 10.00
    ]

    // Weight distribution based on realistic crash game probabilities
    let weights = [
      // 1.00-1.09: 25% chance (house edge protection)
      10, 10, 8, 8, 6, 6, 4, 4, 3, 3,
      // 1.10-1.19: 20% chance 
      5, 5, 4, 4, 3, 3, 2, 2, 2, 2,
      // 1.20-1.60: 30% chance (sweet spot)
      3, 4, 5, 6, 5, 4, 3, 3, 2, 2,
      2, 2, 2, 2, 2, 2, 2, 2, 2, 2,
      // 1.65-2.50: 15% chance
      1, 1, 1, 1, 1, 1, 1, 2, 1, 1,
      // 2.75-10.00: 10% chance (rare big wins)
      1, 1, 1, 1, 1, 1, 1, 1, 1, 1
    ]

    // Adjust weights based on total bet amount (house protection)
    if (totalAmount > 10000) {
      // High betting: Reduce high multiplier chances
      for (let i = 30; i < weights.length; i++) {
        weights[i] = Math.max(1, weights[i] - 1)
      }
      // Increase low multiplier chances
      for (let i = 0; i < 20; i++) {
        weights[i] += 2
      }
    } else if (totalAmount < 500) {
      // Low betting: Slightly better chances for players
      for (let i = 20; i < 40; i++) {
        weights[i] += 1
      }
    }

    // Create weighted array and select random multiplier
    const weightedMultipliers: number[] = []
    for (let i = 0; i < baseMultipliers.length; i++) {
      for (let j = 0; j < weights[i]; j++) {
        weightedMultipliers.push(baseMultipliers[i])
      }
    }

    const randomIndex = Math.floor(Math.random() * weightedMultipliers.length)
    return weightedMultipliers[randomIndex]
  }

  /**
   * Generate random multiplier for no-bet rounds (direct port from PHP)
   */
  generateRandomMultiplier(): number {
    const randomMultipliers = [1.0, 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 1.9, 2.0, 2.2, 2.5, 3.0]
    const randomIndex = Math.floor(Math.random() * randomMultipliers.length)
    return randomMultipliers[randomIndex]
  }

  /**
   * Start a new game round
   */
  async startNewRound(): Promise<GameState> {
    const roundId = `round_${Date.now()}`
    const totalBets = this.activeBets.size
    const totalAmount = Array.from(this.activeBets.values())
      .reduce((sum, bet) => sum + bet.amount, 0)

    // Calculate crash point using existing algorithm
    const crashPoint = totalBets === 0 
      ? this.generateRandomMultiplier()
      : this.calculateCrashMultiplier(totalAmount, totalBets)

    this.gameState = {
      roundId,
      status: 'waiting',
      multiplier: 1.00,
      crashPoint,
      startTime: new Date(),
      duration: 0,
      totalPot: totalAmount,
      playersCount: totalBets,
      topBets: Array.from(this.activeBets.values())
        .sort((a, b) => b.amount - a.amount)
        .slice(0, 5),
      timeRemaining: 5000 // 5 second wait time
    }

    // Store crash point in history for analytics
    this.gameHistory.push(crashPoint)
    if (this.gameHistory.length > 100) {
      this.gameHistory = this.gameHistory.slice(-100) // Keep last 100 rounds
    }

    return this.gameState
  }

  /**
   * Update game multiplier during flight
   */
  updateMultiplier(): GameState | null {
    if (!this.gameState || this.gameState.status !== 'flying') {
      return null
    }

    const elapsed = Date.now() - this.gameState.startTime.getTime()
    const newMultiplier = 1 + (elapsed / 1000) * 0.1 // Rough multiplier growth

    this.gameState.multiplier = Math.min(newMultiplier, this.gameState.crashPoint!)
    this.gameState.duration = elapsed

    // Check if we've reached crash point
    if (this.gameState.multiplier >= this.gameState.crashPoint!) {
      this.gameState.status = 'crashed'
      this.gameState.multiplier = this.gameState.crashPoint!
      this.processCrashEnd()
    }

    return this.gameState
  }

  /**
   * Process bet placement
   */
  async placeBet(fid: number, username: string, amount: number): Promise<PlayerBet> {
    if (!this.gameState || this.gameState.status !== 'waiting') {
      throw new Error('Cannot place bet at this time')
    }

    const bet: PlayerBet = {
      id: `bet_${fid}_${Date.now()}`,
      fid,
      username,
      amount,
      status: 'active',
      placedAt: new Date()
    }

    this.activeBets.set(fid, bet)
    
    // Update game state
    this.gameState.totalPot += amount
    this.gameState.playersCount = this.activeBets.size
    this.gameState.topBets = Array.from(this.activeBets.values())
      .sort((a, b) => b.amount - a.amount)
      .slice(0, 5)

    return bet
  }

  /**
   * Process cash out
   */
  async cashOut(fid: number): Promise<{ success: boolean; winAmount?: number; error?: string }> {
    const bet = this.activeBets.get(fid)
    if (!bet || bet.status !== 'active') {
      return { success: false, error: 'No active bet found' }
    }

    if (!this.gameState || this.gameState.status !== 'flying') {
      return { success: false, error: 'Cannot cash out at this time' }
    }

    const winAmount = bet.amount * this.gameState.multiplier
    bet.status = 'cashed_out'
    bet.cashedOutAt = this.gameState.multiplier
    bet.winAmount = winAmount

    return { success: true, winAmount }
  }

  /**
   * Process end of crashed round
   */
  private processCrashEnd(): void {
    // Mark all uncashed bets as lost
    Array.from(this.activeBets.values()).forEach(bet => {
      if (bet.status === 'active') {
        bet.status = 'lost'
      }
    })
  }

  /**
   * Get game metrics for analytics
   */
  getGameMetrics(): GameMetrics {
    const recent = this.gameHistory.slice(-20) // Last 20 rounds
    const averageMultiplier = recent.length > 0 
      ? recent.reduce((sum, mult) => sum + mult, 0) / recent.length
      : 1.0

    return {
      activeUsers: this.activeBets.size,
      totalBets: this.activeBets.size,
      totalVolume: Array.from(this.activeBets.values()).reduce((sum, bet) => sum + bet.amount, 0),
      averageMultiplier,
      houseEdge: this.calculateCurrentHouseEdge(),
      socialShares: 0, // Updated by social engine
      viralCoefficient: 0 // Updated by viral engine
    }
  }

  /**
   * Calculate current house edge
   */
  private calculateCurrentHouseEdge(): number {
    if (this.gameHistory.length < 10) return 5.0 // Default 5%
    
    const recent = this.gameHistory.slice(-50)
    const averageMultiplier = recent.reduce((sum, mult) => sum + mult, 0) / recent.length
    
    // House edge = (1 - (average payout / average multiplier)) * 100
    // Simplified calculation assuming average cashout at 2x
    return (1 - (1.8 / averageMultiplier)) * 100
  }

  /**
   * Get current game state
   */
  getCurrentGameState(): GameState | null {
    return this.gameState
  }

  /**
   * Get active bets
   */
  getActiveBets(): PlayerBet[] {
    return Array.from(this.activeBets.values())
  }

  /**
   * Reset for new round
   */
  resetForNewRound(): void {
    this.activeBets.clear()
    this.gameState = null
  }
}
