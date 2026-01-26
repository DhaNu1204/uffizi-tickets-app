# GetYourGuide API Integration

**Status**: RESEARCHED - Not implemented (Jan 2026)

## Background

GetYourGuide (GYG) bookings come through Bokun but are missing participant names. Direct GYG integration was researched to supplement this data.

## Key Finding

**The GYG Supplier API does NOT have an endpoint to FETCH booking details.**

The API is push-based:
- GYG pushes bookings TO your system (via `/1/book/`)
- You push availability TO GYG (via `/1/notify-availability-update`)

## GYG API Endpoints (Available)

| Endpoint | Direction | Purpose |
|----------|-----------|---------|
| `/1/notify-availability-update` | You -> GYG | Push availability changes |
| `/1/deals` | You -> GYG | Manage promotional deals |
| `/1/redeem-ticket` | You -> GYG | Mark ticket as used |
| `/1/redeem-booking` | You -> GYG | Mark booking as used |
| `/1/suppliers` | You -> GYG | Register new suppliers |
| `/1/products/{id}/activate` | You -> GYG | Reactivate deactivated products |

## GYG API Endpoints (NOT Available)

These endpoints **do not exist** in the GYG Supplier API:
- `/1/bookings` - Does not exist
- `/1/orders` - Does not exist
- `/1/booking/{reference}` - Does not exist

## To Receive GYG Bookings Directly

Would require implementing these supplier-side endpoints:

| Endpoint | Purpose | Required |
|----------|---------|----------|
| `/1/get-availabilities/` | GYG queries your availability | Yes |
| `/1/reserve/` | Hold spots temporarily (60 min) | Yes |
| `/1/cancel-reservation/` | Release held spots | Yes |
| `/1/book/` | Confirm booking (includes traveler names!) | Yes |
| `/1/cancel-booking/` | Handle cancellations | Yes |

## GYG Credentials

**Integrator Portal**: https://integrator.getyourguide.com

Credentials are stored securely. See your password manager or contact the project owner.

**API Endpoints**:
- Base URL: `https://supplier-api.getyourguide.com`
- Sandbox URL: `https://supplier-api.getyourguide.com/sandbox`

## GYG Testing Status

Tests started but not completed (Dec 26, 2024):
- Time point for Individuals - Started
- Time period for Individuals - Started
- Time point for Groups - Not started
- Time period for Groups - Not started

## Documentation Location

All GYG API documentation saved in: `GYGAPI/` folder (gitignored)
- `GYGAPI_Documents01.txt` - Overview documentation
- `GYG_endpoints.txt` - GYG-side endpoints
- `Suppler_ApiEndpoins.txt` - Supplier-side endpoints
- `supplier-api-gyg-endpoints (1).yaml` - OpenAPI spec (GYG endpoints)
- `supplier-api-supplier-endpoints (1).yaml` - OpenAPI spec (Supplier endpoints)
- Various screenshots from Integrator Portal

## Future Implementation Path

1. Implement all 5 required endpoints in Laravel
2. Pass GYG self-testing in Integrator Portal
3. Set up production configuration
4. Have GYG products mapped to your system
5. Receive bookings with full traveler details

## Alternative Solutions

1. **Contact GYG Support**: Email `supplier-api@getyourguide.com` to ask about webhook-only integration
2. **Bokun Webhooks**: Check if Bokun can provide more detailed GYG data via webhooks
3. **Manual Process**: Continue using Bokun; manually check GYG Supplier Portal for participant names when needed

## Why Not Implemented

- Requires significant development (5 endpoints + testing)
- Need to manage availability sync between systems
- Current Bokun integration works for most needs
- GYG participant names are "nice to have" not critical
