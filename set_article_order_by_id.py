#!/usr/bin/env python3
"""
Set Article Order by ID

Updates article sort_order by content_id (safer than matching titles).
"""

import sys
import os

# Add agents directory to path
sys.path.insert(0, os.path.join(os.path.dirname(__file__), 'agents'))

from ssh_agent import SSHAgent

# Map content_id to desired position based on current database state
ARTICLE_ORDER_BY_ID = {
    138: 1,   # The Future of Photography: Between Image and Language
    14: 2,    # Telling the Subject's Story As Completely as Possible
    15: 3,    # The Key Is Writing Stories People Want To Read
    18: 4,    # The Organizing Questions - What My Story Is About
    19: 5,    # Getting To The Practical Details Of Creating Your Story
    22: 6,    # The Case for "Pure Photography"
    17: 7,    # The Joy Of Getting It Right In the Camera
    23: 8,    # You Really Don't Need To Master Manual Mode
    20: 9,    # How I Learned To Stop Worrying And Love JPEG
    21: 10,   # Making Adjustment Easier On You And Your Sanity
    24: 11,   # The Smartphone Camera is Perfect For Storytelling
    25: 12,   # Can I Shoot (fill in the blank) With My Phone?
    26: 13,   # Photography's New Paradigm
    27: 14,   # How Smartphone Cameras Are Redefining Photography
    28: 15,   # Backgrounder – Smartphone Camera...
    29: 16,   # How I Learned To Stop Worrying – Understanding AI Imaging
    30: 17,   # How I Learned To Stop Worrying – Protecting Your Work From AI
}

def main():
    print("[SET ARTICLE ORDER] Starting...")
    print("=" * 80)

    agent = SSHAgent()

    try:
        # Connect to server
        if not agent.connect():
            print("[ERROR] Failed to connect to server")
            return False

        # Build UPDATE statements using content_id
        print("\n[BUILD] Building UPDATE statements by content_id...")

        updates = []
        for content_id, position in ARTICLE_ORDER_BY_ID.items():
            updates.append(f"UPDATE content SET sort_order = {position} WHERE content_id = {content_id};")

        print(f"[INFO] Will update {len(updates)} articles")

        # Show what will change
        print("\n[PREVIEW] Changes to be made:")
        for content_id, position in sorted(ARTICLE_ORDER_BY_ID.items(), key=lambda x: x[1]):
            print(f"  ID {content_id:3d} -> position {position:2d}")

        # Ask for confirmation
        print("\n[WARNING] This will update article sort_order in the production database.")
        print("Do you want to continue? (yes/no): ", end="")
        confirmation = input().strip().lower()

        if confirmation != 'yes':
            print("[ABORTED] No changes made.")
            return False

        # Execute each update individually (safer)
        print("\n[EXECUTE] Executing updates...")

        success_count = 0
        for content_id, position in ARTICLE_ORDER_BY_ID.items():
            sql = f"UPDATE content SET sort_order = {position} WHERE content_id = {content_id}"

            mysql_cmd = f"mysql -u dalthaus_maincms -p'f4!,Wpds=w6*=~+1' -D dalthaus_maincms -e \"{sql}\" 2>&1"

            result = agent.execute_command(mysql_cmd)

            if "ERROR" in result:
                print(f"  [ERROR] Failed to update ID {content_id}: {result}")
            else:
                print(f"  [OK] Updated ID {content_id} to position {position}")
                success_count += 1

        print(f"\n[INFO] Successfully updated {success_count}/{len(ARTICLE_ORDER_BY_ID)} articles")

        # Verify the new order
        print("\n[VERIFY] Checking new article order...")

        verify_sql = "SELECT sort_order, content_id, title FROM content WHERE content_type = 'article' ORDER BY sort_order ASC LIMIT 20"

        verify_cmd = f"mysql -u dalthaus_maincms -p'f4!,Wpds=w6*=~+1' -D dalthaus_maincms -e \"{verify_sql}\" 2>&1"

        verify_output = agent.execute_command(verify_sql)
        print(verify_output)

        print("\n[SUCCESS] Article order updated!")
        return True

    except Exception as e:
        print(f"[ERROR] {str(e)}")
        import traceback
        traceback.print_exc()
        return False
    finally:
        agent.disconnect()

if __name__ == "__main__":
    success = main()
    sys.exit(0 if success else 1)
