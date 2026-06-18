# Pasttime - Second-Hand Clothing Store

A clean, fully functional PHP/MySQL e-commerce web application for buying and selling pre-loved clothing. Buyers can browse, add items to cart and checkout. Sellers can submit items for sale (pending admin approval). Admins can manage users, clothing stock, orders, and communication.

---

## 1. Software Required

- **PHP 8.1+** with the **mysqli** extension enabled
- **MySQL 5.7+** or **MariaDB 10.x**
- Local server stack such as **XAMPP**
- Modern web browser (Chrome, Edge, Firefox, etc.)

**No Composer packages or external libraries** are used - this is pure vanilla PHP.

---

## 2. How to Open and Run the Project

1. Copy the entire project folder into your web server's root directory and **rename it exactly to `pastimes`** (important because all internal links use absolute paths starting with `/pastimes/`).
   - XAMPP: `htdocs/pastimes`


2. Start **Apache** and **MySQL** from the control panel.

3. Open `DBConn.php` and verify the database credentials. Default is:
   ```php
   new mysqli("localhost", "root", "", "ClothingStore");
   ```
   Update username/password if your setup differs.

4. In your browser, visit:
   ```
   http://localhost/pastimes/setup.php
   ```
   This script will create the database, all tables, an admin account, sample users, clothing items, and orders. You should see a success confirmation page.

5. After setup, go to the home page:
   ```
   http://localhost/pastimes/
   ```

To reset the database at any time, simply re-run `setup.php`.

---

## 3. Database Setup

The recommended and easiest way is to run **`setup.php`** as described above.

**Alternative (manual):**
- The full SQL schema + seed data is located at **`myClothingStore.sql`** in the project root.
- Create a database named `ClothingStore`, then import the SQL file via phpMyAdmin or command line:
  ```bash
  mysql -u root -p ClothingStore < myClothingStore.sql
  ```

### Tables
- `tblAdmin` - Admin accounts
- `tblUser` - Buyer & Seller accounts
- `tblClothes` - Clothing items (pending/available/sold)
- `tblAorder` - Orders
- `tblOrderLine` - Order line items
- `tblMessage` - Messaging system

---

## 4. Test Credentials

| Role       | Username      | Email                      | Password      |
|------------|---------------|----------------------------|---------------|
| **Admin**  | `admin`       | `admin@pasttime.co.za`     | `admin123`    |
| Buyer      | `johndoe`     | `john@example.com`         | `password123` |
| Buyer      | `janesmith`   | `jane@example.com`         | `password123` |
| Buyer      | `sarahlee`    | `sarah@example.com`        | `password123` |
| Seller     | `mikebrown`   | `mike@example.com`         | `password123` |
| Seller     | `tomwilson`   | `tom@example.com`          | `password123` |

- **Admin login**: `/pastimes/admin_login.php`
- **Buyer/Seller login**: `/pastimes/login.php`

---

## 5. Important Notes for Marker

- The project folder **must** be named **`pastimes`** for all links to work correctly.
- `images/` folder must be writable by the web server for seller image uploads.
- Sample data includes one pending seller item ("Vintage Leather Jacket") so you can immediately test the approval workflow.
- Cart functionality automatically increases quantity if the same item is added again.
- Stock is automatically decremented upon successful checkout.
- Messaging system allows two-way communication between admin and users (optionally linked to orders).
- All core features (buyer flow, seller submission, admin management) are implemented without external dependencies.

---

## 6. Project Structure (Key Files)

- `index.php` - Home page
- `setup.php` - Database + seed data
- `myClothingStore.sql` - Alternative SQL script
- `DBConn.php` - Database connection
- `clothes.php` - Browse items
- `cart.php`, `checkout.php` - Shopping flow
- `sell.php` - Seller submission form
- `user_dashboard.php` - Buyer/Seller dashboard
- `admin_dashboard.php`, `admin_clothes.php`, `admin_orders.php`, `admin_messages.php` - Admin panels
- `includes/functions.php` - Helper functions
- `css/style.css` - Stylesheet
- `images/` - Product images

---

**Enjoy testing the application!**  
All major features are functional and ready for demonstration.
