# Lutong Fiesta POS

Welcome to the **Lutong Fiesta POS** repository.

## 📋 Prerequisites

Ensure you have the following installed on your local machine:

* **PHP**: Version 8.3 or higher
* **Composer**: Dependency manager for PHP
* **Node.js and npm**: Required for frontend dependencies and assets
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

Open a terminal in the backend directory and install the PHP dependencies:

```bash
cd backend
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

Update the database settings in `backend/.env` if your MySQL credentials differ from the defaults:

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

### 5. Install Frontend Dependencies

Open a second terminal from the repository root:

```bash
cd frontend
npm install
```

## 🏃‍♂️ Running the Application

Start the Laravel backend from `backend/`:

```bash
php artisan serve
```

The API will be available at `http://127.0.0.1:8000`.

Start the React frontend from `frontend/`:

```bash
npm run dev
```

Vite will display the frontend URL, normally `http://localhost:5173`.

Run the backend and frontend in separate terminals during development.

## 🧪 Useful Commands

### Backend

```bash
cd backend
php artisan route:list
php artisan test
```

### Frontend

```bash
cd frontend
npm run build
npm run lint
npm run preview
```

## 🔐 API Authentication

The API uses Laravel Sanctum. Log in through:

```text
POST /api/auth/login
```

Include the returned token in authenticated requests:

```http
Authorization: Bearer <token>
```

## Troubleshooting

* If Laravel reports a database connection error, make sure MySQL is running and the `DB_*` values in `backend/.env` are correct.
* If the application key is missing, run `php artisan key:generate` from `backend/`.
* If frontend dependencies are missing, run `npm install` from `frontend/`.
* If the frontend cannot reach the API, confirm that the backend is running at `http://127.0.0.1:8000`.
