#!/usr/bin/env python3
"""
Test script to verify email dispatch functionality
Run this to test if the WordPress webhook is working
"""

import requests
import json

def test_email_dispatch():
    """Test the email dispatch via WordPress webhook"""
    
    # WordPress site URL (replace with your actual site)
    wordpress_url = "https://your-wordpress-site.com"  # CHANGE THIS
    
    # Test data
    test_data = {
        "name": "Test User",
        "email": "test@example.com",  # CHANGE THIS to your email
        "ats_score": 85,
        "job_fit_score": 78,
        "pdf_url": "https://example.com/test-report.pdf"
    }
    
    # Headers
    headers = {
        'Content-Type': 'application/json',
        'X-JobReady-Token': 'your-webhook-token'  # CHANGE THIS
    }
    
    # Test endpoint
    endpoint = f"{wordpress_url}/wp-json/jobready/v1/send-report"
    
    print(f"Testing email dispatch...")
    print(f"Endpoint: {endpoint}")
    print(f"Data: {json.dumps(test_data, indent=2)}")
    
    try:
        response = requests.post(
            endpoint,
            json=test_data,
            headers=headers,
            timeout=10
        )
        
        print(f"\nResponse Status: {response.status_code}")
        print(f"Response Headers: {dict(response.headers)}")
        print(f"Response Body: {response.text}")
        
        if response.status_code == 200:
            print("✅ Email dispatch test SUCCESSFUL!")
        else:
            print("❌ Email dispatch test FAILED!")
            
    except Exception as e:
        print(f"❌ Test failed with error: {e}")

if __name__ == "__main__":
    test_email_dispatch()
