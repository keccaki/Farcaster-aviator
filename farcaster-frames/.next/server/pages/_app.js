(()=>{var e={};e.id=888,e.ids=[888],e.modules={7352:(e,t,r)=>{"use strict";r.r(t),r.d(t,{default:()=>n});var a=r(997);let s=require("next/head");var i=r.n(s);function n({Component:e,pageProps:t}){return(0,a.jsxs)(a.Fragment,{children:[(0,a.jsxs)(i(),{children:[a.jsx("title",{children:"Aviator Crash Game"}),a.jsx("meta",{name:"description",content:"Play the thrilling Aviator crash game directly in Farcaster"}),a.jsx("meta",{name:"viewport",content:"width=device-width, initial-scale=1"}),a.jsx("link",{rel:"icon",href:"/icon.svg"}),a.jsx("meta",{property:"fc:miniapp",content:"true"}),a.jsx("meta",{property:"fc:miniapp:name",content:"Aviator Crash Game"}),a.jsx("meta",{property:"fc:miniapp:icon",content:"https://farcaster-aviator.vercel.app/icon.svg"}),a.jsx("meta",{property:"fc:miniapp:splash",content:"https://farcaster-aviator.vercel.app/splash.svg"}),a.jsx("meta",{name:"viewport",content:"width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"}),a.jsx("script",{dangerouslySetInnerHTML:{__html:`
            (function() {
              console.log('Loading Farcaster SDK...');
              const script = document.createElement('script');
              script.type = 'module';
              script.textContent = \`
                try {
                  const { sdk } = await import('https://esm.sh/@farcaster/miniapp-sdk');
                  window.farcasterSDK = sdk;
                  console.log('✅ Farcaster SDK loaded successfully:', sdk);
                  
                  // Dispatch custom event when SDK is ready
                  window.dispatchEvent(new CustomEvent('farcasterSDKReady', { detail: sdk }));
                } catch (error) {
                  console.error('Failed to load Farcaster SDK:', error);
                  window.farcasterSDK = null;
                }
              \`;
              document.head.appendChild(script);
            })();
          `}})]}),a.jsx(e,{...t})]})}r(6764)},6764:()=>{},997:e=>{"use strict";e.exports=require("react/jsx-runtime")}};var t=require("../webpack-runtime.js");t.C(e);var r=t(t.s=7352);module.exports=r})();