<h1>What started as an after-hours project turned into a powerful tool that helped us smash 300% of our sales targets</h1>

Managers would regularly approach us at the end of a shift asking for our sales figures, but we often had no idea where we stood. The mobile department, in my view, has been overlooked from a technical standpoint. There has been little to no investment in software solutions, and we lack real-time tracking of our transactions. This makes it difficult to monitor performance or manage targets with any real accuracy.

To solve this, I decided to use my spare time to develop a system that could fill this gap. Since my area of study also focuses on cybersecurity, I approached the project with a DevSecOps mindset. That means security was considered and integrated into the architecture from the very beginning through to final implementation.

My housemate and I had already designed the architecture for a web-based database, so I applied what I had learned to build a new project that required minimal resources and overhead.

---

### **Requirements**

Unlike other departments where transactions are automatically recorded, scoring a tally in the mobile department has always been a manual and often overlooked process. This is a known limitation of our current PIE system.

It was essential that logging a transaction took no more than a few seconds. Efficiency was key.

This dashboard was designed to become our central hub. It provides a live snapshot of performance, showing clearly how we are doing throughout the day.

---

### **Effectiveness**

The web app needed to be fast, responsive, and intuitive. A clean and simple user interface was essential. It had to work immediately, with no training required. Just pick it up and use it.



### **Quality of Life**

Since colleagues would be using their personal phones to log transactions, the dashboard had to be fully mobile-responsive. It needed to adapt seamlessly to any screen size, from desktops to smartphones.



---

### **Security**

Access to the app is protected by hashed credentials. Only verified staff emails can register and log in. This ensures that only authorised users can interact with the system.

While managers initially suggested keeping track of tallies on paper, that method lacked accessibility and durability. Paper can get lost. It cannot be shared easily. It does not scale.

Digitising the tally system created a secure and accessible solution that stays updated in real time. It removes friction and reduces the chance of errors.



---

### **Scaling Potential**

Each widget on the dashboard serves a specific purpose. I have already integrated a chronological sales graph to highlight peak trading periods and a leader board to encourage healthy competition among mobile team members.

Looking ahead, I plan to expand the system with advanced features such as data analytics, AI-assisted sales insights, trend forecasting, and automated daily email reports. With the right development, this dashboard has the potential to become a powerful and intelligent performance tool.



---

### **Impact**

This tool provides both managers and staff with real-time visibility into key performance indicators. It helps colleagues stay motivated by allowing them to track their performance even outside working hours. It fosters a culture of clarity, accountability, and friendly competition.

Since implementation, the app has contributed to the team achieving over **300 percent** of our post-pay sales targets. It has been a driving force behind our continued growth and performance improvements.

---

There are many more features and technical details behind this project. Each new implementation has directly contributed to improving our team’s performance. You can explore the full project and source code on my GitHub: https://github.com/Teerasak-Mairoddee/currys-tally



I have always been passionate about professional development, and seeing my work actively used by my team, making a real impact has been incredibly rewarding.

Please reach out for any collaborations or networking: https://linktr.ee/teerasakmairoddee

# Currys Tally

A secure, web-based sales tracking system built for Currys Mobile teams. Currys Tally fills a critical gap in Currys’ infrastructure by providing real-time visibility into team and individual performance. The tool has helped frontline staff surpass 200% of Post-Pay targets — all through better transparency and accountability.

---

## Table of Contents

- [Overview](#overview)  
- [Problem Statement](#problem-statement)  
- [Key Benefits](#key-benefits)  
- [Features](#features)  
- [Technology Stack](#technology-stack)  
- [Installation](#installation)  
- [Configuration](#configuration)  
- [Usage](#usage)  
- [Folder Structure](#folder-structure)  
- [Security Considerations](#security-considerations)  
- [Customizing for Your Team](#customizing-for-your-team)  
- [Troubleshooting](#troubleshooting)  
- [Contributing](#contributing)  
- [License](#license)

---

## Overview

Currys Tally is a lightweight PHP + MySQL application for tracking daily mobile sales:  
- Post-Pay  
- Handset-Only  
- Sim-Only  
- Insurance  
- Accessories  
- Upgrades

It provides a live dashboard for each team member and team lead, visualizing performance through:

- Daily leaderboards  
- Accessory & Insurance strike rates  
- Hour-by-hour sales timelines  
- Personal stats via secure login  

By giving every colleague access to clear, up-to-date data, the mobile department was able to more than double its Post-Pay contract performance.

---

## Problem Statement

Currys' internal reporting system does not reflect mobile department KPIs in real time. Key limitations include:

- No breakdown by sale type specific to mobile  
- No individual accountability or historical tracking  
- No strike rate data tied to upselling success  
- No clear performance comparison across staff  

This system was created to make performance visible, data-driven, and empowering for mobile sales teams.

---

## Key Benefits

- **200%+ Target Attainment**: Teams close more contracts by seeing exactly where they stand.  
- **Upsell Visibility**: Track accessories and insurance sales in context.  
- **Real-Time Data**: Hour-by-hour timeline and leaderboards update daily.  
- **Staff Accountability**: Each user logs and reviews their own performance securely.  
- **Secure Access**: Sessions, password hashing, and domain-restricted registration.

---

## Features

### 1. Authentication & Access Control
- Register/login with `@currys.co.uk` emails only  
- Passwords hashed using `password_hash()`  
- Session-based access control  

### 2. Sale Logging
Log the following sale types via dropdown:
- Post-Pay  
- Handset-Only  
- Sim-Only  
- Accessories  
- Insurance  
- Upgrades  

Supports batch input (e.g. logging 3 sales at once).

### 3. Dashboard (index.php)
- Daily totals for each sale type  
- Accessory and insurance strike rates calculated live  
- Leaderboard for top performers  
- Interactive Chart.js timeline (09:00 – 20:00)  
- Date toggle buttons for historical view  

### 4. Account View
- Personal daily sales breakdown  
- Personal strike rate stats  
- Timeline line color customization  

### 5. Strike Rate Calculations
- **Accessory Strike Rate** = Accessories ÷ (Post-Pay + Handset-Only)  
- **Insurance Strike Rate** = Insurance ÷ (Post-Pay + Handset-Only)

---

## Technology Stack

- PHP 8.x  
- MySQL / MariaDB  
- Chart.js  
- Select2 + jQuery  
- Font Awesome  
- Apache or Nginx  
- Optional: Composer (`vlucas/phpdotenv`)  

---

## Installation

### 1. Clone Repo
```bash
cd /var/www
sudo git clone https://github.com/Teerasak-Mairoddee/currys-tally.git
cd currys-tally
sudo chown -R www-data:www-data .
```

### 2. Create MySQL Database & Tables

```sql
CREATE TABLE staff (
  staff_id    INT PRIMARY KEY,
  first_name  VARCHAR(50),
  last_name   VARCHAR(50),
  email       VARCHAR(100) UNIQUE,
  line_color  CHAR(7) DEFAULT '#6A0DAD',
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE sales (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  staff_id        INT NOT NULL,
  contract_value  DECIMAL(10,2),
  sale_type       ENUM('Sim-Only','Post-Pay','Handset-Only','Insurance','Accessories','Upgrades') NOT NULL,
  sold_at         DATETIME NOT NULL,
  FOREIGN KEY (staff_id) REFERENCES staff(staff_id)
);
```

---

## Configuration

### Environment Variables (Optional)
Create a `.env` file:
```
DB_HOST=localhost
DB_NAME=currys_tracker_db
DB_USER=root
DB_PASS=yourpassword
```

Adjust `db_conn.php` to pull values using `getenv()`.

### File Permissions
```bash
sudo chown -R www-data:www-data /var/www/currys-tally
sudo chmod -R 755 /var/www/currys-tally
```

---

## Usage

1. Register with your Currys email  
2. Log in and access the dashboard  
3. Log new sales via **Log Sale**  
4. View personal stats under **Account**  
5. Use left/right arrows to change dates  
6. Click the timeline to see full chart view

---

## Folder Structure

```
currys-tally/
├── index.php
├── account.php
├── log_sale.php
├── timeline.php
├── get_summary.php
├── get_sales_data.php
├── get_leaderboard.php
├── auth.php
├── db_conn.php
├── scripts/
│   └── generate_data.js
├── style/
│   └── css/
│       └── style.css
└── .env
```

---

## Security Considerations

- Uses prepared SQL statements  
- Session auth on protected pages  
- Sanitized HTML output  
- Email domain restriction for registration  
- Passwords hashed with `bcrypt`  

---

## Customizing for Your Team

- Add new sale types by editing:
  - MySQL `sale_type` enum  
  - `$allowedTypes` in `log_sale.php`  
  - `$types` array in `account.php`  

- Change chart hours in `get_sales_data.php`  
- Adjust widget styles in `style.css`

---

## Troubleshooting

- **Chart too tall?** Ensure `maintainAspectRatio: true` is used for dashboard.  
- **AJAX errors?** Confirm file paths and permissions.  
- **Login errors?** Check sessions and cookie settings.  
- **Enum changes failing?** Clean data before altering column types.

---

## Contributing

1. Fork this repo  
2. Create a branch: `git checkout -b feature/my-feature`  
3. Commit and push  
4. Open a Pull Request  
5. Document any schema changes clearly

---

## License

MIT License — use freely, modify openly, and credit the original repo where possible.
