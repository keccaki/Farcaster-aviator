"use strict";(()=>{var t={};t.id=488,t.ids=[488],t.modules={4033:t=>{t.exports=require("@coinbase/onchainkit/frame")},145:t=>{t.exports=require("next/dist/compiled/next-server/pages-api.runtime.prod.js")},2824:t=>{t.exports=require("next/server")},6249:(t,e)=>{Object.defineProperty(e,"l",{enumerable:!0,get:function(){return function t(e,a){return a in e?e[a]:"then"in e&&"function"==typeof e.then?e.then(e=>t(e,a)):"function"==typeof e&&"default"===a?e:void 0}}})},7796:(t,e,a)=>{a.r(e),a.d(e,{config:()=>g,default:()=>m,routeModule:()=>h});var i={};a.r(i),a.d(i,{default:()=>p});var r=a(1802),n=a(7153),s=a(6249),o=a(2824),l=a(4033);async function u(){try{let t=await fetch("https://farcaster-aviator.vercel.app/api/game/state",{method:"GET",headers:{"Content-Type":"application/json"}});if(t.ok){let e=(await t.json()).data;return{status:e.status||"waiting",multiplier:e.multiplier||1,roundId:e.round_id||Date.now().toString(),timeRemaining:e.time_remaining,currentBets:e.current_bets||[]}}}catch(t){console.log("Failed to fetch game state:",t)}return{status:"waiting",multiplier:1,roundId:Date.now().toString()}}function f(t){let{status:e,multiplier:a}=t,i="Ready to Fly!",r="#00ff88",n="";"flying"===e?(i="FLYING! \uD83D\uDE80",r="#ffaa00",n=`${a.toFixed(2)}x`):"crashed"===e&&(i="CRASHED! \uD83D\uDCA5",r="#ff4444",n=`Crashed at ${a.toFixed(2)}x`);let s=`
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
      ${n?`
        <text x="600" y="350" text-anchor="middle" font-family="Arial, sans-serif" 
              font-size="72" font-weight="bold" fill="#ffaa00">
          ${n}
        </text>
      `:""}
      
      <!-- Instructions -->
      <text x="600" y="${n?"450":"350"}" text-anchor="middle" font-family="Arial, sans-serif" 
            font-size="24" fill="#cccccc">
        ${"waiting"===e?"Place your bet and watch the multiplier climb!":"flying"===e?"Cash out before the crash!":"Next round starting soon..."}
      </text>
      
      <!-- Round ID -->
      <text x="600" y="${n?"500":"400"}" text-anchor="middle" font-family="Arial, sans-serif" 
            font-size="16" fill="#888888">
        Round: ${t.roundId.slice(-8)}
      </text>
    </svg>
  `;return`data:image/svg+xml;base64,${Buffer.from(s).toString("base64")}`}async function c(t,e){try{let e=await t.json(),{isValid:a,message:i}=await (0,l.getFrameMessage)(e);if(!a||!i)return new o.NextResponse("Invalid frame message",{status:400});let r=i.button,n=i.interactor.fid,s=await u(),c="",d=s;switch(r){case 1:if("waiting"===s.status)try{let t=await fetch("https://farcaster-aviator.vercel.app/api/game/bet",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({fid:n,amount:10,roundId:s.roundId,autoCashOut:2})});if(t.ok){let e=await t.json();console.log("Bet placed successfully:",e),d=await u()}}catch(t){console.log("Failed to place bet:",t)}else if("flying"===s.status)try{let t=await fetch("https://farcaster-aviator.vercel.app/api/game/cashout",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({fid:n,betId:`bet_${n}_${s.roundId}`,multiplier:s.multiplier})});if(t.ok){let e=await t.json();console.log("Cash out successful:",e)}d=await u()}catch(t){console.log("Failed to cash out:",t)}break;case 2:c=`${process.env.GAME_API_URL}`;break;case 3:c="https://farcaster-aviator.vercel.app/api/frames/aviator/stats"}let p="waiting"===d.status?[{label:"\uD83D\uDCB0 Bet $10"}]:"flying"===d.status?[{label:"\uD83D\uDCB8 Cash Out Now!"}]:[{label:"\uD83D\uDE80 New Round"}],m=(0,l.getFrameHtmlResponse)({buttons:p,image:{src:f(d)},postUrl:"/api/frames/aviator",...c&&{redirectUrl:c}});return new o.NextResponse(m)}catch(t){return console.error("Error in POST handler:",t),new o.NextResponse("Frame error",{status:500})}}async function d(t,e){try{let t=await u(),e="waiting"===t.status?[{label:"\uD83C\uDFAE Play Game"}]:"flying"===t.status?[{label:"\uD83D\uDCB8 Cash Out!"}]:[{label:"\uD83D\uDE80 New Round"}],a=(0,l.getFrameHtmlResponse)({buttons:e,image:{src:f(t)},postUrl:"/api/frames/aviator"});return new o.NextResponse(a)}catch(e){console.error("Error in GET handler:",e);let t=(0,l.getFrameHtmlResponse)({buttons:[{label:"\uD83C\uDFAE Play Game"}],image:{src:f({status:"waiting",multiplier:1,roundId:"demo"})},postUrl:"/api/frames/aviator"});return new o.NextResponse(t)}}async function p(t,e){return"POST"===t.method?await c(t,e):await d(t,e)}let m=(0,s.l)(i,"default"),g=(0,s.l)(i,"config"),h=new r.PagesAPIRouteModule({definition:{kind:n.x.PAGES_API,page:"/api/frames/aviator",pathname:"/api/frames/aviator",bundlePath:"",filename:""},userland:i})},7153:(t,e)=>{var a;Object.defineProperty(e,"x",{enumerable:!0,get:function(){return a}}),function(t){t.PAGES="PAGES",t.PAGES_API="PAGES_API",t.APP_PAGE="APP_PAGE",t.APP_ROUTE="APP_ROUTE"}(a||(a={}))},1802:(t,e,a)=>{t.exports=a(145)}};var e=require("../../../webpack-api-runtime.js");e.C(t);var a=e(e.s=7796);module.exports=a})();