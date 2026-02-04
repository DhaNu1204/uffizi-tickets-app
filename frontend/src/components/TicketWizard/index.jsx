import { useState, useEffect, useCallback, useMemo } from 'react';
import WizardProgress from './WizardProgress';
import WizardNavigation from './WizardNavigation';
import Step1BookingDetails from './steps/Step1BookingDetails';
import Step2TicketReference, { validateReferenceCode } from './steps/Step2TicketReference';
import Step3FileAttach from './steps/Step3FileAttach';
import Step4AudioGuide from './steps/Step4AudioGuide';
import Step4TemplateSelect from './steps/Step4TemplateSelect';
import Step5Preview from './steps/Step5Preview';
import Step6SendStatus from './steps/Step6SendStatus';
import CustomMessageModal from './CustomMessageModal';
import { bookingsAPI, messagesAPI } from '../../services/api';
import './TicketWizard.css';

/**
 * Get steps based on whether booking has audio guide
 * Audio guide bookings: 7 steps (includes Audio Guide step)
 * Regular bookings: 6 steps
 */
const getSteps = (hasAudioGuide) => {
  const steps = [
    { id: 1, title: 'Booking Details', shortTitle: 'Details' },
    { id: 2, title: 'Ticket Reference', shortTitle: 'Reference' },
    { id: 3, title: 'Attach PDF', shortTitle: 'Attach' },
  ];

  if (hasAudioGuide) {
    steps.push({ id: 4, title: 'Audio Guide', shortTitle: 'Audio' });
    steps.push({ id: 5, title: 'Select Language', shortTitle: 'Language' });
    steps.push({ id: 6, title: 'Preview & Confirm', shortTitle: 'Preview' });
    steps.push({ id: 7, title: 'Send Status', shortTitle: 'Send' });
  } else {
    steps.push({ id: 4, title: 'Select Language', shortTitle: 'Language' });
    steps.push({ id: 5, title: 'Preview & Confirm', shortTitle: 'Preview' });
    steps.push({ id: 6, title: 'Send Status', shortTitle: 'Send' });
  }

  return steps;
};

export default function TicketWizard({ booking, onClose, onComplete }) {
  // Get dynamic steps based on whether booking has audio guide
  const hasAudioGuide = booking.has_audio_guide;
  const STEPS = useMemo(() => getSteps(hasAudioGuide), [hasAudioGuide]);
  const totalSteps = STEPS.length;

  // Step mappings for audio guide vs non-audio guide bookings
  // Audio guide: 1=Details, 2=Reference, 3=Attach, 4=Audio, 5=Language, 6=Preview, 7=Send
  // Regular:     1=Details, 2=Reference, 3=Attach, 4=Language, 5=Preview, 6=Send
  const STEP_LANGUAGE = hasAudioGuide ? 5 : 4;
  const STEP_PREVIEW = hasAudioGuide ? 6 : 5;
  const STEP_SEND = hasAudioGuide ? 7 : 6;
  const STEP_AUDIO = hasAudioGuide ? 4 : null;

  const [currentStep, setCurrentStep] = useState(1);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(null);
  const [showCustomModal, setShowCustomModal] = useState(false);

  // Wizard state
  const [wizardData, setWizardData] = useState({
    // Step 2: Ticket reference
    referenceNumber: booking.reference_number || '',
    guideName: booking.guide_name || '',
    // Audio guide credentials (may come from VOX or manual entry)
    audioGuideUsername: booking.audio_guide_username || '',
    audioGuidePassword: booking.audio_guide_password || '',
    audioGuideUrl: booking.audio_guide_url || '',
    // VOX Audio Guide fields
    voxDynamicLink: booking.vox_dynamic_link || null,
    voxAccountId: booking.vox_account_id || null,
    voxUsername: null,
    voxPassword: null,
    hasVoxAccount: !!booking.vox_dynamic_link,
    // Step 3: Attachments
    attachments: [],
    // Step 4/5: Language & Template
    language: 'en',
    customMessage: null, // For custom message { subject, content }
    // Step 5/6: Channel detection
    channelInfo: null,
    preview: null,
    // Step 6/7: Send status
    sendResult: null,
    isSending: false,
  });

  // Validation state per step (dynamic based on audio guide)
  const [stepValidation, setStepValidation] = useState(() => {
    const validation = {
      1: true, // Always valid (read-only)
      2: false,
      3: false,
    };
    if (hasAudioGuide) {
      validation[4] = false; // Audio guide step - needs VOX account
      validation[5] = true;  // Language - default selected
      validation[6] = true;  // Preview - read-only
      validation[7] = true;  // Send step
    } else {
      validation[4] = true;  // Language - default selected
      validation[5] = true;  // Preview - read-only
      validation[6] = true;  // Send step
    }
    return validation;
  });

  // Track wizard progress in database
  useEffect(() => {
    // Mark wizard as started when component mounts
    bookingsAPI.updateWizardProgress(booking.id, 1, 'start').catch(console.error);
  }, [booking.id]);

  // Track step changes
  useEffect(() => {
    if (currentStep > 1) {
      bookingsAPI.updateWizardProgress(booking.id, currentStep, 'progress').catch(console.error);
    }
  }, [currentStep, booking.id]);

  // Validate step 2: Reference number format
  // Note: Audio guide credentials are now handled in Step 4 (Audio Guide step)
  useEffect(() => {
    const referenceValidation = validateReferenceCode(wizardData.referenceNumber);
    setStepValidation((prev) => ({ ...prev, 2: referenceValidation.valid }));
  }, [wizardData.referenceNumber]);

  // Validate step 3: At least one attachment
  useEffect(() => {
    setStepValidation((prev) => ({ ...prev, 3: wizardData.attachments.length > 0 }));
  }, [wizardData.attachments]);

  // Validate step 4 (Audio Guide): Must have VOX account if booking has audio guide
  useEffect(() => {
    if (STEP_AUDIO) {
      setStepValidation((prev) => ({
        ...prev,
        [STEP_AUDIO]: wizardData.hasVoxAccount,
      }));
    }
  }, [wizardData.hasVoxAccount, STEP_AUDIO]);

  // Detect channel when reaching preview step
  useEffect(() => {
    if (currentStep === STEP_PREVIEW && !wizardData.channelInfo) {
      detectChannel();
    }
  }, [currentStep, STEP_PREVIEW]);

  const detectChannel = async () => {
    try {
      setIsLoading(true);
      const response = await messagesAPI.detectChannel(booking.id);
      setWizardData((prev) => ({
        ...prev,
        channelInfo: response.data,
      }));

      // Also get preview
      const previewResponse = await messagesAPI.preview({
        booking_id: booking.id,
        language: wizardData.language,
      });
      setWizardData((prev) => ({
        ...prev,
        preview: previewResponse.data,
      }));
    } catch (err) {
      setError('Failed to detect messaging channel');
      console.error(err);
    } finally {
      setIsLoading(false);
    }
  };

  const updateWizardData = useCallback((updates) => {
    setWizardData((prev) => ({ ...prev, ...updates }));
  }, []);

  // Handle custom message modal
  const handleOpenCustomModal = () => {
    setShowCustomModal(true);
  };

  const handleSaveCustomMessage = (customData) => {
    setWizardData((prev) => ({
      ...prev,
      language: 'custom',
      customMessage: customData,
    }));
    setShowCustomModal(false);
  };

  const handleNext = async () => {
    setError(null);

    // Save data at step 2 before moving forward
    if (currentStep === 2) {
      try {
        setIsLoading(true);
        await bookingsAPI.update(booking.id, {
          reference_number: wizardData.referenceNumber,
          guide_name: wizardData.guideName || null,
          status: 'TICKET_PURCHASED',
        });
      } catch (err) {
        setError('Failed to save ticket reference');
        console.error(err);
        return;
      } finally {
        setIsLoading(false);
      }
    }

    // Refresh preview when changing language (step varies based on audio guide)
    if (currentStep === STEP_LANGUAGE) {
      setWizardData((prev) => ({ ...prev, preview: null, channelInfo: null }));
    }

    if (currentStep < totalSteps) {
      setCurrentStep((prev) => prev + 1);
    }
  };

  const handleBack = () => {
    if (currentStep > 1) {
      setCurrentStep((prev) => prev - 1);
    }
  };

  const handleSend = async () => {
    setError(null);
    updateWizardData({ isSending: true });

    try {
      const sendData = {
        language: wizardData.language,
        attachment_ids: wizardData.attachments.map((a) => a.id),
      };

      // Include custom message if using custom language
      if (wizardData.language === 'custom' && wizardData.customMessage) {
        sendData.custom_subject = wizardData.customMessage.subject;
        sendData.custom_content = wizardData.customMessage.content;
      }

      const response = await messagesAPI.sendTicket(booking.id, sendData);

      updateWizardData({
        sendResult: response.data,
        isSending: false,
      });

      // Move to send status step to show result
      if (currentStep !== STEP_SEND) {
        setCurrentStep(STEP_SEND);
      }
    } catch (err) {
      setError(err.response?.data?.error || 'Failed to send ticket');
      updateWizardData({ isSending: false });
    }
  };

  const handleClose = (force = false) => {
    // If ticket was sent successfully, mark as complete and call onComplete
    if (wizardData.sendResult?.success) {
      bookingsAPI.updateWizardProgress(booking.id, STEP_SEND, 'complete').catch(console.error);
      onComplete?.(wizardData.sendResult);
      onClose();
      return;
    }

    // If user has made progress (step > 1), confirm before closing
    if (!force && currentStep > 1) {
      const confirmClose = window.confirm(
        'Are you sure you want to close? Your progress will be lost.'
      );
      if (!confirmClose) return;
    }

    // Mark as abandoned if closed without completing
    bookingsAPI.updateWizardProgress(booking.id, currentStep, 'abandon').catch(console.error);
    onClose();
  };

  // Handle overlay click - require explicit close, don't close on overlay
  const handleOverlayClick = (e) => {
    // Only close if clicking directly on the overlay, not inside the wizard
    if (e.target === e.currentTarget) {
      // Don't auto-close on overlay click - user must use X button
      // This prevents accidental closure
    }
  };

  const renderStep = () => {
    // Steps 1-3 are always the same
    if (currentStep === 1) {
      return <Step1BookingDetails booking={booking} />;
    }
    if (currentStep === 2) {
      return (
        <Step2TicketReference
          booking={booking}
          data={wizardData}
          onChange={updateWizardData}
        />
      );
    }
    if (currentStep === 3) {
      return (
        <Step3FileAttach
          booking={booking}
          attachments={wizardData.attachments}
          referenceNumber={wizardData.referenceNumber}
          onChange={updateWizardData}
        />
      );
    }

    // Step 4: Audio Guide (only if booking has audio guide)
    if (currentStep === STEP_AUDIO) {
      return (
        <Step4AudioGuide
          booking={booking}
          wizardData={wizardData}
          onChange={updateWizardData}
        />
      );
    }

    // Language selection step
    if (currentStep === STEP_LANGUAGE) {
      return (
        <Step4TemplateSelect
          booking={booking}
          language={wizardData.language}
          detectedLanguage={wizardData.detectedLanguage}
          onChange={updateWizardData}
          onOpenCustomModal={handleOpenCustomModal}
        />
      );
    }

    // Preview step
    if (currentStep === STEP_PREVIEW) {
      return (
        <Step5Preview
          booking={booking}
          wizardData={wizardData}
          isLoading={isLoading}
        />
      );
    }

    // Send status step
    if (currentStep === STEP_SEND) {
      return (
        <Step6SendStatus
          booking={booking}
          wizardData={wizardData}
        />
      );
    }

    return null;
  };

  const isCurrentStepValid = stepValidation[currentStep];
  const canGoNext = isCurrentStepValid && currentStep < totalSteps && !isLoading;
  const canGoBack = currentStep > 1 && currentStep < STEP_SEND && !wizardData.isSending;
  const showSendButton = currentStep === STEP_PREVIEW && !wizardData.sendResult;
  const showCloseButton = currentStep === STEP_SEND && wizardData.sendResult;

  return (
    <div className="ticket-wizard-overlay" onClick={handleOverlayClick}>
      <div className="ticket-wizard" onClick={(e) => e.stopPropagation()}>
        <div className="wizard-header">
          <h2>Send Ticket to Customer</h2>
          <button className="wizard-close" onClick={handleClose}>
            &times;
          </button>
        </div>

        <WizardProgress steps={STEPS} currentStep={currentStep} />

        <div className="wizard-content">
          {error && (
            <div className="wizard-error">
              {error}
            </div>
          )}

          {renderStep()}
        </div>

        <WizardNavigation
          currentStep={currentStep}
          totalSteps={totalSteps}
          canGoBack={canGoBack}
          canGoNext={canGoNext}
          onBack={handleBack}
          onNext={handleNext}
          showSendButton={showSendButton}
          showCloseButton={showCloseButton}
          onSend={handleSend}
          onClose={handleClose}
          isLoading={isLoading || wizardData.isSending}
        />
      </div>

      {/* Custom Message Modal */}
      {showCustomModal && (
        <CustomMessageModal
          booking={booking}
          onSave={handleSaveCustomMessage}
          onClose={() => setShowCustomModal(false)}
        />
      )}
    </div>
  );
}
