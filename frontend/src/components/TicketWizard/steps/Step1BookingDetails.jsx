import { formatDate, formatTime } from '../../../utils/dateUtils';

export default function Step1BookingDetails({ booking }) {
  const tourDate = new Date(booking.tour_date);

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

        {booking.pax_details && (
          Array.isArray(booking.pax_details) ? booking.pax_details.length > 0 : Object.keys(booking.pax_details).length > 0
        ) && (
          <div className="detail-row">
            <span className="detail-label">PAX Details</span>
            <span className="detail-value pax-details">
              {Array.isArray(booking.pax_details)
                ? booking.pax_details.map((item, idx) => (
                    <span key={idx} className="pax-item">
                      {item.quantity}x {item.type}
                    </span>
                  ))
                : Object.entries(booking.pax_details).map(([type, count]) => (
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
