# CampusBuzz – College Social Network (Threads Style)

A simple campus discussion platform built with PHP + MySQL (dark theme, similar to Threads/X).

## Features
- Register / Login
- Post threads with tags (Exam, Fest, Notice, Study, Project, Sports, Event)
- Like / unlike posts
- Responsive design (sidebar on desktop, bottom navigation on mobile)
- Floating action button + modal composer for posting

## Requirements
- XAMPP (Apache + MySQL)
- Web browser (Chrome, Firefox, Edge, etc.)

## Full Installation & Running Steps (XAMPP)

### Step 1: Download the project
1. Download the entire project folder (zip file or Git clone)
2. Extract the zip if necessary → you should see files like:
   - `index.php`
   - `login.php`
   - `register.php`
   - `like.php`
   - `create_post.php`
   - `config/db.php`
   - `assets/css/style.css`
   - etc.

### Step 2: Copy project to XAMPP htdocs
1. Open your XAMPP folder  
   → usually: `C:\xampp` (Windows) or `/Applications/XAMPP` (Mac)

2. Go inside the `htdocs` folder  
   → full path: `C:\xampp\htdocs`

3. Create a new folder called `campusbuzz` (lowercase recommended)

4. Copy **all files and folders** from the downloaded project into  
   `C:\xampp\htdocs\campusbuzz`

   Final structure should look like this:


C:\xampp\htdocs\campusbuzz
├── campusbuzz.sql <--- Database Code compressed in .sql file
├── index.php
├── login.php
├── register.php
├── profile.php
├── explore.php
├── logout.php
├── like.php
├── create_post.php
├── config/
│   └── db.php
├── style.css
└── README.md

### Step 3: Start XAMPP
1. Open **XAMPP Control Panel**  
(search "XAMPP" in Windows start menu or open `xampp-control.exe`)

2. Click **Start** next to **Apache** and **MySQL**  
→ both modules should turn green

### Step 4: Create the database (using phpMyAdmin)
1. Open your browser and go to:  
http://localhost/phpmyadmin

2. On the top bar → click Import

3. Select .sql proivded with code

4. Click the **Import** 

It done correct Your will see the Database created.

After that head to http://localhost/campusbuzz ##if done correctly site will open