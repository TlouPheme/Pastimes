# Pastimes Thrift Store

Pastimes is a PHP and MySQL thrift store website built for the WEDE6021 Part 2 submission. The website allows customers to browse second-hand clothing items, register/login, save favourites, add products to a cart, and checkout. Admin users can manage products, categories, users, inquiries, and orders.

## Demonstration Video

Watch the project demonstration video here:

```text
https://youtu.be/0ZnfmEq2Kg4
```

## Group Members

- Tlou Pheme - ST10177726
- Mahlatse Mphelo - ST10449570

## Technologies Used

- PHP
- MySQL / MariaDB
- XAMPP
- HTML
- CSS
- JavaScript

## Folder Location

Place or keep the project in:

```text
C:\xampp\htdocs\thrift_store
```

Then open the website in a browser at:

```text
http://localhost/thrift_store/index.php
```

## Main Features

- Customer registration and login
- Password hashing with `password_hash()` and `password_verify()`
- Pending customer verification before login
- Admin role support
- Product browsing with categories, search, and images
- Product details pages
- Favourites
- Cart and checkout
- Customer account page
- Admin dashboard
- Admin product, category, inquiry, order, and user management
- CSRF protection on forms

## Database Setup

The working website uses the Part 2 database:

```text
ClothingStore
```

### Option 1: Load the Website Database

1. Start Apache and MySQL in XAMPP.
2. Open this URL:

```text
http://localhost/thrift_store/loadClothingStore.php
```

This recreates the `ClothingStore` database using `myClothingStore.sql`. After that, open the website at:

```text
http://localhost/thrift_store/index.php
```

### Option 2: Recreate `tblUser` From Text File

Open this URL:

```text
http://localhost/thrift_store/createTable.php
```

This drops and recreates `tblUser`, then loads the five fictitious users from `userData.txt`.

## Part 2 Required Files

- `DBConn.php` - main MySQLi database connection file.
- `createTable.php` - drops, recreates, and loads `tblUser`.
- `loadClothingStore.php` - loads the duplicate `ClothingStore` database.
- `userData.txt` - contains at least five fictitious hashed user records.
- `myClothingStore.sql` - SQL export for the duplicate database.

## Part 2 Compatible Tables

The SQL export includes the current website tables and Part 2 compatible table names:

- `users`
- `categories`
- `products`
- `inquiries`
- `favorites`
- `product_images`
- `cart_items`
- `orders`
- `order_items`
- `tblUser`
- `tblAdmin`
- `tblClothes`
- `tblAorder`

## Login Notes

- The first registered user becomes an admin automatically.
- Later registered users are created as customers with `pending` verification.
- Pending customers cannot log in until an admin verifies them from the admin users page.
- Admins can verify users at:

```text
http://localhost/thrift_store/admin/users.php
```

## Admin Pages

- Dashboard: `admin/dashboard.php`
- Add product: `admin/add_product.php`
- Manage products: `admin/products.php`
- Categories: `admin/categories.php`
- Inquiries: `admin/inquiries.php`
- Orders: `admin/orders.php`
- Users: `admin/users.php`

## Testing Checklist

Before submission, confirm:

- Apache and MySQL are running in XAMPP.
- `http://localhost/thrift_store/index.php` opens correctly.
- Register creates a pending customer account.
- Admin can verify customers.
- Verified customers can log in.
- Products display with images.
- Add to cart works.
- Checkout creates an order.
- Admin can view and update orders.
- `loadClothingStore.php` recreates `ClothingStore`.
- `createTable.php` reloads `tblUser` from `userData.txt`.

## Declaration

This project is our own group work except where external sources are referenced.
