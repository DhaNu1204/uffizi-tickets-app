import { useState, useRef, useEffect } from 'react';
import { messagesAPI } from '../services/api';
import { useToast } from '../context/ToastContext';
import './ManualSendModal.css';

const ManualSendModal = ({ isOpen, onClose }) => {
  const toast = useToast();
  const fileInputRef = useRef(null);

  const [activeTab, setActiveTab] = useState('send'); // 'send' or 'history'
  const [channel, setChannel] = useState('whatsapp');
  const [recipient, setRecipient] = useState('');
  const [subject, setSubject] = useState('');
  const [message, setMessage] = useState('');
  const [attachment, setAttachment] = useState(null);
  const [sending, setSending] = useState(false);
  const [errors, setErrors] = useState([]);
  const [success, setSuccess] = useState(null);

  // History state
  const [history, setHistory] = useState([]);
  const [loadingHistory, setLoadingHistory] = useState(false);

  // Load history when tab changes
  useEffect(() => {
    if (isOpen && activeTab === 'history') {
      loadHistory();
    }
  }, [isOpen, activeTab]);

  // Load message history
  const loadHistory = async () => {
    setLoadingHistory(true);
    try {
      const response = await messagesAPI.manualHistory(50);
      setHistory(response.data.messages || []);
    } catch (err) {
      console.error('Failed to load history:', err);
      toast.error('Failed to load message history');
    } finally {
      setLoadingHistory(false);
    }
  };

  // Reset form
  const resetForm = () => {
    setChannel('whatsapp');
    setRecipient('');
    setSubject('');
    setMessage('');
    setAttachment(null);
    setErrors([]);
    setSuccess(null);
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  // Handle close
  const handleClose = () => {
    resetForm();
    setActiveTab('send');
    onClose();
  };

  // Handle file selection
  const handleFileSelect = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    // Validate file type
    if (file.type !== 'application/pdf') {
      setErrors(['Only PDF files are allowed']);
      return;
    }

    // Validate file size (10MB max)
    if (file.size > 10 * 1024 * 1024) {
      setErrors(['File must be less than 10MB']);
      return;
    }

    setAttachment(file);
    setErrors([]);
  };

  // Remove attachment
  const removeAttachment = () => {
    setAttachment(null);
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  // Format file size
  const formatFileSize = (bytes) => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
  };

  // Format date
  const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleString('en-GB', {
      day: '2-digit',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  // Handle send
  const handleSend = async () => {
    setErrors([]);
    setSuccess(null);

    // Basic validation
    const validationErrors = [];

    if (!recipient.trim()) {
      validationErrors.push(channel === 'email' ? 'Email address is required' : 'Phone number is required');
    }

    if (channel === 'email' && !subject.trim()) {
      validationErrors.push('Subject is required for email');
    }

    if (!message.trim()) {
      validationErrors.push('Message is required');
    } else if (message.trim().length < 10) {
      validationErrors.push('Message must be at least 10 characters');
    }

    if (validationErrors.length > 0) {
      setErrors(validationErrors);
      return;
    }

    setSending(true);

    try {
      const response = await messagesAPI.sendManual({
        channel,
        recipient: recipient.trim(),
        message: message.trim(),
        subject: channel === 'email' ? subject.trim() : null,
        attachment: attachment,
      });

      if (response.data.success) {
        setSuccess({
          channel: response.data.data.channel,
          recipient: response.data.data.recipient,
          status: response.data.data.status,
        });
        toast.success('Message sent successfully!');
      }
    } catch (err) {
      const errorMessages = err.response?.data?.errors || ['Failed to send message'];
      setErrors(errorMessages);
      toast.error(errorMessages[0] || 'Failed to send message');
    } finally {
      setSending(false);
    }
  };

  // Handle send another
  const handleSendAnother = () => {
    resetForm();
  };

  // Get status badge class
  const getStatusClass = (status) => {
    switch (status) {
      case 'delivered':
      case 'read':
        return 'status-delivered';
      case 'sent':
      case 'queued':
        return 'status-sent';
      case 'failed':
        return 'status-failed';
      default:
        return 'status-pending';
    }
  };

  // Get channel icon
  const getChannelIcon = (ch) => {
    switch (ch) {
      case 'whatsapp':
        return (
          <svg viewBox="0 0 24 24" fill="currentColor" className="channel-icon-small whatsapp">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
          </svg>
        );
      case 'sms':
        return (
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="channel-icon-small sms">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
          </svg>
        );
      case 'email':
        return (
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="channel-icon-small email">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
            <polyline points="22,6 12,13 2,6" />
          </svg>
        );
      default:
        return null;
    }
  };

  if (!isOpen) return null;

  return (
    <div className="manual-send-overlay" onClick={handleClose}>
      <div className="manual-send-modal" onClick={(e) => e.stopPropagation()}>
        {/* Header */}
        <div className="modal-header">
          <h2>Manual Message Send</h2>
          <button className="modal-close" onClick={handleClose}>
            &times;
          </button>
        </div>

        {/* Tabs */}
        <div className="modal-tabs">
          <button
            className={`tab-btn ${activeTab === 'send' ? 'active' : ''}`}
            onClick={() => setActiveTab('send')}
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <line x1="22" y1="2" x2="11" y2="13" />
              <polygon points="22 2 15 22 11 13 2 9 22 2" />
            </svg>
            Send Message
          </button>
          <button
            className={`tab-btn ${activeTab === 'history' ? 'active' : ''}`}
            onClick={() => setActiveTab('history')}
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <circle cx="12" cy="12" r="10" />
              <polyline points="12 6 12 12 16 14" />
            </svg>
            History
          </button>
        </div>

        {/* Content */}
        <div className="modal-content">
          {/* History Tab */}
          {activeTab === 'history' && (
            <div className="history-tab">
              {loadingHistory ? (
                <div className="loading-state">
                  <span className="spinner large"></span>
                  <p>Loading history...</p>
                </div>
              ) : history.length === 0 ? (
                <div className="empty-state">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                  </svg>
                  <p>No manual messages sent yet</p>
                </div>
              ) : (
                <div className="history-list">
                  {history.map((msg) => (
                    <div key={msg.id} className="history-item">
                      <div className="history-item-header">
                        <span className="history-channel">
                          {getChannelIcon(msg.channel)}
                          {msg.channel}
                        </span>
                        <span className={`history-status ${getStatusClass(msg.status)}`}>
                          {msg.status}
                        </span>
                      </div>
                      <div className="history-recipient">
                        To: <strong>{msg.recipient}</strong>
                      </div>
                      {msg.subject && (
                        <div className="history-subject">
                          Subject: {msg.subject}
                        </div>
                      )}
                      <div className="history-content">
                        {msg.content.length > 100 ? msg.content.substring(0, 100) + '...' : msg.content}
                      </div>
                      <div className="history-footer">
                        <span className="history-date">{formatDate(msg.created_at)}</span>
                        {msg.delivered_at && (
                          <span className="history-delivered">Delivered: {formatDate(msg.delivered_at)}</span>
                        )}
                        {msg.error_message && (
                          <span className="history-error" title={msg.error_message}>Error</span>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              )}
              <button className="btn btn-secondary refresh-btn" onClick={loadHistory} disabled={loadingHistory}>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <polyline points="23 4 23 10 17 10" />
                  <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10" />
                </svg>
                Refresh
              </button>
            </div>
          )}

          {/* Send Tab */}
          {activeTab === 'send' && (
            <>
              {/* Success State */}
              {success && (
                <div className="success-state">
                  <div className="success-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                      <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                      <polyline points="22 4 12 14.01 9 11.01" />
                    </svg>
                  </div>
                  <h3>Message Sent!</h3>
                  <p>
                    Your {success.channel} message was sent to{' '}
                    <strong>{success.recipient}</strong>
                  </p>
                  <p className="delivery-note">
                    Note: "Sent" means accepted by provider. Check History tab for delivery status.
                  </p>
                  <div className="success-actions">
                    <button className="btn btn-primary" onClick={handleSendAnother}>
                      Send Another
                    </button>
                    <button className="btn btn-secondary" onClick={() => setActiveTab('history')}>
                      View History
                    </button>
                  </div>
                </div>
              )}

              {/* Form State */}
              {!success && (
                <>
                  {/* Errors */}
                  {errors.length > 0 && (
                    <div className="error-box">
                      {errors.map((error, idx) => (
                        <p key={idx}>{error}</p>
                      ))}
                    </div>
                  )}

                  {/* Channel Selection */}
                  <div className="form-group">
                    <label>Channel</label>
                    <div className="channel-selector">
                      <button
                        type="button"
                        className={`channel-btn ${channel === 'whatsapp' ? 'active' : ''}`}
                        onClick={() => setChannel('whatsapp')}
                      >
                        <svg viewBox="0 0 24 24" fill="currentColor" className="channel-icon whatsapp">
                          <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        WhatsApp
                      </button>
                      <button
                        type="button"
                        className={`channel-btn ${channel === 'sms' ? 'active' : ''}`}
                        onClick={() => setChannel('sms')}
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="channel-icon sms">
                          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                        </svg>
                        SMS
                      </button>
                      <button
                        type="button"
                        className={`channel-btn ${channel === 'email' ? 'active' : ''}`}
                        onClick={() => setChannel('email')}
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="channel-icon email">
                          <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                          <polyline points="22,6 12,13 2,6" />
                        </svg>
                        Email
                      </button>
                    </div>
                  </div>

                  {/* Recipient */}
                  <div className="form-group">
                    <label>
                      {channel === 'email' ? 'Email Address' : 'Phone Number'}
                      <span className="required">*</span>
                    </label>
                    <input
                      type={channel === 'email' ? 'email' : 'tel'}
                      className="form-input"
                      placeholder={channel === 'email' ? 'example@email.com' : '+39 333 123 4567'}
                      value={recipient}
                      onChange={(e) => setRecipient(e.target.value)}
                      disabled={sending}
                    />
                    {channel !== 'email' && (
                      <p className="form-help">
                        Full international format required (10-15 digits). Examples: +39 333 123 4567 (Italy), +1 555 123 4567 (USA)
                      </p>
                    )}
                  </div>

                  {/* Subject (Email only) */}
                  {channel === 'email' && (
                    <div className="form-group">
                      <label>
                        Subject<span className="required">*</span>
                      </label>
                      <input
                        type="text"
                        className="form-input"
                        placeholder="Enter email subject"
                        value={subject}
                        onChange={(e) => setSubject(e.target.value)}
                        disabled={sending}
                      />
                    </div>
                  )}

                  {/* Message */}
                  <div className="form-group">
                    <label>
                      Message<span className="required">*</span>
                    </label>
                    <textarea
                      className="form-input form-textarea"
                      placeholder="Enter your message..."
                      value={message}
                      onChange={(e) => setMessage(e.target.value)}
                      disabled={sending}
                      rows={5}
                    />
                    <p className="form-help char-count">
                      {message.length} characters {message.length < 10 && '(minimum 10)'}
                    </p>
                  </div>

                  {/* Attachment (WhatsApp and Email only) */}
                  {channel !== 'sms' && (
                    <div className="form-group">
                      <label>Attachment (PDF)</label>
                      {!attachment ? (
                        <div
                          className="file-drop-zone"
                          onClick={() => fileInputRef.current?.click()}
                        >
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="upload-icon">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                            <polyline points="17 8 12 3 7 8" />
                            <line x1="12" y1="3" x2="12" y2="15" />
                          </svg>
                          <p>Click to upload PDF</p>
                          <span>Max 10MB</span>
                        </div>
                      ) : (
                        <div className="attached-file">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="file-icon">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <polyline points="14 2 14 8 20 8" />
                          </svg>
                          <div className="file-info">
                            <span className="file-name">{attachment.name}</span>
                            <span className="file-size">{formatFileSize(attachment.size)}</span>
                          </div>
                          <button
                            type="button"
                            className="file-remove"
                            onClick={removeAttachment}
                            disabled={sending}
                          >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                              <line x1="18" y1="6" x2="6" y2="18" />
                              <line x1="6" y1="6" x2="18" y2="18" />
                            </svg>
                          </button>
                        </div>
                      )}
                      <input
                        ref={fileInputRef}
                        type="file"
                        accept="application/pdf"
                        onChange={handleFileSelect}
                        className="hidden-input"
                        disabled={sending}
                      />
                    </div>
                  )}

                  {/* SMS Attachment Notice */}
                  {channel === 'sms' && (
                    <div className="info-box">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" y1="16" x2="12" y2="12" />
                        <line x1="12" y1="8" x2="12.01" y2="8" />
                      </svg>
                      <span>SMS does not support attachments. Use WhatsApp or Email to send files.</span>
                    </div>
                  )}
                </>
              )}
            </>
          )}
        </div>

        {/* Footer */}
        {activeTab === 'send' && !success && (
          <div className="modal-footer">
            <button className="btn btn-secondary" onClick={handleClose} disabled={sending}>
              Cancel
            </button>
            <button className="btn btn-send" onClick={handleSend} disabled={sending}>
              {sending ? (
                <>
                  <span className="spinner"></span>
                  Sending...
                </>
              ) : (
                <>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="send-icon">
                    <line x1="22" y1="2" x2="11" y2="13" />
                    <polygon points="22 2 15 22 11 13 2 9 22 2" />
                  </svg>
                  Send Message
                </>
              )}
            </button>
          </div>
        )}
      </div>
    </div>
  );
};

export default ManualSendModal;
