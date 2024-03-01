## Description:

This app is an expenses tracker. It allows you to enter (or import) your transactions, add and view PDF receipts for them;
group transactions by categories; display yearly statistics on the graphical chart; calculate your income/expense for the 
time period of your choice.

## Installation of the project:

```bash
cp .env.example .env
```

```bash
cd docker && docker-compose up -d --build
```

```bash
docker exec expenses-db mysql -u root -proot -e "CREATE DATABASE expenses;"
```
Repeat the previous command if the socket connection error occurred (due to timing issues within the Docker environment)

```bash
docker exec -it expenses-app composer install && \
docker exec -it expenses-app npm install && \
docker exec -it expenses-app npm run dev
```

```bash
docker exec -it expenses-app php expenses app:generate-key && \
docker exec -it expenses-app php expenses migrate
```

## You can go to:
http://localhost:8000

## Usage:
* register your account
* go to http://localhost:8025 (MailHog email testing server) and click on the verification link inside your email
* To populate your user with testing data, go to the Transactions page, then Import Transactions, and choose the file
  "transactions_10k.csv" provided in the root of the app directory.

## Technology Stack:

### Backend:
- **PHP 8.1** 
- **Slim Framework** components:
  - `slim/csrf`
  - `slim/psr7`
  - `slim/slim`
  - `slim/twig-view`
- **Symfony** components:
  - `symfony/cache`
  - `symfony/console`
  - `symfony/mailer`
  - `symfony/rate-limiter`
  - `symfony/twig-bridge`
  - `symfony/webpack-encore-bundle`
- **Doctrine ORM** and related tools:
  - `doctrine/dbal`
  - `doctrine/migrations`
  - `doctrine/orm`
- **PHP-DI** (Dependency Injection Container)
- **Clockwork** for Profiling
- **League Flysystem** for filesystem abstraction

- **Twig** for templating
- **PHP dotenv** for environment variable management
- **Valitron** for data validation

### Frontend:
- **Symfony Webpack Encore** for asset management
- **Bootstrap** for frontend styling:
- **Popper.js** for tooltips and popovers:
- **Chart.js** for graphical charts
- **Core-js** for JavaScript polyfills
- **DataTables** for interactive data tables:
- **SASS** for CSS preprocessing

### Docker-related:
- **Docker** for containerization
- **Docker Compose** for managing multi-container Docker applications
- **Nginx** as server
- **MySQL** for database management
- **MailHog** for email testing
- **Redis** for caching