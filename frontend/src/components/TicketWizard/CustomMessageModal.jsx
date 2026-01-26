import { useState } from 'react';
import './CustomMessageModal.css';

export default function CustomMessageModal({ booking, onSave, onClose }) {
  const [subject, setSubject] = useState(`Your Uffizi Gallery Tickets - ${booking.tour_date}`);
  const [message, setMessage] = useState('');
  const [errors, setErrors] = useState({});

  const handleSave = () => {
    const newErrors = {};

    if (!subject.trim()) {
      newErrors.subject = 'Subject is required';
    }
    if (!message.trim()) {
      newErrors.message = 'Message content is required';
    }
    if (message.trim().length < 50) {
      newErrors.message = 'Message should be at least 50 characters';
    }

    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors);
      return;
    }

    onSave({
      subject: subject.trim(),
      content: message.trim(),
      language: 'custom',
    });
  };

  // Available variables for reference
  const variables = [
    { key: '{customer_name}', desc: 'Customer name', example: booking.customer_name },
    { key: '{tour_date}', desc: 'Tour date', example: booking.tour_date },
    { key: '{tour_time}', desc: 'Tour time', example: booking.tour_time || '10:00' },
    { key: '{reference_number}', desc: 'Ticket reference', example: booking.reference_number || 'UFF-12345' },
    { key: '{pax}', desc: 'Number of guests', example: booking.pax },
  ];

  if (booking.has_audio_guide) {
    variables.push(
      { key: '{audio_guide_url}', desc: 'Audio guide link', example: 'https://pg.unlockmy.app/...' },
      { key: '{audio_guide_username}', desc: 'Audio guide username', example: 'TKE-000123' },
      { key: '{audio_guide_password}', desc: 'Audio guide password', example: '52628' }
    );
  }

  const insertVariable = (key) => {
    setMessage(prev => prev + key);
  };

  return (
    <div className="custom-message-modal-overlay" onClick={onClose}>
      <div className="custom-message-modal" onClick={e => e.stopPropagation()}>
        <div className="modal-header">
          <h2>Custom Message</h2>
          <button className="close-button" onClick={onClose}>&times;</button>
        </div>

        <div className="modal-body">
          <p className="modal-description">
            Write a custom message for this customer. You can use variables that will be replaced with actual values.
          </p>

          {/* Subject Field */}
          <div className="form-group">
            <label htmlFor="subject">Email Subject</label>
            <input
              type="text"
              id="subject"
              value={subject}
              onChange={e => setSubject(e.target.value)}
              placeholder="Enter email subject..."
              className={errors.subject ? 'error' : ''}
            />
            {errors.subject && <span className="error-text">{errors.subject}</span>}
          </div>

          {/* Message Field */}
          <div className="form-group">
            <label htmlFor="message">Message Content</label>
            <textarea
              id="message"
              value={message}
              onChange={e => setMessage(e.target.value)}
              placeholder="Type your message here...

Example:
Dear {customer_name},

Your tickets for the Uffizi Gallery on {tour_date} at {tour_time} are ready!

Reference Number: {reference_number}
Number of Guests: {pax}

Please show the attached PDF at the entrance.

Best regards,
Florence with Locals"
              rows={12}
              className={errors.message ? 'error' : ''}
            />
            {errors.message && <span className="error-text">{errors.message}</span>}
            <span className="char-count">{message.length} characters</span>
          </div>

          {/* Available Variables */}
          <div className="variables-section">
            <h4>Available Variables <span className="hint">(click to insert)</span></h4>
            <div className="variables-grid">
              {variables.map(v => (
                <button
                  key={v.key}
                  type="button"
                  className="variable-chip"
                  onClick={() => insertVariable(v.key)}
                  title={`Example: ${v.example}`}
                >
                  <code>{v.key}</code>
                  <span>{v.desc}</span>
                </button>
              ))}
            </div>
          </div>

          {/* Preview Section */}
          {message && (
            <div className="preview-section">
              <h4>Preview</h4>
              <div className="preview-content">
                <div className="preview-subject">
                  <strong>Subject:</strong> {subject
                    .replace('{customer_name}', booking.customer_name || 'John Doe')
                    .replace('{tour_date}', booking.tour_date || 'January 30, 2026')
                    .replace('{reference_number}', booking.reference_number || 'UFF-12345')}
                </div>
                <div className="preview-body">
                  {message
                    .replace('{customer_name}', booking.customer_name || 'John Doe')
                    .replace('{tour_date}', booking.tour_date || 'January 30, 2026')
                    .replace('{tour_time}', booking.tour_time || '10:00')
                    .replace('{reference_number}', booking.reference_number || 'UFF-12345')
                    .replace('{pax}', String(booking.pax || 2))
                    .replace('{audio_guide_url}', 'https://pg.unlockmy.app/abc123')
                    .replace('{audio_guide_username}', 'TKE-000123')
                    .replace('{audio_guide_password}', '52628')}
                </div>
              </div>
            </div>
          )}
        </div>

        <div className="modal-footer">
          <button type="button" className="btn-cancel" onClick={onClose}>
            Cancel
          </button>
          <button type="button" className="btn-save" onClick={handleSave}>
            Use This Message
          </button>
        </div>
      </div>
    </div>
  );
}
