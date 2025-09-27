# Cloudflare Page Rules Setup (3 Rules for Free Plan)

## How to Add These Rules

1. Log into Cloudflare Dashboard
2. Select your domain: `dalthaus.net`
3. Go to **Rules** > **Page Rules**
4. Click **Create Page Rule** for each rule below

---

## Rule 1: Bypass Cache for Admin Area (HIGHEST PRIORITY)

**URL Pattern:** `*dalthaus.net/admin*`

**Settings to Add:**
- **Cache Level:** Bypass
- **Disable Apps**
- **Disable Performance**

**Order:** Set as Rule #1 (highest priority)

---

## Rule 2: Cache Static Assets

**URL Pattern:** `*dalthaus.net/assets/*`

**Settings to Add:**
- **Cache Level:** Cache Everything
- **Edge Cache TTL:** 1 month
- **Browser Cache TTL:** 1 month

**Order:** Set as Rule #2

---

## Rule 3: Cache Content Pages

**URL Pattern:** `*dalthaus.net/article/*`

**Settings to Add:**
- **Cache Level:** Cache Everything
- **Edge Cache TTL:** 2 hours
- **Browser Cache TTL:** 30 minutes

**Order:** Set as Rule #3

---

## Alternative Rule 3 (if you want to cache photobooks too):

**URL Pattern:** `*dalthaus.net/(article|photobook|page)/*`

**Settings to Add:**
- **Cache Level:** Cache Everything
- **Edge Cache TTL:** 2 hours
- **Browser Cache TTL:** 30 minutes

---

## Important Notes

- **Rule Order Matters:** Rules are processed in order. Admin bypass MUST be first.
- **Free Plan Limit:** You only get 3 page rules on the free plan
- **Testing:** After adding rules, test in incognito mode to verify caching behavior
- **Purge Cache:** After setting up rules, purge cache once via Cloudflare dashboard

## Verify Rules Are Working

1. **Test Admin Area:** Go to `/admin` - should load fresh every time
2. **Test Static Assets:** Check browser network tab for `/assets/` files - should show "cf-cache-status: HIT"
3. **Test Articles:** Load an article twice - second load should be faster with "cf-cache-status: HIT"

## If You Need More Rules

Consider upgrading to Cloudflare Pro ($25/month) for:
- 20 page rules
- WAF (Web Application Firewall)
- Advanced DDoS protection
- Image optimization