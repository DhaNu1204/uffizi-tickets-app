import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import * as Sentry from '@sentry/react'
import './index.css'
import App from './App.jsx'

// Initialize Sentry error tracking
Sentry.init({
  dsn: import.meta.env.VITE_SENTRY_DSN,
  environment: import.meta.env.MODE, // 'development' or 'production'
  enabled: import.meta.env.PROD, // Only enable in production
  sendDefaultPii: true,
  // Performance monitoring
  tracesSampleRate: 0.1, // 10% of transactions for performance monitoring
  // Session replay (optional - records user sessions on errors)
  replaysSessionSampleRate: 0, // Don't record normal sessions
  replaysOnErrorSampleRate: 1.0, // Record 100% of sessions with errors
})

createRoot(document.getElementById('root')).render(
  <StrictMode>
    <Sentry.ErrorBoundary fallback={<ErrorFallback />}>
      <App />
    </Sentry.ErrorBoundary>
  </StrictMode>,
)

// Error fallback component
function ErrorFallback() {
  return (
    <div style={{
      display: 'flex',
      flexDirection: 'column',
      alignItems: 'center',
      justifyContent: 'center',
      height: '100vh',
      fontFamily: 'system-ui, sans-serif'
    }}>
      <h1>Something went wrong</h1>
      <p>We've been notified and are working on it.</p>
      <button
        onClick={() => window.location.reload()}
        style={{
          padding: '10px 20px',
          fontSize: '16px',
          cursor: 'pointer',
          marginTop: '20px'
        }}
      >
        Reload Page
      </button>
    </div>
  )
}
