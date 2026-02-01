import { formatDate, formatTime } from '../../../utils/dateUtils';

/**
 * Parse pax_details which may come as string, array, or object
 */
function parsePaxDetails(paxDetails) {
  if (!paxDetails) return null;

  // If it's already an object/array, return as-is
  if (typeof paxDetails === 'object') return paxDetails;

  // If it's a string, try to parse it
  if (typeof paxDetails === 'string') {
    try {
      return JSON.parse(paxDetails);
    } catch (e) {
      console.warn('Failed to parse pax_details:', paxDetails);
      return null;
    }
  }

  return null;
}

export default function Step1BookingDetails({ booking }) {
  const tourDate = new Date(booking.tour_date);
  const paxDetails = parsePaxDetails(booking.pax_details);

  return (
    <div className="wizard-step-content step-booking-details">
      <h3>Confirm Booking Details</h3>
      <p className="step-description">
        Please verify the booking information before proceeding.
      </p>

      <div className="booking-details-card">
        <div className="detail-row">
          <span className="detail-label">Customer</span>
          <span className="detail-value">{booking.customer_name}</span>
        </div>

        {booking.customer_email && (
          <div className="detail-row">
            <span className="detail-label">Email</span>
            <a href={`mailto:${booking.customer_email}`} className="detail-value email-link">
              {booking.customer_email}
            </a>
          </div>
        )}

        {booking.customer_phone && (
          <div className="detail-row">
            <span className="detail-label">Phone</span>
            <a href={`tel:${booking.customer_phone}`} className="detail-value phone-link">
              {booking.customer_phone}
            </a>
          </div>
        )}

        <div className="detail-divider" />

        <div className="detail-row">
          <span className="detail-label">Product</span>
          <span className="detail-value">{booking.product_name}</span>
        </div>

        <div className="detail-row">
          <span className="detail-label">Date</span>
          <span className="detail-value">{formatDate(tourDate)}</span>
        </div>

        <div className="detail-row">
          <span className="detail-label">Time</span>
          <span className="detail-value">{formatTime(tourDate)}</span>
        </div>

        <div className="detail-row">
          <span className="detail-label">Guests</span>
          <span className="detail-value">{booking.pax}</span>
        </div>

        {paxDetails && (
          Array.isArray(paxDetails) ? paxDetails.length > 0 : Object.keys(paxDetails).length > 0
        ) && (
          <div className="detail-row">
            <span className="detail-label">PAX Details</span>
            <span className="detail-value pax-details">
              {Array.isArray(paxDetails)
                ? paxDetails.map((item, idx) => (
                    <span key={idx} className="pax-item">
                      {item.quantity || item.count || 1}x {item.type || item.name || 'Guest'}
                    </span>
                  ))
                : Object.entries(paxDetails).map(([type, count]) => (
                    <span key={type} className="pax-item">
                      {count}x {type}
                    </span>
                  ))
              }
            </span>
          </div>
        )}

        {booking.has_audio_guide && (
          <div className="detail-row highlight">
            <span className="detail-label">Audio Guide</span>
            <span className="detail-value audio-badge">
              <span className="audio-icon">🎧</span> Included
            </span>
          </div>
        )}
      </div>

      {!booking.customer_email && !booking.customer_phone && (
        <div className="warning-box">
          <strong>Warning:</strong> This booking has no contact information.
          The customer cannot be notified.
        </div>
      )}
    </div>
  );
}
