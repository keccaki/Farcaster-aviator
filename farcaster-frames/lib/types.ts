// Core types for Aviator Farcaster integration

export interface GameState {
  roundId: string
  status: 'waiting' | 'flying' | 'crashed'
  multiplier: number
  crashPoint?: number
  startTime: Date
  duration: number
  totalPot: number
  playersCount: number
  topBets: PlayerBet[]
  timeRemaining: number
}

export interface PlayerBet {
  id: string
  fid: number
  username: string
  amount: number
  multiplier?: number
  status: 'active' | 'cashed_out' | 'lost'
  cashedOutAt?: number
  winAmount?: number
  placedAt: Date
}

export interface FarcasterUser {
  fid: number
  username: string
  displayName: string
  pfpUrl: string
  followerCount: number
  followingCount: number
  verified: boolean
}

export interface GameWallet {
  userId: number
  fid: number
  balance: number
  totalDeposited: number
  totalWithdrawn: number
  totalWon: number
  totalLost: number
  lastActivity: Date
}

export interface Tournament {
  id: string
  name: string
  type: 'biggest_multiplier' | 'most_wins' | 'highest_profit'
  duration: string
  prizePool: number
  entryFee: number
  maxParticipants: number
  currentParticipants: number
  leaderboard: TournamentPlayer[]
  status: 'upcoming' | 'active' | 'completed'
  startTime: Date
  endTime: Date
}

export interface TournamentPlayer {
  fid: number
  username: string
  score: number
  position: number
  prize?: number
}

export interface SocialPayment {
  id: string
  fromFid: number
  toFid: number
  amount: number
  type: 'tip' | 'gift_bet' | 'split_win'
  message?: string
  relatedBetId?: string
  createdAt: Date
}

export interface ViralShare {
  id: string
  userFid: number
  shareType: 'big_win' | 'tournament_invite' | 'referral' | 'custom'
  metadata: {
    multiplier?: number
    winAmount?: number
    tournamentId?: string
    customMessage?: string
  }
  clicks: number
  conversions: number
  createdAt: Date
}

export interface FrameResponse {
  image: string
  buttons: FrameButton[]
  inputText?: string
  postUrl?: string
  aspectRatio?: '1.91:1' | '1:1'
}

export interface FrameButton {
  label: string
  action: 'post' | 'link' | 'mint'
  target?: string
  postUrl?: string
}

export interface LaravelApiResponse<T = any> {
  success: boolean
  data?: T
  error?: string
  message?: string
}

export interface GameMetrics {
  activeUsers: number
  totalBets: number
  totalVolume: number
  averageMultiplier: number
  houseEdge: number
  socialShares: number
  viralCoefficient: number
}

export interface UserStats {
  fid: number
  totalBets: number
  totalVolume: number
  biggestWin: number
  bestMultiplier: number
  winRate: number
  netProfit: number
  referralCount: number
  achievementCount: number
  lastActive: Date
}

