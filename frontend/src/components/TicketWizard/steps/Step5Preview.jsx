import { useState, useEffect } from 'react';
import { messagesAPI } from '../../../services/api';
import './Steps.css';

// Format file size
const formatFileSize = (bytes) => {
  if (!bytes) return '0 KB';
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};

// Language names
const LANGUAGE_NAMES = {
  en: 'English',
  it: 'Italian',
  es: 'Spanish',
  de: 'German',
  fr: 'French',
  pt: 'Portuguese',
  ja: 'Japanese',
  ko: 'Korean',
  el: 'Greek',
  tr: 'Turkish',
};

export default function Step5Preview({ booking, wizardData, isLoading: parentLoading }) {
  const { channelInfo, attachments, language } = wizardData;
  const [previewData, setPreviewData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Fetch preview from API when language changes
  useEffect(() => {
    const fetchPreview = async () => {
      setLoading(true);
      setError(null);
      try {
        const response = await messagesAPI.preview({
          booking_id: booking.id,
          language: language,
        });
        setPreviewData(response.data);
      } catch (err) {
        console.error('Failed to fetch preview:', err);
        setError('Failed to load preview');
      } finally {
        setLoading(false);
      }
    };

    fetchPreview();
  }, [booking.id, language]);

  if (parentLoading || loading) {
    return (
      <div className="wizard-step-content step-preview">
        <div className="loading-state">
          <span className="spinner large" />
          <p>{parentLoading ? 'Detecting messaging channel...' : 'Loading preview...'}</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="wizard-step-content step-preview">
        <div className="error-state">
          <p>⚠️ {error}</p>
        </div>
      </div>
    );
  }

  // Determine if WhatsApp is available (primary channel is whatsapp)
  const hasWhatsApp = channelInfo?.primary === 'whatsapp';
  const hasEmail = !!booking.customer_email;

  return (
    <div className="wizard-step-content step-preview">
      <h3>Preview & Confirm</h3>
      <p className="step-description">
        Review the message before sending. Content shown in <strong>{LANGUAGE_NAMES[language] || language}</strong>.
      </p>

      {/* Delivery Channel */}
      <div className="channel-detection">
        <h4>Delivery Channel</h4>
        <div className="channel-info">
          {hasWhatsApp ? (
            <div className="channel-badge primary whatsapp-badge">
              <svg viewBox="0 0 24 24" fill="currentColor" className="channel-icon whatsapp">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
              </svg>
              <span>WhatsApp</span>
            </div>
          ) : hasEmail ? (
            <>
              <div className="channel-badge primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="channel-icon email">
                  <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                  <polyline points="22,6 12,13 2,6" />
                </svg>
                <span>Email</span>
              </div>
              {booking.customer_phone && (
                <>
                  <span className="plus">+</span>
                  <div className="channel-badge secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="channel-icon sms">
                      <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                    </svg>
                    <span>SMS</span>
                  </div>
                </>
              )}
            </>
          ) : (
            <div className="channel-badge error">
              <span>⚠️ No delivery method available</span>
            </div>
          )}
        </div>

        <p className="channel-description">
          {hasWhatsApp
            ? '📱 Will send via WhatsApp with PDF attachment'
            : hasEmail
              ? '📧 Will send PDF via Email' + (booking.customer_phone ? ' + SMS notification' : '')
              : '❌ Cannot send - no contact information'
          }
        </p>
      </div>

      {/* Attachments Summary */}
      <div className="attachments-summary">
        <h4>📎 Attachments for Booking #{booking.id}</h4>
        <div className="attachments-list">
          {attachments.map((att) => (
            <div key={att.id} className="attachment-item-preview">
              <div className="attachment-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                  <polyline points="14 2 14 8 20 8" />
                </svg>
              </div>
              <div className="attachment-details">
                <span className="attachment-name">{att.original_name}</span>
                <span className="attachment-meta">
                  {formatFileSize(att.size)} • Booking #{booking.id}
                </span>
              </div>
              <div className="attachment-check">✓</div>
            </div>
          ))}
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

      {/* WhatsApp Preview */}
      {hasWhatsApp && previewData?.whatsapp_preview && (
        <div className="message-preview whatsapp">
          <h4>📱 WhatsApp Message ({LANGUAGE_NAMES[language] || language})</h4>
          <div className="whatsapp-bubble">
            <pre>{previewData.whatsapp_preview}</pre>
            <div className="whatsapp-attachment">
              <span className="pdf-icon">📄</span>
              <span>{attachments[0]?.original_name || 'ticket.pdf'}</span>
            </div>
          </div>
          <div className="preview-recipient">
            <strong>To:</strong> {booking.customer_phone}
          </div>
        </div>
      )}

      {/* Email + SMS Preview */}
      {!hasWhatsApp && hasEmail && (
        <>
          <div className="message-preview email">
            <h4>📧 Email ({LANGUAGE_NAMES[language] || language})</h4>
            <div className="email-preview-card">
              <div className="email-subject">
                <strong>Subject:</strong> {previewData?.email_subject || 'Your Uffizi Gallery Tickets'}
              </div>
              <div className="email-template-info">
                <strong>Template:</strong> {previewData?.email_type || 'Standard Ticket Template'}
              </div>
              <div className="email-content-summary">
                <p>📧 Full HTML email with Florence with Locals branding</p>
                <ul>
                  <li>Entry instructions and door location</li>
                  <li>Date, time, and important reminders</li>
                  {booking.has_audio_guide && <li>Audio guide activation link</li>}
                  <li>Online guide and tips links</li>
                </ul>
              </div>
              <div className="email-attachment">
                <span className="pdf-icon">📄</span>
                <span>PDF Attachment: {attachments[0]?.original_name || 'ticket.pdf'}</span>
              </div>
            </div>
            <div className="preview-recipient">
              <strong>To:</strong> {booking.customer_email}
            </div>
          </div>

          {booking.customer_phone && previewData?.sms_preview && (
            <div className="message-preview sms">
              <h4>📱 SMS Notification ({LANGUAGE_NAMES[language] || language})</h4>
              <div className="sms-bubble">
                <pre>{previewData.sms_preview}</pre>
              </div>
              <div className="preview-recipient">
                <strong>To:</strong> {booking.customer_phone}
              </div>
            </div>
          )}
        </>
      )}

      <div className="confirm-box">
        <strong>✅ Ready to send?</strong>
        <p>Click "Send Ticket" to deliver the ticket to the customer.</p>
      </div>
    </div>
  );
}
