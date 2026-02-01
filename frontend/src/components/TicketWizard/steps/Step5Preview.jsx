import './Steps.css';

export default function Step5Preview({ booking, wizardData, isLoading }) {
  const { channelInfo, preview, attachments, language, customMessage } = wizardData;
  const isCustomMessage = language === 'custom' && customMessage;

  if (isLoading) {
    return (
      <div className="wizard-step-content step-preview">
        <div className="loading-state">
          <span className="spinner large" />
          <p>Detecting messaging channel...</p>
        </div>
      </div>
    );
  }

  const getChannelIcon = (channel) => {
    switch (channel) {
      case 'whatsapp':
        return (
          <svg viewBox="0 0 24 24" fill="currentColor" className="channel-icon whatsapp">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
          </svg>
        );
      case 'email':
        return (
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="channel-icon email">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
            <polyline points="22,6 12,13 2,6" />
          </svg>
        );
      case 'sms':
        return (
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="channel-icon sms">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
          </svg>
        );
      default:
        return null;
    }
  };

  const getChannelName = (channel) => {
    switch (channel) {
      case 'whatsapp':
        return 'WhatsApp';
      case 'email':
        return 'Email';
      case 'sms':
        return 'SMS';
      default:
        return channel;
    }
  };

  const primaryChannel = channelInfo?.primary;
  const fallbackChannel = channelInfo?.fallback;

  return (
    <div className="wizard-step-content step-preview">
      <h3>Preview & Confirm</h3>
      <p className="step-description">
        Review the message before sending.
      </p>

      {/* Channel Detection */}
      <div className="channel-detection">
        <h4>Delivery Channel</h4>
        <div className="channel-info">
          {primaryChannel ? (
            <div className="channel-badge primary">
              {getChannelIcon(primaryChannel)}
              <span>{getChannelName(primaryChannel)}</span>
            </div>
          ) : (
            <div className="channel-badge error">
              <span>No channel available</span>
            </div>
          )}
          {fallbackChannel && (
            <>
              <span className="plus">+</span>
              <div className="channel-badge secondary">
                {getChannelIcon(fallbackChannel)}
                <span>{getChannelName(fallbackChannel)}</span>
              </div>
            </>
          )}
        </div>
        <p className="channel-description">{channelInfo?.description}</p>
        {channelInfo?.pdf_note && (
          <div className={`pdf-note ${channelInfo.pdf_note.includes('Warning') ? 'warning' : 'info'}`}>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="note-icon">
              <circle cx="12" cy="12" r="10" />
              <line x1="12" y1="16" x2="12" y2="12" />
              <line x1="12" y1="8" x2="12.01" y2="8" />
            </svg>
            <span>{channelInfo.pdf_note}</span>
          </div>
        )}
      </div>

      {/* Attachments Summary */}
      <div className="attachments-summary">
        <h4>Attachments</h4>
        <div className="attachment-count">
          {attachments.length} PDF file{attachments.length !== 1 ? 's' : ''} attached
        </div>
      </div>

      {/* Audio Guide Section */}
      {wizardData.hasVoxAccount && wizardData.voxDynamicLink && (
        <div className="preview-section audio-guide-preview">
          <h4>🎧 Audio Guide</h4>
          <div className="audio-guide-info">
            <p>PopGuide link will be included in the message:</p>
            <code>{wizardData.voxDynamicLink}</code>
          </div>
        </div>
      )}

      {/* Custom Message Preview */}
      {isCustomMessage && (
        <div className="message-preview custom">
          <h4>Custom Message Preview</h4>
          <div className="custom-message-badge">
            <span className="badge-icon"></span>
            <span>Custom Message</span>
          </div>
          {customMessage.subject && (
            <div className="preview-subject">
              <strong>Subject:</strong> {customMessage.subject}
            </div>
          )}
          <div className="preview-content">
            <pre>{customMessage.content}</pre>
          </div>
          <div className="preview-recipient">
            <strong>To:</strong> {booking.customer_email || 'Customer email'}
          </div>
        </div>
      )}

      {/* Standard Message Preview */}
      {!isCustomMessage && preview && primaryChannel && preview.previews[primaryChannel] && (
        <div className="message-preview">
          <h4>Message Preview ({getChannelName(primaryChannel)})</h4>
          {preview.previews[primaryChannel].subject && (
            <div className="preview-subject">
              <strong>Subject:</strong> {preview.previews[primaryChannel].subject}
            </div>
          )}
          <div className="preview-content">
            <pre>{preview.previews[primaryChannel].content}</pre>
          </div>
          <div className="preview-recipient">
            <strong>To:</strong> {preview.previews[primaryChannel].recipient}
          </div>
        </div>
      )}

      {/* Fallback Preview */}
      {fallbackChannel && preview && preview.previews[fallbackChannel] && (
        <div className="message-preview fallback">
          <h4>{getChannelName(fallbackChannel)} Notification</h4>
          <div className="preview-content">
            <pre>{preview.previews[fallbackChannel].content}</pre>
          </div>
        </div>
      )}

      <div className="confirm-box">
        <strong>Ready to send?</strong>
        <p>Click "Send Ticket" to deliver the ticket to the customer.</p>
      </div>
    </div>
  );
}
