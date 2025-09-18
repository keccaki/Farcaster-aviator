"use strict";(()=>{var e={};e.id=491,e.ids=[491],e.modules={145:e=>{e.exports=require("next/dist/compiled/next-server/pages-api.runtime.prod.js")},6249:(e,t)=>{Object.defineProperty(t,"l",{enumerable:!0,get:function(){return function e(t,r){return r in t?t[r]:"then"in t&&"function"==typeof t.then?t.then(t=>e(t,r)):"function"==typeof t&&"default"===r?t:void 0}}})},8993:(e,t,r)=>{r.r(t),r.d(t,{config:()=>d,default:()=>c,routeModule:()=>p});var a={};r.r(a),r.d(a,{default:()=>s});var n=r(1802),o=r(7153),i=r(6249);async function s(e,t){let r=`<!DOCTYPE html>
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
        <strong>Method:</strong> ${e.method}<br>
        <strong>User-Agent:</strong> ${e.headers["user-agent"]?.slice(0,50)||"Unknown"}<br>
        <strong>Time:</strong> ${new Date().toLocaleString()}
      </div>
      ${"POST"===e.method?`
        <div style="background: #10b981; color: white; padding: 15px; border-radius: 8px;">
          ✅ Frame button clicked! POST request received.
        </div>
      `:`
        <div style="background: #3b82f6; color: white; padding: 15px; border-radius: 8px;">
          ℹ️ This is a GET request. Click frame buttons to test POST.
        </div>
      `}
    </div>
  </div>
</body>
</html>`;t.setHeader("Content-Type","text/html"),t.status(200).send(r)}let c=(0,i.l)(a,"default"),d=(0,i.l)(a,"config"),p=new n.PagesAPIRouteModule({definition:{kind:o.x.PAGES_API,page:"/api/test-frame",pathname:"/api/test-frame",bundlePath:"",filename:""},userland:a})},7153:(e,t)=>{var r;Object.defineProperty(t,"x",{enumerable:!0,get:function(){return r}}),function(e){e.PAGES="PAGES",e.PAGES_API="PAGES_API",e.APP_PAGE="APP_PAGE",e.APP_ROUTE="APP_ROUTE"}(r||(r={}))},1802:(e,t,r)=>{e.exports=r(145)}};var t=require("../../webpack-api-runtime.js");t.C(e);var r=t(t.s=8993);module.exports=r})();