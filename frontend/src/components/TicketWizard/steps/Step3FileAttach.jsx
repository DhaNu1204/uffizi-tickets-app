import { useState, useRef } from 'react';
import { attachmentsAPI } from '../../../services/api';

export default function Step3FileAttach({ booking, attachments, onChange }) {
  const [isUploading, setIsUploading] = useState(false);
  const [uploadProgress, setUploadProgress] = useState(0);
  const [error, setError] = useState(null);
  const [isDragging, setIsDragging] = useState(false);
  const fileInputRef = useRef(null);

  const handleFileSelect = async (files) => {
    if (!files || files.length === 0) return;

    const file = files[0];

    // Validate file type
    if (file.type !== 'application/pdf') {
      setError('Only PDF files are allowed');
      return;
    }

    // Validate file size (10 MB)
    if (file.size > 10 * 1024 * 1024) {
      setError('File size must not exceed 10 MB');
      return;
    }

    setError(null);
    setIsUploading(true);
    setUploadProgress(0);

    try {
      const response = await attachmentsAPI.upload(booking.id, file, (progress) => {
        setUploadProgress(progress);
      });

      onChange({
        attachments: [...attachments, response.data.attachment],
      });
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to upload file');
    } finally {
      setIsUploading(false);
      setUploadProgress(0);
    }
  };

  const handleInputChange = (e) => {
    handleFileSelect(e.target.files);
    e.target.value = ''; // Reset input
  };

  const handleDragOver = (e) => {
    e.preventDefault();
    setIsDragging(true);
  };

  const handleDragLeave = (e) => {
    e.preventDefault();
    setIsDragging(false);
  };

  const handleDrop = (e) => {
    e.preventDefault();
    setIsDragging(false);
    handleFileSelect(e.dataTransfer.files);
  };

  const handleRemove = async (attachmentId) => {
    try {
      await attachmentsAPI.delete(attachmentId);
      onChange({
        attachments: attachments.filter((a) => a.id !== attachmentId),
      });
    } catch (err) {
      setError('Failed to remove file');
    }
  };

  const formatFileSize = (bytes) => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
  };

  return (
    <div className="wizard-step-content step-file-attach">
      <h3>Attach Ticket PDF</h3>
      <p className="step-description">
        Upload the Uffizi ticket PDF to send to the customer. <span className="required">Required</span>
      </p>

      {error && (
        <div className="upload-error">
          {error}
        </div>
      )}

      <div
        className={`drop-zone ${isDragging ? 'dragging' : ''} ${isUploading ? 'uploading' : ''}`}
        onDragOver={handleDragOver}
        onDragLeave={handleDragLeave}
        onDrop={handleDrop}
        onClick={() => fileInputRef.current?.click()}
      >
        <input
          ref={fileInputRef}
          type="file"
          accept="application/pdf"
          onChange={handleInputChange}
          className="hidden-input"
        />

        {isUploading ? (
          <div className="upload-progress">
            <div className="progress-bar">
              <div className="progress-fill" style={{ width: `${uploadProgress}%` }} />
            </div>
            <span className="progress-text">Uploading... {uploadProgress}%</span>
          </div>
        ) : (
          <>
            <div className="drop-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                <polyline points="17 8 12 3 7 8" />
                <line x1="12" y1="3" x2="12" y2="15" />
              </svg>
            </div>
            <p className="drop-text">
              <strong>Drop PDF here</strong> or click to browse
            </p>
            <p className="drop-hint">Maximum file size: 10 MB</p>
          </>
        )}
      </div>

      {attachments.length > 0 && (
        <div className="attached-files">
          <h4>Attached Files</h4>
          <ul className="file-list">
            {attachments.map((file) => (
              <li key={file.id} className="file-item">
                <div className="file-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                    <polyline points="14 2 14 8 20 8" />
                    <line x1="16" y1="13" x2="8" y2="13" />
                    <line x1="16" y1="17" x2="8" y2="17" />
                    <polyline points="10 9 9 9 8 9" />
                  </svg>
                </div>
                <div className="file-info">
                  <span className="file-name">{file.original_name}</span>
                  <span className="file-size">{formatFileSize(file.size)}</span>
                  <span className="file-booking">Booking #{booking.id}</span>
                </div>
                <button
                  type="button"
                  className="file-remove"
                  onClick={() => handleRemove(file.id)}
                  title="Remove file"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                  </svg>
                </button>
              </li>
            ))}
          </ul>

          {/* Warning if filename doesn't contain reference number */}
          {booking.reference_number && attachments.some(file =>
            !file.original_name?.toLowerCase().includes(booking.reference_number?.toLowerCase())
          ) && (
            <div className="file-reference-warning">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                <line x1="12" y1="9" x2="12" y2="13" />
                <line x1="12" y1="17" x2="12.01" y2="17" />
              </svg>
              <span>
                Filename does not contain reference "{booking.reference_number}".
                Please verify this is the correct ticket for this booking.
              </span>
            </div>
          )}

          {/* Success confirmation */}
          <div className="upload-confirmation">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
              <polyline points="22 4 12 14.01 9 11.01" />
            </svg>
            <span>
              {attachments.length} file{attachments.length !== 1 ? 's' : ''} uploaded for Booking #{booking.id}
              {booking.reference_number && ` (Ref: ${booking.reference_number})`}
            </span>
          </div>
        </div>
      )}
    </div>
  );
}
