#!/usr/bin/env python3
"""
Set Final Article Order - Exact match to user specification
"""

import sys
import os

sys.path.insert(0, os.path.join(os.path.dirname(__file__), 'agents'))

from ssh_agent import SSHAgent

# Final article order by content_id (verified from database)
FINAL_ORDER = {
    138: 1,   # The Future of Photography: Between Image and Language
    14: 2,    # Telling the Subject's Story As Completely as Possible
    15: 3,    # The Key Is Writing Stories People Want To Read
    18: 4,    # The Organizing Questions - What My Story Is About
    19: 5,    # Getting To The Practical Details Of Creating Your Story
    22: 6,    # The Case for "Pure Photography"
    17: 7,    # The Joy Of Getting It Right In the Camera
    23: 8,    # You Really Don't Need To Master Manual Mode
    20: 9,    # How I Learned To Stop Worrying And Love JPEG
    21: 10,   # Making Adjustment Easier On You And Your Sanity (note: title may have slight variation)
    24: 11,   # The Smartphone Camera is Perfect For Storytelling
    25: 12,   # Can I Shoot (fill in the blank) With My Phone?
    26: 13,   # Photography's New Paradigm
    27: 14,   # How Smartphone Cameras Are Redefining Photography
    28: 15,   # Backgrounder – Smartphone Camera...
    29: 16,   # How I Learned To Stop Worrying – Understanding AI Imaging
    30: 17,   # How I Learned To Stop Worrying – Protecting Your Work From AI
}

def main():
    print("[SET FINAL ORDER] Updating article order...")

    agent = SSHAgent()

    try:
        if not agent.connect():
            print("[ERROR] Failed to connect")
            return False

        print(f"[INFO] Updating {len(FINAL_ORDER)} articles...")

        success = 0
        for content_id, position in FINAL_ORDER.items():
            cmd = f"mysql -u dalthaus_maincms -p'f4!,Wpds=w6*=~+1' -D dalthaus_maincms -e \"UPDATE content SET sort_order = {position} WHERE content_id = {content_id}\" 2>&1"
            result = agent.execute_command(cmd)

            if "ERROR" not in result:
                success += 1
                print(f"  [OK] ID {content_id} -> position {position}")
            else:
                print(f"  [FAIL] ID {content_id} failed: {result}")

        print(f"\n[SUCCESS] Updated {success}/{len(FINAL_ORDER)} articles")

        # Verify
        verify = "mysql -u dalthaus_maincms -p'f4!,Wpds=w6*=~+1' -D dalthaus_maincms -e \"SELECT sort_order, title FROM content WHERE content_type = 'article' ORDER BY sort_order ASC LIMIT 17\" 2>&1"
        output = agent.execute_command(verify)

        print("\n[VERIFY] New order:")
        print(output)

        return True

    except Exception as e:
        print(f"[ERROR] {e}")
        return False
    finally:
        agent.disconnect()

if __name__ == "__main__":
    main()
