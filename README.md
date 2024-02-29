## Description:

This app is an expenses tracker. It allows you to enter (or import) your transactions, group them by categories,
display yearly statistics on the graphical chart, and calculate your income/expense for the time period of your choice.

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
* To populate your user with data, go to the Transactions page, then Import Transactions, and choose the file
  "transactions_10000.csv" provided in the root of the app directory.