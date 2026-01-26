import { useEffect, useState } from 'react';
import { templatesAPI } from '../../../services/api';

// Extended country code to language mapping (10+ languages)
const COUNTRY_CODE_TO_LANGUAGE = {
  // Italian
  '39': 'it',
  // Spanish
  '34': 'es',  // Spain
  '52': 'es',  // Mexico
  '54': 'es',  // Argentina
  '57': 'es',  // Colombia
  '56': 'es',  // Chile
  '51': 'es',  // Peru
  '58': 'es',  // Venezuela
  // German
  '49': 'de',  // Germany
  '43': 'de',  // Austria
  '41': 'de',  // Switzerland (partial)
  // French
  '33': 'fr',  // France
  '32': 'fr',  // Belgium (partial)
  '352': 'fr', // Luxembourg
  '377': 'fr', // Monaco
  // Japanese
  '81': 'ja',
  // Greek
  '30': 'el',
  // Turkish
  '90': 'tr',
  // Korean
  '82': 'ko',
  // Portuguese
  '351': 'pt', // Portugal
  '55': 'pt',  // Brazil
  // Russian
  '7': 'ru',
  // Arabic (various countries)
  '966': 'ar', // Saudi Arabia
  '971': 'ar', // UAE
  '20': 'ar',  // Egypt
  '212': 'ar', // Morocco
  // Chinese
  '86': 'zh',  // China
  '852': 'zh', // Hong Kong
  '853': 'zh', // Macau
  '886': 'zh', // Taiwan
  // Dutch
  '31': 'nl',
  // Polish
  '48': 'pl',
  // Default to English for:
  // '1' - USA/Canada
  // '44' - UK
  // '61' - Australia
  // '64' - New Zealand
  // And all others
};

// Country names for display
const COUNTRY_NAMES = {
  '39': 'Italy',
  '34': 'Spain',
  '52': 'Mexico',
  '54': 'Argentina',
  '57': 'Colombia',
  '56': 'Chile',
  '51': 'Peru',
  '58': 'Venezuela',
  '49': 'Germany',
  '43': 'Austria',
  '41': 'Switzerland',
  '33': 'France',
  '32': 'Belgium',
  '352': 'Luxembourg',
  '377': 'Monaco',
  '81': 'Japan',
  '30': 'Greece',
  '90': 'Turkey',
  '82': 'South Korea',
  '351': 'Portugal',
  '55': 'Brazil',
  '7': 'Russia',
  '966': 'Saudi Arabia',
  '971': 'UAE',
  '20': 'Egypt',
  '212': 'Morocco',
  '86': 'China',
  '852': 'Hong Kong',
  '853': 'Macau',
  '886': 'Taiwan',
  '31': 'Netherlands',
  '48': 'Poland',
};

/**
 * Detect language from phone number country code
 * @param {string} phoneNumber - Phone number with country code (e.g., +39123456789)
 * @returns {object} - { language: 'it', country: 'Italy', confidence: 'high' }
 */
export function detectLanguageFromPhone(phoneNumber) {
  if (!phoneNumber) {
    return { language: 'en', country: null, confidence: 'none' };
  }

  // Remove spaces, dashes, and get just digits
  const cleaned = phoneNumber.replace(/[\s\-\(\)]/g, '');

  // Extract country code (remove leading + or 00)
  let digits = cleaned;
  if (digits.startsWith('+')) {
    digits = digits.substring(1);
  } else if (digits.startsWith('00')) {
    digits = digits.substring(2);
  }

  // Try to match country codes (longest first for codes like 352, 966)
  for (const codeLength of [3, 2, 1]) {
    const potentialCode = digits.substring(0, codeLength);
    if (COUNTRY_CODE_TO_LANGUAGE[potentialCode]) {
      const language = COUNTRY_CODE_TO_LANGUAGE[potentialCode];
      return {
        language,
        country: COUNTRY_NAMES[potentialCode] || null,
        confidence: 'high',
      };
    }
  }

  // Default to English
  return { language: 'en', country: null, confidence: 'low' };
}

export default function Step4TemplateSelect({ booking, language, onChange, detectedLanguage, onOpenCustomModal }) {
  const [languages, setLanguages] = useState([]);
  const [loading, setLoading] = useState(true);
  const [isAutoDetected, setIsAutoDetected] = useState(false);

  // Fetch available languages from API
  useEffect(() => {
    const fetchLanguages = async () => {
      try {
        const response = await templatesAPI.getLanguages();
        setLanguages(response.data.languages || []);
      } catch (error) {
        console.error('Failed to fetch languages:', error);
        // Fallback to basic list if API fails
        setLanguages([
          { code: 'en', name: 'English', flag: '' },
          { code: 'it', name: 'Italian', flag: '' },
          { code: 'es', name: 'Spanish', flag: '' },
          { code: 'de', name: 'German', flag: '' },
          { code: 'fr', name: 'French', flag: '' },
        ]);
      } finally {
        setLoading(false);
      }
    };
    fetchLanguages();
  }, []);

  // Auto-detect language on first render if not already set
  useEffect(() => {
    if (booking.customer_phone && language === 'en' && languages.length > 0) {
      const detection = detectLanguageFromPhone(booking.customer_phone);
      // Only auto-select if the detected language exists in our list
      const langExists = languages.some(l => l.code === detection.language);
      if (detection.confidence === 'high' && detection.language !== 'en' && langExists) {
        onChange({ language: detection.language, detectedLanguage: detection });
        setIsAutoDetected(true);
      }
    }
  }, [languages]); // Run when languages are loaded

  const handleLanguageChange = (code) => {
    if (code === 'custom') {
      // Open custom message modal
      if (onOpenCustomModal) {
        onOpenCustomModal();
      }
      return;
    }
    setIsAutoDetected(false);
    onChange({ language: code });
  };

  const detection = detectedLanguage || detectLanguageFromPhone(booking.customer_phone);
  const detectedLang = languages.find(l => l.code === detection.language);

  if (loading) {
    return (
      <div className="wizard-step-content step-template-select">
        <h3>Select Message Language</h3>
        <div className="loading-spinner">Loading languages...</div>
      </div>
    );
  }

  return (
    <div className="wizard-step-content step-template-select">
      <h3>Select Message Language</h3>
      <p className="step-description">
        Choose the language for the ticket notification message.
      </p>

      {/* Auto-detection notice */}
      {detection.confidence === 'high' && booking.customer_phone && (
        <div className="language-detection-notice">
          <span className="detection-icon"></span>
          <div className="detection-text">
            <strong>Auto-detected:</strong> Phone number from {detection.country}
            {isAutoDetected && language === detection.language && (
              <span className="auto-selected"> — {detectedLang?.name} selected</span>
            )}
          </div>
          {booking.customer_phone && (
            <span className="detection-phone">{booking.customer_phone}</span>
          )}
        </div>
      )}

      <div className="language-grid">
        {languages.map((lang) => (
          <button
            key={lang.code}
            type="button"
            className={`language-card ${language === lang.code ? 'selected' : ''} ${detection.language === lang.code && detection.confidence === 'high' ? 'suggested' : ''}`}
            onClick={() => handleLanguageChange(lang.code)}
          >
            <span className="language-flag">{lang.flag}</span>
            <span className="language-name">{lang.name}</span>
            {language === lang.code && (
              <span className="check-mark"></span>
            )}
            {detection.language === lang.code && detection.confidence === 'high' && language !== lang.code && (
              <span className="suggested-badge">Suggested</span>
            )}
          </button>
        ))}

        {/* Custom Language Option */}
        <button
          type="button"
          className={`language-card custom-option ${language === 'custom' ? 'selected' : ''}`}
          onClick={() => handleLanguageChange('custom')}
        >
          <span className="language-flag"></span>
          <span className="language-name">Custom</span>
          <span className="custom-hint">Type your own message</span>
        </button>
      </div>

      <div className="template-info">
        <h4>What will be sent:</h4>
        <ul>
          <li>
            <strong>Ticket PDF</strong> - The attached Uffizi ticket file
          </li>
          <li>
            <strong>Booking details</strong> - Date, time, reference number
          </li>
          {booking.has_audio_guide && (
            <li>
              <strong>Audio guide credentials</strong> - Username, password, and access link
            </li>
          )}
        </ul>
      </div>

      <div className="tip-box">
        <strong>Tip:</strong> The message will be automatically sent via the best available channel
        (WhatsApp if available, otherwise Email + SMS).
      </div>
    </div>
  );
}
