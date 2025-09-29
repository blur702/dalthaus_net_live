# Cloudflare Configuration Guide

## Problem Resolution for Server Errors (408, 502, 504, 520-524)

### Quick Fixes Applied
1. ✅ Added Cloudflare IP restoration to .htaccess
2. ✅ Reduced PHP execution timeout from 300s to 90s (Cloudflare has 100s limit)
3. ✅ Reduced PHP input timeout from 300s to 60s

### Cloudflare Dashboard Settings to Configure

#### 1. SSL/TLS Settings
- **SSL/TLS encryption mode**: Set to "Full (strict)" if you have valid SSL, otherwise "Full"
- **Always Use HTTPS**: Enable
- **Automatic HTTPS Rewrites**: Enable
- **Minimum TLS Version**: 1.2

#### 2. Speed > Optimization
- **Auto Minify**: Enable for JavaScript, CSS, and HTML
- **Brotli**: Enable
- **Rocket Loader**: Disable (can cause issues with some JavaScript)
- **Mirage**: Enable (optimizes image loading)
- **Polish**: Enable with "Lossy" compression

#### 3. Caching > Configuration
- **Caching Level**: Standard
- **Browser Cache TTL**: Respect Existing Headers
- **Always Online**: Enable

#### 4. Page Rules (Free plan allows 3 rules)

**Rule 1: Cache Everything for Static Assets**
- URL: `dalthaus.net/assets/*`
- Settings:
  - Cache Level: Cache Everything
  - Edge Cache TTL: 1 month
  - Browser Cache TTL: 1 month

**Rule 2: Bypass Cache for Admin**
- URL: `dalthaus.net/admin/*`
- Settings:
  - Cache Level: Bypass
  - Disable Performance Features

**Rule 3: Cache Articles and Pages**
- URL: `dalthaus.net/article/*` OR `dalthaus.net/page/*`
- Settings:
  - Cache Level: Cache Everything
  - Edge Cache TTL: 1 hour
  - Browser Cache TTL: 30 minutes

#### 5. Network Settings
- **WebSockets**: Enable
- **gRPC**: Enable if needed
- **HTTP/2**: Enable
- **HTTP/3 (with QUIC)**: Enable

#### 6. Firewall > Settings
- **Security Level**: Medium
- **Challenge Passage**: 30 minutes
- **Browser Integrity Check**: Enable

#### 7. Speed > Optimization > Early Hints
- **Early Hints**: Enable (improves performance)

### Advanced Troubleshooting

#### If errors persist after these changes:

1. **Check Origin Server Health**
   ```bash
   python agents/deploy_agent.py health
   ```

2. **Enable Development Mode** (temporarily)
   - Go to Cloudflare Dashboard > Caching > Configuration
   - Enable "Development Mode" to bypass cache for 3 hours

3. **Check Error Analytics**
   - Go to Analytics > Traffic
   - Look for patterns in error timing

4. **Rate Limiting** (if under attack)
   - Create rate limiting rules for suspicious patterns
   - Limit requests per IP to reasonable levels

5. **Consider Cloudflare APO** (Automatic Platform Optimization)
   - $5/month add-on that significantly improves WordPress/PHP site performance
   - Reduces origin load by serving from edge

### Monitor These Metrics

1. **Origin Response Time**: Should be < 1 second
2. **Cache Hit Ratio**: Aim for > 80%
3. **Bandwidth Saved**: Should increase over time
4. **Threats Blocked**: Monitor for DDoS attempts

### Emergency Actions

If site goes down:
1. Enable "Under Attack Mode" in Cloudflare
2. Check A2 Hosting server status
3. Review recent deployments
4. Contact A2 Hosting support if server issue

### Contact Support

**Cloudflare Support**: https://support.cloudflare.com
**A2 Hosting Support**: Available via cPanel

### Additional Optimizations Made

1. **Database Connection Pooling**: Consider implementing if not already done
2. **PHP OpCache**: Verify it's enabled on A2 Hosting
3. **CDN for Large Media**: Upload large images to Cloudflare Images or similar

### Testing After Changes

1. Test site performance: https://www.webpagetest.org
2. Check SSL: https://www.ssllabs.com/ssltest/
3. Monitor uptime: https://uptimerobot.com (free monitoring)

---

**Last Updated**: September 27, 2025
**Issue Resolved**: Origin server timeout errors (408, 502, 504, 520-524)