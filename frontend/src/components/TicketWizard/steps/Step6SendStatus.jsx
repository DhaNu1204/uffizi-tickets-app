import { useState } from 'react';
import { messagesAPI } from '../../../services/api';

export default function Step6SendStatus({ booking, wizardData }) {
  const { sendResult, isSending } = wizardData;
  const [retryingChannels, setRetryingChannels] = useState({});
  const [retryResults, setRetryResults] = useState({});

  if (isSending) {
    return (
      <div className="wizard-step-content step-send-status">
        <div className="sending-state">
          <span className="spinner large" />
          <h3>Sending Ticket...</h3>
          <p>Please wait while we deliver the ticket to the customer.</p>
        </div>
      </div>
    );
  }

  if (!sendResult) {
    return (
      <div className="wizard-step-content step-send-status">
        <div className="pending-state">
          <p>Click "Send Ticket" on the previous step to send.</p>
        </div>
      </div>
    );
  }

  const { success, channel_used, channel_status, messages, errors } = sendResult;

  // Check if we have partial success (some channels worked, some failed)
  const hasPartialFailure = success && errors && errors.length > 0;

  // Count successes and failures from channel_status
  const channelResults = channel_status || {};
  const successCount = Object.values(channelResults).filter(c => c.success).length;
  const failCount = Object.values(channelResults).filter(c => !c.success).length;

  // Helper to get channel display info
  const getChannelDisplay = (channel) => {
    switch (channel) {
      case 'whatsapp':
        return { icon: '💬', name: 'WhatsApp', color: '#25D366' };
      case 'email':
        return { icon: '✉️', name: 'Email', color: '#7C3AED' };
      case 'sms':
        return { icon: '📱', name: 'SMS', color: '#3B82F6' };
      default:
        return { icon: '📨', name: channel, color: '#6B7280' };
    }
  };

  // Determine channel description
  const getChannelDescription = () => {
    if (channel_used === 'whatsapp_email') {
      return 'WhatsApp + Email (Dual Delivery)';
    }
    if (channel_used === 'whatsapp') return 'WhatsApp';
    if (channel_used === 'email_sms') return 'Email + SMS Notification';
    if (channel_used === 'email') return 'Email';
    return channel_used;
  };

  // Handle retry for a failed channel
  const handleRetry = async (channel, messageId) => {
    if (!messageId) {
      console.error('No message ID for retry');
      return;
    }

    setRetryingChannels(prev => ({ ...prev, [channel]: true }));
    setRetryResults(prev => ({ ...prev, [channel]: null }));

    try {
      const response = await messagesAPI.retry(messageId);
      if (response.data.success) {
        setRetryResults(prev => ({
          ...prev,
          [channel]: { success: true, message: 'Retry successful!' }
        }));
      } else {
        setRetryResults(prev => ({
          ...prev,
          [channel]: { success: false, message: response.data.error || 'Retry failed' }
        }));
      }
    } catch (err) {
      const errorMsg = err.response?.data?.error || 'Retry failed';
      setRetryResults(prev => ({
        ...prev,
        [channel]: { success: false, message: errorMsg }
      }));
    } finally {
      setRetryingChannels(prev => ({ ...prev, [channel]: false }));
    }
  };

  // Get message ID for a channel from messages array
  const getMessageIdForChannel = (channel) => {
    if (messages && messages.length > 0) {
      const msg = messages.find(m => m.channel === channel);
      return msg?.id;
    }
    return null;
  };

  // Render per-channel status using channel_status object
  const renderChannelStatus = () => {
    if (!channel_status || Object.keys(channel_status).length === 0) {
      // Fallback to messages array if no channel_status
      if (messages && messages.length > 0) {
        return messages.map((msg, index) => {
          const channelInfo = getChannelDisplay(msg.channel);
          const isSuccess = msg.status !== 'failed';
          return (
            <div key={index} className={`channel-status-row ${isSuccess ? 'success' : 'failed'}`}>
              <div className="channel-info">
                <span className="channel-icon">{channelInfo.icon}</span>
                <span className="channel-name">{channelInfo.name}</span>
              </div>
              <div className="status-info">
                {isSuccess ? (
                  <span className="status-badge success">✓ {msg.status === 'sent' ? 'Sent' : msg.status}</span>
                ) : (
                  <span className="status-badge failed">✗ Failed</span>
                )}
              </div>
              <div className="recipient-info">{msg.recipient}</div>
            </div>
          );
        });
      }
      return null;
    }

    return Object.entries(channel_status).map(([channel, status]) => {
      const channelInfo = getChannelDisplay(channel);
      const messageId = getMessageIdForChannel(channel);
      const isRetrying = retryingChannels[channel];
      const retryResult = retryResults[channel];

      return (
        <div key={channel} className={`channel-status-row ${status.success || retryResult?.success ? 'success' : 'failed'}`}>
          <div className="channel-info">
            <span className="channel-icon">{channelInfo.icon}</span>
            <span className="channel-name">{channelInfo.name}</span>
          </div>
          <div className="status-info">
            {retryResult?.success ? (
              <span className="status-badge success">✓ Retried</span>
            ) : status.success ? (
              <span className="status-badge success">✓ {status.status === 'sent' ? 'Sent' : status.status}</span>
            ) : (
              <span className="status-badge failed">✗ Failed</span>
            )}
          </div>
          <div className="recipient-info">{status.recipient}</div>
          {/* Show retry button for failed channels */}
          {!status.success && !retryResult?.success && messageId && (
            <div className="retry-action">
              <button
                className="btn-retry-channel"
                onClick={() => handleRetry(channel, messageId)}
                disabled={isRetrying}
              >
                {isRetrying ? (
                  <>
                    <span className="retry-spinner-small"></span>
                    Retrying...
                  </>
                ) : (
                  <>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="retry-icon-small">
                      <polyline points="23 4 23 10 17 10" />
                      <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10" />
                    </svg>
                    Retry
                  </>
                )}
              </button>
            </div>
          )}
          {/* Show retry result message */}
          {retryResult && (
            <div className={`retry-result ${retryResult.success ? 'success' : 'failed'}`}>
              {retryResult.message}
            </div>
          )}
          {!status.success && !retryResult?.success && status.error && (
            <div className="error-detail">{status.error}</div>
          )}
        </div>
      );
    });
  };

  return (
    <div className="wizard-step-content step-send-status">
      {success ? (
        <div className={`success-state ${hasPartialFailure ? 'partial' : ''}`}>
          {/* Icon changes based on partial failure */}
          <div className={`result-icon ${hasPartialFailure ? 'warning' : 'success'}`}>
            {hasPartialFailure ? (
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                <line x1="12" y1="9" x2="12" y2="13" />
                <line x1="12" y1="17" x2="12.01" y2="17" />
              </svg>
            ) : (
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                <polyline points="22 4 12 14.01 9 11.01" />
              </svg>
            )}
          </div>

          <h3>{hasPartialFailure ? 'Ticket Partially Sent' : 'Ticket Sent Successfully!'}</h3>

          {hasPartialFailure ? (
            <p className="partial-warning">
              The ticket was delivered via some channels, but <strong>{failCount} channel{failCount > 1 ? 's' : ''} failed</strong>.
            </p>
          ) : (
            <p>
              The ticket has been delivered to <strong>{booking.customer_name}</strong>
            </p>
          )}

          <div className="delivery-details">
            <h4>Delivery Status</h4>
            <div className="detail-row">
              <span className="label">Method:</span>
              <span className="value">{getChannelDescription()}</span>
            </div>

            {/* Per-Channel Status Display */}
            <div className="channel-status-list">
              {renderChannelStatus()}
            </div>

            {/* Show detailed errors for failed channels */}
            {hasPartialFailure && errors && errors.length > 0 && (
              <div className="partial-failure-details">
                <h5>⚠️ Failed Channel Details:</h5>
                <ul>
                  {errors.map((error, index) => (
                    <li key={index}>{error}</li>
                  ))}
                </ul>
              </div>
            )}
          </div>

          <div className="next-steps">
            <h4>What's Next?</h4>
            <ul>
              <li>The booking status has been updated to "Tickets Sent"</li>
              {hasPartialFailure ? (
                <li className="warning-item">
                  ⚠️ Some delivery channels failed. The customer may not receive all notifications.
                </li>
              ) : channel_used === 'whatsapp_email' ? (
                <li>The customer will receive the ticket via both WhatsApp and Email</li>
              ) : channel_used === 'email_sms' ? (
                <li>The ticket PDF was sent via Email. An SMS notification was also sent to alert the customer.</li>
              ) : channel_used === 'whatsapp' ? (
                <li>The customer will receive the ticket via WhatsApp</li>
              ) : (
                <li>The customer will receive the ticket via Email</li>
              )}
              <li>You can close this wizard and continue with other bookings</li>
            </ul>
          </div>
        </div>
      ) : (
        <div className="error-state">
          <div className="result-icon error">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <circle cx="12" cy="12" r="10" />
              <line x1="15" y1="9" x2="9" y2="15" />
              <line x1="9" y1="9" x2="15" y2="15" />
            </svg>
          </div>
          <h3>Failed to Send Ticket</h3>
          <p>Something went wrong while sending the ticket.</p>

          {/* Per-Channel Status Display for failures */}
          {channel_status && Object.keys(channel_status).length > 0 && (
            <div className="delivery-details">
              <h4>Channel Status</h4>
              <div className="channel-status-list">
                {renderChannelStatus()}
              </div>
            </div>
          )}

          {errors && errors.length > 0 && (
            <div className="error-details">
              <h4>Errors:</h4>
              <ul>
                {errors.map((error, index) => (
                  <li key={index}>{error}</li>
                ))}
              </ul>
            </div>
          )}

          <div className="retry-instructions">
            <p>You can try again by going back and clicking "Send Ticket" again.</p>
          </div>
        </div>
      )}
    </div>
  );
}
