# Homework

This project built with **Symfony 7**, **PHP 8.2**, and **MySQL 8**.

## 🚀 How to Run the Project

1. Make sure you have **Docker** and **Docker Compose** installed.
2. Start the project by running:

```bash
docker compose up --build
```

3. Once started, the application will be available at:
http://localhost:8082
(you can change the port in docker-compose.yml)

4. Add to the .env file and configure the database connection:
```
DATABASE_URL="mysql://symfony:symfony@db:3306/symfony?serverVersion=8.0"
```

## 🛠 What to Do If Something Breaks

If the project doesn’t start automatically:
1.	Enter the PHP container:
```
docker exec -it <php-container-name> bash
```
2. Manually install dependencies:
```
composer install
```
3. Run the database migrations:
```
php bin/console doctrine:migrations:migrate
```

## 📁 Project Structure

- `entrypoint.sh`  
  Shell script used to bootstrap the project. It checks for the `vendor` directory, installs dependencies if needed, and starts the local server.

- `app/`  
  Root directory of the Symfony project.

    - `app/var/uploads/`  
      Directory where uploaded CSV files are stored.

    - `app/var/log/`  
      Directory for task logs and debug output (e.g. failed task logs).

    - `app/src/Command/RunQueueCommand.php`  
      Symfony console command that simulates a task queue by processing pending tasks.

    - `app/src/Service/`  
      Contains services responsible for business logic.

        - `TaskRunnerService.php`  
          Central service that dispatches tasks to the appropriate processor based on type.

        - `Tasks/StatsTask.php`  
          Example task processor that performs aggregation of call data in batches.

        - `Resolver/IpContinentResolver.php`  
          Resolves continent based on IP address using the external ipgeolocation API.

        - `Resolver/PhoneContinentResolver.php`  
          Resolves continent based on dialed phone numbers using the local `countryInfo.txt`.

    - `app/src/Entity/Task.php`  
      Represents a generic task entity with type, status, result, and optional parent-child relationship.

    - `app/src/Entity/CallRecord.php`  
      Entity that stores uploaded call records parsed from CSV files.

    - `app/src/Entity/CallStat.php`  
      Aggregated statistics per customer, such as total call count, durations, and continent match data.