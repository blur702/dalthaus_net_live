#!/usr/bin/env python3
"""
Update Article Order via SSH

Direct SQL update of article sort_order on production database.
"""

import sys
import os

# Add agents directory to path
sys.path.insert(0, os.path.join(os.path.dirname(__file__), 'agents'))

from ssh_agent import SSHAgent

# Desired article order (title => position)
ARTICLE_ORDER = {
    "The Future of Photography: Between Image and Language": 1,
    "Telling the Subject's Story As Completely as Possible": 2,
    "The Key Is Writing Stories People Want To Read": 3,
    "The Organizing Questions - What My Story Is About": 4,
    "Getting To The Practical Details Of Creating Your Story": 5,
    "The Case for \"Pure Photography\"": 6,
    "The Joy Of Getting It Right In the Camera": 7,
    "You Really Don't Need To Master Manual Mode": 8,
    "How I Learned To Stop Worrying And Love JPEG": 9,
    "Making Adjustment Easier On You And Your Sanity": 10,
    "The Smartphone Camera is Perfect For Storytelling": 11,
    "Can I Shoot (fill in the blank) With My Phone?": 12,
    "Photography's New Paradigm": 13,
    "How Smartphone Cameras Are Redefining Photography": 14,
    "Backgrounder – Smartphone Camera Image Signal Processing / Neural Engine Processing – Apple iPhone 15/16 Pro /Pro Max, Google 8 / 9 Pro / Pro XL": 15,
    "How I Learned To Stop Worrying – Understanding AI Imaging": 16,
    "How I Learned To Stop Worrying – Protecting Your Work From AI": 17
}

def main():
    print("[UPDATE ARTICLE ORDER] Starting...")
    print("=" * 80)

    agent = SSHAgent()

    try:
        # Connect to server
        if not agent.connect():
            print("[ERROR] Failed to connect to server")
            return False

        # First, get current article list
        print("\n[FETCH] Getting current article order from database...")

        get_articles_sql = """
        mysql -u dalthaus_maincms -p'f4!,Wpds=w6*=~+1' -D dalthaus_maincms -e "
        SELECT content_id, title, sort_order
        FROM content
        WHERE content_type = 'article'
        ORDER BY sort_order ASC
        " 2>&1
        """

        output = agent.execute_command(get_articles_sql)
        print(output)

        # Build UPDATE statements for each article
        print("\n[BUILD] Building UPDATE statements...")

        updates = []
        for title, position in ARTICLE_ORDER.items():
            # Escape single quotes in title
            escaped_title = title.replace("'", "\\'")

            update_sql = f"""
            UPDATE content
            SET sort_order = {position}
            WHERE content_type = 'article'
              AND title = '{escaped_title}'
            """

            updates.append(update_sql)

        # Combine all updates into a single transaction
        full_sql = "START TRANSACTION;\\n"
        full_sql += "\\n".join(updates)
        full_sql += "\\nCOMMIT;"

        print(f"\n[PREVIEW] Will execute {len(updates)} UPDATE statements")

        # Ask for confirmation
        print("\n[WARNING] This will update article sort_order in the production database.")
        print("Do you want to continue? (yes/no): ", end="")
        confirmation = input().strip().lower()

        if confirmation != 'yes':
            print("[ABORTED] No changes made.")
            return False

        # Execute the updates
        print("\n[EXECUTE] Executing updates on production database...")

        mysql_cmd = f"""
        mysql -u dalthaus_maincms -p'f4!,Wpds=w6*=~+1' -D dalthaus_maincms -e "{full_sql}" 2>&1
        """

        result = agent.execute_command(mysql_cmd)
        print(result)

        if "ERROR" in result:
            print("\n[ERROR] Database update failed!")
            return False

        # Verify the new order
        print("\n[VERIFY] Checking new article order...")

        verify_sql = """
        mysql -u dalthaus_maincms -p'f4!,Wpds=w6*=~+1' -D dalthaus_maincms -e "
        SELECT sort_order, content_id, title
        FROM content
        WHERE content_type = 'article'
        ORDER BY sort_order ASC
        LIMIT 20
        " 2>&1
        """

        verify_output = agent.execute_command(verify_sql)
        print(verify_output)

        print("\n[SUCCESS] Article order updated successfully!")
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
