# Page snapshot

```yaml
- generic [ref=e3]:
  - heading "Database Connection Error" [level=1] [ref=e4]
  - paragraph [ref=e5]: The application could not connect to the database. This is usually due to incorrect configuration.
  - generic [ref=e6]:
    - heading "How to Fix This:" [level=2] [ref=e7]
    - paragraph [ref=e8]:
      - text: Please ensure your database server is running and that the credentials in
      - strong [ref=e9]: config/config.php
      - text: are correct. You may need to create the database and user.
    - list [ref=e10]:
      - listitem [ref=e11]:
        - strong [ref=e12]: "Create the database in MySQL:"
        - code [ref=e14]: CREATE DATABASE IF NOT EXISTS dalthaus_maincms;
      - listitem [ref=e15]:
        - strong [ref=e16]: "Create the database user:"
        - code [ref=e18]: CREATE USER IF NOT EXISTS 'dalthaus_maincms'@'localhost' IDENTIFIED BY 'f4!,Wpds=w6*=~+1';
      - listitem [ref=e19]:
        - strong [ref=e20]: "Grant privileges to the user:"
        - code [ref=e22]: GRANT ALL PRIVILEGES ON dalthaus_maincms.* TO 'dalthaus_maincms'@'localhost';
      - listitem [ref=e23]:
        - strong [ref=e24]: "Import the database schema:"
        - paragraph [ref=e25]: "Run this command from your project's root directory in your terminal:"
        - code [ref=e27]: mysql -u dalthaus_maincms -p dalthaus_maincms < database.sql
        - generic [ref=e28]:
          - text: "You will be prompted for the password:"
          - strong [ref=e29]: f4!,Wpds=w6*=~+1
  - paragraph [ref=e31]: Once the database is set up, please refresh this page.
```