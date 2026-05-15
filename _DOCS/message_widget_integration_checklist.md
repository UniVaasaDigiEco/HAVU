# Message Widget Integration Checklist

Use this checklist whenever the route-creator messaging widget is enabled on a new page.

- [ ] Page loads Bootstrap JS (for modal behavior).
- [ ] Page loads reCAPTCHA v3 script with site key.
- [ ] Page includes shared widget template: `includes/_message-widget.php`.
- [ ] Route cards/buttons call `window.openMessageModal(routeId, routeTitle)`.
- [ ] `routeId` passed to modal is internal numeric route `id` (not `public_id`).
- [ ] Endpoint resolves to `actions/send-message.php` and submits as POST.
- [ ] User-facing button text uses locale key `common.message_creator`.
- [ ] Sending from page succeeds and row appears in `messages` table.
- [ ] If route-specific message is expected, verify `route_id` is saved with the message row.
- [ ] Verify sender mapping: logged-in sender sets `sender_user_id`, guest stays NULL.

## Quick Smoke Test

1. Open page with route cards.
2. Click "Viesti luojalle".
3. Fill title + content and submit.
4. Confirm success message in modal.
5. Confirm DB row created for recipient and route.
