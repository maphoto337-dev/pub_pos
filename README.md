# Pub POS Starter for XAMPP MySQL

A simple PHP + MySQL starter POS for a pub that sells alcohol.

## Included
- 2-phase login: Manager portal and Cashier portal
- Manager pages: dashboard, products, stock receiving, damages, reports, users
- Cashier pages: sell screen, shift open/close, sales history
- Manual stock capture
- Slip/barcode-assisted stock intake field
- Damage capture that deducts stock
- No open tabs
- Stock movement logging
- XAMPP-friendly MySQL database setup

## Default login
- Manager: `manager` / `admin123`
- Cashier: `cashier` / `cash123`

## XAMPP setup
1. Copy the `pub_pos` folder into `C:/xampp/htdocs/`
2. Start **Apache** and **MySQL** in XAMPP Control Panel.
3. Open **phpMyAdmin**.
4. Import the file `database.sql`
5. Open `config.php` and confirm these values:
   - db_host = `127.0.0.1`
   - db_name = `pub_pos`
   - db_user = `root`
   - db_pass = ``
   - base_url = `/pub_pos`
6. Open this in your browser:
   - `http://localhost/pub_pos`

## Notes
This is a starter prototype, not a hardened production deployment yet.
For production, add:
- CSRF protection
- stronger validation
- receipt printing integration
- user PIN login
- barcode scanner hardware testing
- OCR slip reader
- audit log expansion
- backup and sync
- card terminal / payment gateway integration
