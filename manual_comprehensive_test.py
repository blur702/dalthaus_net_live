#!/usr/bin/env python3
"""Manual comprehensive test of images and pagebreak functionality"""

import requests
import re
from urllib.parse import urljoin
import time

def test_image_and_pagebreak_functionality():
    """Test images and pagebreak functionality comprehensively"""
    
    base_url = "https://dalthaus.net"
    results = {
        'images_found': [],
        'images_working': [],
        'images_broken': [],
        'pagination_found': [],
        'articles_tested': [],
        'photobooks_tested': []
    }
    
    print("🔍 COMPREHENSIVE IMAGE AND PAGEBREAK TEST")
    print("=" * 50)
    
    # Test 1: Homepage images
    print("\n1. Testing Homepage Images...")
    try:
        response = requests.get(f"{base_url}/?test={int(time.time())}", timeout=10)
        if response.status_code == 200:
            print(f"✅ Homepage loads: {response.status_code}")
            
            # Look for image src attributes
            img_pattern = r'src="([^"]*uploads[^"]*)"'
            images = re.findall(img_pattern, response.text)
            
            print(f"Found {len(images)} upload images on homepage")
            for img_src in images[:5]:  # Test first 5 images
                img_url = urljoin(base_url, img_src)
                try:
                    img_response = requests.head(img_url, timeout=5)
                    if img_response.status_code == 200:
                        results['images_working'].append(img_url)
                        print(f"  ✅ {img_src} - {img_response.status_code}")
                    else:
                        results['images_broken'].append(img_url)
                        print(f"  ❌ {img_src} - {img_response.status_code}")
                except Exception as e:
                    results['images_broken'].append(img_url)
                    print(f"  ❌ {img_src} - Error: {str(e)}")
                
                results['images_found'].append(img_url)
        else:
            print(f"❌ Homepage failed: {response.status_code}")
    except Exception as e:
        print(f"❌ Homepage error: {str(e)}")
    
    # Test 2: Articles page and pagination
    print("\n2. Testing Articles and Pagination...")
    try:
        articles_response = requests.get(f"{base_url}/articles?test={int(time.time())}", timeout=10)
        if articles_response.status_code == 200:
            print(f"✅ Articles page loads: {articles_response.status_code}")
            
            # Find article links
            article_pattern = r'href="(/article/[^"]*)"'
            article_links = re.findall(article_pattern, articles_response.text)
            
            print(f"Found {len(article_links)} article links")
            
            # Test first 3 articles for pagination
            for i, article_path in enumerate(article_links[:3]):
                article_url = urljoin(base_url, article_path)
                try:
                    article_response = requests.get(f"{article_url}?test={int(time.time())}", timeout=10)
                    if article_response.status_code == 200:
                        results['articles_tested'].append(article_url)
                        
                        # Check for pagination
                        has_pagination = (
                            'pagination-wrapper' in article_response.text or
                            'class="pagination"' in article_response.text or
                            '?p=' in article_response.text
                        )
                        
                        # Check for pagebreak markers
                        has_pagebreak_marker = (
                            '<!-- pagebreak -->' in article_response.text or
                            'mce-pagebreak' in article_response.text
                        )
                        
                        # Check for page parameter links
                        page_links = re.findall(r'href="[^"]*\?p=(\d+)', article_response.text)
                        
                        print(f"  Article {i+1}: {article_path}")
                        print(f"    ✅ Loads: {article_response.status_code}")
                        print(f"    Pagination wrapper: {'✅' if has_pagination else '❌'}")
                        print(f"    Pagebreak markers: {'✅' if has_pagebreak_marker else '❌'}")
                        print(f"    Page links found: {len(page_links)} {page_links}")
                        
                        if page_links:
                            results['pagination_found'].append({
                                'url': article_url,
                                'pages': page_links
                            })
                            
                            # Test page 2 if available
                            if '2' in page_links:
                                page2_url = f"{article_url}?p=2"
                                try:
                                    page2_response = requests.get(page2_url, timeout=10)
                                    print(f"    Page 2 test: {'✅' if page2_response.status_code == 200 else '❌'} ({page2_response.status_code})")
                                except Exception as e:
                                    print(f"    Page 2 test: ❌ Error: {str(e)}")
                        
                        # Check for images in article
                        article_images = re.findall(img_pattern, article_response.text)
                        print(f"    Images found: {len(article_images)}")
                        
                    else:
                        print(f"  Article {i+1}: ❌ Failed to load ({article_response.status_code})")
                except Exception as e:
                    print(f"  Article {i+1}: ❌ Error: {str(e)}")
        else:
            print(f"❌ Articles page failed: {articles_response.status_code}")
    except Exception as e:
        print(f"❌ Articles page error: {str(e)}")
    
    # Test 3: Photobooks and pagination
    print("\n3. Testing Photobooks...")
    try:
        photobooks_response = requests.get(f"{base_url}/photobooks?test={int(time.time())}", timeout=10)
        if photobooks_response.status_code == 200:
            print(f"✅ Photobooks page loads: {photobooks_response.status_code}")
            
            # Find photobook links
            photobook_pattern = r'href="(/photobook/[^"]*)"'
            photobook_links = re.findall(photobook_pattern, photobooks_response.text)
            
            print(f"Found {len(photobook_links)} photobook links")
            
            # Test first 2 photobooks
            for i, photobook_path in enumerate(photobook_links[:2]):
                photobook_url = urljoin(base_url, photobook_path)
                try:
                    photobook_response = requests.get(f"{photobook_url}?test={int(time.time())}", timeout=10)
                    if photobook_response.status_code == 200:
                        results['photobooks_tested'].append(photobook_url)
                        
                        # Check for pagination
                        has_pagination = 'pagination-wrapper' in photobook_response.text
                        page_links = re.findall(r'href="[^"]*\?p=(\d+)', photobook_response.text)
                        
                        print(f"  Photobook {i+1}: {photobook_path}")
                        print(f"    ✅ Loads: {photobook_response.status_code}")
                        print(f"    Pagination: {'✅' if has_pagination else '❌'}")
                        print(f"    Page links: {len(page_links)} {page_links}")
                        
                        # Check for images
                        photobook_images = re.findall(img_pattern, photobook_response.text)
                        print(f"    Images found: {len(photobook_images)}")
                        
                    else:
                        print(f"  Photobook {i+1}: ❌ Failed to load ({photobook_response.status_code})")
                except Exception as e:
                    print(f"  Photobook {i+1}: ❌ Error: {str(e)}")
        else:
            print(f"❌ Photobooks page failed: {photobooks_response.status_code}")
    except Exception as e:
        print(f"❌ Photobooks page error: {str(e)}")
    
    # Test 4: Direct image access
    print("\n4. Testing Direct Image Access...")
    test_images = [
        "/uploads/content/featured/2025/09/8fc5d5a9d1ddb15c0d44ffad84df6d50.png",
        "/uploads/content/featured/2025/09/55a281e98034dbf05a6692241b65c767.jpg",
        "/uploads/content/img_68bddc7eaca327.15677786.png"
    ]
    
    for img_path in test_images:
        img_url = urljoin(base_url, img_path)
        try:
            img_response = requests.head(img_url, timeout=5)
            print(f"  {img_path}: {'✅' if img_response.status_code == 200 else '❌'} ({img_response.status_code})")
        except Exception as e:
            print(f"  {img_path}: ❌ Error: {str(e)}")
    
    # Summary
    print("\n" + "=" * 50)
    print("📊 TEST SUMMARY")
    print("=" * 50)
    print(f"Images found: {len(results['images_found'])}")
    print(f"Images working: {len(results['images_working'])}")
    print(f"Images broken: {len(results['images_broken'])}")
    print(f"Articles tested: {len(results['articles_tested'])}")
    print(f"Photobooks tested: {len(results['photobooks_tested'])}")
    print(f"Pagination found: {len(results['pagination_found'])}")
    
    if results['pagination_found']:
        print("\nPagination Details:")
        for item in results['pagination_found']:
            print(f"  - {item['url']}: {len(item['pages'])} pages")
    
    return results

if __name__ == "__main__":
    test_image_and_pagebreak_functionality()