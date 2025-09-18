import { NextApiRequest, NextApiResponse } from 'next';

export const config = {
  runtime: 'edge',
};

export default async function handler(req: Request): Promise<Response> {
  if (req.method !== 'POST') {
    return new Response(JSON.stringify({ success: false, error: 'Method not allowed' }), {
      status: 405,
      headers: { 'Content-Type': 'application/json' },
    });
  }

  try {
    const body = await req.json();
    const { fid, betId, multiplier } = body;

    // Validate required fields
    if (!fid || !betId || !multiplier) {
      return new Response(JSON.stringify({
        success: false,
        error: 'Missing required fields: fid, betId, multiplier'
      }), {
        status: 400,
        headers: { 'Content-Type': 'application/json' },
      });
    }

    if (multiplier < 1) {
      return new Response(JSON.stringify({
        success: false,
        error: 'Invalid multiplier'
      }), {
        status: 400,
        headers: { 'Content-Type': 'application/json' },
      });
    }

    // Simulate cash out for demo
    // In production, this would find the actual bet, validate it's active, calculate winnings, etc.
    
    const simulatedBetAmount = 10; // Mock bet amount
    const winAmount = simulatedBetAmount * multiplier;
    const simulatedBalance = 1000; // Mock current balance
    const newBalance = simulatedBalance + winAmount;

    const response = {
      success: true,
      data: {
        betId: betId,
        winAmount: parseFloat(winAmount.toFixed(2)),
        multiplier: parseFloat(multiplier.toFixed(2)),
        newBalance: parseFloat(newBalance.toFixed(2)),
        betAmount: simulatedBetAmount,
        cashedOutAt: new Date().toISOString()
      },
      message: `Congratulations! You won ${winAmount.toFixed(2)} with ${multiplier.toFixed(2)}x multiplier!`
    };

    console.log('Cash out processed:', {
      fid,
      betId,
      multiplier,
      winAmount,
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
    console.error('Cash out error:', error);
    
    return new Response(JSON.stringify({
      success: false,
      error: 'Cash out failed'
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
