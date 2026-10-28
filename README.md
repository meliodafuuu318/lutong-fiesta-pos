# Lutong Fiesta POS

Welcome to the **Lutong Fiesta POS** repository.

## 📋 Prerequisites

Ensure you have the following installed on your local machine:

* **PHP**: Version 8.3 or higher
* **Composer**: Dependency manager for PHP
* **MySQL**: Database server
* **Git**: Version control

## 🚀 Installation & Setup

Follow these steps to set up the project locally.

### 1. Clone the Repository

```bash
git clone https://github.com/meliodafuuu318/lutong-fiesta-pos.git
cd lutong-fiesta-pos
```

### 2. Install Backend Dependencies

Open a terminal in the directory and install the PHP dependencies:

```bash
composer install
```

### 3. Environment Configuration

Create your environment file and generate the application key:

```bash
cp .env.example .env
php artisan key:generate
```

On Windows PowerShell, use:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Update the database settings in `.env` if your MySQL credentials differ from the defaults:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lutong_fiesta_db
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Database Migration & Seeding

On a new installation:

```bash
php artisan migrate --seed
```

To recreate an existing local database and reload its seed data:

```bash
php artisan migrate:fresh --seed
```

## 🏃‍♂️ Running the Application

```bash
php artisan serve
```

The API will be available at `http://127.0.0.1:8000`.

## 💾 Daily transaction backups

A complete JSON export of the transaction history is emailed to a recovery mailbox every day at 11:00 PM Manila time. See [docs/transaction-backups.md](docs/transaction-backups.md) for setup, manual runs and recovery.
