import { useState, useEffect, useCallback, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import { useToast } from '../context/ToastContext';
import { conversationsAPI } from '../services/api';
import './ConversationsPage.css';

const ConversationsPage = () => {
  const navigate = useNavigate();
  const toast = useToast();
  const messagesEndRef = useRef(null);
  const pollIntervalRef = useRef(null);

  const [conversations, setConversations] = useState([]);
  const [selectedConversation, setSelectedConversation] = useState(null);
  const [messages, setMessages] = useState([]);
  const [loading, setLoading] = useState(true);
  const [loadingMessages, setLoadingMessages] = useState(false);
  const [sending, setSending] = useState(false);
  const [replyText, setReplyText] = useState('');
  const [searchQuery, setSearchQuery] = useState('');
  const [showUnreadOnly, setShowUnreadOnly] = useState(false);
  const [mobileShowChat, setMobileShowChat] = useState(false);

  // Fetch conversations list
  const fetchConversations = useCallback(async () => {
    try {
      const params = {};
      if (searchQuery) params.search = searchQuery;
      if (showUnreadOnly) params.unread_only = true;

      const response = await conversationsAPI.list(params);
      setConversations(response.data.data || []);
    } catch (err) {
      console.error('Error fetching conversations:', err);
    } finally {
      setLoading(false);
    }
  }, [searchQuery, showUnreadOnly]);

  // Fetch single conversation with messages
  const fetchConversationMessages = useCallback(async (conversationId) => {
    if (!conversationId) return;

    setLoadingMessages(true);
    try {
      const response = await conversationsAPI.get(conversationId);
      setSelectedConversation(response.data.conversation);
      setMessages(response.data.messages || []);

      // Also refresh the list to update unread counts
      fetchConversations();
    } catch (err) {
      console.error('Error fetching messages:', err);
      toast.error('Failed to load messages');
    } finally {
      setLoadingMessages(false);
    }
  }, [fetchConversations, toast]);

  // Initial load
  useEffect(() => {
    fetchConversations();
  }, [fetchConversations]);

  // Poll for new messages every 5 seconds
  useEffect(() => {
    pollIntervalRef.current = setInterval(() => {
      fetchConversations();
      if (selectedConversation) {
        fetchConversationMessages(selectedConversation.id);
      }
    }, 5000);

    return () => {
      if (pollIntervalRef.current) {
        clearInterval(pollIntervalRef.current);
      }
    };
  }, [fetchConversations, fetchConversationMessages, selectedConversation]);

  // Scroll to bottom when messages change
  useEffect(() => {
    if (messagesEndRef.current) {
      messagesEndRef.current.scrollIntoView({ behavior: 'smooth' });
    }
  }, [messages]);

  // Handle conversation selection
  const handleSelectConversation = (conversation) => {
    setSelectedConversation(conversation);
    fetchConversationMessages(conversation.id);
    setMobileShowChat(true);
  };

  // Handle send reply
  const handleSendReply = async () => {
    if (!replyText.trim() || !selectedConversation || sending) return;

    setSending(true);
    try {
      const response = await conversationsAPI.reply(selectedConversation.id, replyText.trim());
      setReplyText('');

      // Add the new message to the list
      if (response.data.data) {
        setMessages(prev => [...prev, response.data.data]);
      }

      // Refresh conversation to update last message
      fetchConversations();
    } catch (err) {
      console.error('Error sending reply:', err);
      const errorMsg = err.response?.data?.error || 'Failed to send reply';
      toast.error(errorMsg);

      // Check if WhatsApp window expired
      if (err.response?.data?.window_expired) {
        toast.warning('WhatsApp 24-hour window has expired. Customer needs to message first.');
      }
    } finally {
      setSending(false);
    }
  };

  // Handle archive conversation
  const handleArchive = async () => {
    if (!selectedConversation) return;

    try {
      await conversationsAPI.archive(selectedConversation.id);
      toast.success('Conversation archived');
      setSelectedConversation(null);
      setMessages([]);
      setMobileShowChat(false);
      fetchConversations();
    } catch (err) {
      console.error('Error archiving conversation:', err);
      toast.error('Failed to archive conversation');
    }
  };

  // Format time for display
  const formatTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;
    return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
  };

  // Format message time
  const formatMessageTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
  };

  // Get avatar initials
  const getInitials = (name, phone) => {
    if (name && name !== phone) {
      return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
    }
    return phone.slice(-2);
  };

  // Render message status icon
  const renderStatusIcon = (status) => {
    switch (status) {
      case 'sent':
        return (
          <span className="message-status sent">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <polyline points="20 6 9 17 4 12" />
            </svg>
          </span>
        );
      case 'delivered':
        return (
          <span className="message-status delivered">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <polyline points="18 6 9 17 4 12" />
              <polyline points="22 6 13 17" />
            </svg>
          </span>
        );
      case 'read':
        return (
          <span className="message-status read">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <polyline points="18 6 9 17 4 12" />
              <polyline points="22 6 13 17" />
            </svg>
          </span>
        );
      default:
        return null;
    }
  };

  // Format WhatsApp window remaining time
  const formatWindowRemaining = (minutes) => {
    if (!minutes) return '';
    if (minutes < 60) return `${minutes}m remaining`;
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours}h ${mins}m remaining`;
  };

  return (
    <div className="conversations-page">
      {/* Header */}
      <header className="conversations-header">
        <div className="header-left">
          <button className="back-btn" onClick={() => navigate('/')}>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <polyline points="15 18 9 12 15 6" />
            </svg>
            Back
          </button>
          <h1>Conversations</h1>
        </div>
        <div className="header-actions">
          <button
            className={`filter-btn ${showUnreadOnly ? 'active' : ''}`}
            onClick={() => setShowUnreadOnly(!showUnreadOnly)}
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <circle cx="12" cy="12" r="10" />
              <circle cx="12" cy="12" r="3" fill="currentColor" />
            </svg>
            Unread Only
          </button>
        </div>
      </header>

      {/* Main Content */}
      <div className="conversations-content">
        {/* Conversation List */}
        <div className={`conversation-list ${mobileShowChat ? 'hidden-mobile' : ''}`}>
          <div className="conversation-list-header">
            <div className="conversation-search">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <circle cx="11" cy="11" r="8" />
                <path d="M21 21l-4.35-4.35" />
              </svg>
              <input
                type="text"
                placeholder="Search conversations..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
              />
            </div>
          </div>

          <div className="conversation-items">
            {loading ? (
              <div className="loading-conversations">
                <div className="spinner"></div>
              </div>
            ) : conversations.length === 0 ? (
              <div className="empty-conversations">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
                  <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                </svg>
                <h3>No conversations yet</h3>
                <p>When customers reply to messages, they'll appear here.</p>
              </div>
            ) : (
              conversations.map((conv) => (
                <div
                  key={conv.id}
                  className={`conversation-item ${
                    selectedConversation?.id === conv.id ? 'active' : ''
                  } ${conv.unread_count > 0 ? 'unread' : ''}`}
                  onClick={() => handleSelectConversation(conv)}
                >
                  <div className={`conversation-avatar ${conv.channel}`}>
                    {getInitials(conv.display_name, conv.phone_number)}
                  </div>
                  <div className="conversation-info">
                    <div className="top-row">
                      <span className="name">{conv.display_name}</span>
                      <span className="time">{formatTime(conv.last_message_at)}</span>
                    </div>
                    <div className="preview">
                      {conv.last_message_preview || 'No messages'}
                      {conv.unread_count > 0 && (
                        <span className="unread-badge">{conv.unread_count}</span>
                      )}
                    </div>
                    <span className={`channel-badge ${conv.channel}`}>
                      {conv.channel === 'whatsapp' ? 'WhatsApp' : 'SMS'}
                    </span>
                  </div>
                </div>
              ))
            )}
          </div>
        </div>

        {/* Chat Panel */}
        <div className={`chat-panel ${!selectedConversation ? 'empty' : ''} ${!mobileShowChat ? 'hidden-mobile' : ''}`}>
          {!selectedConversation ? (
            <div className="no-conversation-selected">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                <line x1="9" y1="10" x2="15" y2="10" />
                <line x1="12" y1="7" x2="12" y2="13" />
              </svg>
              <h3>Select a conversation</h3>
              <p>Choose a conversation from the list to view messages</p>
            </div>
          ) : (
            <>
              {/* Chat Header */}
              <div className="chat-header">
                <div className="chat-header-info">
                  <button
                    className="mobile-back-btn"
                    onClick={() => setMobileShowChat(false)}
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                      <polyline points="15 18 9 12 15 6" />
                    </svg>
                  </button>
                  <div className={`chat-header-avatar ${selectedConversation.channel}`}>
                    {getInitials(selectedConversation.display_name, selectedConversation.phone_number)}
                  </div>
                  <div className="chat-header-details">
                    <h2>{selectedConversation.display_name}</h2>
                    <div className="subtitle">
                      <span>{selectedConversation.phone_number}</span>
                      {selectedConversation.booking && (
                        <>
                          <span>|</span>
                          <span
                            className="booking-link"
                            onClick={() => navigate(`/?search=${selectedConversation.booking.bokun_booking_id}`)}
                          >
                            {selectedConversation.booking.customer_name} ({new Date(selectedConversation.booking.tour_date).toLocaleDateString('en-GB', { month: 'short', day: 'numeric' })})
                          </span>
                        </>
                      )}
                    </div>
                  </div>
                </div>
                <div className="chat-header-actions">
                  <button onClick={handleArchive} title="Archive conversation">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                      <polyline points="21 8 21 21 3 21 3 8" />
                      <rect x="1" y="3" width="22" height="5" />
                      <line x1="10" y1="12" x2="14" y2="12" />
                    </svg>
                    Archive
                  </button>
                </div>
              </div>

              {/* WhatsApp Window Warning */}
              {selectedConversation.channel === 'whatsapp' && (
                selectedConversation.whatsapp_window_open ? (
                  selectedConversation.whatsapp_window_remaining < 120 && (
                    <div className="whatsapp-window-warning">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12 16 14" />
                      </svg>
                      WhatsApp window: {formatWindowRemaining(selectedConversation.whatsapp_window_remaining)}
                    </div>
                  )
                ) : (
                  <div className="whatsapp-window-warning whatsapp-window-expired">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                      <circle cx="12" cy="12" r="10" />
                      <line x1="12" y1="8" x2="12" y2="12" />
                      <line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                    WhatsApp 24-hour window expired. Customer must message first to reply.
                  </div>
                )
              )}

              {/* Messages */}
              <div className="chat-messages">
                {loadingMessages ? (
                  <div className="loading-messages">
                    <div className="spinner"></div>
                  </div>
                ) : (
                  <>
                    {messages.map((msg) => (
                      <div
                        key={msg.id}
                        className={`message-bubble ${msg.direction}`}
                      >
                        <div className="message-content">{msg.content}</div>
                        <div className="message-meta">
                          <span>{formatMessageTime(msg.created_at)}</span>
                          {msg.direction === 'outbound' && renderStatusIcon(msg.status)}
                        </div>
                      </div>
                    ))}
                    <div ref={messagesEndRef} />
                  </>
                )}
              </div>

              {/* Reply Box */}
              <div className="chat-reply-box">
                <div className="reply-input-wrapper">
                  <textarea
                    value={replyText}
                    onChange={(e) => setReplyText(e.target.value)}
                    onKeyDown={(e) => {
                      if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        handleSendReply();
                      }
                    }}
                    placeholder="Type your reply..."
                    rows={1}
                    disabled={
                      sending ||
                      (selectedConversation.channel === 'whatsapp' && !selectedConversation.whatsapp_window_open)
                    }
                  />
                  <button
                    className="send-btn"
                    onClick={handleSendReply}
                    disabled={
                      !replyText.trim() ||
                      sending ||
                      (selectedConversation.channel === 'whatsapp' && !selectedConversation.whatsapp_window_open)
                    }
                  >
                    {sending ? (
                      <div className="spinner"></div>
                    ) : (
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                        <line x1="22" y1="2" x2="11" y2="13" />
                        <polygon points="22 2 15 22 11 13 2 9 22 2" />
                      </svg>
                    )}
                  </button>
                </div>
              </div>
            </>
          )}
        </div>
      </div>
    </div>
  );
};

export default ConversationsPage;
