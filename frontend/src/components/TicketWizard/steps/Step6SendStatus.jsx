export default function Step6SendStatus({ booking, wizardData }) {
  const { sendResult, isSending } = wizardData;

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

  const { success, channel_used, messages, errors } = sendResult;

  return (
    <div className="wizard-step-content step-send-status">
      {success ? (
        <div className="success-state">
          <div className="success-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
              <polyline points="22 4 12 14.01 9 11.01" />
            </svg>
          </div>
          <h3>Ticket Sent Successfully!</h3>
          <p>
            The ticket has been delivered to <strong>{booking.customer_name}</strong>
          </p>

          <div className="delivery-details">
            <h4>Delivery Details</h4>
            <div className="detail-row">
              <span className="label">Channel:</span>
              <span className="value">
                {channel_used === 'whatsapp' && 'WhatsApp'}
                {channel_used === 'email_sms' && 'Email + SMS'}
                {channel_used === 'email' && 'Email'}
              </span>
            </div>

            {messages && messages.length > 0 && (
              <div className="messages-sent">
                {messages.map((msg, index) => (
                  <div key={index} className="message-status">
                    <span className={`status-badge ${msg.status}`}>
                      {msg.status === 'sent' && '✓ Sent'}
                      {msg.status === 'queued' && '⏳ Queued'}
                      {msg.status === 'delivered' && '✓✓ Delivered'}
                      {msg.status === 'failed' && '✗ Failed'}
                    </span>
                    <span className="channel">{msg.channel}</span>
                    <span className="recipient">{msg.recipient}</span>
                  </div>
                ))}
              </div>
            )}
          </div>

          <div className="next-steps">
            <h4>What's Next?</h4>
            <ul>
              <li>The booking status has been updated to "Tickets Sent"</li>
              <li>The customer will receive the ticket via {channel_used === 'whatsapp' ? 'WhatsApp' : 'email'}</li>
              <li>You can close this wizard and continue with other bookings</li>
            </ul>
          </div>
        </div>
      ) : (
        <div className="error-state">
          <div className="error-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <circle cx="12" cy="12" r="10" />
              <line x1="15" y1="9" x2="9" y2="15" />
              <line x1="9" y1="9" x2="15" y2="15" />
            </svg>
          </div>
          <h3>Failed to Send Ticket</h3>
          <p>Something went wrong while sending the ticket.</p>

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
