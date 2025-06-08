# Laravel Docker Project

A complete Laravel application with Docker environment ready to run with just a few commands.

## 📋 What's Included

-   **Laravel** - Latest Laravel framework
-   **Nginx** - Web server
-   **PHP 8.2-FPM** - PHP processor with extensions
-   **MySQL 8.0** - Database
-   **Redis** - Cache and session storage
-   **Docker Compose** - Container orchestration

## 🚀 Quick Setup (4 Steps)

### Step 1: Clone the Repository

```bash
git clone https://github.com/palash140/innoscripta.git
cd innoscripta
```

### Step 2: Setup Environment File

```bash
# Copy environment configuration
cp .env.example .env
```

### Step 3: Start Docker Containers

```bash
# Make sure Docker is running, then:
docker-compose up -d --build
```

### Step 4: Setup Laravel Application

```bash
# Wait 30 seconds for MySQL to initialize, then run:
docker-compose exec php php artisan key:generate
docker-compose exec php composer install
docker-compose exec php php artisan migrate
docker-compose exec php php artisan db:seed

#intial seed
docker-compose exec php php artisan news:sync --from=2025-06-01 --to=2025-06-07  --records=50 --immediate

```

**That's it! 🎉**

Your Laravel application is now running at: **http://localhost:8080**

---

## 📦 What Happens During Setup

1. **Environment file** is copied from `.env.example` to `.env` with Docker-ready settings
2. **Docker builds** the PHP container with all required extensions
3. **MySQL and Redis** containers start and initialize
4. **Nginx** serves your Laravel application
5. **Laravel** generates app key, installs dependencies, and runs migrations

## 🌐 Access Points

| Service         | URL/Host              | Credentials                          |
| --------------- | --------------------- | ------------------------------------ |
| **Laravel App** | http://localhost:8080 | -                                    |
| **MySQL**       | localhost:3307        | user: `laravel`, password: `laravel` |
| **Redis**       | localhost:6379        | -                                    |

## ⚙️ Project Structure

```
project/
├── docker/
│   ├── nginx/default.conf     # Nginx configuration
│   └── php/Dockerfile         # PHP container setup
├── docker-compose.yml         # Container orchestration
├── .env.example              # Environment template (copy to .env)
├── .env                      # Laravel environment (created from .env.example)
├── app/                      # Laravel application code
├── public/                   # Web accessible files
└── ... (standard Laravel files)
```

## 🛠️ Development Commands

### Container Management

```bash
# Start containers
docker-compose up -d

# Stop containers
docker-compose down

# View running containers
docker-compose ps

# View logs
docker-compose logs nginx
docker-compose logs php
docker-compose logs mysql
```

### Laravel Development

```bash
# Run Artisan commands
docker-compose exec php php artisan migrate
docker-compose exec php php artisan make:model Product
docker-compose exec php php artisan tinker

# Composer commands
docker-compose exec php composer require package/name
docker-compose exec php composer update

# Access container shell
docker-compose exec php bash
```

### Database Operations

```bash
# Access MySQL
docker-compose exec mysql mysql -u laravel -p
# Password: laravel

# Run fresh migrations
docker-compose exec php php artisan migrate:fresh --seed
```

## 🔧 Troubleshooting

### If containers don't start:

```bash
# Check Docker is running
docker --version
docker ps

# Check port conflicts
sudo lsof -i :8080  # Should be empty
sudo lsof -i :3307  # Should be empty

# Clean and retry
docker-compose down
docker system prune -f
docker-compose up -d --build
```

### If Laravel shows errors:

```bash
# Clear all Laravel caches
docker-compose exec php php artisan config:clear
docker-compose exec php php artisan cache:clear
docker-compose exec php php artisan view:clear

# Fix permissions
docker-compose exec php chmod -R 775 /var/www/html/storage
docker-compose exec php chmod -R 775 /var/www/html/bootstrap/cache
```

### If MySQL connection fails:

```bash
# Wait for MySQL to fully start (can take 1-2 minutes)
docker-compose logs mysql

# Test connection
docker-compose exec mysql mysql -u laravel -p laravel -e "SHOW DATABASES;"
```

### Docker Context Issues (Linux):

```bash
# If docker ps shows nothing but docker-compose ps shows containers
docker context use default
docker-compose down && docker-compose up -d
```

## 🚨 Prerequisites

### Required Software

-   **Docker** - [Install Docker](https://docs.docker.com/get-docker/)
-   **Docker Compose** - Usually included with Docker Desktop

### Check Installation

```bash
docker --version          # Should show Docker version
docker-compose --version  # Should show Docker Compose version
docker ps                 # Should work without errors
```

### Install Docker (Ubuntu/Debian)

```bash
# If Docker is not installed:
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER
# Log out and log back in
```

## 📝 Environment Configuration

The project includes a `.env.example` file with Docker-optimized settings. Key configurations:

```env
# Application
APP_URL=http://localhost:8080

# Database (Docker)
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=laravel

# Redis (Docker)
REDIS_HOST=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis
```

**Important**: Always copy `.env.example` to `.env` before starting:

```bash
cp .env.example .env
```

## 🔄 Daily Workflow

1. **Start development:**

    ```bash
    docker-compose up -d
    ```

2. **Make code changes** in your favorite editor

3. **Run migrations** (if needed):

    ```bash
    docker-compose exec php php artisan migrate
    ```

4. **View your app** at http://localhost:8080

5. **Stop containers** when done:
    ```bash
    docker-compose down
    ```

## 📊 Container Details

| Container | Purpose                     | Exposed Port |
| --------- | --------------------------- | ------------ |
| nginx     | Web server serving Laravel  | 8080         |
| php       | PHP-FPM processing requests | -            |
| mysql     | Database storage            | 3307         |
| redis     | Cache and sessions          | 6379         |

## 🆘 Need Help?

### Common Issues & Solutions

1. **Port already in use**: Change ports in `docker-compose.yml`
2. **Permission denied**: Run `sudo chmod -R 755 storage bootstrap/cache`
3. **MySQL not ready**: Wait 1-2 minutes after first start
4. **App key missing**: Run `docker-compose exec php php artisan key:generate`

### Getting Support

-   Check container logs: `docker-compose logs [container-name]`
-   Restart containers: `docker-compose restart`
-   Clean restart: `docker-compose down && docker-compose up -d --build`

---

## 🎯 Quick Commands Reference

```bash
# One-time setup after clone
git clone <repo-url> && cd <repo-name>
cp .env.example .env                          # Copy environment file
docker-compose up -d --build                 # Start containers
# Wait 30 seconds
docker-compose exec php php artisan key:generate
docker-compose exec php composer install
docker-compose exec php php artisan migrate

# Daily development
docker-compose up -d              # Start
# ... do your development work ...
docker-compose down               # Stop

# Useful commands
docker-compose exec php php artisan tinker        # Laravel REPL
docker-compose exec php composer require <package> # Add package
docker-compose exec mysql mysql -u laravel -p     # Database access
```
