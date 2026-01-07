# Uffizi Ticket App - Optimization Implementation Plan

## Overview
This plan addresses all issues identified in the comprehensive analysis report.
Each phase is designed to be non-breaking and backwards compatible.

---

## Phase 1: Backend Improvements

### 1.1 Create BookingStatus Enum
- File: `backend/app/Enums/BookingStatus.php`
- Replace magic strings with type-safe enum
- Update all references in controllers/models

### 1.2 Create Shared Constants
- File: `backend/app/Constants/ProductConstants.php`
- Centralize GUIDED_TOUR_IDS and PRODUCT_IDS
- Single source of truth

### 1.3 Add API Health Check
- Add `/api/health` endpoint
- Check database connectivity
- Return system status

### 1.4 Split BookingController
- Extract sync logic to `SyncService`
- Extract webhook logic to `WebhookService`
- Keep controller thin

### 1.5 Add API Versioning Preparation
- Create route group structure for v1
- Maintain backward compatibility

---

## Phase 2: Frontend Improvements

### 2.1 Add Error Boundaries
- Create `ErrorBoundary` component
- Wrap main routes
- Graceful error handling

### 2.2 Split Large Components
- Extract `BookingModal` from `BookingTable`
- Extract `DateNavigator` from `Dashboard`
- Extract `CalendarPicker` from `Dashboard`
- Extract `StatsCards` from `Dashboard`

### 2.3 Shared Constants
- Create `constants/bookingStatus.js`
- Create `constants/guidedTours.js`
- Import from single source

### 2.4 API Service Improvements
- Add request/response interceptors for errors
- Centralized error handling

---

## Phase 3: Test Suite

### 3.1 Backend Unit Tests
- `BokunServiceTest` - Participant extraction methods
- `BookingStatusEnumTest` - Enum functionality

### 3.2 Backend Feature Tests
- `AuthenticationTest` - Login/logout flows
- `BookingControllerTest` - CRUD operations
- `WebhookTest` - HMAC verification
- `HealthCheckTest` - API health endpoint

### 3.3 Test Configuration
- Configure test database
- Add test helpers

---

## Phase 4: DevOps

### 4.1 GitHub Actions CI/CD
- Create `.github/workflows/ci.yml`
- Run tests on PR
- Lint PHP and JS

### 4.2 Structured Logging
- Configure log channels
- Add context to logs
- Separate sync logs

---

## Phase 5: Verification

### 5.1 Run All Tests
- Backend PHPUnit tests
- Frontend lint checks

### 5.2 Manual Verification
- Test sync functionality
- Test booking CRUD
- Test webhook handling

---

## Execution Order

```
Phase 1.1 → 1.2 → 1.3 → 1.4 → 1.5 (Sequential - backend foundation)
     ↓
Phase 2.1 → 2.2 → 2.3 → 2.4 (Can run parallel to Phase 1 completion)
     ↓
Phase 3 (After Phases 1 & 2 complete)
     ↓
Phase 4 (After Phase 3)
     ↓
Phase 5 (Final verification)
```

---

## Risk Mitigation

1. **No Breaking Changes**: All changes are additive or use deprecation
2. **Backward Compatibility**: Old patterns still work during transition
3. **Incremental Testing**: Each phase is tested before moving on
4. **Rollback Ready**: Git commits per phase for easy rollback

---

## Success Criteria

- [ ] All new tests pass
- [ ] Existing functionality works
- [ ] No console errors in frontend
- [ ] API endpoints respond correctly
- [ ] Sync operation completes successfully
