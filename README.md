# Digital Recipe App

This is a Recipe Management Web Application that allows users to add, view, and manage recipes. The application supports user authentication, recipe uploading, and interaction via comments.

## ✨ Features

- **👥 User Authentication**: Users can sign up and log in.
- **📖 Recipe Management**: Add, view, and manage recipes.
- **💬 Comment System**: Users can leave comments on recipes.
- **📂 Database Integration**: MySQL database setup for recipe storage.
- **🎨 Frontend & Backend**: HTML, CSS, JavaScript for the frontend; PHP for the backend.

## 🛠️ Technologies Used

| Technology    | Purpose |
|--------------|---------|
| HTML5 & CSS3 | Frontend UI |
| JavaScript   | Client-side interactions |
| PHP          | Backend processing |
| MySQL        | Database management |
| XAMPP        | Local server setup (Apache, MySQL, PHP) |
---

## Project Structure

Digital-Recipe-App
├── auth/                     # Authentication system (login.php, logout.php, register.php)
├── css/                      # Stylesheets
├── includes/                 # Database_Connection.php
├── js/                       # JavaScript files
├── uploads/                  # Images
├── database.sql              # SQL script for database setup
├── index.php                 # Homepage
├── about.php                 # About Page
├── add_recipe.php            # Add Recipes Page 
├── edit_recipe.php           # Edit Recipe
├── my_recipe.php             # My Recipe
├── contact.php               # Contact page
├── profile.php               # Profile
├── recipes.php               # Recipes
├── view_recipe.php           # View Recipe
├── user_recipes.php          # User's Recipe
├── README.md                 # Project documentation
└── .gitignore                # Git ignore file

### 🚀 Setup Instructions

1. Clone the repository:
   git clone https://github.com/siwanthikaherath/Digital-Recipe-Book.git
2. Move into the project directory:
    cd recipe-app
3. Set up a database in MySQL using the database.sql file.
4. Configure the database connection in includes/db.php:
    $host = "localhost";
    $user = "your_username";
    $password = "your_password";
    $dbname = "your_database_name";
5. Start a local server using XAMPP or any PHP server.
    php -S localhost:8000
6. Open http://localhost:8000/home.html in your browser


### 📌 Usage Guide
Sign Up for an account.
Sign In to access recipe features.
Add Recipes with details like title, ingredients, and instructions.
View Recipes submitted by other users.
Comment on recipes and interact with other users.