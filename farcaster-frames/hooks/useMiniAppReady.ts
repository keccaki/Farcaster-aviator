'use client';

import { useEffect, useRef, useState } from 'react';

type MiniAppState = {
  sdkReady: boolean;
  isMiniApp: boolean;
  error: unknown | null;
};

export function useMiniAppReady(): MiniAppState {
  const [sdkReady, setSdkReady] = useState(false);
  const [isMiniApp, setIsMiniApp] = useState(false);
  const [error, setError] = useState<unknown>(null);
  const called = useRef(false);

  useEffect(() => {
    let mounted = true;

    (async () => {
      // Only run in the browser
      if (typeof window === 'undefined') {
        return;
      }

      try {
        // Dynamic ESM import – no require()
        const { sdk } = await import('@farcaster/miniapp-sdk');

        // Try to detect if we're in a Mini App by checking context
        let inside = false;
        try {
          const context = await sdk.context;
          inside = !!context;
        } catch {
          // If context fails, we're probably not in a Mini App
          inside = false;
        }
        
        setIsMiniApp(inside);

        if (inside && !called.current) {
          called.current = true;
          // Call as early as possible once UI shell can paint
          await sdk.actions.ready({ disableNativeGestures: true });
        }

        if (mounted) setSdkReady(true);
      } catch (e) {
        // Never block rendering—fall back to normal web
        if (mounted) {
          setError(e);
          setSdkReady(true);
          setIsMiniApp(false);
        }
      }
    })();

    return () => {
      mounted = false;
    };
  }, []);

  return { sdkReady, isMiniApp, error };
}
