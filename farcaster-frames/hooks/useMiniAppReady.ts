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
    ;(async () => {
      if (!sdkMod || typeof window === 'undefined') {
        // Not in a Mini App environment (e.g., dev browser). Don't block the UI.
        setIsMiniApp(false)
        setSdkReady(true)
        return
      }

      const { sdk } = sdkMod
      try {
        // Check if we're running inside a Mini App by trying to access the context
        let runningInsideMiniApp = false
        try {
          const context = await sdk.context
          runningInsideMiniApp = !!context
        } catch (contextError) {
          // If context fails, we're probably not in a Mini App
          console.log('Not running in Mini App context:', contextError)
        }
        
        setIsMiniApp(runningInsideMiniApp)

        // Always allow your app to render; only call ready inside Mini App
        if (!runningInsideMiniApp) {
          setSdkReady(true)
          return
        }

        if (!called.current) {
          called.current = true
          // Call as early as you can once your initial view can paint
          await sdk.actions.ready({ disableNativeGestures: true })
        }
        if (!cancelled) setSdkReady(true)
      } catch (e) {
        // Do NOT trap the user in a spinner—render app anyway
        setError(e)
        setSdkReady(true)
      }
    })()
    return () => {
      cancelled = true
    }
  }, [])

  return { sdkReady, isMiniApp, error }
}
