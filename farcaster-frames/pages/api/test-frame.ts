// Simple test frame for immediate testing
import { NextApiRequest, NextApiResponse } from 'next'

export default async function handler(req: NextApiRequest, res: NextApiResponse) {
  // Simple frame HTML for testing
  const frameHtml = `<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Aviator Test Frame</title>
  
  <!-- Farcaster Frame Meta Tags -->
  <meta property="fc:frame" content="vNext">
  <meta property="fc:frame:image" content="https://i.imgur.com/example.jpg">
  <meta property="fc:frame:button:1" content="🎮 Play Aviator">
  <meta property="fc:frame:button:1:action" content="post">
  <meta property="fc:frame:button:2" content="💰 Check Balance">
  <meta property="fc:frame:button:2:action" content="post">
  <meta property="fc:frame:post_url" content="http://localhost:3000/api/test-frame">
  <meta property="og:title" content="Aviator Crash Game">
  <meta property="og:description" content="Play Aviator crash game on Farcaster">
</head>
<body>
  <div style="display: flex; align-items: center; justify-content: center; height: 100vh; font-family: Arial, sans-serif;">
    <div style="text-align: center;">
      <h1 style="color: #8b5cf6;">🚀 Aviator Test Frame</h1>
      <p style="font-size: 18px; color: #666;">Your Farcaster frame is working!</p>
      <div style="background: #f0f0f0; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <strong>Method:</strong> ${req.method}<br>
        <strong>User-Agent:</strong> ${req.headers['user-agent']?.slice(0, 50) || 'Unknown'}<br>
        <strong>Time:</strong> ${new Date().toLocaleString()}
      </div>
      ${req.method === 'POST' ? `
        <div style="background: #10b981; color: white; padding: 15px; border-radius: 8px;">
          ✅ Frame button clicked! POST request received.
        </div>
      ` : `
        <div style="background: #3b82f6; color: white; padding: 15px; border-radius: 8px;">
          ℹ️ This is a GET request. Click frame buttons to test POST.
        </div>
      `}
    </div>
  </div>
</body>
</html>`

  // Set proper headers
  res.setHeader('Content-Type', 'text/html')
  res.status(200).send(frameHtml)
}

