import type { AppProps } from 'next/app'
import Head from 'next/head'
import '../styles/globals.css'

export default function App({ Component, pageProps }: AppProps) {
  return (
    <>
      <Head>
        <title>Aviator Crash Game</title>
        <meta name="description" content="Play the thrilling Aviator crash game directly in Farcaster" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link rel="icon" href="/icon.svg" />
        
        {/* Farcaster Mini App meta tags */}
        <meta property="fc:miniapp" content="true" />
        <meta property="fc:miniapp:name" content="Aviator Crash Game" />
        <meta property="fc:miniapp:icon" content="https://farcaster-aviator.vercel.app/icon.svg" />
        <meta property="fc:miniapp:splash" content="https://farcaster-aviator.vercel.app/splash.svg" />
        
        {/* Prevent zoom on mobile */}
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
        
        {/* Farcaster Mini App SDK - CDN approach as per docs */}
        <script type="module" dangerouslySetInnerHTML={{
          __html: `
            import { sdk } from 'https://esm.sh/@farcaster/miniapp-sdk'
            window.farcasterSDK = sdk
            console.log('Farcaster SDK loaded:', sdk)
          `
        }} />
      </Head>
      <Component {...pageProps} />
    </>
  )
}
