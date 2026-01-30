import { useState, useEffect } from 'react';
import { voxAPI } from '../../../services/api';
import './Steps.css';

export default function Step4AudioGuide({ booking, wizardData, onChange }) {
  const [isGenerating, setIsGenerating] = useState(false);
  const [error, setError] = useState(null);
  const [voxStatus, setVoxStatus] = useState(null);
  const [copied, setCopied] = useState(false);

  // Check if VOX account already exists on mount
  useEffect(() => {
    checkVoxStatus();
  }, []);

  const checkVoxStatus = async () => {
    try {
      const response = await voxAPI.getStatus(booking.id);
      setVoxStatus(response.data);

      // If already has VOX account, update wizard data
      if (response.data.has_vox_account) {
        onChange({
          voxDynamicLink: response.data.vox_dynamic_link,
          voxAccountId: response.data.vox_account_id,
          voxUsername: response.data.audio_guide_username,
          voxPassword: response.data.audio_guide_password,
          hasVoxAccount: true,
        });
      }
    } catch (err) {
      console.error('Failed to check VOX status:', err);
    }
  };

  const handleGenerateAudioGuide = async () => {
    setIsGenerating(true);
    setError(null);

    try {
      const response = await voxAPI.createAccount(booking.id);

      if (response.data.success) {
        onChange({
          voxDynamicLink: response.data.dynamic_link,
          voxAccountId: response.data.account_id,
          voxUsername: response.data.username,
          voxPassword: response.data.password,
          hasVoxAccount: true,
          // Also update audio guide fields for template
          audioGuideUrl: response.data.dynamic_link,
          audioGuideUsername: response.data.username,
          audioGuidePassword: response.data.password,
        });
        setVoxStatus({
          has_vox_account: true,
          vox_dynamic_link: response.data.dynamic_link,
          audio_guide_username: response.data.username,
          audio_guide_password: response.data.password,
        });
      } else {
        setError(response.data.error || 'Failed to generate audio guide');
      }
    } catch (err) {
      setError(err.response?.data?.error || 'Failed to connect to audio guide service');
    } finally {
      setIsGenerating(false);
    }
  };

  const handleCopy = (text) => {
    navigator.clipboard.writeText(text);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  const hasVoxAccount = voxStatus?.has_vox_account || wizardData.hasVoxAccount;
  const dynamicLink = voxStatus?.vox_dynamic_link || wizardData.voxDynamicLink;
  const username = voxStatus?.audio_guide_username || wizardData.voxUsername;
  const password = voxStatus?.audio_guide_password || wizardData.voxPassword;

  return (
    <div className="wizard-step-content step-audio-guide">
      <h3>Generate Audio Guide</h3>
      <p className="step-description">
        Create the PopGuide audio guide link for this customer.
      </p>

      {/* Booking Info Summary */}
      <div className="audio-info-card">
        <div className="info-row">
          <span className="label">Customer:</span>
          <span className="value">{booking.customer_name}</span>
        </div>
        <div className="info-row">
          <span className="label">Guests:</span>
          <span className="value">{booking.pax} people</span>
        </div>
        <div className="info-row">
          <span className="label">Tour Date:</span>
          <span className="value">{booking.tour_date}</span>
        </div>
      </div>

      {/* Audio Guide Status */}
      {hasVoxAccount ? (
        <div className="audio-success-card">
          <div className="success-header">
            <span className="success-icon">&#10003;</span>
            <div>
              <h4>Audio Guide Ready!</h4>
              <p>The PopGuide link has been generated for this customer.</p>
            </div>
          </div>

          <div className="credentials-section">
            <div className="credential-item">
              <label>Dynamic Link:</label>
              <div className="credential-value">
                <code>{dynamicLink}</code>
                <button
                  className="copy-btn"
                  onClick={() => handleCopy(dynamicLink)}
                  title="Copy to clipboard"
                >
                  {copied ? 'Copied!' : 'Copy'}
                </button>
              </div>
            </div>

            {username && (
              <div className="credential-item">
                <label>Username:</label>
                <div className="credential-value">
                  <code>{username}</code>
                </div>
              </div>
            )}

            {password && (
              <div className="credential-item">
                <label>Password:</label>
                <div className="credential-value">
                  <code>{password}</code>
                </div>
              </div>
            )}
          </div>

          <div className="audio-info-note">
            <strong>How it works:</strong>
            <ol>
              <li>Customer clicks the dynamic link</li>
              <li>PopGuide app downloads automatically</li>
              <li>Customer is auto-logged into their account</li>
              <li>Uffizi audio guide is ready to use</li>
            </ol>
          </div>
        </div>
      ) : (
        <div className="audio-generate-card">
          <div className="generate-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M3 18v-6a9 9 0 0 1 18 0v6" />
              <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z" />
            </svg>
          </div>
          <h4>Audio Guide Not Generated Yet</h4>
          <p>Click the button below to create the PopGuide account and generate the dynamic link.</p>

          {error && (
            <div className="audio-error-message">
              {error}
            </div>
          )}

          <button
            className="generate-btn"
            onClick={handleGenerateAudioGuide}
            disabled={isGenerating}
          >
            {isGenerating ? (
              <>
                <span className="btn-spinner"></span>
                Generating...
              </>
            ) : (
              <>Generate Audio Guide</>
            )}
          </button>

          <p className="generate-hint">
            This will create a VOX/PopGuide account with {booking.pax} access(es) for the Uffizi Gallery.
          </p>
        </div>
      )}
    </div>
  );
}
