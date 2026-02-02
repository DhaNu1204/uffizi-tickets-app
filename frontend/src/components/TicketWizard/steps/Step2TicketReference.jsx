import { useState, useEffect } from 'react';

// Uffizi reference code pattern: 8 uppercase alphanumeric characters
const REFERENCE_PATTERN = /^[A-Z0-9]{8}$/;

export function validateReferenceCode(code) {
  if (!code || code.trim() === '') {
    return { valid: false, error: 'Reference code is required' };
  }

  const upperCode = code.trim().toUpperCase();

  if (upperCode.length !== 8) {
    return { valid: false, error: `Code must be exactly 8 characters (currently ${upperCode.length})` };
  }

  if (!REFERENCE_PATTERN.test(upperCode)) {
    return { valid: false, error: 'Code must contain only letters and numbers' };
  }

  return { valid: true, error: null };
}

export default function Step2TicketReference({ booking, data, onChange }) {
  const [touched, setTouched] = useState(false);
  const [validation, setValidation] = useState({ valid: false, error: null });

  // Validate on change
  useEffect(() => {
    const result = validateReferenceCode(data.referenceNumber);
    setValidation(result);
  }, [data.referenceNumber]);

  const handleChange = (field) => (e) => {
    let value = e.target.value;

    // Auto-uppercase for reference number
    if (field === 'referenceNumber') {
      value = value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 8);
    }

    onChange({ [field]: value });
  };

  const handleBlur = () => {
    setTouched(true);
  };

  const showError = touched && !validation.valid && data.referenceNumber.length > 0;
  const showSuccess = validation.valid;

  return (
    <div className="wizard-step-content step-ticket-reference">
      <h3>Enter Ticket Information</h3>
      <p className="step-description">
        Enter the Uffizi ticket reference number and any additional details.
      </p>

      <div className="form-group">
        <label htmlFor="referenceNumber">
          Uffizi Ticket Reference <span className="required">*</span>
        </label>
        <div className="input-with-validation">
          <input
            type="text"
            id="referenceNumber"
            value={data.referenceNumber}
            onChange={handleChange('referenceNumber')}
            onBlur={handleBlur}
            placeholder="e.g., 9JBH3A8K"
            className={`form-input ${showError ? 'input-error' : ''} ${showSuccess ? 'input-success' : ''}`}
            autoFocus
            maxLength={8}
          />
          {showSuccess && (
            <span className="input-icon success">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3">
                <polyline points="20 6 9 17 4 12" />
              </svg>
            </span>
          )}
          {showError && (
            <span className="input-icon error">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <circle cx="12" cy="12" r="10" />
                <line x1="15" y1="9" x2="9" y2="15" />
                <line x1="9" y1="9" x2="15" y2="15" />
              </svg>
            </span>
          )}
        </div>

        {showError && (
          <p className="form-error">{validation.error}</p>
        )}

        <p className="form-help">
          8-character code from the Uffizi B2B booking system (e.g., 9JBH3A8K, PY3DVPZB)
        </p>

        <div className="code-format-hint">
          <span className="format-label">Format:</span>
          <span className="format-example">
            {data.referenceNumber.padEnd(8, '_').split('').map((char, i) => (
              <span
                key={i}
                className={`format-char ${char !== '_' ? 'filled' : 'empty'}`}
              >
                {char}
              </span>
            ))}
          </span>
          <span className="format-count">{data.referenceNumber.length}/8</span>
        </div>
      </div>

      {booking.isGuidedTour && (
        <div className="form-group">
          <label htmlFor="guideName">Guide Name</label>
          <input
            type="text"
            id="guideName"
            value={data.guideName}
            onChange={handleChange('guideName')}
            placeholder="e.g., Marco"
            className="form-input"
          />
          <p className="form-help">
            Assign a guide for this tour (optional)
          </p>
        </div>
      )}

      {booking.has_audio_guide && (
        <div className="audio-guide-notice">
          <div className="notice-icon">🎧</div>
          <div className="notice-content">
            <h4>Audio Guide Included</h4>
            <p>
              This booking includes an audio guide. You'll generate the PopGuide
              access link in the next steps.
            </p>
          </div>
        </div>
      )}
    </div>
  );
}
