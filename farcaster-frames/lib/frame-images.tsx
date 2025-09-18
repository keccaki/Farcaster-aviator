// Dynamic Frame Image Generator for Aviator Game
import { ImageResponse } from '@vercel/og'
import { GameState, PlayerBet, Tournament, UserStats } from './types'

export class FrameImageGenerator {
  private baseUrl: string

  constructor(baseUrl: string = process.env.NEXT_PUBLIC_FRAME_BASE_URL || 'https://aviator-frames.vercel.app') {
    this.baseUrl = baseUrl
  }

  /**
   * Generate main game frame image
   */
  async generateGameFrame(gameState: GameState): Promise<ImageResponse> {
    return new ImageResponse(
      (
        <div
          style={{
            height: '100%',
            width: '100%',
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'center',
            justifyContent: 'center',
            backgroundColor: '#1a1a2e',
            backgroundImage: 'linear-gradient(135deg, #1a1a2e 0%, #16213e 35%, #0f3460 100%)',
            color: 'white',
            fontFamily: 'Inter, sans-serif',
          }}
        >
          {/* Header */}
          <div style={{
            position: 'absolute',
            top: '20px',
            left: '30px',
            display: 'flex',
            alignItems: 'center',
            fontSize: '28px',
            fontWeight: 'bold'
          }}>
            🚀 AVIATOR
          </div>

          {/* Round Info */}
          <div style={{
            position: 'absolute',
            top: '20px',
            right: '30px',
            fontSize: '20px',
            opacity: 0.8
          }}>
            Round #{gameState.roundId.slice(-4)}
          </div>

          {/* Main Multiplier Display */}
          <div style={{
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'center',
            marginTop: '-50px'
          }}>
            {gameState.status === 'waiting' ? (
              <div style={{ textAlign: 'center' }}>
                <div style={{ fontSize: '48px', marginBottom: '20px' }}>⏳</div>
                <div style={{ fontSize: '32px', fontWeight: 'bold' }}>Starting Soon...</div>
                <div style={{ fontSize: '24px', opacity: 0.8, marginTop: '10px' }}>
                  {Math.ceil(gameState.timeRemaining / 1000)}s
                </div>
              </div>
            ) : gameState.status === 'flying' ? (
              <div style={{ textAlign: 'center' }}>
                <div style={{ 
                  fontSize: '80px', 
                  fontWeight: 'bold',
                  background: 'linear-gradient(45deg, #ffd700, #ffed4e)',
                  backgroundClip: 'text',
                  WebkitBackgroundClip: 'text',
                  WebkitTextFillColor: 'transparent'
                }}>
                  {gameState.multiplier.toFixed(2)}x
                </div>
                <div style={{ fontSize: '24px', opacity: 0.8, marginTop: '10px' }}>
                  🔥 FLYING HIGH!
                </div>
              </div>
            ) : (
              <div style={{ textAlign: 'center' }}>
                <div style={{ fontSize: '64px', color: '#ff4757' }}>💥</div>
                <div style={{ 
                  fontSize: '48px', 
                  fontWeight: 'bold', 
                  color: '#ff4757',
                  marginTop: '10px'
                }}>
                  CRASHED at {gameState.multiplier.toFixed(2)}x
                </div>
              </div>
            )}
          </div>

          {/* Game Stats */}
          <div style={{
            position: 'absolute',
            bottom: '100px',
            left: '50%',
            transform: 'translateX(-50%)',
            display: 'flex',
            gap: '40px',
            fontSize: '18px'
          }}>
            <div style={{ textAlign: 'center' }}>
              <div style={{ opacity: 0.7 }}>Players</div>
              <div style={{ fontWeight: 'bold' }}>{gameState.playersCount}</div>
            </div>
            <div style={{ textAlign: 'center' }}>
              <div style={{ opacity: 0.7 }}>Total Pot</div>
              <div style={{ fontWeight: 'bold' }}>${gameState.totalPot.toFixed(0)}</div>
            </div>
          </div>

          {/* Top Bets */}
          {gameState.topBets.length > 0 && (
            <div style={{
              position: 'absolute',
              bottom: '30px',
              left: '50%',
              transform: 'translateX(-50%)',
              display: 'flex',
              gap: '20px'
            }}>
              {gameState.topBets.slice(0, 3).map((bet, index) => (
                <div
                  key={bet.id}
                  style={{
                    backgroundColor: 'rgba(255, 255, 255, 0.1)',
                    borderRadius: '12px',
                    padding: '8px 16px',
                    fontSize: '16px',
                    textAlign: 'center'
                  }}
                >
                  <div style={{ opacity: 0.8 }}>{bet.username}</div>
                  <div style={{ fontWeight: 'bold', color: '#ffd700' }}>
                    ${bet.amount}
                  </div>
                </div>
              ))}
            </div>
          )}

          {/* Plane Visual for Flying State */}
          {gameState.status === 'flying' && (
            <div style={{
              position: 'absolute',
              top: '140px',
              right: '100px',
              fontSize: '40px',
              transform: 'rotate(-15deg)',
              animation: 'bounce 2s infinite'
            }}>
              ✈️
            </div>
          )}
        </div>
      ),
      {
        width: 1200,
        height: 630,
      }
    )
  }

  /**
   * Generate betting interface frame
   */
  async generateBettingFrame(userBalance: number, minBet: number = 1): Promise<ImageResponse> {
    return new ImageResponse(
      (
        <div style={{
          height: '100%',
          width: '100%',
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          justifyContent: 'center',
          backgroundColor: '#1a1a2e',
          backgroundImage: 'linear-gradient(135deg, #1a1a2e 0%, #16213e 35%, #0f3460 100%)',
          color: 'white',
          fontFamily: 'Inter, sans-serif',
        }}>
          <div style={{
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'center'
          }}>
            <div style={{ fontSize: '48px', marginBottom: '20px' }}>💰</div>
            <div style={{ fontSize: '36px', fontWeight: 'bold', marginBottom: '10px' }}>
              Place Your Bet
            </div>
            <div style={{ fontSize: '24px', opacity: 0.8, marginBottom: '30px' }}>
              Balance: ${userBalance.toFixed(2)}
            </div>
            
            <div style={{
              display: 'flex',
              gap: '20px',
              marginBottom: '30px'
            }}>
              {[10, 25, 50, 100].map(amount => (
                <div
                  key={amount}
                  style={{
                    backgroundColor: amount <= userBalance ? '#4caf50' : '#666',
                    borderRadius: '12px',
                    padding: '15px 25px',
                    fontSize: '20px',
                    fontWeight: 'bold'
                  }}
                >
                  ${amount}
                </div>
              ))}
            </div>

            <div style={{
              fontSize: '18px',
              opacity: 0.7,
              textAlign: 'center'
            }}>
              Choose your bet amount or enter custom amount below
            </div>
          </div>
        </div>
      ),
      {
        width: 1200,
        height: 630,
      }
    )
  }

  /**
   * Generate win celebration frame
   */
  async generateWinFrame(
    username: string, 
    betAmount: number, 
    multiplier: number, 
    winAmount: number
  ): Promise<ImageResponse> {
    const isLegendaryWin = multiplier >= 10
    const isBigWin = multiplier >= 5

    return new ImageResponse(
      (
        <div style={{
          height: '100%',
          width: '100%',
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          justifyContent: 'center',
          backgroundColor: isLegendaryWin ? '#8b5cf6' : isBigWin ? '#10b981' : '#3b82f6',
          backgroundImage: `linear-gradient(135deg, ${
            isLegendaryWin ? '#8b5cf6 0%, #a855f7 50%, #c084fc 100%' :
            isBigWin ? '#10b981 0%, #14b8a6 50%, #06d6a0 100%' :
            '#3b82f6 0%, #1d4ed8 50%, #1e40af 100%'
          })`,
          color: 'white',
          fontFamily: 'Inter, sans-serif',
        }}>
          {/* Celebration Icons */}
          <div style={{
            position: 'absolute',
            top: '20px',
            left: '50%',
            transform: 'translateX(-50%)',
            fontSize: '48px'
          }}>
            {isLegendaryWin ? '🏆🎉🚀' : isBigWin ? '🎉💰🔥' : '🎉💎✨'}
          </div>

          {/* Main Content */}
          <div style={{
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'center',
            textAlign: 'center'
          }}>
            <div style={{ 
              fontSize: isLegendaryWin ? '32px' : '28px', 
              fontWeight: 'bold',
              marginBottom: '20px'
            }}>
              {isLegendaryWin ? 'LEGENDARY WIN!' : isBigWin ? 'BIG WIN!' : 'WINNER!'}
            </div>

            <div style={{
              fontSize: '72px',
              fontWeight: 'bold',
              marginBottom: '10px',
              textShadow: '0 4px 8px rgba(0,0,0,0.3)'
            }}>
              {multiplier.toFixed(2)}x
            </div>

            <div style={{
              fontSize: '36px',
              fontWeight: 'bold',
              marginBottom: '20px'
            }}>
              ${betAmount} → ${winAmount.toFixed(2)}
            </div>

            <div style={{
              fontSize: '24px',
              opacity: 0.9,
              marginBottom: '20px'
            }}>
              @{username}
            </div>

            {/* Rarity indicator */}
            <div style={{
              backgroundColor: 'rgba(255,255,255,0.2)',
              borderRadius: '20px',
              padding: '8px 16px',
              fontSize: '16px'
            }}>
              {isLegendaryWin 
                ? 'Only 1% of players hit this!' 
                : isBigWin 
                ? 'Top 5% multiplier!' 
                : 'Great timing!'
              }
            </div>
          </div>

          {/* Footer */}
          <div style={{
            position: 'absolute',
            bottom: '20px',
            fontSize: '20px',
            opacity: 0.8
          }}>
            🚀 Play Aviator on Farcaster
          </div>
        </div>
      ),
      {
        width: 1200,
        height: 630,
      }
    )
  }

  /**
   * Generate tournament frame
   */
  async generateTournamentFrame(tournament: Tournament, userPosition?: number): Promise<ImageResponse> {
    return new ImageResponse(
      (
        <div style={{
          height: '100%',
          width: '100%',
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          justifyContent: 'center',
          backgroundColor: '#6366f1',
          backgroundImage: 'linear-gradient(135deg, #6366f1 0%, #8b5cf6 35%, #d946ef 100%)',
          color: 'white',
          fontFamily: 'Inter, sans-serif',
        }}>
          {/* Header */}
          <div style={{
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'center',
            textAlign: 'center',
            marginBottom: '40px'
          }}>
            <div style={{ fontSize: '48px', marginBottom: '10px' }}>🏆</div>
            <div style={{ fontSize: '36px', fontWeight: 'bold', marginBottom: '10px' }}>
              {tournament.name}
            </div>
            <div style={{ fontSize: '28px', fontWeight: 'bold', color: '#ffd700' }}>
              ${tournament.prizePool} Prize Pool
            </div>
          </div>

          {/* Stats */}
          <div style={{
            display: 'flex',
            gap: '40px',
            marginBottom: '30px'
          }}>
            <div style={{ textAlign: 'center' }}>
              <div style={{ fontSize: '24px', fontWeight: 'bold' }}>
                {tournament.currentParticipants}
              </div>
              <div style={{ fontSize: '16px', opacity: 0.8 }}>
                Players
              </div>
            </div>
            <div style={{ textAlign: 'center' }}>
              <div style={{ fontSize: '24px', fontWeight: 'bold' }}>
                ${tournament.entryFee}
              </div>
              <div style={{ fontSize: '16px', opacity: 0.8 }}>
                Entry Fee
              </div>
            </div>
            <div style={{ textAlign: 'center' }}>
              <div style={{ fontSize: '24px', fontWeight: 'bold' }}>
                {tournament.duration}
              </div>
              <div style={{ fontSize: '16px', opacity: 0.8 }}>
                Duration
              </div>
            </div>
          </div>

          {/* Leaderboard Preview */}
          {tournament.leaderboard.length > 0 && (
            <div style={{
              display: 'flex',
              flexDirection: 'column',
              gap: '10px',
              minWidth: '400px'
            }}>
              {tournament.leaderboard.slice(0, 3).map((player, index) => (
                <div
                  key={player.fid}
                  style={{
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'center',
                    backgroundColor: 'rgba(255,255,255,0.1)',
                    borderRadius: '8px',
                    padding: '8px 16px'
                  }}
                >
                  <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                    <div style={{ fontSize: '20px' }}>
                      {index === 0 ? '🥇' : index === 1 ? '🥈' : '🥉'}
                    </div>
                    <div>{player.username}</div>
                  </div>
                  <div style={{ fontWeight: 'bold' }}>
                    {player.score.toFixed(2)}x
                  </div>
                </div>
              ))}
            </div>
          )}

          {/* User Position */}
          {userPosition && (
            <div style={{
              position: 'absolute',
              bottom: '20px',
              fontSize: '20px',
              textAlign: 'center'
            }}>
              Your Position: #{userPosition}
            </div>
          )}
        </div>
      ),
      {
        width: 1200,
        height: 630,
      }
    )
  }

  /**
   * Generate leaderboard frame
   */
  async generateLeaderboardFrame(leaders: any[], period: string = 'Daily'): Promise<ImageResponse> {
    return new ImageResponse(
      (
        <div style={{
          height: '100%',
          width: '100%',
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          justifyContent: 'center',
          backgroundColor: '#1f2937',
          backgroundImage: 'linear-gradient(135deg, #1f2937 0%, #374151 35%, #4b5563 100%)',
          color: 'white',
          fontFamily: 'Inter, sans-serif',
        }}>
          {/* Header */}
          <div style={{
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'center',
            marginBottom: '40px'
          }}>
            <div style={{ fontSize: '48px', marginBottom: '10px' }}>🏆</div>
            <div style={{ fontSize: '32px', fontWeight: 'bold' }}>
              {period} Leaderboard
            </div>
          </div>

          {/* Leaders List */}
          <div style={{
            display: 'flex',
            flexDirection: 'column',
            gap: '15px',
            minWidth: '500px'
          }}>
            {leaders.slice(0, 5).map((leader, index) => (
              <div
                key={leader.fid}
                style={{
                  display: 'flex',
                  justifyContent: 'space-between',
                  alignItems: 'center',
                  backgroundColor: index < 3 
                    ? `rgba(255, 215, 0, ${0.3 - index * 0.1})` 
                    : 'rgba(255,255,255,0.05)',
                  borderRadius: '12px',
                  padding: '15px 20px',
                  border: index < 3 ? '2px solid rgba(255, 215, 0, 0.5)' : 'none'
                }}
              >
                <div style={{ display: 'flex', alignItems: 'center', gap: '15px' }}>
                  <div style={{ 
                    fontSize: '24px',
                    minWidth: '40px',
                    textAlign: 'center'
                  }}>
                    {index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : `#${index + 1}`}
                  </div>
                  <div style={{ fontSize: '18px', fontWeight: 'bold' }}>
                    {leader.username}
                  </div>
                </div>
                <div style={{
                  display: 'flex',
                  flexDirection: 'column',
                  alignItems: 'flex-end'
                }}>
                  <div style={{ fontSize: '20px', fontWeight: 'bold', color: '#10b981' }}>
                    {leader.bestMultiplier ? `${leader.bestMultiplier}x` : `$${leader.totalWinnings}`}
                  </div>
                  <div style={{ fontSize: '14px', opacity: 0.7 }}>
                    {leader.gamesPlayed} games
                  </div>
                </div>
              </div>
            ))}
          </div>

          {/* Footer */}
          <div style={{
            position: 'absolute',
            bottom: '20px',
            fontSize: '16px',
            opacity: 0.7,
            textAlign: 'center'
          }}>
            🚀 Compete in Aviator • Top players win prizes
          </div>
        </div>
      ),
      {
        width: 1200,
        height: 630,
      }
    )
  }
}

