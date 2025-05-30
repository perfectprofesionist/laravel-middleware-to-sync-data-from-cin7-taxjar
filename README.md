# Laravel Cin7 to TaxJar Sync Middleware

## Overview

This Laravel project includes a middleware designed to synchronize sales data from Cin7 to TaxJar. The middleware ensures that sales details are automatically synced every hour, providing seamless integration between Cin7 and TaxJar.

## Features

- **Automatic Sync**: The middleware is set up to automatically sync sales details from Cin7 to TaxJar after every 1 hour.


# How to Deploy

1. Clone the repository.
2. Run `composer install`.
3. Copy `.env.example` to `.env`.
4. Run `php artisan key:generate` to generate key.
5. Fill `.env` with correct credentials.
6. Fill `TAXJAR_API_LIVE`, `CIN7_USERNAME` and `CIN7_PASSWORD` to the `.env` file and fill in correct details.
7. Run `php artisan migrate` to create database tables.

# Commands

`php artisan cin7:fetch` - Fetch data from CIN7 and save to local database. Run every X hours or once a day.

`php artisan taxjar:post` - Post local database data to Taxjar. Can be run once a day.

## Dependencies

- Laravel

## Contribution

Feel free to contribute to the project by submitting issues or pull requests.

## License

This project is licensed under the [MIT License](LICENSE).



