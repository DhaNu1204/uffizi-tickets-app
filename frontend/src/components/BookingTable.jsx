import { useState, useMemo } from 'react';
import './BookingTable.css';

// Guided tour product IDs that require a guide assignment
const GUIDED_TOUR_IDS = ['961801', '962885', '962886', '1130528', '1135055'];

// Timed Entry product ID (only product with audio guide option)
const TIMED_ENTRY_PRODUCT_ID = '961802';

const BookingTable = ({ bookings, onUpdate, loading, compact = false, productTypes = [] }) => {
  const [selectedBooking, setSelectedBooking] = useState(null);
  const [refInput, setRefInput] = useState('');
  const [notesInput, setNotesInput] = useState('');
  const [guideInput, setGuideInput] = useState('');
  const [sendingId, setSendingId] = useState(null); // Track which booking ticket is being toggled
  const [sendingAudioGuideId, setSendingAudioGuideId] = useState(null); // Track audio guide toggle
  const [copiedRef, setCopiedRef] = useState(null); // Track which reference was copied

  // Get short product type label
  const getProductType = (productId) => {
    const product = productTypes.find(p => p.id === String(productId));
    return product ? product.short : null;
  };

  // Check if it's a tour (not just tickets)
  const isTour = (productId) => {
    return String(productId) !== '961802'; // 961802 is Timed Entry Tickets
  };

  // Check if it's a guided tour (requires guide assignment)
  const isGuidedTour = (productId) => {
    return GUIDED_TOUR_IDS.includes(String(productId));
  };

  const handleOpenModal = (booking) => {
    setSelectedBooking(booking);
    setRefInput(booking.reference_number || '');
    setNotesInput(booking.notes || '');
    setGuideInput(booking.guide_name || '');
  };

  const handleCloseModal = () => {
    setSelectedBooking(null);
    setRefInput('');
    setNotesInput('');
    setGuideInput('');
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (selectedBooking) {
      const payload = {
        reference_number: refInput,
        notes: notesInput || null,
      };
      // Include guide_name for guided tours
      if (isGuidedTour(selectedBooking.bokun_product_id)) {
        payload.guide_name = guideInput || null;
      }
      // Only set status to purchased if reference is provided
      if (refInput) {
        payload.status = 'TICKET_PURCHASED';
      }
      onUpdate(selectedBooking.id, payload);
      handleCloseModal();
    }
  };

  // Handle toggle tickets sent
  const handleToggleSent = async (booking) => {
    setSendingId(booking.id);
    try {
      await onUpdate(booking.id, {
        tickets_sent: !booking.tickets_sent_at,
      });
    } finally {
      setSendingId(null);
    }
  };

  // Handle toggle audio guide sent
  const handleToggleAudioGuideSent = async (booking) => {
    setSendingAudioGuideId(booking.id);
    try {
      await onUpdate(booking.id, {
        audio_guide_sent: !booking.audio_guide_sent_at,
      });
    } finally {
      setSendingAudioGuideId(null);
    }
  };

  // Check if booking has audio guide
  const hasAudioGuide = (booking) => {
    return booking.has_audio_guide && String(booking.bokun_product_id) === TIMED_ENTRY_PRODUCT_ID;
  };

  // Copy reference number to clipboard
  const handleCopyRef = async (refNumber) => {
    try {
      await navigator.clipboard.writeText(refNumber);
      setCopiedRef(refNumber);
      // Reset after 2 seconds
      setTimeout(() => setCopiedRef(null), 2000);
    } catch (err) {
      console.error('Failed to copy:', err);
    }
  };

  const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB', {
      weekday: 'short',
      day: 'numeric',
      month: 'short',
    });
  };

  const formatTime = (dateString) => {
    const date = new Date(dateString);
    // Use UTC time since Bokun stores local Florence time as UTC
    return date.toLocaleTimeString('en-GB', {
      hour: '2-digit',
      minute: '2-digit',
      hour12: false,
      timeZone: 'UTC',
    });
  };

  const getDaysUntil = (dateString) => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const tourDate = new Date(dateString);
    tourDate.setHours(0, 0, 0, 0);
    const diffTime = tourDate - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
  };

  const formatPaxDetails = (paxDetails, totalPax) => {
    if (!paxDetails || !Array.isArray(paxDetails) || paxDetails.length === 0) {
      return totalPax.toString();
    }
    return paxDetails
      .filter(item => item.quantity > 0)
      .map(item => `${item.quantity}x ${item.type}`)
      .join(', ');
  };

  // Get short type from full type (e.g., "Adult (18 - 99)" -> "Adult")
  const getShortType = (type) => {
    if (!type) return '';
    // Remove age range in parentheses
    return type.replace(/\s*\([^)]*\)\s*/g, '').trim();
  };

  // Group bookings by time slot
  const groupedByTime = useMemo(() => {
    if (!compact) return null; // Only group in compact mode (day view)

    const groups = {};
    bookings.forEach(booking => {
      const time = formatTime(booking.tour_date);
      if (!groups[time]) {
        groups[time] = {
          time,
          bookings: [],
          totalPax: 0,
          pendingCount: 0,
        };
      }
      groups[time].bookings.push(booking);
      groups[time].totalPax += booking.pax;
      if (booking.status !== 'TICKET_PURCHASED') {
        groups[time].pendingCount++;
      }
    });

    // Convert to array and sort by time
    return Object.values(groups).sort((a, b) => a.time.localeCompare(b.time));
  }, [bookings, compact]);

  if (loading) {
    return (
      <div className="table-loading">
        <div className="loading-spinner"></div>
        <p>Loading bookings...</p>
      </div>
    );
  }

  if (bookings.length === 0) {
    return (
      <div className="table-empty">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
          <polyline points="14 2 14 8 20 8" />
          <line x1="12" y1="18" x2="12" y2="12" />
          <line x1="9" y1="15" x2="15" y2="15" />
        </svg>
        <p>No bookings found</p>
        <span>Try adjusting your filters or sync with Bokun</span>
      </div>
    );
  }

  // Render a single booking row
  const renderBookingRow = (booking) => {
    const daysUntil = getDaysUntil(booking.tour_date);
    const isUrgent = daysUntil <= 3 && booking.status !== 'TICKET_PURCHASED';

    return (
      <tr
        key={booking.id}
        className={`
          ${booking.status === 'TICKET_PURCHASED' ? 'row-done' : 'row-pending'}
          ${isUrgent ? 'row-urgent' : ''}
        `}
      >
        <td>
          <div className="customer-cell">
            <span className="customer-name">{booking.customer_name}</span>
            {booking.notes && (
              <span className="notes-indicator" title={booking.notes}>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                  <polyline points="14 2 14 8 20 8" />
                  <line x1="16" y1="13" x2="8" y2="13" />
                  <line x1="16" y1="17" x2="8" y2="17" />
                </svg>
              </span>
            )}
          </div>
        </td>
        <td>
          <div className="tour-cell">
            <div className="tour-name-row">
              <span className="tour-name">{booking.product_name}</span>
              {isTour(booking.bokun_product_id) && (
                <span className="product-badge tour">{getProductType(booking.bokun_product_id) || 'Tour'}</span>
              )}
              {hasAudioGuide(booking) && (
                <span className="product-badge audio-guide" title="Includes Audio Guide">Audio Guide</span>
              )}
            </div>
            <span className="tour-id">#{booking.bokun_booking_id}</span>
            {isGuidedTour(booking.bokun_product_id) && (
              <div className="guide-info">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                  <circle cx="12" cy="7" r="4" />
                </svg>
                <span className={booking.guide_name ? 'guide-assigned' : 'guide-unassigned'}>
                  {booking.guide_name || 'No guide assigned'}
                </span>
              </div>
            )}
          </div>
        </td>
        <td>
          <div className="pax-cell">
            {booking.participants && booking.participants.length > 0 ? (
              <div className="participants-list">
                {booking.participants.map((p, idx) => (
                  <div key={idx} className="participant-item">
                    <span className="participant-name">{p.name}</span>
                    <span className="participant-type">{getShortType(p.type)}</span>
                  </div>
                ))}
                {booking.has_incomplete_participants && (
                  <div className="incomplete-warning" title={`Only ${booking.participants.length} of ${booking.pax} participant names available (${booking.booking_channel || 'OTA'} booking)`}>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                      <circle cx="12" cy="12" r="10" />
                      <line x1="12" y1="8" x2="12" y2="12" />
                      <line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                    <span>+{booking.pax - booking.participants.length} more</span>
                  </div>
                )}
              </div>
            ) : (
              <span className="pax-details">{formatPaxDetails(booking.pax_details, booking.pax)}</span>
            )}
          </div>
        </td>
        <td>
          <span className={`status-badge ${booking.status === 'TICKET_PURCHASED' ? 'purchased' : 'pending'}`}>
            {booking.status === 'TICKET_PURCHASED' ? 'Purchased' : 'Pending'}
          </span>
        </td>
        <td>
          {booking.reference_number ? (
            <span
              className={`ref-number clickable ${copiedRef === booking.reference_number ? 'copied' : ''}`}
              onClick={() => handleCopyRef(booking.reference_number)}
              title="Click to copy"
            >
              {copiedRef === booking.reference_number ? 'Copied!' : booking.reference_number}
            </span>
          ) : (
            <span className="ref-empty">-</span>
          )}
        </td>
        <td>
          <div className="actions-cell">
            <button
              className={`action-btn ${booking.status === 'TICKET_PURCHASED' ? 'edit' : 'primary'}`}
              onClick={() => handleOpenModal(booking)}
            >
              {booking.status === 'TICKET_PURCHASED' ? 'Edit' : 'Add Ticket'}
            </button>
            {booking.status === 'TICKET_PURCHASED' && (
              <button
                className={`sent-btn ${booking.tickets_sent_at ? 'sent' : ''}`}
                onClick={() => handleToggleSent(booking)}
                disabled={sendingId === booking.id}
                title={booking.tickets_sent_at ? `Sent on ${new Date(booking.tickets_sent_at).toLocaleString('en-GB')}` : 'Mark as sent to client'}
              >
                {sendingId === booking.id ? (
                  <span className="btn-spinner"></span>
                ) : booking.tickets_sent_at ? (
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <polyline points="20 6 9 17 4 12" />
                  </svg>
                ) : (
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <path d="M22 2L11 13" />
                    <path d="M22 2L15 22L11 13L2 9L22 2Z" />
                  </svg>
                )}
                <span>{booking.tickets_sent_at ? 'Sent' : 'Send'}</span>
              </button>
            )}
            {booking.status === 'TICKET_PURCHASED' && hasAudioGuide(booking) && (
              <button
                className={`sent-btn audio-guide-btn ${booking.audio_guide_sent_at ? 'sent' : ''}`}
                onClick={() => handleToggleAudioGuideSent(booking)}
                disabled={sendingAudioGuideId === booking.id}
                title={booking.audio_guide_sent_at ? `Audio guide sent on ${new Date(booking.audio_guide_sent_at).toLocaleString('en-GB')}` : 'Mark audio guide as sent'}
              >
                {sendingAudioGuideId === booking.id ? (
                  <span className="btn-spinner"></span>
                ) : booking.audio_guide_sent_at ? (
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <polyline points="20 6 9 17 4 12" />
                  </svg>
                ) : (
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z" />
                    <path d="M19 10v2a7 7 0 0 1-14 0v-2" />
                    <line x1="12" y1="19" x2="12" y2="23" />
                    <line x1="8" y1="23" x2="16" y2="23" />
                  </svg>
                )}
                <span>{booking.audio_guide_sent_at ? 'AG Sent' : 'AG Send'}</span>
              </button>
            )}
          </div>
        </td>
      </tr>
    );
  };

  // Render mobile card
  const renderMobileCard = (booking) => {
    const daysUntil = getDaysUntil(booking.tour_date);
    const isUrgent = daysUntil <= 3 && booking.status !== 'TICKET_PURCHASED';

    return (
      <div
        key={booking.id}
        className={`booking-card ${booking.status === 'TICKET_PURCHASED' ? 'done' : 'pending'} ${isUrgent ? 'urgent' : ''}`}
      >
        <div className="card-header">
          <div className="card-customer-name">{booking.customer_name}</div>
          <span className={`status-badge ${booking.status === 'TICKET_PURCHASED' ? 'purchased' : 'pending'}`}>
            {booking.status === 'TICKET_PURCHASED' ? 'Purchased' : 'Pending'}
          </span>
        </div>

        <div className="card-body">
          {booking.participants && booking.participants.length > 0 && (
            <div className="card-participants">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                <circle cx="9" cy="7" r="4" />
                <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
              </svg>
              <div className="participants-list-mobile">
                {booking.participants.map((p, idx) => (
                  <span key={idx} className="participant-badge">
                    {p.name} <small>({getShortType(p.type)})</small>
                  </span>
                ))}
                {booking.has_incomplete_participants && (
                  <span className="participant-badge incomplete" title={`${booking.booking_channel || 'OTA'} booking`}>
                    +{booking.pax - booking.participants.length} more
                  </span>
                )}
              </div>
            </div>
          )}

          <div className="card-tour">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
              <line x1="16" y1="2" x2="16" y2="6" />
              <line x1="8" y1="2" x2="8" y2="6" />
              <line x1="3" y1="10" x2="21" y2="10" />
            </svg>
            <span>{booking.product_name}</span>
            {isTour(booking.bokun_product_id) && (
              <span className="product-badge tour">{getProductType(booking.bokun_product_id) || 'Tour'}</span>
            )}
            {hasAudioGuide(booking) && (
              <span className="product-badge audio-guide" title="Includes Audio Guide">Audio Guide</span>
            )}
          </div>

          {isGuidedTour(booking.bokun_product_id) && (
            <div className="card-guide">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
              </svg>
              <span className={booking.guide_name ? '' : 'unassigned'}>
                {booking.guide_name || 'No guide assigned'}
              </span>
            </div>
          )}

          {booking.reference_number && (
            <div
              className={`card-ref clickable ${copiedRef === booking.reference_number ? 'copied' : ''}`}
              onClick={() => handleCopyRef(booking.reference_number)}
              title="Click to copy"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <polyline points="9 11 12 14 22 4" />
                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
              </svg>
              <span>{copiedRef === booking.reference_number ? 'Copied!' : `Ref: ${booking.reference_number}`}</span>
            </div>
          )}

          {booking.notes && (
            <div className="card-notes">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                <polyline points="14 2 14 8 20 8" />
                <line x1="16" y1="13" x2="8" y2="13" />
                <line x1="16" y1="17" x2="8" y2="17" />
              </svg>
              <span>{booking.notes}</span>
            </div>
          )}
        </div>

        <div className="card-footer">
          <span className="booking-id">#{booking.bokun_booking_id}</span>
          <div className="card-actions">
            <button
              className={`action-btn ${booking.status === 'TICKET_PURCHASED' ? 'edit' : 'primary'}`}
              onClick={() => handleOpenModal(booking)}
            >
              {booking.status === 'TICKET_PURCHASED' ? 'Edit' : 'Add Ticket'}
            </button>
            {booking.status === 'TICKET_PURCHASED' && (
              <button
                className={`sent-btn ${booking.tickets_sent_at ? 'sent' : ''}`}
                onClick={() => handleToggleSent(booking)}
                disabled={sendingId === booking.id}
                title={booking.tickets_sent_at ? `Sent on ${new Date(booking.tickets_sent_at).toLocaleString('en-GB')}` : 'Mark as sent'}
              >
                {sendingId === booking.id ? (
                  <span className="btn-spinner"></span>
                ) : booking.tickets_sent_at ? (
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <polyline points="20 6 9 17 4 12" />
                  </svg>
                ) : (
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <path d="M22 2L11 13" />
                    <path d="M22 2L15 22L11 13L2 9L22 2Z" />
                  </svg>
                )}
              </button>
            )}
            {booking.status === 'TICKET_PURCHASED' && hasAudioGuide(booking) && (
              <button
                className={`sent-btn audio-guide-btn ${booking.audio_guide_sent_at ? 'sent' : ''}`}
                onClick={() => handleToggleAudioGuideSent(booking)}
                disabled={sendingAudioGuideId === booking.id}
                title={booking.audio_guide_sent_at ? `Audio guide sent on ${new Date(booking.audio_guide_sent_at).toLocaleString('en-GB')}` : 'Mark audio guide as sent'}
              >
                {sendingAudioGuideId === booking.id ? (
                  <span className="btn-spinner"></span>
                ) : booking.audio_guide_sent_at ? (
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <polyline points="20 6 9 17 4 12" />
                  </svg>
                ) : (
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z" />
                    <path d="M19 10v2a7 7 0 0 1-14 0v-2" />
                    <line x1="12" y1="19" x2="12" y2="23" />
                    <line x1="8" y1="23" x2="16" y2="23" />
                  </svg>
                )}
              </button>
            )}
          </div>
        </div>
      </div>
    );
  };

  return (
    <div className={`table-wrapper ${compact ? 'compact' : ''}`}>
      {/* Time-grouped view for compact mode */}
      {compact && groupedByTime ? (
        <div className="time-grouped-view">
          {groupedByTime.map((timeGroup) => (
            <div key={timeGroup.time} className="time-slot-group">
              <div className="time-slot-header">
                <div className="time-slot-time">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <circle cx="12" cy="12" r="10" />
                    <polyline points="12 6 12 12 16 14" />
                  </svg>
                  <span className="time-value">{timeGroup.time}</span>
                </div>
                <div className="time-slot-stats">
                  <span className="stat-item">
                    <strong>{timeGroup.bookings.length}</strong> booking{timeGroup.bookings.length !== 1 ? 's' : ''}
                  </span>
                  <span className="stat-item">
                    <strong>{timeGroup.totalPax}</strong> guest{timeGroup.totalPax !== 1 ? 's' : ''}
                  </span>
                  {timeGroup.pendingCount > 0 && (
                    <span className="stat-item pending">
                      <strong>{timeGroup.pendingCount}</strong> pending
                    </span>
                  )}
                </div>
              </div>

              {/* Desktop Table for this time slot */}
              <table className="booking-table desktop-table time-slot-table">
                <thead>
                  <tr>
                    <th>Customer</th>
                    <th>Tour</th>
                    <th>Participants</th>
                    <th>Status</th>
                    <th>Reference</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {timeGroup.bookings.map(renderBookingRow)}
                </tbody>
              </table>

              {/* Mobile Cards for this time slot */}
              <div className="mobile-cards">
                {timeGroup.bookings.map(renderMobileCard)}
              </div>
            </div>
          ))}
        </div>
      ) : (
        <>
          {/* Non-compact view - original table */}
          <table className="booking-table desktop-table">
            <thead>
              <tr>
                <th>Tour Date</th>
                <th>Customer</th>
                <th>Tour</th>
                <th>PAX</th>
                <th>Status</th>
                <th>Reference</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {bookings.map((booking) => {
                const daysUntil = getDaysUntil(booking.tour_date);
                const isUrgent = daysUntil <= 3 && booking.status !== 'TICKET_PURCHASED';

                return (
                  <tr
                    key={booking.id}
                    className={`
                      ${booking.status === 'TICKET_PURCHASED' ? 'row-done' : 'row-pending'}
                      ${isUrgent ? 'row-urgent' : ''}
                    `}
                  >
                    <td>
                      <div className="date-cell">
                        <span className="date-main">{formatDate(booking.tour_date)}</span>
                        {daysUntil >= 0 && (
                          <span className={`days-badge ${daysUntil <= 1 ? 'today' : daysUntil <= 3 ? 'soon' : ''}`}>
                            {daysUntil === 0 ? 'Today' : daysUntil === 1 ? 'Tomorrow' : `${daysUntil} days`}
                          </span>
                        )}
                      </div>
                    </td>
                    <td>
                      <div className="customer-cell">
                        <span className="customer-name">{booking.customer_name}</span>
                        {booking.notes && (
                          <span className="notes-indicator" title={booking.notes}>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                              <polyline points="14 2 14 8 20 8" />
                              <line x1="16" y1="13" x2="8" y2="13" />
                              <line x1="16" y1="17" x2="8" y2="17" />
                            </svg>
                          </span>
                        )}
                      </div>
                    </td>
                    <td>
                      <div className="tour-cell">
                        <div className="tour-name-row">
                          <span className="tour-name">{booking.product_name}</span>
                          {isTour(booking.bokun_product_id) && (
                            <span className="product-badge tour">{getProductType(booking.bokun_product_id) || 'Tour'}</span>
                          )}
                          {hasAudioGuide(booking) && (
                            <span className="product-badge audio-guide" title="Includes Audio Guide">Audio Guide</span>
                          )}
                        </div>
                        <span className="tour-id">#{booking.bokun_booking_id}</span>
                        {isGuidedTour(booking.bokun_product_id) && (
                          <div className="guide-info">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                              <circle cx="12" cy="7" r="4" />
                            </svg>
                            <span className={booking.guide_name ? 'guide-assigned' : 'guide-unassigned'}>
                              {booking.guide_name || 'No guide assigned'}
                            </span>
                          </div>
                        )}
                      </div>
                    </td>
                    <td>
                      <div className="pax-cell">
                        {booking.participants && booking.participants.length > 0 ? (
                          <div className="participants-list">
                            {booking.participants.map((p, idx) => (
                              <div key={idx} className="participant-item">
                                <span className="participant-name">{p.name}</span>
                                <span className="participant-type">{getShortType(p.type)}</span>
                              </div>
                            ))}
                            {booking.has_incomplete_participants && (
                              <div className="incomplete-warning" title={`Only ${booking.participants.length} of ${booking.pax} participant names available (${booking.booking_channel || 'OTA'} booking)`}>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                  <circle cx="12" cy="12" r="10" />
                                  <line x1="12" y1="8" x2="12" y2="12" />
                                  <line x1="12" y1="16" x2="12.01" y2="16" />
                                </svg>
                                <span>+{booking.pax - booking.participants.length} more</span>
                              </div>
                            )}
                          </div>
                        ) : (
                          <span className="pax-details">{formatPaxDetails(booking.pax_details, booking.pax)}</span>
                        )}
                      </div>
                    </td>
                    <td>
                      <span className={`status-badge ${booking.status === 'TICKET_PURCHASED' ? 'purchased' : 'pending'}`}>
                        {booking.status === 'TICKET_PURCHASED' ? 'Purchased' : 'Pending'}
                      </span>
                    </td>
                    <td>
                      {booking.reference_number ? (
                        <span
                          className={`ref-number clickable ${copiedRef === booking.reference_number ? 'copied' : ''}`}
                          onClick={() => handleCopyRef(booking.reference_number)}
                          title="Click to copy"
                        >
                          {copiedRef === booking.reference_number ? 'Copied!' : booking.reference_number}
                        </span>
                      ) : (
                        <span className="ref-empty">-</span>
                      )}
                    </td>
                    <td>
                      <div className="actions-cell">
                        <button
                          className={`action-btn ${booking.status === 'TICKET_PURCHASED' ? 'edit' : 'primary'}`}
                          onClick={() => handleOpenModal(booking)}
                        >
                          {booking.status === 'TICKET_PURCHASED' ? 'Edit' : 'Add Ticket'}
                        </button>
                        {booking.status === 'TICKET_PURCHASED' && (
                          <button
                            className={`sent-btn ${booking.tickets_sent_at ? 'sent' : ''}`}
                            onClick={() => handleToggleSent(booking)}
                            disabled={sendingId === booking.id}
                            title={booking.tickets_sent_at ? `Sent on ${new Date(booking.tickets_sent_at).toLocaleString('en-GB')}` : 'Mark as sent to client'}
                          >
                            {sendingId === booking.id ? (
                              <span className="btn-spinner"></span>
                            ) : booking.tickets_sent_at ? (
                              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                <polyline points="20 6 9 17 4 12" />
                              </svg>
                            ) : (
                              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                <path d="M22 2L11 13" />
                                <path d="M22 2L15 22L11 13L2 9L22 2Z" />
                              </svg>
                            )}
                            <span>{booking.tickets_sent_at ? 'Sent' : 'Send'}</span>
                          </button>
                        )}
                        {booking.status === 'TICKET_PURCHASED' && hasAudioGuide(booking) && (
                          <button
                            className={`sent-btn audio-guide-btn ${booking.audio_guide_sent_at ? 'sent' : ''}`}
                            onClick={() => handleToggleAudioGuideSent(booking)}
                            disabled={sendingAudioGuideId === booking.id}
                            title={booking.audio_guide_sent_at ? `Audio guide sent on ${new Date(booking.audio_guide_sent_at).toLocaleString('en-GB')}` : 'Mark audio guide as sent'}
                          >
                            {sendingAudioGuideId === booking.id ? (
                              <span className="btn-spinner"></span>
                            ) : booking.audio_guide_sent_at ? (
                              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                <polyline points="20 6 9 17 4 12" />
                              </svg>
                            ) : (
                              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z" />
                                <path d="M19 10v2a7 7 0 0 1-14 0v-2" />
                                <line x1="12" y1="19" x2="12" y2="23" />
                                <line x1="8" y1="23" x2="16" y2="23" />
                              </svg>
                            )}
                            <span>{booking.audio_guide_sent_at ? 'AG Sent' : 'AG Send'}</span>
                          </button>
                        )}
                      </div>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>

          {/* Mobile Cards - non-compact view */}
          <div className="mobile-cards">
            {bookings.map((booking) => {
              const daysUntil = getDaysUntil(booking.tour_date);
              const isUrgent = daysUntil <= 3 && booking.status !== 'TICKET_PURCHASED';

              return (
                <div
                  key={booking.id}
                  className={`booking-card ${booking.status === 'TICKET_PURCHASED' ? 'done' : 'pending'} ${isUrgent ? 'urgent' : ''}`}
                >
                  <div className="card-header">
                    <div className="card-date">
                      <span className="date-main">{formatDate(booking.tour_date)}</span>
                      {daysUntil >= 0 && (
                        <span className={`days-badge ${daysUntil <= 1 ? 'today' : daysUntil <= 3 ? 'soon' : ''}`}>
                          {daysUntil === 0 ? 'Today' : daysUntil === 1 ? 'Tomorrow' : `${daysUntil}d`}
                        </span>
                      )}
                    </div>
                    <span className={`status-badge ${booking.status === 'TICKET_PURCHASED' ? 'purchased' : 'pending'}`}>
                      {booking.status === 'TICKET_PURCHASED' ? 'Purchased' : 'Pending'}
                    </span>
                  </div>

                  <div className="card-body">
                    <div className="card-customer">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                      </svg>
                      <span>{booking.customer_name}</span>
                    </div>

                    <div className="card-tour">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                        <line x1="16" y1="2" x2="16" y2="6" />
                        <line x1="8" y1="2" x2="8" y2="6" />
                        <line x1="3" y1="10" x2="21" y2="10" />
                      </svg>
                      <span>{booking.product_name}</span>
                      {hasAudioGuide(booking) && (
                        <span className="product-badge audio-guide" title="Includes Audio Guide">Audio Guide</span>
                      )}
                    </div>

                    {isGuidedTour(booking.bokun_product_id) && (
                      <div className="card-guide">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                          <circle cx="12" cy="7" r="4" />
                        </svg>
                        <span className={booking.guide_name ? '' : 'unassigned'}>
                          {booking.guide_name || 'No guide assigned'}
                        </span>
                      </div>
                    )}
                  </div>

                  <div className="card-footer">
                    <span className="booking-id">#{booking.bokun_booking_id}</span>
                    <div className="card-actions">
                      <button
                        className={`action-btn ${booking.status === 'TICKET_PURCHASED' ? 'edit' : 'primary'}`}
                        onClick={() => handleOpenModal(booking)}
                      >
                        {booking.status === 'TICKET_PURCHASED' ? 'Edit' : 'Add Ticket'}
                      </button>
                      {booking.status === 'TICKET_PURCHASED' && (
                        <button
                          className={`sent-btn ${booking.tickets_sent_at ? 'sent' : ''}`}
                          onClick={() => handleToggleSent(booking)}
                          disabled={sendingId === booking.id}
                          title={booking.tickets_sent_at ? `Sent on ${new Date(booking.tickets_sent_at).toLocaleString('en-GB')}` : 'Mark as sent'}
                        >
                          {sendingId === booking.id ? (
                            <span className="btn-spinner"></span>
                          ) : booking.tickets_sent_at ? (
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                              <polyline points="20 6 9 17 4 12" />
                            </svg>
                          ) : (
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                              <path d="M22 2L11 13" />
                              <path d="M22 2L15 22L11 13L2 9L22 2Z" />
                            </svg>
                          )}
                        </button>
                      )}
                      {booking.status === 'TICKET_PURCHASED' && hasAudioGuide(booking) && (
                        <button
                          className={`sent-btn audio-guide-btn ${booking.audio_guide_sent_at ? 'sent' : ''}`}
                          onClick={() => handleToggleAudioGuideSent(booking)}
                          disabled={sendingAudioGuideId === booking.id}
                          title={booking.audio_guide_sent_at ? `Audio guide sent on ${new Date(booking.audio_guide_sent_at).toLocaleString('en-GB')}` : 'Mark audio guide as sent'}
                        >
                          {sendingAudioGuideId === booking.id ? (
                            <span className="btn-spinner"></span>
                          ) : booking.audio_guide_sent_at ? (
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                              <polyline points="20 6 9 17 4 12" />
                            </svg>
                          ) : (
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                              <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z" />
                              <path d="M19 10v2a7 7 0 0 1-14 0v-2" />
                              <line x1="12" y1="19" x2="12" y2="23" />
                              <line x1="8" y1="23" x2="16" y2="23" />
                            </svg>
                          )}
                        </button>
                      )}
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        </>
      )}

      {/* Modal */}
      {selectedBooking && (
        <div className="modal-overlay" onClick={handleCloseModal}>
          <div className="modal" onClick={(e) => e.stopPropagation()}>
            <button className="modal-close" onClick={handleCloseModal}>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
              </svg>
            </button>

            <div className="modal-header">
              <h3>Update Ticket</h3>
            </div>

            <div className="modal-body">
              <div className="modal-content-wrapper">
                {/* Left side: Booking Summary */}
                <div className="modal-left">
                  <h4 className="section-title">Booking Details</h4>
                  <div className="booking-summary">
                    <div className="summary-row">
                      <span className="label">Customer</span>
                      <span className="value">{selectedBooking.customer_name}</span>
                    </div>
                    {selectedBooking.customer_email && (
                      <div className="summary-row contact-row">
                        <span className="label">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="contact-icon">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                            <polyline points="22,6 12,13 2,6" />
                          </svg>
                          Email
                        </span>
                        <a href={`mailto:${selectedBooking.customer_email}`} className="value contact-link">
                          {selectedBooking.customer_email}
                        </a>
                      </div>
                    )}
                    {selectedBooking.customer_phone && (
                      <div className="summary-row contact-row">
                        <span className="label">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="contact-icon">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
                          </svg>
                          Phone
                        </span>
                        <a href={`tel:${selectedBooking.customer_phone}`} className="value contact-link">
                          {selectedBooking.customer_phone}
                        </a>
                      </div>
                    )}
                    <div className="summary-row">
                      <span className="label">Tour</span>
                      <span className="value">{selectedBooking.product_name}</span>
                    </div>
                    <div className="summary-row">
                      <span className="label">Date</span>
                      <span className="value">{formatDate(selectedBooking.tour_date)}</span>
                    </div>
                    <div className="summary-row">
                      <span className="label">PAX</span>
                      <span className="value">{formatPaxDetails(selectedBooking.pax_details, selectedBooking.pax)}</span>
                    </div>
                    {selectedBooking.participants && selectedBooking.participants.length > 0 && (
                      <div className="summary-row participants-row">
                        <span className="label">Participants</span>
                        <div className="value participants-modal-list">
                          {selectedBooking.participants.map((p, idx) => (
                            <div key={idx} className="participant-modal-item">
                              <span className="participant-name">{p.name}</span>
                              <span className="participant-type-badge">{getShortType(p.type)}</span>
                            </div>
                          ))}
                          {selectedBooking.has_incomplete_participants && (
                            <div className="incomplete-warning-modal">
                              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                <circle cx="12" cy="12" r="10" />
                                <line x1="12" y1="8" x2="12" y2="12" />
                                <line x1="12" y1="16" x2="12.01" y2="16" />
                              </svg>
                              <span>+{selectedBooking.pax - selectedBooking.participants.length} participant names not available ({selectedBooking.booking_channel || 'OTA'} booking)</span>
                            </div>
                          )}
                        </div>
                      </div>
                    )}
                  </div>
                </div>

                {/* Right side: Form */}
                <div className="modal-right">
                  <h4 className="section-title">Ticket Information</h4>
                  <form onSubmit={handleSubmit}>
                    <div className="form-group">
                      <label htmlFor="refNumber">Uffizi Ticket Reference</label>
                      <input
                        type="text"
                        id="refNumber"
                        value={refInput}
                        onChange={(e) => setRefInput(e.target.value)}
                        placeholder="e.g. 4CZSHPDD"
                        autoFocus
                      />
                    </div>

                    {isGuidedTour(selectedBooking.bokun_product_id) && (
                      <div className="form-group guide-field">
                        <label htmlFor="guideName">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                          </svg>
                          Assigned Guide
                        </label>
                        <input
                          type="text"
                          id="guideName"
                          value={guideInput}
                          onChange={(e) => setGuideInput(e.target.value)}
                          placeholder="Enter guide name..."
                        />
                      </div>
                    )}

                    <div className="form-group">
                      <label htmlFor="notes">Notes (optional)</label>
                      <textarea
                        id="notes"
                        value={notesInput}
                        onChange={(e) => setNotesInput(e.target.value)}
                        placeholder="Add any notes about this booking..."
                        rows={3}
                      />
                    </div>

                    <div className="modal-actions">
                      <button type="button" onClick={handleCloseModal} className="btn-cancel">
                        Cancel
                      </button>
                      <button type="submit" className="btn-confirm">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                          <polyline points="20 6 9 17 4 12" />
                        </svg>
                        {refInput ? 'Save & Mark Purchased' : 'Save Notes'}
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default BookingTable;
