(self.webpackChunk_N_E=self.webpackChunk_N_E||[]).push([[488],{67:t=>{"use strict";t.exports=require("node:async_hooks")},195:t=>{"use strict";t.exports=require("node:buffer")},623:(t,e,a)=>{"use strict";a.r(e),a.d(e,{default:()=>h}),a(639);var i=a(458),r=a(161),s=a(278),n=a(534),o=a(166),l=a(195).Buffer;async function f(){try{let t=await fetch("https://farcaster-aviator.vercel.app/api/game/state",{method:"GET",headers:{"Content-Type":"application/json"}});if(t.ok){let e=(await t.json()).data;return{status:e.status||"waiting",multiplier:e.multiplier||1,roundId:e.round_id||Date.now().toString(),timeRemaining:e.time_remaining,currentBets:e.current_bets||[]}}}catch(t){console.log("Failed to fetch game state:",t)}return{status:"waiting",multiplier:1,roundId:Date.now().toString()}}function u(t){let{status:e,multiplier:a}=t,i="Ready to Fly!",r="#00ff88",s="";"flying"===e?(i="FLYING! \uD83D\uDE80",r="#ffaa00",s=`${a.toFixed(2)}x`):"crashed"===e&&(i="CRASHED! \uD83D\uDCA5",r="#ff4444",s=`Crashed at ${a.toFixed(2)}x`);let n=`
    <svg width="1200" height="630" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" style="stop-color:#1a1a2e"/>
          <stop offset="100%" style="stop-color:#16213e"/>
        </linearGradient>
      </defs>
      <rect width="1200" height="630" fill="url(#bg)"/>
      
      <!-- Title -->
      <text x="600" y="150" text-anchor="middle" font-family="Arial, sans-serif" 
            font-size="48" font-weight="bold" fill="#ffffff">
        🚀 AVIATOR CRASH GAME
      </text>
      
      <!-- Status -->
      <text x="600" y="250" text-anchor="middle" font-family="Arial, sans-serif" 
            font-size="52" fill="${r}">
        ${i}
      </text>
      
      <!-- Multiplier (if flying/crashed) -->
      ${s?`
        <text x="600" y="350" text-anchor="middle" font-family="Arial, sans-serif" 
              font-size="72" font-weight="bold" fill="#ffaa00">
          ${s}
        </text>
      `:""}
      
      <!-- Instructions -->
      <text x="600" y="${s?"450":"350"}" text-anchor="middle" font-family="Arial, sans-serif" 
            font-size="24" fill="#cccccc">
        ${"waiting"===e?"Place your bet and watch the multiplier climb!":"flying"===e?"Cash out before the crash!":"Next round starting soon..."}
      </text>
      
      <!-- Round ID -->
      <text x="600" y="${s?"500":"400"}" text-anchor="middle" font-family="Arial, sans-serif" 
            font-size="16" fill="#888888">
        Round: ${t.roundId.slice(-8)}
      </text>
    </svg>
  `;return`data:image/svg+xml;base64,${l.from(n).toString("base64")}`}async function c(t,e){try{let e=await t.json(),{isValid:a,message:i}=await (0,o.Xg)(e);if(!a||!i)return new n.xk("Invalid frame message",{status:400});let r=i.button,s=i.interactor.fid,l=await f(),c="",d=l;switch(r){case 1:if("waiting"===l.status)try{let t=await fetch("https://farcaster-aviator.vercel.app/api/game/bet",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({fid:s,amount:10,roundId:l.roundId,autoCashOut:2})});if(t.ok){let e=await t.json();console.log("Bet placed successfully:",e),d=await f()}}catch(t){console.log("Failed to place bet:",t)}else if("flying"===l.status)try{let t=await fetch("https://farcaster-aviator.vercel.app/api/game/cashout",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({fid:s,betId:`bet_${s}_${l.roundId}`,multiplier:l.multiplier})});if(t.ok){let e=await t.json();console.log("Cash out successful:",e)}d=await f()}catch(t){console.log("Failed to cash out:",t)}break;case 2:c=`${process.env.GAME_API_URL}`;break;case 3:c="https://farcaster-aviator.vercel.app/api/frames/aviator/stats"}let p="waiting"===d.status?[{label:"\uD83D\uDCB0 Bet $10"}]:"flying"===d.status?[{label:"\uD83D\uDCB8 Cash Out Now!"}]:[{label:"\uD83D\uDE80 New Round"}],h=(0,o.WB)({buttons:p,image:{src:u(d)},postUrl:"/api/frames/aviator",...c&&{redirectUrl:c}});return new n.xk(h)}catch(t){return console.error("Error in POST handler:",t),new n.xk("Frame error",{status:500})}}async function d(t,e){try{let t=await f(),e="waiting"===t.status?[{label:"\uD83C\uDFAE Play Game"}]:"flying"===t.status?[{label:"\uD83D\uDCB8 Cash Out!"}]:[{label:"\uD83D\uDE80 New Round"}],a=(0,o.WB)({buttons:e,image:{src:u(t)},postUrl:"/api/frames/aviator"});return new n.xk(a)}catch(e){console.error("Error in GET handler:",e);let t=(0,o.WB)({buttons:[{label:"\uD83C\uDFAE Play Game"}],image:{src:u({status:"waiting",multiplier:1,roundId:"demo"})},postUrl:"/api/frames/aviator"});return new n.xk(t)}}async function p(t,e){return"POST"===t.method?await c(t,e):await d(t,e)}function h(t){return(0,i.C)({...t,IncrementalCache:r.k,page:"/api/frames/aviator",handler:(0,s.fd)("/api/frames/aviator",p)})}}},t=>{var e=e=>t(t.s=e);t.O(0,[730,45],()=>e(623));var a=t.O();(_ENTRIES="undefined"==typeof _ENTRIES?{}:_ENTRIES)["middleware_pages/api/frames/aviator"]=a}]);
//# sourceMappingURL=aviator.js.map