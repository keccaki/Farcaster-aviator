import { NextApiRequest, NextApiResponse } from 'next';

export default async function handler(req: Request): Promise<Response> {
  if (req.method !== 'POST') {
    return new Response(JSON.stringify({ success: false, error: 'Method not allowed' }), {
      status: 405,
      headers: { 'Content-Type': 'application/json' },
    });
  }

  try {
    const body = await req.json();
    const { fid, amount, roundId, autoCashOut } = body;

    // Validate required fields
    if (!fid || !amount || !roundId) {
      return new Response(JSON.stringify({
        success: false,
        error: 'Missing required fields: fid, amount, roundId'
      }), {
        status: 400,
        headers: { 'Content-Type': 'application/json' },
      });
    }

    if (amount < 1) {
      return new Response(JSON.stringify({
        success: false,
        error: 'Minimum bet amount is 1'
      }), {
        status: 400,
        headers: { 'Content-Type': 'application/json' },
      });
    }

    // Simulate bet placement for demo
    // In production, this would validate user balance, create DB records, etc.
    
    const simulatedBalance = 1000; // Mock balance
    
    if (simulatedBalance < amount) {
      return new Response(JSON.stringify({
        success: false,
        error: 'Insufficient balance'
      }), {
        status: 400,
        headers: { 'Content-Type': 'application/json' },
      });
    }

    // Generate a mock bet ID
    const betId = `bet_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;

    const response = {
      success: true,
      data: {
        betId: betId,
        amount: parseFloat(amount),
        roundId: roundId,
        newBalance: simulatedBalance - amount,
        autoCashOut: autoCashOut || null,
        placedAt: new Date().toISOString()
      },
      message: 'Bet placed successfully!'
    };

    console.log('Bet placed:', {
      fid,
      amount,
      roundId,
      betId,
      timestamp: new Date().toISOString()
    });

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
    console.error('Bet placement error:', error);
    
    return new Response(JSON.stringify({
      success: false,
      error: 'Bet placement failed'
    }), {
      status: 500,
      headers: { 
        'Content-Type': 'application/json',
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Methods': 'GET, POST, OPTIONS',
        'Access-Control-Allow-Headers': 'Content-Type'
      },
    });
  }
}
