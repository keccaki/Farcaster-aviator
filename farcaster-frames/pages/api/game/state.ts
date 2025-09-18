import { NextApiRequest, NextApiResponse } from 'next';

export const config = {
  runtime: 'edge',
};

export default async function handler(req: Request): Promise<Response> {
  if (req.method !== 'GET') {
    return new Response(JSON.stringify({ success: false, error: 'Method not allowed' }), {
      status: 405,
      headers: { 'Content-Type': 'application/json' },
    });
  }

  try {
    // Simulate current game state for demo
    const gameStates = ['waiting', 'flying', 'crashed'];
    const currentState = gameStates[Math.floor(Date.now() / 10000) % gameStates.length];
    
    let multiplier = 1.0;
    let timeRemaining = null;
    
    if (currentState === 'flying') {
      // Simulate flying multiplier that increases over time
      const flightTime = (Date.now() % 10000) / 1000; // 0-10 seconds
      multiplier = 1.0 + (flightTime * 0.5); // Increases by 0.5x per second
      timeRemaining = Math.max(0, 10 - flightTime);
    } else if (currentState === 'crashed') {
      multiplier = 1.0 + ((Date.now() % 10000) / 1000 * 0.5);
    }

    // Simulate some current bets
    const mockBets = currentState !== 'crashed' ? [
      {
        user_id: 1,
        username: 'Player1',
        amount: 10.0,
        auto_cashout: 2.5
      },
      {
        user_id: 2, 
        username: 'Player2',
        amount: 25.0,
        auto_cashout: null
      }
    ] : [];

    const response = {
      success: true,
      data: {
        status: currentState,
        multiplier: parseFloat(multiplier.toFixed(2)),
        round_id: Math.floor(Date.now() / 30000).toString(), // New round every 30 seconds
        time_remaining: timeRemaining,
        current_bets: mockBets,
        total_bets: mockBets.length,
        total_bet_amount: mockBets.reduce((sum, bet) => sum + bet.amount, 0)
      },
      timestamp: new Date().toISOString()
    };

    return new Response(JSON.stringify(response), {
      status: 200,
      headers: { 
        'Content-Type': 'application/json',
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Methods': 'GET, POST, OPTIONS',
        'Access-Control-Allow-Headers': 'Content-Type'
      },
    });

  } catch (error) {
    console.error('Game state error:', error);
    
    const fallbackResponse = {
      success: true,
      data: {
        status: 'waiting',
        multiplier: 1.0,
        round_id: Date.now().toString(),
        current_bets: [],
        total_bets: 0,
        total_bet_amount: 0
      },
      timestamp: new Date().toISOString()
    };

    return new Response(JSON.stringify(fallbackResponse), {
      status: 200,
      headers: { 
        'Content-Type': 'application/json',
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Methods': 'GET, POST, OPTIONS', 
        'Access-Control-Allow-Headers': 'Content-Type'
      },
    });
  }
}
