// hooks/useMiniAppReady.ts
import { useEffect, useRef, useState } from 'react'

// Import the official SDK directly (no window polling)
let sdkMod: typeof import('@farcaster/miniapp-sdk') | null = null
try {
  // Dynamic import so SSR never touches it
  if (typeof window !== 'undefined') {
    // eslint-disable-next-line @typescript-eslint/consistent-type-imports
    sdkMod = require('@farcaster/miniapp-sdk') as typeof import('@farcaster/miniapp-sdk')
  }
} catch (_) {
  // ignore – we'll treat as "not a mini app" below
}

export function useMiniAppReady() {
  const [sdkReady, setSdkReady] = useState(false)
  const [isMiniApp, setIsMiniApp] = useState(false)
  const [error, setError] = useState<unknown>(null)
  const called = useRef(false)

  useEffect(() => {
    let cancelled = false
    
    const initializeApp = async () => {
      try {
        console.log('🔍 useMiniAppReady: Starting initialization...')
        console.log('🔍 sdkMod:', sdkMod)
        console.log('🔍 typeof window:', typeof window)
        
        if (!sdkMod || typeof window === 'undefined') {
          // Not in a Mini App environment (e.g., dev browser). Don't block the UI.
          console.log('🔍 Not in Mini App environment, setting ready=true')
          setIsMiniApp(false)
          setSdkReady(true)
          return
        }

        console.log('🔍 SDK module found, attempting to use it...')
        const { sdk } = sdkMod
        console.log('🔍 SDK object:', sdk)
        
        // Check if we're running inside a Mini App by trying to access the context
        let runningInsideMiniApp = false
        try {
          console.log('🔍 Attempting to access SDK context...')
          const context = await sdk.context
          console.log('🔍 Context result:', context)
          runningInsideMiniApp = !!context
        } catch (contextError) {
          // If context fails, we're probably not in a Mini App
          console.log('🔍 Not running in Mini App context:', contextError)
        }
        
        console.log('🔍 Running inside Mini App:', runningInsideMiniApp)
        setIsMiniApp(runningInsideMiniApp)

        // Always allow your app to render; only call ready inside Mini App
        if (!runningInsideMiniApp) {
          console.log('🔍 Not in Mini App, setting ready=true')
          if (!cancelled) {
            setSdkReady(true)
            console.log('🔍 setSdkReady(true) called successfully')
          }
          return
        }

        if (!called.current) {
          called.current = true
          console.log('🔍 Calling sdk.actions.ready()...')
          // Call as early as you can once your initial view can paint
          await sdk.actions.ready({ disableNativeGestures: true })
          console.log('🔍 SDK ready() called successfully')
        }
        if (!cancelled) {
          console.log('🔍 Setting sdkReady=true')
          setSdkReady(true)
        }
      } catch (e) {
        console.error('🔍 Error in useMiniAppReady:', e)
        // Do NOT trap the user in a spinner—render app anyway
        setError(e)
        setSdkReady(true)
      }
    }
    
    initializeApp()
    
    return () => {
      cancelled = true
    }
  }, [])

  return { sdkReady, isMiniApp, error }
}
