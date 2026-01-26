import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { templatesAPI } from '../services/api';
import './TemplateAdmin.css';

export default function TemplateAdmin() {
  const navigate = useNavigate();
  const [templates, setTemplates] = useState([]);
  const [languages, setLanguages] = useState({});
  const [channels, setChannels] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [selectedTemplate, setSelectedTemplate] = useState(null);
  const [filterChannel, setFilterChannel] = useState('');
  const [filterLanguage, setFilterLanguage] = useState('');
  const [filterType, setFilterType] = useState('');
  const [editMode, setEditMode] = useState(false);
  const [editData, setEditData] = useState({});
  const [saving, setSaving] = useState(false);

  // Fetch templates on mount
  useEffect(() => {
    fetchTemplates();
  }, [filterChannel, filterLanguage, filterType]);

  const fetchTemplates = async () => {
    try {
      setLoading(true);
      const params = {};
      if (filterChannel) params.channel = filterChannel;
      if (filterLanguage) params.language = filterLanguage;
      if (filterType) params.template_type = filterType;

      const response = await templatesAPI.list(params);
      setTemplates(response.data.templates || []);
      setLanguages(response.data.languages || {});
      setChannels(response.data.channels || []);
      setError(null);
    } catch (err) {
      setError('Failed to load templates');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const handleSelectTemplate = (template) => {
    setSelectedTemplate(template);
    setEditMode(false);
    setEditData({});
  };

  const handleEdit = () => {
    setEditMode(true);
    setEditData({
      name: selectedTemplate.name,
      subject: selectedTemplate.subject || '',
      content: selectedTemplate.content,
      is_active: selectedTemplate.is_active,
      is_default: selectedTemplate.is_default,
    });
  };

  const handleCancelEdit = () => {
    setEditMode(false);
    setEditData({});
  };

  const handleSave = async () => {
    try {
      setSaving(true);
      await templatesAPI.update(selectedTemplate.id, editData);
      await fetchTemplates();
      setEditMode(false);

      // Update selected template with new data
      setSelectedTemplate((prev) => ({
        ...prev,
        ...editData,
      }));
    } catch (err) {
      setError('Failed to save template');
      console.error(err);
    } finally {
      setSaving(false);
    }
  };

  const handlePreview = async () => {
    try {
      const response = await templatesAPI.preview(selectedTemplate.id);
      alert(`Subject: ${response.data.subject}\n\n${response.data.content}`);
    } catch (err) {
      console.error('Preview failed:', err);
    }
  };

  const getChannelBadgeClass = (channel) => {
    switch (channel) {
      case 'email':
        return 'channel-email';
      case 'whatsapp':
        return 'channel-whatsapp';
      case 'sms':
        return 'channel-sms';
      default:
        return '';
    }
  };

  const getTypeBadgeClass = (type) => {
    return type === 'ticket_with_audio' ? 'type-audio' : 'type-ticket';
  };

  if (loading && templates.length === 0) {
    return (
      <div className="template-admin">
        <div className="loading-state">Loading templates...</div>
      </div>
    );
  }

  return (
    <div className="template-admin">
      <header className="admin-header">
        <button className="back-button" onClick={() => navigate('/')}>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M19 12H5M12 19l-7-7 7-7" />
          </svg>
          Back to Dashboard
        </button>
        <h1>Message Templates</h1>
        <p>Manage message templates for different languages and channels</p>
      </header>

      {error && <div className="error-banner">{error}</div>}

      <div className="admin-layout">
        {/* Sidebar: Template List */}
        <aside className="template-sidebar">
          <div className="filter-section">
            <select
              value={filterChannel}
              onChange={(e) => setFilterChannel(e.target.value)}
            >
              <option value="">All Channels</option>
              {channels.map((ch) => (
                <option key={ch} value={ch}>
                  {ch.charAt(0).toUpperCase() + ch.slice(1)}
                </option>
              ))}
            </select>

            <select
              value={filterLanguage}
              onChange={(e) => setFilterLanguage(e.target.value)}
            >
              <option value="">All Languages</option>
              {Object.entries(languages).map(([code, info]) => (
                <option key={code} value={code}>
                  {info.flag} {info.name}
                </option>
              ))}
            </select>

            <select
              value={filterType}
              onChange={(e) => setFilterType(e.target.value)}
            >
              <option value="">All Types</option>
              <option value="ticket_only">Ticket Only</option>
              <option value="ticket_with_audio">With Audio Guide</option>
            </select>
          </div>

          <div className="template-list">
            {templates.map((template) => (
              <div
                key={template.id}
                className={`template-item ${selectedTemplate?.id === template.id ? 'selected' : ''}`}
                onClick={() => handleSelectTemplate(template)}
              >
                <div className="template-item-header">
                  <span className={`channel-badge ${getChannelBadgeClass(template.channel)}`}>
                    {template.channel}
                  </span>
                  <span className={`type-badge ${getTypeBadgeClass(template.template_type)}`}>
                    {template.template_type === 'ticket_with_audio' ? 'Audio' : 'Ticket'}
                  </span>
                </div>
                <div className="template-item-name">{template.name}</div>
                <div className="template-item-lang">
                  {languages[template.language]?.flag} {template.language}
                </div>
                <div className="template-item-status">
                  {template.is_active ? (
                    <span className="status-active">Active</span>
                  ) : (
                    <span className="status-inactive">Inactive</span>
                  )}
                  {template.is_default && (
                    <span className="status-default">Default</span>
                  )}
                </div>
              </div>
            ))}

            {templates.length === 0 && (
              <div className="no-templates">No templates found</div>
            )}
          </div>
        </aside>

        {/* Main: Template Editor */}
        <main className="template-editor">
          {selectedTemplate ? (
            <>
              <div className="editor-header">
                <div>
                  <h2>{selectedTemplate.name}</h2>
                  <div className="editor-meta">
                    <span className={`channel-badge ${getChannelBadgeClass(selectedTemplate.channel)}`}>
                      {selectedTemplate.channel}
                    </span>
                    <span className="lang-badge">
                      {languages[selectedTemplate.language]?.flag} {languages[selectedTemplate.language]?.name}
                    </span>
                    <span className={`type-badge ${getTypeBadgeClass(selectedTemplate.template_type)}`}>
                      {selectedTemplate.template_type === 'ticket_with_audio'
                        ? 'With Audio Guide'
                        : 'Ticket Only'}
                    </span>
                  </div>
                </div>
                <div className="editor-actions">
                  {editMode ? (
                    <>
                      <button
                        className="btn btn-secondary"
                        onClick={handleCancelEdit}
                        disabled={saving}
                      >
                        Cancel
                      </button>
                      <button
                        className="btn btn-primary"
                        onClick={handleSave}
                        disabled={saving}
                      >
                        {saving ? 'Saving...' : 'Save Changes'}
                      </button>
                    </>
                  ) : (
                    <>
                      <button className="btn btn-secondary" onClick={handlePreview}>
                        Preview
                      </button>
                      <button className="btn btn-primary" onClick={handleEdit}>
                        Edit
                      </button>
                    </>
                  )}
                </div>
              </div>

              {editMode ? (
                <div className="editor-form">
                  <div className="form-group">
                    <label>Template Name</label>
                    <input
                      type="text"
                      value={editData.name}
                      onChange={(e) =>
                        setEditData((prev) => ({ ...prev, name: e.target.value }))
                      }
                    />
                  </div>

                  {selectedTemplate.channel === 'email' && (
                    <div className="form-group">
                      <label>Email Subject</label>
                      <input
                        type="text"
                        value={editData.subject}
                        onChange={(e) =>
                          setEditData((prev) => ({ ...prev, subject: e.target.value }))
                        }
                      />
                    </div>
                  )}

                  <div className="form-group">
                    <label>Message Content</label>
                    <textarea
                      rows={20}
                      value={editData.content}
                      onChange={(e) =>
                        setEditData((prev) => ({ ...prev, content: e.target.value }))
                      }
                    />
                  </div>

                  <div className="form-group checkbox-group">
                    <label>
                      <input
                        type="checkbox"
                        checked={editData.is_active}
                        onChange={(e) =>
                          setEditData((prev) => ({ ...prev, is_active: e.target.checked }))
                        }
                      />
                      Active
                    </label>
                    <label>
                      <input
                        type="checkbox"
                        checked={editData.is_default}
                        onChange={(e) =>
                          setEditData((prev) => ({ ...prev, is_default: e.target.checked }))
                        }
                      />
                      Default for this language
                    </label>
                  </div>
                </div>
              ) : (
                <div className="editor-view">
                  {selectedTemplate.subject && (
                    <div className="view-section">
                      <h4>Subject</h4>
                      <p className="subject-preview">{selectedTemplate.subject}</p>
                    </div>
                  )}

                  <div className="view-section">
                    <h4>Content</h4>
                    <pre className="content-preview">{selectedTemplate.content}</pre>
                  </div>

                  <div className="view-section">
                    <h4>Available Variables</h4>
                    <div className="variables-list">
                      <code>{'{customer_name}'}</code>
                      <code>{'{tour_date}'}</code>
                      <code>{'{tour_time}'}</code>
                      <code>{'{reference_number}'}</code>
                      <code>{'{pax}'}</code>
                      {selectedTemplate.template_type === 'ticket_with_audio' && (
                        <>
                          <code>{'{audio_guide_url}'}</code>
                          <code>{'{audio_guide_username}'}</code>
                          <code>{'{audio_guide_password}'}</code>
                        </>
                      )}
                    </div>
                  </div>
                </div>
              )}
            </>
          ) : (
            <div className="no-selection">
              <p>Select a template from the list to view or edit</p>
            </div>
          )}
        </main>
      </div>
    </div>
  );
}
