// Laravel API Client for Farcaster integration
import axios, { AxiosInstance, AxiosResponse } from 'axios'
import { LaravelApiResponse, FarcasterUser, GameWallet, PlayerBet } from './types'

export class LaravelApiClient {
  private client: AxiosInstance

  constructor(baseURL: string = process.env.LARAVEL_API_URL || 'http://localhost:8000') {
    this.client = axios.create({
      baseURL,
      timeout: 10000,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    })

    // Add response interceptor for consistent error handling
    this.client.interceptors.response.use(
      (response) => response,
      (error) => {
        console.error('Laravel API Error:', error.response?.data || error.message)
        return Promise.reject(error)
      }
    )
  }

  /**
   * Authentication - Farcaster user login/registration
   */
  async authenticateFarcasterUser(fid: number, username: string, displayName: string): Promise<LaravelApiResponse<any>> {
    try {
      const response = await this.client.post('/auth/farcaster', {
        fid,
        username,
        displayName
      })
      return response.data
    } catch (error: any) {
      return {
        success: false,
        error: error.response?.data?.message || 'Authentication failed'
      }
    }
  }

  /**
   * Get user wallet balance
   */
  async getUserWallet(fid: number): Promise<LaravelApiResponse<GameWallet>> {
    try {
      const response = await this.client.get(`/api/farcaster/wallet/${fid}`)
      return response.data
    } catch (error: any) {
      return {
        success: false,
        error: error.response?.data?.message || 'Failed to get wallet'
      }
    }
  }

  /**
   * Process Farcaster deposit
   */
  async processDeposit(data: {
    fid: number
    amount: number
    transactionHash: string
    fromAddress: string
    currency?: string
  }): Promise<LaravelApiResponse<{ newBalance: number }>> {
    try {
      const response = await this.client.post('/api/farcaster/deposit', data)
      return response.data
    } catch (error: any) {
      return {
        success: false,
        error: error.response?.data?.message || 'Deposit failed'
      }
    }
  }

  /**
   * Process withdrawal request
   */
  async processWithdrawal(data: {
    fid: number
    amount: number
    toAddress: string
    currency?: string
  }): Promise<LaravelApiResponse<{ withdrawalId: string }>> {
    try {
      const response = await this.client.post('/api/farcaster/withdraw', data)
      return response.data
    } catch (error: any) {
      return {
        success: false,
        error: error.response?.data?.message || 'Withdrawal failed'
      }
    }
  }

  /**
   * Place a bet through Laravel API
   */
  async placeBet(data: {
    fid: number
    amount: number
    roundId: string
    autoCashOut?: number
  }): Promise<LaravelApiResponse<PlayerBet>> {
    try {
      const response = await this.client.post('/api/farcaster/bet', data)
      return response.data
    } catch (error: any) {
      return {
        success: false,
        error: error.response?.data?.message || 'Bet placement failed'
      }
    }
  }

  /**
   * Process cash out
   */
  async cashOut(data: {
    fid: number
    betId: string
    multiplier: number
  }): Promise<LaravelApiResponse<{ winAmount: number; newBalance: number }>> {
    try {
      const response = await this.client.post('/api/farcaster/cashout', data)
      return response.data
    } catch (error: any) {
      return {
        success: false,
        error: error.response?.data?.message || 'Cash out failed'
      }
    }
  }

  /**
   * Get game history for user
   */
  async getUserGameHistory(fid: number, limit: number = 50): Promise<LaravelApiResponse<PlayerBet[]>> {
    try {
      const response = await this.client.get(`/api/farcaster/history/${fid}?limit=${limit}`)
      return response.data
    } catch (error: any) {
      return {
        success: false,
        error: error.response?.data?.message || 'Failed to get history'
      }
    }
  }

  /**
   * Get current game state from Laravel
   */
  async getCurrentGameState(): Promise<LaravelApiResponse<any>> {
    try {
      const response = await this.client.get('/api/game/current-state')
      return response.data
    } catch (error: any) {
      return {
        success: false,
        error: error.response?.data?.message || 'Failed to get game state'
      }
    }
  }

  /**
   * Social Payments
   */
  async sendSocialPayment(data: {
    fromFid: number
    toFid: number
    amount: number
    type: 'tip' | 'gift_bet' | 'split_win'
    message?: string
  }): Promise<LaravelApiResponse<any>> {
    try {
      const response = await this.client.post('/api/farcaster/social-payment', data)
      return response.data
    } catch (error: any) {
      return {
        success: false,
        error: error.response?.data?.message || 'Social payment failed'
      }
    }
  }

  /**
   * Process referral
   */
  async processReferral(referrerFid: number, newUserFid: number): Promise<LaravelApiResponse<any>> {
    try {
      const response = await this.client.post('/api/farcaster/referral', {
        referrerFid,
        newUserFid
      })
      return response.data
    } catch (error: any) {
      return {
        success: false,
        error: error.response?.data?.message || 'Referral processing failed'
      }
    }
  }

  /**
   * Get leaderboard data
   */
  async getLeaderboard(type: 'daily' | 'weekly' | 'all_time' = 'daily'): Promise<LaravelApiResponse<any[]>> {
    try {
      const response = await this.client.get(`/api/farcaster/leaderboard?type=${type}`)
      return response.data
    } catch (error: any) {
      return {
        success: false,
        error: error.response?.data?.message || 'Failed to get leaderboard'
      }
    }
  }

  /**
   * Tournament operations
   */
  async getTournaments(): Promise<LaravelApiResponse<any[]>> {
    try {
      const response = await this.client.get('/api/farcaster/tournaments')
      return response.data
    } catch (error: any) {
      return {
        success: false,
        error: error.response?.data?.message || 'Failed to get tournaments'
      }
    }
  }

  async joinTournament(fid: number, tournamentId: string): Promise<LaravelApiResponse<any>> {
    try {
      const response = await this.client.post('/api/farcaster/tournament/join', {
        fid,
        tournamentId
      })
      return response.data
    } catch (error: any) {
      return {
        success: false,
        error: error.response?.data?.message || 'Failed to join tournament'
      }
    }
  }

  /**
   * Analytics and tracking
   */
  async trackEvent(data: {
    fid: number
    event: string
    properties: Record<string, any>
  }): Promise<LaravelApiResponse<any>> {
    try {
      const response = await this.client.post('/api/farcaster/track', data)
      return response.data
    } catch (error: any) {
      console.warn('Analytics tracking failed:', error.message)
      return { success: false, error: 'Tracking failed' }
    }
  }

  /**
   * Get user stats for personalization
   */
  async getUserStats(fid: number): Promise<LaravelApiResponse<any>> {
    try {
      const response = await this.client.get(`/api/farcaster/stats/${fid}`)
      return response.data
    } catch (error: any) {
      return {
        success: false,
        error: error.response?.data?.message || 'Failed to get user stats'
      }
    }
  }

  /**
   * Sync game results back to Laravel
   */
  async syncGameResult(data: {
    roundId: string
    crashPoint: number
    results: PlayerBet[]
    duration: number
  }): Promise<LaravelApiResponse<any>> {
    try {
      const response = await this.client.post('/api/farcaster/sync-results', data)
      return response.data
    } catch (error: any) {
      return {
        success: false,
        error: error.response?.data?.message || 'Failed to sync results'
      }
    }
  }

  /**
   * Health check
   */
  async healthCheck(): Promise<boolean> {
    try {
      const response = await this.client.get('/api/health')
      return response.status === 200
    } catch (error) {
      return false
    }
  }
}

// Export singleton instance
export const laravelApi = new LaravelApiClient()

