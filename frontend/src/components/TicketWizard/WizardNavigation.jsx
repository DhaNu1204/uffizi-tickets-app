export default function WizardNavigation({
  currentStep,
  totalSteps,
  canGoBack,
  canGoNext,
  onBack,
  onNext,
  showSendButton,
  showCloseButton,
  onSend,
  onClose,
  isLoading,
}) {
  return (
    <div className="wizard-navigation">
      <div className="nav-left">
        {canGoBack && (
          <button
            type="button"
            className="btn btn-secondary"
            onClick={onBack}
            disabled={isLoading}
          >
            Back
          </button>
        )}
      </div>

      <div className="nav-center">
        <span className="step-counter">
          Step {currentStep} of {totalSteps}
        </span>
      </div>

      <div className="nav-right">
        {showCloseButton ? (
          <button
            type="button"
            className="btn btn-primary"
            onClick={onClose}
          >
            Done
          </button>
        ) : showSendButton ? (
          <button
            type="button"
            className="btn btn-send"
            onClick={onSend}
            disabled={isLoading}
          >
            {isLoading ? (
              <>
                <span className="spinner" /> Sending...
              </>
            ) : (
              <>
                <svg className="send-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <line x1="22" y1="2" x2="11" y2="13" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                  <polygon points="22 2 15 22 11 13 2 9 22 2" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                </svg>
                Send Ticket
              </>
            )}
          </button>
        ) : currentStep < totalSteps ? (
          <button
            type="button"
            className="btn btn-primary"
            onClick={onNext}
            disabled={!canGoNext || isLoading}
          >
            {isLoading ? 'Saving...' : 'Next'}
          </button>
        ) : null}
      </div>
    </div>
  );
}
