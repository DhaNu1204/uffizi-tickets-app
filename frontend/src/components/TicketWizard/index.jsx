import { useState, useEffect, useCallback } from 'react';
import WizardProgress from './WizardProgress';
import WizardNavigation from './WizardNavigation';
import Step1BookingDetails from './steps/Step1BookingDetails';
import Step2TicketReference, { validateReferenceCode } from './steps/Step2TicketReference';
import Step3FileAttach from './steps/Step3FileAttach';
import Step4TemplateSelect from './steps/Step4TemplateSelect';
import Step5Preview from './steps/Step5Preview';
import Step6SendStatus from './steps/Step6SendStatus';
import CustomMessageModal from './CustomMessageModal';
import { bookingsAPI, messagesAPI, attachmentsAPI } from '../../services/api';
import './TicketWizard.css';

const STEPS = [
  { id: 1, title: 'Booking Details', shortTitle: 'Details' },
  { id: 2, title: 'Ticket Reference', shortTitle: 'Reference' },
  { id: 3, title: 'Attach PDF', shortTitle: 'Attach' },
  { id: 4, title: 'Select Language', shortTitle: 'Language' },
  { id: 5, title: 'Preview & Confirm', shortTitle: 'Preview' },
  { id: 6, title: 'Send Status', shortTitle: 'Send' },
];

export default function TicketWizard({ booking, onClose, onComplete }) {
  const [currentStep, setCurrentStep] = useState(1);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(null);
  const [showCustomModal, setShowCustomModal] = useState(false);

  // Wizard state
  const [wizardData, setWizardData] = useState({
    // Step 2: Ticket reference
    referenceNumber: booking.reference_number || '',
    guideName: booking.guide_name || '',
    // Audio guide credentials
    audioGuideUsername: booking.audio_guide_username || '',
    audioGuidePassword: booking.audio_guide_password || '',
    audioGuideUrl: booking.audio_guide_url || '',
    // Step 3: Attachments
    attachments: [],
    // Step 4: Language & Template
    language: 'en',
    customMessage: null, // For custom message { subject, content }
    // Step 5: Channel detection
    channelInfo: null,
    preview: null,
    // Step 6: Send status
    sendResult: null,
    isSending: false,
  });

  // Validation state per step
  const [stepValidation, setStepValidation] = useState({
    1: true, // Always valid (read-only)
    2: false,
    3: false,
    4: true, // Default language is selected
    5: true, // Preview is read-only
    6: true, // Send step
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

  // Validate step 2: Reference number format (and audio guide if applicable)
  useEffect(() => {
    const referenceValidation = validateReferenceCode(wizardData.referenceNumber);
    const hasValidReference = referenceValidation.valid;

    let hasAudioGuide = true;
    if (booking.has_audio_guide) {
      hasAudioGuide =
        wizardData.audioGuideUsername.trim().length > 0 &&
        wizardData.audioGuidePassword.trim().length > 0;
    }

    setStepValidation((prev) => ({ ...prev, 2: hasValidReference && hasAudioGuide }));
  }, [wizardData.referenceNumber, wizardData.audioGuideUsername, wizardData.audioGuidePassword, booking.has_audio_guide]);

  // Validate step 3: At least one attachment
  useEffect(() => {
    setStepValidation((prev) => ({ ...prev, 3: wizardData.attachments.length > 0 }));
  }, [wizardData.attachments]);

  // Detect channel when reaching step 5
  useEffect(() => {
    if (currentStep === 5 && !wizardData.channelInfo) {
      detectChannel();
    }
  }, [currentStep]);

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
          audio_guide_username: wizardData.audioGuideUsername || null,
          audio_guide_password: wizardData.audioGuidePassword || null,
          audio_guide_url: wizardData.audioGuideUrl || null,
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

    // Refresh preview when changing language
    if (currentStep === 4) {
      setWizardData((prev) => ({ ...prev, preview: null, channelInfo: null }));
    }

    if (currentStep < STEPS.length) {
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

      // Move to step 6 to show result
      if (currentStep !== 6) {
        setCurrentStep(6);
      }
    } catch (err) {
      setError(err.response?.data?.error || 'Failed to send ticket');
      updateWizardData({ isSending: false });
    }
  };

  const handleClose = () => {
    // If ticket was sent successfully, mark as complete and call onComplete
    if (wizardData.sendResult?.success) {
      bookingsAPI.updateWizardProgress(booking.id, 6, 'complete').catch(console.error);
      onComplete?.(wizardData.sendResult);
    } else {
      // Mark as abandoned if closed without completing
      bookingsAPI.updateWizardProgress(booking.id, currentStep, 'abandon').catch(console.error);
    }
    onClose();
  };

  const renderStep = () => {
    switch (currentStep) {
      case 1:
        return <Step1BookingDetails booking={booking} />;
      case 2:
        return (
          <Step2TicketReference
            booking={booking}
            data={wizardData}
            onChange={updateWizardData}
          />
        );
      case 3:
        return (
          <Step3FileAttach
            booking={booking}
            attachments={wizardData.attachments}
            onChange={updateWizardData}
          />
        );
      case 4:
        return (
          <Step4TemplateSelect
            booking={booking}
            language={wizardData.language}
            detectedLanguage={wizardData.detectedLanguage}
            onChange={updateWizardData}
            onOpenCustomModal={handleOpenCustomModal}
          />
        );
      case 5:
        return (
          <Step5Preview
            booking={booking}
            wizardData={wizardData}
            isLoading={isLoading}
          />
        );
      case 6:
        return (
          <Step6SendStatus
            booking={booking}
            wizardData={wizardData}
          />
        );
      default:
        return null;
    }
  };

  const isCurrentStepValid = stepValidation[currentStep];
  const canGoNext = isCurrentStepValid && currentStep < STEPS.length && !isLoading;
  const canGoBack = currentStep > 1 && currentStep < 6 && !wizardData.isSending;
  const showSendButton = currentStep === 5 && !wizardData.sendResult;
  const showCloseButton = currentStep === 6 && wizardData.sendResult;

  return (
    <div className="ticket-wizard-overlay" onClick={handleClose}>
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
          totalSteps={STEPS.length}
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
