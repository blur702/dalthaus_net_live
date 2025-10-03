#!/usr/bin/env python3
"""
Purge Cloudflare cache for photobook URLs
"""

import requests
import json

# You'll need to get these from your Cloudflare account
ZONE_ID = "YOUR_ZONE_ID"  # Find in Cloudflare dashboard
API_TOKEN = "YOUR_API_TOKEN"  # Create in My Profile > API Tokens

def purge_urls(urls):
    """Purge specific URLs from Cloudflare cache"""
    
    headers = {
        "Authorization": f"Bearer {API_TOKEN}",
        "Content-Type": "application/json",
    }
    
    data = {
        "files": urls
    }
    
    response = requests.post(
        f"https://api.cloudflare.com/client/v4/zones/{ZONE_ID}/purge_cache",
        headers=headers,
        data=json.dumps(data)
    )
    
    if response.status_code == 200:
        result = response.json()
        if result.get("success"):
            print("✅ Cache purged successfully!")
            return True
        else:
            print(f"❌ Error: {result.get('errors')}")
            return False
    else:
        print(f"❌ HTTP Error: {response.status_code}")
        return False

def purge_by_prefix():
    """Purge all photobook URLs using prefix (requires Enterprise plan)"""
    
    headers = {
        "Authorization": f"Bearer {API_TOKEN}",
        "Content-Type": "application/json",
    }
    
    data = {
        "prefixes": ["https://dalthaus.net/photobook/"]
    }
    
    response = requests.post(
        f"https://api.cloudflare.com/client/v4/zones/{ZONE_ID}/purge_cache",
        headers=headers,
        data=json.dumps(data)
    )
    
    return response.status_code == 200

if __name__ == "__main__":
    # List specific photobook URLs to purge
    photobook_urls = [
        "https://dalthaus.net/photobook/route-66-still-america-s-mother-road",
        # Add more photobook URLs as needed
    ]
    
    print("Purging photobook URLs from Cloudflare cache...")
    purge_urls(photobook_urls)
    
    print("\nAlternatively, you can:")
    print("1. Go to Cloudflare Dashboard > Caching > Configuration")
    print("2. Click 'Custom Purge'")
    print("3. Enter URL pattern: https://dalthaus.net/photobook/*")
    print("4. Or use 'Purge Everything' to clear all cache")